<?php 
  
  SRG::db()->query('
    CREATE TABLE repositories (
      id int(11) AUTO_INCREMENT PRIMARY KEY,
      url varchar(255),
      modified_at datetime,
      verified_at datetime
    )
  ');

  SRG::db()->query('
    CREATE TABLE records (
      id int(11) AUTO_INCREMENT PRIMARY KEY,
      repository_id int(11),
      identifier varchar(255),
      datestamp date,
      data text
    )
  ');

?>