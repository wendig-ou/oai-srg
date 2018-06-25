<?php
  SRG::db()->query('
    ALTER TABLE repositories
      ADD COLUMN name varchar(255),
      ADD COLUMN version varchar(10),
      ADD COLUMN first_record_at date,

  ');
?>