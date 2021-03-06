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
      if (! $date instanceof \DateTime) {
        $date = new \DateTime($date);
      }
      
      # We pull dates from the db where we only write utc timestamps (but without
      # the timezone information). Therefore we don't convert them to utc (a
      # second time) but we assume utc.
      return $date->format('D, d M Y H:i:s \G\M\T'); # RFC 7231
    }

    public static function to_db_date($date) {
      if (! $date instanceof \DateTime) {
        $date = new \DateTime($date);
      }

      $date->setTimezone(new \DateTimeZone("UTC"));
      return $date->format('Y-m-d H:i:s');
    }

    public static function to_filter_date($date) {
      if (! $date instanceof \DateTime) {
        $date = new \DateTime($date);
      }

      return $date->format('Y-m-d'); 
    }

    public static function to_oai_date($date) {
      if (! $date instanceof \DateTime) {
        $date = new \DateTime($date);
      }

      $date->setTimezone(new \DateTimeZone("UTC"));
      return $date->format('Y-m-d\TH:i:s\Z');
    }

    public static function validateDate($date, $format = 'Y-m-d') {
      if (!$date) {return TRUE;}

      $d = \DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) === $date;
    }

    public static function build_url($uri) {
      if (preg_match('/^([^\/:]+)(:\d+)?(.*)$/', $uri, $matches)) {
        $host = $matches[1];
        $port = ($matches[2] ? preg_replace('/^:/', '', $matches[2]) : '80');
        $path = $matches[3];

        if ($port === '443') {
          return "https://$host$path";
        }

        if ($port === '80') {
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

      if ($parts['scheme'] === 'https') {$parts['port'] = '443';}

      if ($parts['port'] === '443' || $parts['port'] == 443) {
        return $parts['host'] . ':443' . $parts['path'];
      }

      if ($parts['port'] === '') {
        return $parts['host'] . $parts['path'];
      }

      return $parts['host'] . ':' . $parts['port'] . $parts['path'];
    }

    public static function get($array, $key, $default = NULL) {
      if (isset($array[$key])) {
        return $array[$key];
      } else {
        return $default;
      }
    }

    public static function preferred_prefix($repository) {
      if (getenv('SRG_PREFER_ALTERNATE_PREFIX') === 'true') {
        error_log(print_r($repository->prefixes, TRUE));
        foreach (explode(',', $repository->prefixes) as $prefix) {
          if ($prefix !== 'oai_dc') {
            return $prefix;
          }
        }
      }

      return 'oai_dc';
    }
  }
?>