<?php 
  namespace SRG;

  class Gateway {
    static $http = null;

    public static function initiate($url) {
      \SRG::log("initiating mediation for repository '$url'");
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
      \SRG::log("terminating mediation for repository '$url'");
      $repository = Repository::find($url);
      $repository->delete();
    }

    public static function verify($url) {
      $validator = new \SRG\Validator($url);
      return $validator->verify();
    }

    public static function extract($url) {
      $validator = new \SRG\Validator($url);
      if ($validator->verify()) {
        if (!$validator->not_modified()) {
          $importer = new \SRG\Importer($validator->repository(), $validator->body);
          $importer->import();
        }

        return TRUE;
      } else {
        return FALSE;
      }
    }

    public static function OAI_PMH_identify($url) {
      $repository = Repository::find($url);
      return $repository->identify;
    }
  }
?>