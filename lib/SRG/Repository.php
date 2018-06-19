<?php 
  namespace SRG;

  class Repository extends Model {
    static $table_name = 'repositories';
    static $safe_columns = [
      'url', 'modified_at', 'approved', 'verified', 'errors', 'admin_email',
      'formats', 'verified_at', 'identify', 'list_metadata_formats',
      'imported_at'
    ];

    public static function find_by_url($url, $options = []) {
      return static::find_by('url', $url, $options);
    }

    public function error_list() {
      if ($this->errors) {
        return explode('|', $this->errors);
      } else {
        return [];
      }
    }

    public function warning_list() {
      if ($this->warnings) {
        return explode('|', $this->warnings);
      } else {
        return [];
      }
    }

    public function ready() {
      return $this->approved && $this->verified;
    }

    public function never_imported() {
      return !$this->imported_at;
    }

    public function record_count() {
      $tn = Record::$table_name;
      $s = \SRG::db()->prepare("SELECT count(*) AS c FROM $tn WHERE repository_id = ?");
      $s->execute([$this->id]);
      return $s->fetch()['c'];
    }

    public function delete_records() {
      \SRG\Record::delete_by_repository_id($this->id);
    }

    public function create_record($values) {
      $values['repository_id'] = $this->id;
      \SRG\Record::create($values);
    }

    public function delete() {
      $this->delete_records();
      parent::delete();
    }

  }
?>