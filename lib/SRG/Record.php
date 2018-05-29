<?php 
  namespace SRG;

  class Record extends Model {
    static $table_name = 'records';
    static $safe_columns = [
      'identifier', 'modified_at', 'payload', 'repository_id'
    ];

    public static function find_by_identifier($value, $options = []) {
      return static::find_by('identifier', $value, $options);
    }

    public static function delete_by_repository_id($id) {
      return static::delete_by('repository_id', $id);
    }
  }
?>