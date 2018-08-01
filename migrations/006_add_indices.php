<?php
  SRG::db()->query('
    ALTER TABLE repositories
      ADD INDEX findy (url)
  ');

  SRG::db()->query('
    ALTER TABLE records
      ADD INDEX findy (repository_id, identifier, prefix, modified_at)
  ');

  SRG::db()->query('
    ALTER TABLE resumption_tokens
      ADD INDEX findy (repository_id, verb, identifier, created_at)
  ');
?>