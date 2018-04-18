<?php 
  namespace SRG;

  class Repository {
    public static function by_url($url) {
      $s = \SRG::db()->prepare('SELECT * FROM repositories WHERE url LIKE ?');
      $s->execute([$url]);

      if ($s->rowCount()) {
        $s->setFetchMode(\PDO::FETCH_CLASS, '\SRG\Repository');
        return $s->fetch();
      }
    }

    public static function create($values) {
      $columns = join(',', array_keys($values));
      $values = array_values($values);
      $qm = join(',', array_fill(0, sizeof($values), '?'));
      $s = \SRG::db()->prepare('INSERT INTO repositories ('.$columns.') VALUES ('.$qm.')');
      $s->execute($values);
    }

    public function update($values) {
      $columns = [];
      foreach($values as $k => $v) {
        $columns[]= $k.' = ?';
      }
      $columns = join(',', $columns);
      $values = array_values($values);
      $values[] = $this->url;
      $s = \SRG::db()->prepare('
        UPDATE repositories SET ('.$columns.') WHERE url LIKE ?
      ');
      $s->execute($values);
    }
  }
?>