<?php 
  namespace SRG;

  class Repository {
    static $safe_columns = [
      'url', 'modified_at', 'approved', 'verified', 'errors', 'admin_email',
      'formats'
    ];

    public static function find($url, $options = []) {
      Util::reverse_merge($options, ['strict' => TRUE]);

      $s = \SRG::db()->prepare('SELECT * FROM repositories WHERE url LIKE ?');
      $s->execute([$url]);

      if ($s->rowCount()) {
        $s->setFetchMode(\PDO::FETCH_CLASS, '\SRG\Repository');
        return $s->fetch();
      } else {
        if ($options['strict']) {
          throw new \SRG\Exception("repository with url '" . $url . "' couldn't be found");
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
      $s = \SRG::db()->prepare('INSERT INTO repositories ('.$columns.') VALUES ('.$qm.')');
      $s->execute($value_list);
    }

    public function update($values) {
      $setters = [];
      $update_values = [];
      foreach (self::$safe_columns as $column) {
        if (array_key_exists($column, $values)) {
          $setters[] = $column . '=?';
          $update_values[] = $values[$column];
        }
      }

      $setters = join(',', $setters);
      $update_values[] = $this->url;

      echo 'UPDATE repositories SET '.$setters.' WHERE url LIKE ?';
      var_dump($update_values);
      $s = \SRG::db()->prepare('
        UPDATE repositories SET '.$setters.' WHERE url LIKE ?
      ');
      $values[0] = 1;
      $s->execute($update_values);
    }

    public function delete() {
      $s = \SRG::db()->prepare('DELETE FROM repositories WHERE url LIKE ?');
      $s->execute([$this->url]);
    }
  }
?>