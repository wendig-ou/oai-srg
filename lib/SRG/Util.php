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
      $components = parse_url($uri);

      if ($components['port'] == 443) {
        return "https://{$components['host']}{$components['path']}";
      }

      if ($components['port'] == 80) {
        return "http://{$components['host']}{$components['path']}";  
      } else {
        return "http://{$components['host']}:{$components['port']}{$components['path']}";
      }
    }
  }
?>