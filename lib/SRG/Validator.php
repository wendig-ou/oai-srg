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

          if ($this->ok()) {
            $this->has_last_modified();

            if ($this->base_url_matches()) {
              if ($this->satisfies_schema()) {
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
      $result = $this->status == 304;
      return $result;
    }

    public function modified() {
      return !$this->not_modified();
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
      $response = \SRG::http()->request('GET', $this->repository()->url, $opts);
      $this->last_modified = $response->getHeaderLine('Last-Modified');
      if (!$this->last_modified) {
        $this->last_modified = Util::to_http_date($this->repository()->modified_at);
      }
      $this->status = $response->getStatusCode();
      $this->rp = $response->getReasonPhrase();
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
      if (getenv('SRG_REQUIRE_BASE_URL') == 'true') {
        $ns = 'http://www.openarchives.org/OAI/2.0/';
        $baseUrl = $this->doc()->getElementsByTagNameNS($ns, 'baseURL')->item(0)->textContent;
        $expectedBaseUrl = \SRG::baseUrl() . '/gateway';
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