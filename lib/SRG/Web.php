<?php 
  namespace SRG;

  class Web {
    public function __construct($container) {
      $this->container = $container;
      $this->extend();
    }

    public function login($req, $res, $args) {
      $password = NULL;

      if ($req->isPost()) {
        $password = $req->getParsedBodyParam('password');
      }

      if (\SRG::auth()->login($password)) {
        return $res->withRedirect(getenv('SRG_BASE_URL'), 302);
      } else {
        $opts = ['password_mismatch' => $req->isPost()];
        return $this->container->view->render($res, 'admin/login.html', $opts);
      }
    }

    public function logout($req, $res, $args) {
      \SRG::auth()->logout();
      return $res->withRedirect(getenv('SRG_BASE_URL'), 302);
    }

    public function index($req, $res, $args) {
      if (\SRG::auth()->logged_in()) {
        $repositories = Gateway::all();
      } else {
        $repositories = Gateway::published();
      }
      
      return $this->container->view->render($res, 'index.html', [
        'repositories' => $repositories
      ]);
    }

    public function gateway($req, $res, $args) {
      $params = $req->getQueryParams();

      if (isset($params['initiate'])) {
        $url = trim($params['initiate']);
        Gateway::initiate($url);
      }

      if (isset($params['terminate'])) {
        $url = trim($params['terminate']);
        Gateway::terminate($url);
      }

      if ($this->logged_in()) {
        if (isset($params['approve'])) {
          $url = trim($params['approve']);
          Gateway::approve($url);
        }

        if (isset($params['import'])) {
          $url = trim($params['import']);
          Gateway::import($url);
        }

        if (isset($params['terminate-unilaterally'])) {
          $url = trim($params['terminate-unilaterally']);
          Gateway::terminate_unilaterally($url);
        }
      }

      return $res->withRedirect(getenv('SRG_BASE_URL'), 302);
    }

    public function form($req, $res, $args) {
      return $this->container->view->render($res, 'admin/initiate.html');
    }

    public function oai_pmh($req, $res, $args) {
      $params = $req->getQueryParams();

      $url = \SRG\Util::build_url($args['repository']);
      $oai = new \SRG\OAI_PMH($url);

      if (!\SRG\Util::get($params, 'verb')) {
        throw new \SRG\OAIException(
          'no OAI PMH verb given', 'badVerb', $view_url, 406
        );
      }

      $res = $res->withHeader('Content-type', 'text/xml');

      if ($params['verb'] === 'Identify') {
        return $this->container->view->render(
          $res, 'oai_pmh/Identify.xml', $oai->identify()
        );
      }

      if ($params['verb'] === 'ListMetadataFormats') {
        return $this->container->view->render(
          $res, 'oai_pmh/ListMetadataFormats.xml', $oai->list_metadata_formats()
        );
      }

      if ($params['verb'] === 'GetRecord') {
        return $this->container->view->render(
          $res, 'oai_pmh/GetRecord.xml', $oai->get_record(
            \SRG\Util::get($params, 'identifier'),
            \SRG\Util::get($params, 'metadataPrefix')
          )
        );
      }

      if ($params['verb'] === 'ListIdentifiers') {
        return $this->container->view->render(
          $res, 'oai_pmh/ListIdentifiers.xml', $oai->list_identifiers(
            \SRG\Util::get($params, 'resumptionToken'),
            \SRG\Util::get($params, 'metadataPrefix'),
            \SRG\Util::get($params, 'from'),
            \SRG\Util::get($params, 'until')
          )
        );
      }

      if ($params['verb'] === 'ListRecords') {
        return $this->container->view->render(
          $res, 'oai_pmh/ListRecords.xml', $oai->list_records(
            \SRG\Util::get($params, 'resumptionToken'),
            \SRG\Util::get($params, 'metadataPrefix'),
            \SRG\Util::get($params, 'from'),
            \SRG\Util::get($params, 'until')
          )
        );
      }

      throw new \SRG\OAIException(
        'Value of the verb argument is not a legal OAI-PMH verb', 'badVerb', $view_url, 406
      );
    }

    protected function params() {
      return $req->getQueryParams();
    }

    protected function logged_in() {
      return !!\SRG::auth()->user();
    }

    protected function extend() {
      $twig_env = $this->container->view->getEnvironment();
      $twig_env->addGlobal('base_url', getenv('SRG_BASE_URL'));
      $twig_env->addGlobal('user', \SRG::auth()->user());
      $twig_env->addGlobal('now', \SRG\Util::to_oai_date('now'));

      $filter = new \Twig_SimpleFilter('reposify', function($s) {return \SRG\Util::reposify($s);});
      $this->container->view->getEnvironment()->addFilter($filter);
    }

    // protected function absolute_url(relative_url) {
    //   return $
    // }
  }
?>