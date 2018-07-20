<?php 
  namespace SRG;

  class OaiPmh {
    public function __construct($url) {
      $this->url = $url;
      $this->repository = \SRG\Repository::find_by_url($this->url);

      $this->verify_gateway();
    }

    public function verify_gateway() {
      if (!$this->repository) {
        $message = [
          "the repository '{$this->url}' was not found (because it was not initiated",
          "or bacause mediation has been terminated"
        ];
        throw new \SRG\Exception(join(' ', $message), 502);
      }

      if (!$this->repository->approved) {
        $message = "the repository '{$this->url}' has not been approved for mediation (yet)";
        throw new \SRG\Exception($message, 503);
      }

      if (!\SRG\Gateway::import($this->url)) {
        $message = [
          "the repository '{$this->url}' is not available at its designated location",
          "and/or there were errors interacting with it and/or the retrieved",
          "data didn't pass verification"
        ];
        throw new \SRG\Exception(join(' ', $message), 504);
      }

      # we reload the repository because the import might have changed it
      $this->repository = \SRG\Repository::find_by_url($this->url);
    }

    public function identify() {
      return [
        'url' => $this->endpoint_url(),
        'admin_email' => getenv('SRG_ADMIN_EMAIL'),
        'notes' => getenv('SRG_NOTES'),
        'friends' => \Srg\Repository::friends(),
        'repository' => $this->repository
      ];
    }

    public function list_metadata_formats() {
      return [
        'url' => $this->endpoint_url(),
        'list_metadata_formats' => $this->repository->list_metadata_formats
      ];
    }

    public function get_record($identifier, $prefix) {
      if (!$identifier) {
        throw new \SRG\OAIException('No identifier given', 'badArgument', $this->endpoint_url());
      }

      if (!$prefix) {
        throw new \SRG\OAIException('No metadata prefix given', 'badArgument', $this->endpoint_url());
      }

      if (!$this->repository->can_disseminate($prefix)) {
        throw new \SRG\OAIException('Metadata format not supported', 'cannotDisseminateFormat', $this->endpoint_url());
      }

      $record = $this->repository->find_record($prefix, $identifier);

      if (!$record) {
        throw new \SRG\OAIException('record not found', 'idDoesNotExist', $this->endpoint_url());
      }

      return [
        'url' => $this->endpoint_url(),
        'record' => $record
      ];
    }

    public function list_identifiers($resumptionToken, $prefix, $from, $until) {
      return $this->listing('ListIdentifiers', $resumptionToken, $prefix, $from, $until);
    }

    public function list_records($resumptionToken, $prefix, $from, $until) {
      return $this->listing('ListRecords', $resumptionToken, $prefix, $from, $until);
    }

    public function listing($verb, $resumptionToken, $prefix, $from, $until) {
      $page = 1;

      if ($resumptionToken) {
        $state = $this->repository->load_state($verb, $resumptionToken);

        if (!$state) {
          throw new \SRG\OAIException(
            'The value of the resumptionToken argument is invalid or expired. Also, the static repository might have changed after this resumptionToken was issued', 'badResumptionToken', $this->endpoint_url()
          );
        }

        $prefix = \SRG\Util::get($state, 'prefix');
        $from = \SRG\Util::get($state, 'from');
        $until = \SRG\Util::get($state, 'until');
        $page = \SRG\Util::get($state, 'page', 1);
      }

      if (!$prefix) {
        throw new \SRG\OAIException('No metadata prefix given', 'badArgument', $this->endpoint_url());
      }

      if (!$this->repository->can_disseminate($prefix)) {
        throw new \SRG\OAIException('Metadata format not supported', 'cannotDisseminateFormat', $this->endpoint_url());
      }

      if (!\SRG\Util::validateDate($from)) {
        throw new \SRG\OAIException('from parameter is not a valid date', 'badArgument', $this->endpoint_url());
      }

      if (!\SRG\Util::validateDate($until)) {
        throw new \SRG\OAIException('until parameter is not a valid date', 'badArgument', $this->endpoint_url());
      }

      $criteria = ['from' => $from, 'until' => $until];
      $search = $this->repository->find_records($prefix, $page, $criteria);

      if ($search['total'] == 0) {
        throw new \SRG\OAIException('No records match the from, until and metadataPrefix combination', 'noRecordsMatch', $this->endpoint_url());
      }

      $per_page = intval(getenv('SRG_PER_PAGE'));
      $newResumptionToken = NULL;
      if ($search['total'] > $per_page) {
        # entire response set too big for one page
        if ($search['total'] > ($page * $per_page)) {
          # this page is not enough, we need another one
          $state = array_merge($criteria, [
            'prefix' => $prefix,
            'page' => $page + 1
          ]);
          $newResumptionToken = $this->repository->save_state($verb, $state);
        } else {
          # this is the last page
          $newResumptionToken = 'LAST';
        }
      }

      return [
        'url' => $this->endpoint_url(),
        'prefix' => $prefix,
        'from' => $from,
        'until' => $until,
        'records' => $search['records'],
        'oldToken' => $resumptionToken,
        'newToken' => $newResumptionToken,
        'total' => $search['total'],
        'expires_at' => \SRG\Util::to_oai_date((new \DateTime())->modify('+2 hours'))
      ];
    }

    public function endpoint_url() {
      return getenv('SRG_BASE_URL') . '/oai-pmh/' . \SRG\Util::reposify($this->url);
    }
  }
?>