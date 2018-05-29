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

    $response = $this->view->render($response, 'identify.xml', [
      'url' => $url,
      # TODO: this is not so good and has to be changed. The namespace prefix
      # should be read and identified from the original repository xml and
      # removed from the <Identify> element and its decendents for use in a 
      # normal Identify response
      'identify' => preg_replace('/oai:/', '', $repository->identify)
    ]);

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