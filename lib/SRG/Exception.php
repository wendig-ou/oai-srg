<?php 
  namespace SRG;

  class Exception extends \Exception {
    public function __construct($message = null, $code = 0, Exception $previous = null) {
      if ($code == 0) {$code = 400;}
      parent::__construct($message , $code, $previous);
    }
  }
?>