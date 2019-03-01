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

    public static function find_by_criteria($repository_id, $prefix, $page, $criteria = []) {
      $where = ['repository_id = ?', 'prefix = ?'];
      $params = [$repository_id, $prefix];

      if ($criteria['from']) {
        $where[] = 'modified_at >= ?';
        $params[] = \SRG\Util::to_filter_date($criteria['from']);
      }

      if ($criteria['until']) {
        $where[] = 'modified_at <= ?';
        $params[] = \SRG\Util::to_filter_date($criteria['until']);
      }

      $tn = static::$table_name;
      $per_page = intval(getenv('SRG_PER_PAGE'));
      $offset = ($page - 1) * $per_page;
      $query = "SELECT * FROM $tn WHERE " . join(' AND ', $where) . " ORDER BY id LIMIT $per_page OFFSET $offset";
      $count_query = "SELECT count(*) FROM $tn WHERE " . join(' AND ', $where);

      \SRG::log('SQL params: ' . print_r($params, TRUE));
      \SRG::log('SQL: ' . $query);
      $s = \SRG::db()->prepare($query);
      $s->execute($params);
      $s->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
      $records = $s->fetchAll();

      \SRG::log('SQL: ' . $count_query);
      $s = \SRG::db()->prepare($count_query);
      $s->execute($params);
      $total = $s->fetch(\PDO::FETCH_COLUMN);

      return ['records' => $records, 'total' => $total];
    }

    public static function delete_by_repository_id($id) {
      return static::delete_by('repository_id', $id);
    }

    public static function find_first_by_repository_id($id) {
      $tn = static::$table_name;
      $query = "SELECT * FROM $tn WHERE repository_id = ? LIMIT 1";
      $params = [$id];
      \SRG::log('SQL params: ' . print_r($params, TRUE));
      \SRG::log('SQL: ' . $query);
      $s = \SRG::db()->prepare($query);
      $s->execute($params);
      $s->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
      $records = $s->fetchAll();
      
      return $records[0];
    }
  }
?>