<?php 
  namespace SRG;

  class Gateway {
    static $http = null;

    public static function all($page = 1) {
      return Repository::all([
        'per_page' => 100,
        'page' => $page
      ]);
    }

    public static function published($page = 1) {
      return Repository::all([
        'where' => 'verified = 1 AND approved = 1',
        'per_page' => 100,
        'page' => $page
      ]);
    }

    public static function initiate($url) {
      \SRG::log("initiating mediation for repository '$url'");
      if (!static::validate_repository_url($url)) {
        $message = [
          'You are submitting the url "' . $url . '" for mediation.',
          'Repository urls must have scheme, host and path.',
          'Also they cannot contain a query string nor a hash fragment.'
        ];

        throw new \SRG\Exception(join(' ', $message), 406);
      }

      if ($repository = Repository::find_by_url($url, ['strict' => FALSE])) {
        \SRG::log("repository '$url' exists in db");
      } else {
        \SRG::log("repository '$url' doesn't exist in db");
        Repository::create(['url' => $url]);
        \SRG::log("repository '$url' created");
      }
    }

    public static function approve($url) {
      \SRG::log("approving mediation for repository '$url'");
      $repository = Repository::find_by_url($url);
      $repository->update(['approved' => TRUE]);
    }

    public static function terminate($url) {
      \SRG::log("verifying termination for repository '$url'");
      $repository = Repository::find_by_url($url);
      $validator = static::validator_for($url);
      if ($validator->can_terminate()) {
        $repository->delete();
      }
    }

    public static function terminate_unilaterally($url) {
      \SRG::log("terminating mediation for repository '$url'");
      $repository = Repository::find_by_url($url);
      $repository->delete();
    }

    public static function verify($url) {
      \SRG::log("verifying repository '$url'");
      $validator = static::validator_for($url);
      return $validator->verify();
    }

    public static function import($url) {
      \SRG::log("importing repository '$url'");
      $validator = static::validator_for($url);
      if ($validator->verify()) {
        $repository = $validator->repository();

        if ($validator->modified()) {
          $importer = new \SRG\Importer($validator->repository(), $validator->body);
          $importer->import();
        }

        return TRUE;
      } else {
        return FALSE;
      }
    }

    // public static function OAI_PMH_identify($url) {
    //   $repository = Repository::find($url);
    //   return $repository->identify;
    // }

    public static function validator_for($url) {
      return new \SRG\Validator($url);
    }

    public static function validate_repository_url($url) {
      $parts = parse_url($url);

      if (!isset($parts['host']) || !$parts['host']) {return FALSE;}
      if (!isset($parts['scheme']) || !$parts['scheme']) {return FALSE;}
      if (isset($parts['query']) && $parts['query']) {return FALSE;}
      if (isset($parts['fragment']) && $parts['fragment']) {return FALSE;}

      return TRUE;
    }
  }
?>