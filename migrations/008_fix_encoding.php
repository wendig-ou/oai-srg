<?php
  SRG::db()->query(
    'ALTER DATABASE ' . getenv('SRG_DB_DBNAME') .
    ' CHARACTER SET utf8 COLLATE utf8_unicode_ci'
  );

  $tables = ['records', 'repositories', 'resumption_tokens', 'migrations'];
  foreach ($tables as $table) {
    SRG::db()->query(
      'ALTER TABLE ' . $table .
      ' CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci'
    );
  }

  # we remove the index because it is too big for myisam, then convert the
  # table, shrink one of the columns and re-add the index
  SRG::db()->query('ALTER TABLE records DROP INDEX findy');
  SRG::db()->query('ALTER TABLE records ENGINE=MyISAM');
  SRG::db()->query('ALTER TABLE records MODIFY prefix varchar(50)');
  SRG::db()->query('
    ALTER TABLE records
      ADD INDEX findy (repository_id, identifier, prefix, modified_at)
  ');
?>