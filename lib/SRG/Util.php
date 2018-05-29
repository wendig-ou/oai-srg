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
  }
?>