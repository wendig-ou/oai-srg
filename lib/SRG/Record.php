<?php 
  namespace SRG;

  class Record extends Model {
    static $table_name = 'records';
    static $safe_columns = [
      'identifier', 'modified_at', 'payload', 'repository_id', 'prefix'
    ];

    public static function find_by_identifier($value, $options = []) {
      return static::find_by('identifier', $value, $options);
    }

    public static function find_by_repository_and_prefix_and_identifier($repository_id, $prefix, $identifier) {
      $tn = static::$table_name;
      $query = join(' ', [
        "SELECT * FROM $tn",
        "WHERE",
          "repository_id = :repository_id AND",
          "prefix = :prefix AND",
          "identifier = :identifier"
      ]);

      \SRG::log('SQL: ' . $query);
      $s = \SRG::db()->prepare($query);
      $s->bindParam('repository_id', $repository_id);
      $s->bindParam('prefix', $prefix);
      $s->bindParam('identifier', $identifier);
      $s->execute();
      return $s->fetchObject(get_called_class());
    }

    public static function delete_by_repository_id($id) {
      return static::delete_by('repository_id', $id);
    }
  }
?>