<?php 
  namespace SRG;

  class Validator {
    public function __construct($url) {
      $this->url = $url;
      $this->errors = [];
      $this->warnings = [];

      $this->repo = NULL;
      $this->xml_doc = NULL;
      $this->last_modified = NULL;
    }

    public function verify() {
      $this->log("verifying");

      if ($this->exists_in_db()) {
        if ($this->fetch()) {

          if ($this->not_modified()) {
            $this->persist(['verified' => TRUE]);
            return TRUE;
          }

          if (!$this->is_xml()) {
            $this->persist(['verified' => FALSE]);
            return FALSE;
          }

          if ($this->ok()) {
            $this->has_last_modified();

            if (!$this->is_utf8()) {
              $this->persist(['verified' => FALSE]);
              return FALSE;
            }

            if ($this->base_url_matches()) {
              if ($this->satisfies_schema()) {

                if (!$this->no_sets()) {
                  $this->persist(['verified' => FALSE]);
                  return FALSE;
                }

                if (!$this->no_header_status()) {
                  $this->persist(['verified' => FALSE]);
                  return FALSE;
                }

                if (!$this->no_compression()) {
                  $this->persist(['verified' => FALSE]);
                  return FALSE;
                }

                if (!$this->granularity_ok()) {
                  $this->persist(['verified' => FALSE]);
                  return FALSE;
                }

                if (!$this->no_resumption_tokens()) {
                  $this->persist(['verified' => FALSE]);
                  return FALSE;
                }

                $this->persist(['verified' => TRUE]);
                return TRUE;
              }
            }
          }
        }
      }

      $this->persist(['verified' => FALSE]);
      return FALSE;
    }

    public function can_terminate() {
      $this->log("sending GET request");
      $response = NULL;

      try {
        $response = \SRG::http()->request('GET', $this->repository()->url);

        if ($response->getStatusCode() === 200) {
          $this->body = $response->getBody();

          if (getenv('SRG_REQUIRE_BASE_URL') === 'true') {
            return !$this->base_url_matches();
          } else {
            return TRUE;
          }
        } else {
          return TRUE;
        }
      } catch(\GuzzleHttp\Exception\ConnectException $e) {
        $this->errors[] = $e->getMessage();
        return TRUE;
      }
    }

    public function repository() {
      if (!$this->repo) {
        $this->repo = Repository::find_by_url($this->url, ['strict' => FALSE]);
      }
      return $this->repo;
    }

    private function exists_in_db() {
      if ($this->repository()) {
        return TRUE;
      } else {
        $this->errors[] = "repository '{$this->url}' doesn't exist in db";
        return FALSE;
      }
    }

    public function not_modified() {
      return $this->status == 304;
    }

    public function modified() {
      return !$this->not_modified();
    }

    private function is_xml() {
      if (!preg_match('/^text\/xml/', $this->content_type)) {
        if (getenv('SRG_ALLOW_APPLICATION_XML') === 'true') {
          if (!preg_match('/^application\/xml/', $this->content_type)) {
            $this->errors[] = "the content type '{$this->content_type}' doesn't match text/xml or application/xml";
            return FALSE;
          }
        } else {
          $this->errors[] = "the content type '{$this->content_type}' doesn't match text/xml";
          return FALSE;
        }
      }

      return TRUE;
    }

    private function is_utf8() {
      if (!mb_check_encoding($this->body, 'UTF-8')) {
        $this->errors[] = "the xml encoding is not valid UTF-8";
        return FALSE;
      }

      return TRUE;
    }

    private function no_sets() {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      $sets = $this->doc()->getElementsByTagNameNS($ns, 'setSpec');

      if ($sets->length > 0) {
        $this->errors[] = 'the repository contains setSpec elements';
        return FALSE;
      }

      return TRUE;
    }

    # check for status on header elements indicating deleted records support
    private function no_header_status() {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      $headers = $this->doc()->getElementsByTagNameNS($ns, 'header');
      for ($i = 0; $i < $headers->length; $i++) {
        $e = $headers->item($i);
        if ($e->getAttribute('status') != '') {
          $this->errors[] = 'the repository contains header elements with a status attribute indicating deleted record support';
          return FALSE;
        }
      }

      return TRUE;
    }

    private function no_compression() {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      $compression = $this->doc()->getElementsByTagNameNS($ns, 'compression');

      if ($compression->length > 0) {
        $this->errors[] = 'the repository contains a compression element';
        return FALSE;
      }

      return TRUE;
    }

    private function granularity_ok() {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      $granularity = $this->doc()->getElementsByTagNameNS($ns, 'granularity');

      if ($granularity->length == 0) {
        $this->errors[] = 'granularity element not found';
        return FALSE;
      } else {
        $actual = $granularity->item(0)->textContent;
        if ($actual != 'YYYY-MM-DD') {
          $this->errors[] = 'harvesting granularity has to be "YYYY-MM-DD" but is "' . $actual . '"';
          return FALSE;
        }
      }

      return TRUE;
    }

    private function no_resumption_tokens() {
      $ns = 'http://www.openarchives.org/OAI/2.0/';
      $rt = $this->doc()->getElementsByTagNameNS($ns, 'resumptionToken');

      if ($rt->length > 0) {
        $this->errors[] = 'the repository contains resumptionToken elements';
        return FALSE;
      }

      return TRUE;
    }

    private function ok() {
      if ($this->status == 200) {
        return TRUE;
      } else {
        $this->errors[] = "the server responded with {$this->status} {$this->rp}\n{$this->body}\n";
        return FALSE;
      }
    }

    private function has_last_modified() {
      if ($this->last_modified) {
        return TRUE;
      } else {
        $this->warnings[] = 'there was no Last-Modified header present in the response';
        return FALSE;
      }
    }

    private function fetch() {
      $opts = [
        'http_errors' => FALSE,
        'allow_redirects' => ['max' => 10]
      ];

      if ($this->repository()->modified_at && !$this->repository()->never_imported()) {
        $opts['headers'] = [
          'If-Modified-Since' => Util::to_http_date($this->repository()->modified_at)
        ];
      }
      
      $this->log("sending GET request");
      $response = NULL;

      try {
        $response = \SRG::http()->request('GET', $this->repository()->url, $opts);
      } catch(\GuzzleHttp\Exception\ConnectException $e) {
        $this->errors[] = $e->getMessage();
        return FALSE;
      }

      $this->last_modified = $response->getHeaderLine('Last-Modified');
      if (!$this->last_modified) {
        $this->last_modified = Util::to_http_date($this->repository()->modified_at);
      }
      $this->status = $response->getStatusCode();
      $this->rp = $response->getReasonPhrase();
      $this->content_type = $response->getHeaderLine('Content-Type');
      $this->body = $response->getBody();
      $this->log("received {$this->status} {$this->rp}");

      if ($this->status < 200 || $this->status > 399) {
        $this->errors[] = "received {$this->status} {$this->rp}";
        return FALSE;
      } else {
        return TRUE;
      }
    }

    private function doc() {
      if (!$this->xml_doc) {
        $this->xml_doc = new \DOMDocument();
        $this->xml_doc->loadXML($this->body);
      }
      return $this->xml_doc;
    }

    private function satisfies_schema() {
      $verrors = $this->outerXmlErrors();
      if (sizeof($verrors)) {
        foreach ($verrors as $error) {
          $this->errors[] = $error->line . ': ' . $error->message;
        }
        return FALSE;
      }
      return TRUE;
    }

    private function base_url_matches() {
      if (getenv('SRG_REQUIRE_BASE_URL') === 'true') {
        $ns = 'http://www.openarchives.org/OAI/2.0/';
        $baseUrl = $this->doc()->getElementsByTagNameNS($ns, 'baseURL')->item(0)->textContent;
        $expectedBaseUrl = \SRG::baseUrl() . '/gateway/' . \SRG\Util::reposify($this->repository()->url);
        if ($baseUrl != $expectedBaseUrl) {
          $this->errors[] = 'base url should be ' . $expectedBaseUrl . ' but was ' . $baseUrl;
          return FALSE;
        }
      }

      return TRUE;
    }

    private function outerXmlErrors() {
      libxml_clear_errors();

      // remove the contents of elements with an <any processContents="strict"
      // like schema since libxml doesn't validate them if their namespace is
      // not defined on the doc root. Insert a recognizable dummy element
      // instead.
      foreach (['metadata', 'about', 'setDescription', 'description'] as $name) {
        $ns = 'http://www.openarchives.org/OAI/2.0/';
        foreach($this->doc()->getElementsByTagNameNS($ns, $name) as $e) {
          $e->nodeValue = '';

          $frag = $this->doc()->createDocumentFragment();
          $frag->appendXML('<dummy></dummy>');
          $e->appendChild($frag);
        }
      }

      if (!$this->doc()->schemaValidateSource(self::getSchema())) {
        $returnErrors = [];
        foreach (libxml_get_errors() as $error) {
          if ($error->code == 1871 && preg_match('/dummy/', $error->message)) {
            // do nothing, we intentionally inserted this element to make this
            // error message recognizable so we can ignorable it.
          } else {
            $returnErrors[] = $error;
          }
        }
      }

      return $returnErrors;
    }

    private static function getSchema() {
      $response = \SRG::http()->request(
        'GET', 'http://www.openarchives.org/OAI/2.0/static-repository.xsd'
      );
      return $response->getBody();
    }

    private function persist($values = []) {
      if (sizeof($this->warnings)) {
        \SRG::log('warnings: ' . join('|', $this->warnings));
      }
      if (sizeof($this->errors)) {
        \SRG::log('errors: ' . join('|', $this->errors));
      }

      Util::reverse_merge($values, [
        'verified_at' => Util::to_db_date('now'),
        'modified_at' => Util::to_db_date($this->last_modified),
        'errors' => join("|", $this->errors),
        'warnings' => join("|", $this->warnings)
      ]);

      $this->repository()->update($values);
    }

    private function log($message) {
      \SRG::log("repository '{$this->url}': $message");
    }
  }
?>