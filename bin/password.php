<?php
  require __DIR__.'/../lib/SRG.php';

  $password = $argv[1];

  \SRG\Auth::hash($password);
?>
