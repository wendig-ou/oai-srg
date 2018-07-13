<?php 
  namespace SRG;

  class OAIException extends \SRG\Exception {
    public function __construct($message, $oaiErrorCode, $url, $code = 0, Exception $previous = null) {
      if ($code == 0) {$code = 406;}
      parent::__construct($message , $code, $previous);
      $this->oaiErrorCode = $oaiErrorCode;
      $this->url = $url;
    }

    public function getOaiErrorCode() {
      return $this->oaiErrorCode;
    }

    public function getUrl() {
      return $this->url;
    }
  }
?>