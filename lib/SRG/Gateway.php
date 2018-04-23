<?php 
  namespace SRG;

  class Gateway {
    static $http = null;

    // public static function intermediate($url) {
      
    // }

    public static function initiate($url) {
      if ($repository = Repository::find($url, ['strict' => FALSE])) {
        // echo $repository->url;
      } else {
        Repository::create(['url' => $url]);
        // echo $repository->url;
      }
    }

    public static function approve($url) {
      $repository = Repository::find($url);
      $repository->update(['approved' => TRUE]);
    }

    public static function terminate($url) {
      $repository = Repository::find($url);
      $repository->delete();
    }

    public static function verify($url) {
      $repository = Repository::find($url);

      $opts = [];

      if ($repository->modified_at) {
        $opts['headers'] = [
          'If-Modified-Since' => self::to_http_date($repository->modified_at)
        ];
      }
      
      $response = self::http()->request('GET', $repository->url, $opts);
      $status = $response->getStatusCode();

      // check for cached response
      if ($status == 304) {
        $repository->update(['verified_at' => self::to_db_date('now')]);
        return TRUE;
      }

      $errors = [];

      // check for success response code
      if ($status != 200) {
        $errors[] = 'the server responded with ' .
          $response->getStatusCode() . ' ' .
          $response->getReasonPhrase . ":\n" .
          $response->getBody() . "\n";
      }

      // check presence of Last-Modified header
      $lm = $response->getHeaderLine('Last-Modified');
      if (!$lm) {
        $errors[] = 'there was no Last-Modified header present in the response';
      }

      // validate xml
      $xml = $response->getBody();
      $doc = new \DOMDocument();
      $doc->loadXML($xml);
      $verrors = self::xmlValidationErrors($doc);
      if (sizeof($verrors)) {
        foreach ($verrors as $error) {
          $errors[] = $error->line . ': ' . $error->message;
        }
      }

      // check baseUrl of response matches gateway
      // TODO: reenable
      // $baseUrl = self::getBaseUrl($doc);
      // if ($baseUrl != \SRG::baseUrl()) {
      //   $errors[] = 'base url should be ' . \SRG::baseUrl() . ' but was ' . $baseUrl;
      // }

      if (sizeof($errors)) {
        $repository->update([
          'errors' => join("\n", $errors),
          'verified' => FALSE,
          'verified_at' => self::to_db_date('now')
        ]);
      } else {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $data = self::extract($doc);

        $repository->update([
          'errors' => NULL,
          'verified' => TRUE,
          'verified_at' => self::to_db_date('now'),
          'modified_at' => self::to_db_date($lm),
          'admin_email' => $data['admin_email'],
          'identify' => $data['data']['identify'],
          'list_metadata_formats' => $data['data']['list_metadata_formats']
        ]);

        $repository->delete_records();
        foreach ($data['data']['records'] as $record) {
          $repository->create_record([
            'prefix' => $record['prefix'],
            'identifier' => $record['identifier'],
            'modified_at' => $record['datestamp'],
            'payload' => $record['payload']
          ]);
        }
      }

    }

    private static function extract($doc) {
      $oai_ns = 'http://www.openarchives.org/OAI/2.0/';
      $sr_ns = 'http://www.openarchives.org/OAI/2.0/static-repository';

      $result = [
        'repository_name' => $doc->getElementsByTagNameNS($oai_ns, 'repositoryName')->item(0)->nodeValue,
        'admin_email' => $doc->getElementsByTagNameNS($oai_ns, 'adminEmail')->item(0)->nodeValue,
        'earliest_datestamp' => $doc->getElementsByTagNameNS($oai_ns, 'earliestDatestamp')->item(0)->nodeValue,
        'data' => [
          'identify' => self::getContentsByTagNameNS($doc, $sr_ns, 'Identify'),
          'list_metadata_formats' => self::getContentsByTagNameNS($doc, $sr_ns, 'ListMetadataFormats'),
          'records' => []
        ]
      ];

      $list_records_batches = $doc->getElementsByTagNameNS($sr_ns, 'ListRecords');
      for ($i = 0; $i < $list_records_batches->length; $i++) {
        $batch = $list_records_batches->item($i);
        $prefix = $batch->getAttribute('metadataPrefix');

        $records = $batch->getElementsByTagNameNS($oai_ns, 'record');
        foreach ($records as $record) {
          $result['data']['records'][] = [
            'prefix' => $prefix,
            'identifier' => $record->getElementsByTagNameNS($oai_ns, 'identifier')->item(0)->nodeValue,
            'datestamp' => $record->getElementsByTagNameNS($oai_ns, 'datestamp')->item(0)->nodeValue,
            'payload' => $record->ownerDocument->saveHTML($record)
          ];
        }
      }

      return $result;
    }

    private static function getContentsByTagNameNS($doc, $ns, $name) {
      $element = $doc->getElementsByTagNameNS($ns, $name)->item(0);
      return $element->ownerDocument->saveHTML($element);
    }

    private static function http() {
      if (!self::$http) {
        self::$http = new \GuzzleHttp\Client();
      }
      return self::$http;
    }

    private static function to_http_date($date) {
      # RFC 7231
      return (new \DateTime($date))->format('D, d M Y H:i:s e');
    }

    private static function to_db_date($date) {
      return (new \DateTime($date))->format('Y-m-d H:i:s');
    }

    private static function getSchema() {
      $response = self::http()->request(
        'GET', 'http://www.openarchives.org/OAI/2.0/static-repository.xsd'
      );
      return $response->getBody();
    }

    private static function xmlValidationErrors($doc) {
      libxml_clear_errors();

      // remove the contents of elements with an <any processContents="strict"
      // like schema since libxml doesn't validate them if their namespace is
      // not defined on the doc root. Insert a recognizable dummy element
      // instead.
      foreach (['metadata', 'about', 'setDescription', 'description'] as $name) {
        $ns = 'http://www.openarchives.org/OAI/2.0/';
        foreach($doc->getElementsByTagNameNS($ns, $name) as $e) {
          $e->nodeValue = '';

          $frag = $doc->createDocumentFragment();
          $frag->appendXML('<dummy></dummy>');
          $e->appendChild($frag);
        }
      }

      if (!$doc->schemaValidateSource(self::getSchema())) {
        $returnErrors = [];
        foreach (libxml_get_errors() as $error) {
          if ($error->code == 1871 && preg_match('/dummy/', $error->message)) {
            // do nothing, we intentionally inserted this element to make this
            // error message unambivalent.
          } else {
            $returnErrors[] = $error;
          }
        }
      }

      return $returnErrors;
    }

    private static function getBaseUrl($doc) {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      return $doc->getElementsByTagNameNS($ns, 'baseURL')->item(0)->textContent;
    }
  }
?>