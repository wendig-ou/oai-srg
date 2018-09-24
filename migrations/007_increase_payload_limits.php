<?php
  SRG::db()->query('
    ALTER TABLE records
      MODIFY COLUMN payload LONGTEXT
  ');
?>