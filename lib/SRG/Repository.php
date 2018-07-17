<?php 
  namespace SRG;

  class Repository extends Model {
    static $table_name = 'repositories';
    static $safe_columns = [
      'url', 'modified_at', 'approved', 'verified', 'errors', 'admin_email',
      'formats', 'verified_at', 'identify', 'list_metadata_formats',
      'imported_at', 'name', 'version', 'first_record_at', 'prefixes'
    ];

    public static function find_by_url($url, $options = []) {
      return static::find_by('url', $url, $options);
    }

    public static function friends() {
      $tn = static::$table_name;
      $query = "SELECT url FROM $tn WHERE approved AND verified";
      $s = \SRG::db()->prepare($query);
      $s->execute();

      $f = function($v) {
        return getenv('SRG_BASE_URL') . '/oai-pmh/' . \SRG\Util::reposify($v);
      };
      return array_map($f, $s->fetchAll(\PDO::FETCH_COLUMN, 0));
    }

    public function load_state($verb, $resumptionToken) {
      return \SRG\ResumptionToken::load_state($this->id, $verb, $resumptionToken);
    }

    public function save_state($verb, $state) {
      return \SRG\ResumptionToken::save_state($this->id, $verb, $state);
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

    public function can_disseminate($prefix) {
      $prefixes = preg_split('/,/', $this->prefixes);
      return in_array($prefix, $prefixes);
    }

    public function record_count() {
      $tn = Record::$table_name;
      $s = \SRG::db()->prepare("SELECT count(*) AS c FROM $tn WHERE repository_id = ?");
      $s->execute([$this->id]);
      return $s->fetch()['c'];
    }

    public function find_record($prefix, $identifier) {
      return \SRG\Record::find_by_repository_and_prefix_and_identifier(
        $this->id, $prefix, $identifier
      );
    }

    public function find_records($prefix, $page, $criteria = []) {
      return \SRG\Record::find_by_criteria($this->id, $prefix, $page, $criteria);
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