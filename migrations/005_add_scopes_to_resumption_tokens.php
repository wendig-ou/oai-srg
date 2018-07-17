<?php
  SRG::db()->query('
    ALTER TABLE resumption_tokens
      ADD COLUMN repository_id int(11),
      ADD COLUMN verb varchar(255)
  ');
?>