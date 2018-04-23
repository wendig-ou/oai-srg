<?php 
  namespace SRG;

  class Record {
    static $safe_columns = [
      'identifier', 'modified_at', 'payload', 'repository_id'
    ];

    public static function find($identifier, $options = []) {
      Util::reverse_merge($options, ['strict' => TRUE]);

      $s = \SRG::db()->prepare('SELECT * FROM records WHERE identifier LIKE ?');
      $s->execute([$url]);

      if ($s->rowCount()) {
        $s->setFetchMode(\PDO::FETCH_CLASS, '\SRG\Record');
        return $s->fetch();
      } else {
        if ($options['strict']) {
          throw new \SRG\Exception("record with identifier '" . $identifier . "' couldn't be found");
        }
      }
    }

    public static function create($values) {
      $columns = [];
      $value_list = [];
      foreach (self::$safe_columns as $column) {
        if (array_key_exists($column, $values)) {
          $columns[] = $column;
          $value_list[] = $values[$column];
        }
      }
      $columns = join(',', $columns);
      $qm = join(',', array_fill(0, sizeof($value_list), '?'));
      $s = \SRG::db()->prepare('INSERT INTO records ('.$columns.') VALUES ('.$qm.')');
      $s->execute($value_list);
    }

    public static function delete_by_repository_id($id) {
      $s = \SRG::db()->prepare('DELETE FROM records WHERE repository_id = ?');
      $s->execute([$id]);
    }

  }
?>