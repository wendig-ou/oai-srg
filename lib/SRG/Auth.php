<?php
  namespace SRG;

  class Auth {
    public function __construct() {
      session_start();
    }

    public function login() {
      if (isset($_SERVER['REMOTE_USER'])) {
        $user = $_SERVER['REMOTE_USER'];
        $_SESSION['user'] = $user;
        \SRG::log("logged in user '$user' via REMOTE_USER server variable");
        return TRUE;
      } else {
        session_destroy();
        return FALSE;
      }
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
  }
?>