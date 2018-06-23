<?php
  namespace SRG;

  class Auth {
    public function __construct() {
      session_start();
    }

    public function login($password = NULL) {
      if (isset($_SERVER['REMOTE_USER'])) {
        $user = $_SERVER['REMOTE_USER'];
        $_SESSION['user'] = $user;
        \SRG::log("logged in user '$user' via REMOTE_USER server variable");
        return TRUE;
      }

      if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
        $user = $_SERVER['REDIRECT_REMOTE_USER'];
        $_SESSION['user'] = $user;
        \SRG::log("logged in user '$user' via REDIRECT_REMOTE_USER server variable");
        return TRUE;
      }

      if ($password) {
        if ($password === getenv('SRG_ADMIN_PASSWORD')) {
          $user = 'admin';
          $_SESSION['user'] = $user;
          \SRG::log("logged in user '$user' via PLAIN TEXT ENVIRONMENT VARIABLE");
          return TRUE;
        }

        $hash = getenv('SRG_ADMIN_PASSWORD_HASH');
        $salt = getenv('SRG_ADMIN_PASSWORD_SALT');
        if ($hash === static::hash($password, $salt)) {
          $user = 'admin';
          $_SESSION['user'] = $user;
          \SRG::log("logged in user '$user' via HASHED ENVIRONMENT VARIABLE");
          return TRUE;
        }
      }

      session_destroy();
      return FALSE;
    }

    public function logout() {
      session_destroy();
    }

    public function logged_in() {
      return isset($_SESSION['user']);
    }

    public function user() {
      return $this->logged_in() ? $_SESSION['user'] : NULL;
    }

    public static function hash($password, $salt = NULL) {
      if ($salt == NULL) {
        $salt = static::generate_salt();
        $hash = hash('sha256', $password . $salt);

        // echo "PLAIN: $password$salt\n";
        echo "SALT: $salt\n";
        echo "HASH: $hash\n";
      } else {
        return hash('sha256', $password . $salt);
      }
    }

    public static function generate_salt($length = 6) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen($characters);
      $salt = '';
      for ($i = 0; $i < $length; $i++) {
          $salt .= $characters[rand(0, $charactersLength - 1)];
      }
      return $salt;
    }
  }
?>