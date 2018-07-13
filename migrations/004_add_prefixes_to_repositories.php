<?php
  SRG::db()->query('
    ALTER TABLE repositories
      ADD COLUMN prefixes varchar(255)
  ');
?>