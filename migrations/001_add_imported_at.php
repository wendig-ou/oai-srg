<?php
  SRG::db()->query('
    ALTER TABLE repositories
      ADD COLUMN imported_at datetime
  ');
?>