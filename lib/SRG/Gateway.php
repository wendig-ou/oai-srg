<?php 
  namespace SRG;

  class Gateway {
    static $http = null;

    public static function intermediate($url) {
      
    }

    public static function initiate($url) {
      if ($repository = Repository::by_url($url)) {
        echo $repository->url;
      } else {
        Repository::create(['url' => $url]);
        $repository = Repository::by_url($url);
        echo $repository->url;
      }

      return $repository;
    }

    public static function terminate($url) {
      
    }

    public static function verify($url) {
      $repository = Repository::by_url($url);

      $response = self::http()->request('GET', $repository->url, [
        'headers' => ['If-Modified-Since' => $repository->modified_at]
      ]);

      $lm = new \Datetime($response->getHeaderLine('Last-Modified'));
      $repository->update([
        'modified_at' => $lm->format('Y-m-d H:M:S'),
      ]);

      echo $response->getBody();
    }

    public static function find_by($url) {
      
    }


    private static function fetch($url, $options = []) {
      self::reverse_merge($options, [
        'use_cache' => TRUE
      ]);

      $response = self::http()->request('GET', $url, [
        'headers' => ['If-Modified-Since' => time()]
      ]);
    }

    private static function http() {
      if (!self::$http) {
        self::$http = new \GuzzleHttp\Client();
      }
      return self::$http;
    }

    private static function reverse_merge(&$array, $defaults) {
      foreach ($defaults as $k => $v) {
        if (!array_key_exists($k, $array)) {
          $array[$k] = $v;
        }
      }
    }
  }
?>