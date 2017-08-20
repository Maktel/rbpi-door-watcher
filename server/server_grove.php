<?php

require 'db_helper.php';

define('DB_FILE', 'grove_db.db');

$server = new Server(DB_FILE);

if (false) {
  $server->init();
  error_log('LOG: Database initialized');
  echo 'Database initialized';
  die();
}

$server->handle();

class Server {
  const ACCESS_TOKEN = 'U0tOSTMyNwo=';

  private $db_helper;

  public function __construct($database_file) {
    $this->db_helper = new DatabaseHelper($database_file);
  }

  public function init() {
    $this->db_helper->initializeDatabase();
  }

  public function handle() {
    error_log('LOG: Request method is ' . print_r($_SERVER['REQUEST_METHOD'], 
    true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->handlePost();
    } else {
      $this->handleGet();
    }
  }

  private function handlePost() {
    $post_raw = file_get_contents('php://input');
    error_log('LOG: Raw data: ' . $post_raw);
  
    $post_data = $post_raw;  // received data should be already JSON string
    $post_json = json_decode($post_data);
    if (json_last_error() != 0) {
      error_log('LOG: Request JSON parsing error code: ' . json_last_error());
      return false;
    }
  
    if (!$this->isAuthorized($post_json->access_token)) {
      error_log('LOG: No authorization');
      $this->setPlainHeader();
      echo 'Authorization error';
      return false;
    }
  
    // don't store credentials in database
    unset($post_json->access_token);
  
    $final_json = (object) [];
    $final_json->data = $post_json;
    $final_json->meta = (object) [
      'timestamp' => time(),
      'remote_addr' => $_SERVER['REMOTE_ADDR'],
    ];
  
    $save_successful = $this->db_helper->saveRecord($final_json);
  
    $this->setPlainHeader();
    echo 'Save ' . ($save_successful ? 'successful' : 'failed');
  }

  private function handleGet() {
    $result_json = $this->db_helper->getAllRecords();
    $this->setJsonHeader();
    echo json_encode($result_json);
  }
  
  private function setJsonHeader() {
    header('Content-type: application/json; charset=utf-8');
  }
  
  private function setPlainHeader() {
    header('Content-Type: text/html; charset=utf-8');
  }

  /**
  * Returns true if user is authorized
  */
  private function isAuthorized($access_token) {
    return ($access_token == self::ACCESS_TOKEN);
  }
}
?>
