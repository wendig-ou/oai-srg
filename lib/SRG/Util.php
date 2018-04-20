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
  }
?>