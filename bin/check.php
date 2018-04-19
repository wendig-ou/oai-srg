<?php
  require __DIR__.'/../lib/SRG.php';

  \SRG\Gateway::initiate('https://static.wendig.io/tmp/sample.xml');
  
  \SRG\Gateway::verify('https://static.wendig.io/tmp/sample.xml');
?>