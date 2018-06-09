<?php 
  # To help the built-in PHP dev server, check if the request was actually for
  # something which should probably be served as a static file
  if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
  }

  # dirty fix, see https://github.com/slimphp/Slim/issues/359
  $_SERVER['SCRIPT_NAME'] = '/index.php';

  require 'vendor/autoload.php';
  require 'lib/SRG.php';

  // phpinfo();
  // return false;

  $app = new \Slim\App([
    'settings' => [
      'debug' => getenv('SRG_DEBUG') == 'true',
      'displayErrorDetails' => getenv('SRG_DEBUG') == 'true',
      'addContentLengthHeader' => TRUE,
      'determineRouteBeforeAppMiddleware' => TRUE
    ]
  ]);

  $container = $app->getContainer();

  $container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('templates', [
      'cache' => FALSE,
      'strict_variables' => TRUE
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));

    return $view;
  };

  # request logging
  $app->add(function ($req, $res, $next) {
    $method = $req->getMethod();
    $path = $req->getRequestTarget();
    \SRG::log("$method: $path");
    $res = $next($req, $res);
    return $res;
  });

  # routes
  $app->get('/', '\SRG\Web:index');
  $app->get('/login', '\SRG\Web:login');
  $app->get('/gateway', '\SRG\Web:gateway');
  $app->get('/gateway/new', '\SRG\Web:form');

  use \Psr\Http\Message\ServerRequestInterface as Request;
  use \Psr\Http\Message\ResponseInterface as Response;
  $app->get('/oai/{repository:.*}', function (Request $request, Response $response, array $args) {
    $params = $request->getQueryParams();
    $url = \SRG\Util::build_url($args['repository']);

    $repository = \SRG\Repository::find_by_url($url, ['strict' => TRUE]);

    $response = $response->withHeader('Content-type', 'text/xml');

    if ($params['verb'] == 'Identify') {
      return $this->view->render($response, 'identify.xml', [
        'url' => $url,
        'identify' => $repository->identify
      ]);
    }

    if ($params['verb'] == 'ListMetadataFormats') {
      return $this->view->render($response, 'list_metadata_formats.xml', [
        'url' => $url,
        'list_metadata_formats' => $repository->list_metadata_formats
      ]);
    }

    // $response->getBody()->write($repository->identify);
    // $response->getBody()->write(var_export($args, true));
    // $response->getBody()->write(var_export($params, true));
    // $response->getBody()->write(var_export($url, true));

    return $response;
  });

  // $app->get('/', function (Request $request, Response $response, array $args) {
  //   $repositories = \SRG\Repository::all();
  //   $response->getBody()->write(var_export($repositories, true));
  //   return $response;
  // });

  // $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
  //     $name = $args['name'];
  //     $response->getBody()->write("Hello, $name");

  //     return $response;
  // });

  $app->run();
?>