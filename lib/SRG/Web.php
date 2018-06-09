<?php 
  namespace SRG;

  class Web {
    public function __construct($container) {
      $this->container = $container;
      $this->extend();
    }

    public function login($req, $res, $args) {
      if (\SRG::auth()->login()) {
        return $res->withRedirect('/', 302);
      } else {
        return $this->container->view->render($res, 'login_failed.html');
      }
    }

    public function logout($req, $res, $args) {
      \SRG::auth()->logout();
      return $res->withRedirect('/', 302);
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
        Gateway::initiate($params['initiate']);
      }

      if (isset($params['approve'])) {
        Gateway::approve($params['approve']);
      }

      if (isset($params['verify'])) {
        Gateway::verify($params['verify']);
      }

      if (isset($params['terminate'])) {
        Gateway::terminate($params['terminate']);
      }

      return $res->withRedirect('/', 302);
    }

    public function form($req, $res, $args) {
      return $this->container->view->render($res, 'admin/form.html');
    }

    public function oai_pmh($req, $res, $args) {

    }

    // protected function render($res, $template, $args = []) {
    //   $args['base_url'] = ;

    //   return $this->container->view->render($res, $template, $args);
    // }

    protected function params() {
      return $req->getQueryParams();
    }

    protected function extend() {
      $twig_env = $this->container->view->getEnvironment();
      $twig_env->addGlobal('base_url', getenv('SRG_BASE_URL'));
      $twig_env->addGlobal('user', \SRG::auth()->user());

      $filter = new \Twig_SimpleFilter('reposify', function ($string) {
        $parts = parse_url($string);
        if ($parts['scheme'] == 'https') {
          return $parts['host'] . ':443' . $parts['path'];
        } else {
          return $parts['host'] . ':' . $parts['port'] . $parts['path'];
        }
      });
      $this->container->view->getEnvironment()->addFilter($filter);
    }

    // protected function absolute_url(relative_url) {
    //   return $
    // }
  }
?>