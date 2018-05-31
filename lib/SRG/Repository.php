<?php 
  namespace SRG;

  class Repository extends Model {
    static $table_name = 'repositories';
    static $safe_columns = [
      'url', 'modified_at', 'approved', 'verified', 'errors', 'admin_email',
      'formats', 'verified_at', 'identify', 'list_metadata_formats'
    ];

    public static function find_by_url($url, $options = []) {
      return static::find_by('url', $url, $options);
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