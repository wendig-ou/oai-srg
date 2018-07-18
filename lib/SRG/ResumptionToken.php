<?php 
  namespace SRG;

  class ResumptionToken extends Model {
    static $table_name = 'resumption_tokens';
    static $safe_columns = [
      'identifier', 'state', 'created_at', 'repository_id', 'verb'
    ];

    public static function save_state($repository_id, $verb, $state) {
      $identifier = static::generate_identifier();
      static::create([
        'repository_id' => $repository_id,
        'verb' => $verb,
        'identifier' => $identifier,
        'created_at' => \SRG\Util::to_db_date('now'),
        'state' => json_encode($state)
      ]);
      return $identifier;
    }

    public static function load_state($repository_id, $verb, $identifier) {
      $tn = static::$table_name;
      $s = \SRG::db()->prepare("SELECT * FROM $tn WHERE repository_id = ? AND verb = ? AND identifier = ?");
      $s->execute([$repository_id, $verb, $identifier]);

      if ($s->rowCount()) {
        $s->setFetchMode(\PDO::FETCH_CLASS, get_called_class());
        $rt = $s->fetch();
        return [
          'state' => json_decode($rt->state, TRUE),
          'created_at' => $rt->created_at
        ];
      }
    }

    public static function generate_identifier() {
      $bytes = openssl_random_pseudo_bytes(32);
      return bin2hex($bytes);
    }

    public static function cleanup() {
      $tn = static::$table_name;
      $ts = \SRG\Util::to_db_date((new \DateTime())->modify('-2 hours'));
      $s = \SRG::db()->prepare("DELETE FROM $tn WHERE created_at < ? OR created_at IS NULL");
      $s->execute([$ts]);
    }
  }
?>