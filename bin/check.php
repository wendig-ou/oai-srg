#!/usr/bin/env php

<?php
  require __DIR__.'/../lib/SRG.php';


  if (isset($argv[1])) {
    $url = $argv[1];
    \SRG\Gateway::initiate($url);
    \SRG\Gateway::approve($url);
    \SRG\Gateway::import($url);
  } else {
    \SRG::log('please provide a valid url as first parameter');
  }

  // \SRG\Gateway::initiate('https://static.wendig.io/tmp/sample.xml');
  // \SRG\Gateway::approve('https://static.wendig.io/tmp/sample.xml');
  // \SRG\Gateway::extract('https://static.wendig.io/tmp/sample.xml');
  
  // \SRG\Gateway::terminate('https://static.wendig.io/tmp/sample.xml');
?>