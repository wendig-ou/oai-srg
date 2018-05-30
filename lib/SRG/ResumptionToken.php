<?php 
  namespace SRG;

  class ResumptionToken extends Model {
    static $table_name = 'resumption_tokens';
    static $safe_columns = ['identifier', 'state'];

    public static function before_save($values) {
      if ($values['state']) {
        $values['state'] = json_encode($values['state']);
      }
      return $values;
    }

    public static function cleanup() {
      $ts = (new \DateTime())->modify('-24 hours');
      
      $s = \SRG::db()->prepare("INSERT INTO $tn ($columns) VALUES ($qm)");
      $s->execute($value_list);
    }

    public function state() {
      return json_decode($this->state);
    }
  }
?>