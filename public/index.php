<?php 
  # dirty fix, see https://github.com/slimphp/Slim/issues/359
  $_SERVER['SCRIPT_NAME'] = '/index.php';

  use \Psr\Http\Message\ServerRequestInterface as Request;
  use \Psr\Http\Message\ResponseInterface as Response;

  require 'vendor/autoload.php';
  require 'lib/SRG.php';

  $app = new \Slim\App([
    'settings' => [
      'displayErrorDetails' => getenv('SRG_DEBUG') == 'true',
      'addContentLengthHeader' => TRUE
    ]
  ]);

  $container = $app->getContainer();

  $container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('templates', [
      'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container->get('request')->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container->get('router'), $basePath));

    return $view;
  };

  $app->get('/gateway/{repository:.*}', function (Request $request, Response $response, array $args) {
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

  $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
      $name = $args['name'];
      $response->getBody()->write("Hello, $name");

      return $response;
  });

  $app->run();

  // foreach (\SRG\Repository::all() as $repository) {
  //   echo $repository->url . '<br />';
  // }
?>