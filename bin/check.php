<?php
  require __DIR__.'/../lib/SRG.php';

  \SRG\Gateway::initiate('https://static.wendig.io/tmp/sample.xml');
  \SRG\Gateway::approve('https://static.wendig.io/tmp/sample.xml');

  \SRG\Gateway::verify('https://static.wendig.io/tmp/sample.xml');
  
  // \SRG\Gateway::terminate('https://static.wendig.io/tmp/sample.xml');
?>