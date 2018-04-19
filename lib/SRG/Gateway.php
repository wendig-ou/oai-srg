<?php 
  namespace SRG;

  class Gateway {
    static $http = null;

    public static function intermediate($url) {
      
    }

    public static function initiate($url) {
      if ($repository = Repository::by_url($url)) {
        // echo $repository->url;
      } else {
        Repository::create(['url' => $url]);
        $repository = Repository::by_url($url);
        // echo $repository->url;
      }

      return $repository;
    }

    public static function terminate($url) {
      
    }

    public static function verify($url) {
      $repository = Repository::by_url($url);

      $opts = [];

      if ($repository->modified_at) {
        $opts['headers'] = [
          'If-Modified-Since' => self::to_http_date($repository->modified_at)
        ];
      }
      
      $response = self::http()->request('GET', $repository->url, $opts);
      $status = $response->getStatusCode();

      if ($status == 304) {
        $repository->update(['verified_at' => self::to_db_date('now')]);
        return TRUE;
      }

      if ($status != 200) {
        echo $response->getStatusCode() . $response->getReasonPhrase . ":\n";
        echo $response->getBody() . "\n";
        return FALSE;
      }

      // TODO: check presence of Last-Modified header

      // TODO: check response is xml and passes schema validation

      // TODO: check baseUrl of response matches gateway

      $lm = self::to_db_date($response->getHeaderLine('Last-Modified'));
      $repository->update([
        'modified_at' => $lm,
      ]);

      echo $response->getBody();
    }

    // public static function update($repository, $response) {
    //   if ($response->getStatusCode() == 200) {

    //   } else {

    //   }
    //   $repository->update()
    // }

    public static function find_by($url) {
      
    }

    private static function http() {
      if (!self::$http) {
        self::$http = new \GuzzleHttp\Client();
      }
      return self::$http;
    }

    // private static function reverse_merge(&$array, $defaults) {
    //   foreach ($defaults as $k => $v) {
    //     if (!array_key_exists($k, $array)) {
    //       $array[$k] = $v;
    //     }
    //   }
    // }

    private static function to_http_date($date) {
      # RFC 7231
      return (new \DateTime($date))->format('D, d M Y H:i:s e');
    }

    private static function to_db_date($date) {
      return (new \DateTime($date))->format('Y-m-d H:i:s');
    }
  }
?>