<?php
class Database {
    private static $instance = null;
    private $connection;
    private $host = '172.17.0.1';
    private $db = 'sisacad';
    private $user = 'iweb';
    private $pass = 'sjbdls';

    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->connection->connect_error) {
            die("Conexión fallida: " . $this->connection->connect_error);
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
?>
