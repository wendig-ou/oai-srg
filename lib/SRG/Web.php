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
      $view_url = getenv('SRG_BASE_URL') . '/oai-pmh/' . \SRG\Util::reposify($url);
      $repository = \SRG\Repository::find_by_url($url);

      if (!$repository) {
        $message = [
          "the repository '{$url}' was not found (because it was not initiated",
          "or bacause mediation has been terminated"
        ];
        throw new \SRG\Exception(join(' ', $message), 502);
      }
      
      if (!$repository->approved) {
        $message = "the repository '{$url}' has not been approved for mediation (yet)";
        throw new \SRG\Exception($message, 503);
      }

      if (!\SRG\Gateway::import($url)) {
        $message = [
          "the repository '{$url}' is not available at its designated location",
          "and/or there were errors interacting with it and/or the retrieved",
          "data didn't pass verification"
        ];
        throw new \SRG\Exception(join(' ', $message), 504);
      }

      $res = $res->withHeader('Content-type', 'text/xml');

      if (!\SRG\Util::get($params, 'verb')) {
        throw new \SRG\OAIException(
          'no OAI PMH verb given', 'badVerb', $view_url, 406
        );
      }

      if ($params['verb'] === 'Identify') {
        return $this->container->view->render($res, 'oai_pmh/Identify.xml', [
          'url' => $view_url,
          'admin_email' => getenv('SRG_ADMIN_EMAIL'),
          'notes' => getenv('SRG_NOTES'),
          'friends' => \Srg\Repository::friends(),
          'repository' => $repository
        ]);
      }

      if ($params['verb'] === 'ListMetadataFormats') {
        return $this->container->view->render($res, 'oai_pmh/ListMetadataFormats.xml', [
          'url' => $view_url,
          'list_metadata_formats' => $repository->list_metadata_formats
        ]);
      }

      if ($params['verb'] === 'GetRecord') {
        if (!\SRG\Util::get($params, 'identifier')) {
          throw new \SRG\OAIException('No identifier given', 'badArgument', $view_url, 406);
        }

        if (!\SRG\Util::get($params, 'metadataPrefix')) {
          throw new \SRG\OAIException('No metadata prefix given', 'badArgument', $view_url, 406);
        }

        # TODO: implement and test cannotDisseminateFormat

        $record = $repository->find_record($params['metadataPrefix'], $params['identifier']);

        if (!$record) {
          throw new \SRG\OAIException('record not found', 'idDoesNotExist', $view_url, 404);
        }

        return $this->container->view->render($res, 'oai_pmh/GetRecord.xml', [
          'url' => $view_url,
          'record' => $record
        ]);
      }

      if ($params['verb'] === 'ListIdentifiers') {
        $resumptionToken = \SRG\Util::get($params, 'resumptionToken');

        if ($resumptionToken) {
          $state = ResumptionToken::load_state($resumptionToken);
          $params = array_merge($params, $state);
        }

        $prefix = \SRG\Util::get($params, 'metadataPrefix');
        if (!$prefix) {
          throw new \SRG\OAIException('No metadata prefix given', 'badArgument', $view_url, 406);
        }

        # TODO: implement and test cannotDisseminateFormat

        $criteria = [
          'from' => \SRG\Util::get($params, 'from'),
          'until' => \SRG\Util::get($params, 'until'),
          'page' => \SRG\Util::get($params, 'page', 1)
        ];
        $search = $repository->find_records($prefix, $criteria);

        $per_page = intval(getenv('SRG_PER_PAGE'));
        $newResumptionToken = NULL;
        if ($search['total'] <= $per_page) {
          # one-page response set, nothing to do
        } else {
          if ($search['total'] > ($criteria['page'] * $per_page)) {
            # this page is not enough, we need another one
            $state = array_merge($criteria, [
              'metadataPrefix' => \SRG\Util::get($params, 'metadataPrefix'),
              'page' => \SRG\Util::get($params, 'page', 1) + 1
            ]);
            $newResumptionToken = ResumptionToken::save_state($state);
          } else {
            # this is the last page
            $newResumptionToken = 'LAST';
          }
        }

        return $this->container->view->render($res, 'oai_pmh/ListIdentifiers.xml', [
          'url' => $view_url,
          'records' => $search['records'],
          'token' => $newResumptionToken,
          'total' => $search['total'],
          'expires_at' => \SRG\Util::to_oai_date((new \DateTime())->modify('+24 hours')),
        ]);
      }

      throw new \SRG\OAIException(
        'Value of the verb argument is not a legal OAI-PMH verb', 'badVerb', $view_url, 406
      );
      // if ($params['verb']) {
      // } else {
      //   throw new \SRG\OAIException(join(' ', [
      //       'The request includes illegal arguments, is missing required',
      //       'arguments, includes a repeated argument, or values for arguments',
      //       'have an illegal syntax.'
      //     ]),
      //     'badArgument', $view_url,  406
      //   );
      // }
    }

    // protected function render($res, $template, $args = []) {
    //   $args['base_url'] = ;

    //   return $this->container->view->render($res, $template, $args);
    // }

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