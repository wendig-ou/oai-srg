<?php 
  require_once 'vendor/autoload.php';

  define('SRG_ROOT', realpath(__DIR__ . '/..'));

  $dotenv = new Dotenv\Dotenv(SRG_ROOT);
  $dotenv->load();

  libxml_use_internal_errors(TRUE);
  libxml_disable_entity_loader(FALSE);

  // use Illuminate\Database\Capsule\Manager as Capsule;
  // $capsule = new Capsule();
  // $capsule->addConnection([
  //   'driver' => getenv('SRG_DB_DRIVER'),
  //   'host' => getenv('SRG_DB_HOST'),
  //   'database' => getenv('SRG_DB_DATABASE'),
  //   'username' => getenv('SRG_DB_USERNAME'),
  //   'password' => getenv('SRG_DB_PASSWORD'),
  //   'charset' => getenv('SRG_DB_CHARSET')
  // ]);
  // $capsule->setAsGlobal();
  // $capsule->bootEloquent();

  require 'SRG/Exception.php';
  require 'SRG/Gateway.php';
  require 'SRG/Record.php';
  require 'SRG/Repository.php';
  require 'SRG/Util.php';
  require 'SRG/Validator.php';

  class SRG {
    static $db = NULL;

    public static function baseUrl() {
      return getenv('SRG_BASE_URL');
    }

    public static function log($message) {
      if (getenv('SRG_DEBUG')) {
        error_log('SRG: ' . $message . "\n");
      }
    }

    public static function db() {
      if (!self::$db) {
        $dsn = (
          getenv('SRG_DB_DRIVER') . ':' .
          'host=' . getenv('SRG_DB_HOST') . ';' .
          'port=' . getenv('SRG_DB_PORT') . ';' .
          'charset=' . getenv('SRG_DB_CHARSET') . ';' .
          'charset=' . getenv('SRG_DB_CHARSET') . ';'
        );
        self::$db = new PDO($dsn, getenv('SRG_DB_USERNAME'), getenv('SRG_DB_PASSWORD'));
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::ensureDb();
      }

      return self::$db;
    }

    public static function setup() {
      $name = getenv('SRG_DB_DBNAME');
      self::db()->query('DROP DATABASE IF EXISTS ' . $name);
      self::ensureDb();
      self::ensureMigrations();
    }

    public static function update() {
      self::ensureDb();
      self::ensureMigrations();
    }

    public static function ensureDb() {
      $name = getenv('SRG_DB_DBNAME');
      $s = self::db()->query('SHOW DATABASES');
      $names = $s->fetchAll(PDO::FETCH_COLUMN);
      if (!in_array($name, $names)) {
        self::db()->query('CREATE DATABASE ' . $name);
      }

      self::db()->query('USE ' . $name);
    }

    public static function ensureMigrations() {
      $s = self::db()->query('SHOW TABLES');
      $names = $s->fetchAll(PDO::FETCH_COLUMN);
      if (!in_array('migrations', $names)) {
        self::db()->query('
          CREATE TABLE migrations (
            sequence varchar(4),
            name varchar(255)
          )
        ');
      }

      foreach (glob(SRG_ROOT . '/migrations/*.php') as $file) {
        $matches = [];
        preg_match('/\/(\d+)_([^\/]+)\.php$/', $file, $matches);
        $sequence = $matches[1];
        $name = $matches[2];

        $s = self::db()->query('SELECT sequence FROM migrations');
        $migrated = $s->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array($sequence, $migrated)) {
          echo "migrating " . $sequence . "\n";
          try {
            require $file;
            $s = self::db()->prepare('INSERT INTO migrations VALUES (?, ?)');
            $s->execute([$sequence, $name]);
          } catch (Exception $e) {
            echo $e;
          }
        }
      }
    }
  }

?>