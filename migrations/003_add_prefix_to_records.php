<?php
  SRG::db()->query('
    ALTER TABLE records
      ADD COLUMN prefix varchar(255)
  ');
?>