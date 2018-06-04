<?php 
  namespace SRG;

  class Web {
    public function __construct($container) {
      $this->container = $container;
      $this->extend();
    }

    public function index($req, $res, $args) {
      $repositories = Gateway::all();

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
      $this->container->view->getEnvironment()->addGlobal(
        'base_url', getenv('SRG_BASE_URL')
      );

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