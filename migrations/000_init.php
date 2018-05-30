<?php 
  
  SRG::db()->query('
    CREATE TABLE repositories (
      id int(11) AUTO_INCREMENT PRIMARY KEY,
      url varchar(255),
      admin_email varchar(255),
      formats varchar(255),
      approved boolean,
      verified boolean,
      modified_at datetime,
      verified_at datetime,
      errors text,
      warnings text,
      identify text,
      list_metadata_formats text
    )
  ');

  SRG::db()->query('
    CREATE TABLE records (
      id int(11) AUTO_INCREMENT PRIMARY KEY,
      repository_id int(11),
      identifier varchar(255),
      modified_at date,
      payload text
    )
  ');

  SRG::db()->query('
    CREATE TABLE resumption_tokens (
      id int(11) AUTO_INCREMENT PRIMARY KEY,
      identifier varchar(255),
      created_at datetime,
      state text
    )
  ');

?>