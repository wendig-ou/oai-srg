<?php 
  namespace SRG;

  abstract class Model {
    static $safe_columns = [];
    static $table_name = 'unknown_table';
    static $primary_key = 'id';

    public static function find($id, $options = []) {
      return static::find_by(static::$primary_key, $id, $options);
    }

    public static function find_by($column, $value, $options = []) {
      Util::reverse_merge($options, ['strict' => TRUE]);

      $tn = static::$table_name;
      $s = \SRG::db()->prepare("SELECT * FROM $tn WHERE $column = ?");
      $s->execute([$value]);

      if ($s->rowCount()) {
        $s->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
        return $s->fetch();
      } else {
        if ($options['strict']) {
          throw new \SRG\Exception("{__CLASS__}: record with $column = '{$value}' couldn't be found");
        }
      }
    }

    public static function create($values) {
      $columns = [];
      $value_list = [];
      foreach (static::$safe_columns as $column) {
        if (array_key_exists($column, $values)) {
          $columns[] = $column;
          $value_list[] = $values[$column];
        }
      }
      $columns = join(',', $columns);
      $qm = join(',', array_fill(0, sizeof($value_list), '?'));
      $tn = static::$table_name;
      $s = \SRG::db()->prepare("INSERT INTO $tn ($columns) VALUES ($qm)");
      $s->execute($value_list);
    }

    public static function delete_by($column, $value) {
      $tn = static::$table_name;
      $s = \SRG::db()->prepare("DELETE FROM $tn WHERE $column = ?");
      $s->execute([$value]);
    }

    public function update($values) {
      $setters = [];
      $update_values = [];
      foreach (static::$safe_columns as $column) {
        if (array_key_exists($column, $values)) {
          $setters[] = $column . '=?';
          $update_values[] = $values[$column];
        }
      }

      $setters = join(',', $setters);
      $update_values[] = $this->url;
      $tn = static::$table_name;
      $s = \SRG::db()->prepare("UPDATE $tn SET $setters WHERE url LIKE ?");
      // $values[0] = 1;
      $s->execute($update_values);
    }

    public function delete() {
      $id = $this->${static::$primary_key};
      static::delete_by(static::$primary_key, $id);
    }
  }

?>