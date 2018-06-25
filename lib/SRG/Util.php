<?php 
  namespace SRG;

  class Util {
    public static function reverse_merge(&$array, $defaults) {
      foreach ($defaults as $k => $v) {
        if (!array_key_exists($k, $array)) {
          $array[$k] = $v;
        }
      }
      
      return $array;
    }

    public static function to_http_date($date) {
      # RFC 7231
      return (new \DateTime($date))->format('D, d M Y H:i:s e');
    }

    public static function to_db_date($date) {
      return (new \DateTime($date))->format('Y-m-d H:i:s');
    }

    public static function build_url($uri) {
      if (preg_match('/^([^\/]+)(:\d+)?(.*)$/', $uri, $matches)) {
        $host = $matches[1];
        $port = ($matches[2] ? preg_replace('/^:/', $matches[2]) : '80');
        $path = $matches[3];

        if ($port == '443') {
          return "https://$host$path";
        }

        if ($port == '80') {
          return "http://$host$path";
        }

        return "http://$host:$port$path";
      }
    }

    public static function reposify($string) {
      $parts = parse_url($string);

      if (!isset($parts['host'])) {$parts['host'] = '';}
      if (!isset($parts['port'])) {$parts['port'] = '';}
      if (!isset($parts['path'])) {$parts['path'] = '';}

      if ($parts['port'] === '443') {
        return $parts['host'] . ':443' . $parts['path'];
      }

      if ($parts['port'] === '') {
        return $parts['host'] . $parts['path'];
      }

      return $parts['host'] . ':' . $parts['port'] . $parts['path'];
    }
  }
?>