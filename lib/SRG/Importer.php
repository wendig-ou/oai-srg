<?php 
  namespace SRG;

  class Importer {
    public function __construct($repository, $xml) {
      $this->repository = $repository;
      $this->xml = $xml;
    }

    public function import() {
      $this->doc = new \DOMDocument();
      $this->doc->loadXML($this->xml);
      $data = static::extract();

      $this->repository->update([
        'admin_email' => $data['admin_email'],
        'identify' => $data['data']['identify'],
        'list_metadata_formats' => $data['data']['list_metadata_formats'],
        'imported_at' => Util::to_db_date('now'),
        'name' => $data['repository_name'],
        'first_record_at' => $data['earliest_datestamp'],
        'version' => $data['version']
      ]);

      $this->repository->delete_records();
      foreach ($data['data']['records'] as $record) {
        $this->repository->create_record([
          'prefix' => $record['prefix'],
          'identifier' => $record['identifier'],
          'modified_at' => $record['datestamp'],
          'payload' => $record['payload']
        ]);
      }
    }

    private function extract() {
      $result = [
        'repository_name' => $this->doc->getElementsByTagNameNS(\SRG::$oai_ns, 'repositoryName')->item(0)->nodeValue,
        'admin_email' => $this->doc->getElementsByTagNameNS(\SRG::$oai_ns, 'adminEmail')->item(0)->nodeValue,
        'earliest_datestamp' => $this->doc->getElementsByTagNameNS(\SRG::$oai_ns, 'earliestDatestamp')->item(0)->nodeValue,
        'version' => $this->doc->getElementsByTagNameNS(\SRG::$oai_ns, 'protocolVersion')->item(0)->nodeValue,
        'data' => [
          'identify' => $this->getIdentify(),
          'list_metadata_formats' => $this->getListMetadataFormats(),
          'records' => []
        ]
      ];

      $list_records_batches = $this->doc->getElementsByTagNameNS(\SRG::$sr_ns, 'ListRecords');
      for ($i = 0; $i < $list_records_batches->length; $i++) {
        $batch = $list_records_batches->item($i);
        $prefix = $batch->getAttribute('metadataPrefix');

        $records = $batch->getElementsByTagNameNS(\SRG::$oai_ns, 'record');
        foreach ($records as $record) {
          $result['data']['records'][] = [
            'prefix' => $prefix,
            'identifier' => $record->getElementsByTagNameNS(\SRG::$oai_ns, 'identifier')->item(0)->nodeValue,
            'datestamp' => $record->getElementsByTagNameNS(\SRG::$oai_ns, 'datestamp')->item(0)->nodeValue,
            'payload' => $record->ownerDocument->saveHTML($record)
          ];
        }
      }

      return $result;
    }

    private function getListMetadataFormats() {
      $result = $this->getContentsByTagNameNS(\SRG::$sr_ns, 'ListMetadataFormats');
      return $this->removeOaiNsPrefix($result);
    }

    private function getIdentify() {
      $result = $this->getContentsByTagNameNS(\SRG::$sr_ns, 'Identify');
      return $this->removeOaiNsPrefix($result);
    }

    private function removeOaiNsPrefix($xml) {
      $prefix = $this->doc->lookupPrefix(\SRG::$oai_ns);
      if ($prefix != '') {
        # TODO: not exactly bulletproof ... but not so bad either
        $result = preg_replace("/<$prefix:/", '<', $xml);
        $result = preg_replace("/<\\/$prefix:/", "</", $result);
        return preg_replace("/ $prefix:/", ' ', $result);
      } else {
        return $xml;
      }
    }

    private function getContentsByTagNameNS($ns, $name) {
      $element = $this->doc->getElementsByTagNameNS($ns, $name)->item(0);
      return $element->ownerDocument->saveHTML($element);
    }
  }
?>