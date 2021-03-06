<?php 
  # To help the built-in PHP dev server, check if the request was actually for
  # something which should probably be served as a static file
  if (PHP_SAPI === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }

    # dirty fix, see https://github.com/slimphp/Slim/issues/359
    $_SERVER['SCRIPT_NAME'] = '/index.php';
  }

  require __DIR__ . '/../vendor/autoload.php';
  require __DIR__ . '/../lib/SRG.php';

  $app = new \Slim\App([
    'settings' => [
      'debug' => getenv('SRG_DEBUG') === 'true',
      'displayErrorDetails' => getenv('SRG_DEBUG') === 'true',
      'addContentLengthHeader' => TRUE,
      'determineRouteBeforeAppMiddleware' => TRUE
    ]
  ]);

  $container = $app->getContainer();

  # twig view
  $container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(SRG_ROOT . '/templates', [
      'cache' => FALSE,
      'strict_variables' => TRUE
    ]);
    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));
    return $view;
  };

  # request logging middleware
  $app->add(function($req, $res, $next) {
    $method = $req->getMethod();
    $path = $req->getRequestTarget();
    \SRG::log("$method: $path");
    $res = $next($req, $res);
    return $res;
  });

  # SRG exceptions middleware
  $app->add(function($req, $res, $next) {
    try {
      $res = $next($req, $res);
    } catch(\SRG\OAIException $e) {
      global $container;
      $res = $res->withHeader('Content-type', 'text/xml');
      $res = $res->withStatus($e->getCode());
      $params = $req->getQueryParams();
      return $container->view->render($res, 'oai_pmh/Error.xml', [
        'url' => $e->getUrl(),
        'verb' => \SRG\Util::get($params, 'verb'),
        'code' => $e->getOAIErrorCode(),
        'message' => $e->getMessage()
      ]);
    } catch(\SRG\Exception $e) {
      global $container;
      $res = $res->withStatus($e->getCode());
      return $container->view->render($res, 'error.html', [
        'message' => $e->getMessage()
      ]);
    }

    return $res;
  });

  # routes
  $app->get('/', '\SRG\Web:index');
  $app->get('/login', '\SRG\Web:login');
  $app->post('/login', '\SRG\Web:login');
  $app->get('/logout', '\SRG\Web:logout');
  $app->get('/gateway/new', '\SRG\Web:form');

  # the gateway routes (initiate, approve, terminate etc)
  $app->get('/gateway', '\SRG\Web:gateway');

  # the oai pmh routes (Identify, ListRecords etc)
  $app->get('/oai-pmh/{repository:.*}', '\SRG\Web:oai_pmh');
  $app->post('/oai-pmh/{repository:.*}', '\SRG\Web:oai_pmh');

  $app->run();
?>