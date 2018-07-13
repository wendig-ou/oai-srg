<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
  public function _beforeSuite($settings = array()) {
      putenv('APP_ENV=test');

      echo "creating structure dump from dev db\n";
      exec('bin/structure-dump.sh');
      
      echo "purging screenshot directory\n";
      exec('rm -rf ./tests/_output/');
      exec('mkdir ./tests/_output/');
  }
}
