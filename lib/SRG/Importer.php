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
      $data = self::extract();

      $this->repository->update([
        'admin_email' => $data['admin_email'],
        'identify' => $data['data']['identify'],
        'list_metadata_formats' => $data['data']['list_metadata_formats']
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
      $oai_ns = 'http://www.openarchives.org/OAI/2.0/';
      $sr_ns = 'http://www.openarchives.org/OAI/2.0/static-repository';

      $result = [
        'repository_name' => $this->doc->getElementsByTagNameNS($oai_ns, 'repositoryName')->item(0)->nodeValue,
        'admin_email' => $this->doc->getElementsByTagNameNS($oai_ns, 'adminEmail')->item(0)->nodeValue,
        'earliest_datestamp' => $this->doc->getElementsByTagNameNS($oai_ns, 'earliestDatestamp')->item(0)->nodeValue,
        'data' => [
          'identify' => self::getContentsByTagNameNS($sr_ns, 'Identify'),
          'list_metadata_formats' => self::getContentsByTagNameNS($sr_ns, 'ListMetadataFormats'),
          'records' => []
        ]
      ];

      $list_records_batches = $this->doc->getElementsByTagNameNS($sr_ns, 'ListRecords');
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

    private function getContentsByTagNameNS($ns, $name) {
      $element = $this->doc->getElementsByTagNameNS($ns, $name)->item(0);
      return $element->ownerDocument->saveHTML($element);
    }
  }
?>