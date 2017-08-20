<?php

class DatabaseHelper {
  private $database_file;
  private $db;

  public function __construct($path) {
    $this->database_file = $path;

    // FIXME: change the way constructor and initializations are called
    try {
      $this->db = new SQLite3($this->database_file, SQLITE3_OPEN_READWRITE);
      $this->db->enableExceptions(true);

    } catch (Exception $e) {
      $this->initializeDatabase();
    }
  }

  public function __destruct() {
    $this->db->close();
  }

  /**
  * Returns JSON representation of all records from the DB
  */
  public function getAllRecords() {
    $result_json = [];
    try {
      $meta_stmt = $this->db->prepare('SELECT timestamp, remote_addr FROM Meta WHERE entry_id = :entry_id');
      
      $readings_stmt = $this->db->prepare('SELECT * FROM Readings ORDER BY entry_id DESC');
      $readings_result = $readings_stmt->execute();
      
      while ($readings_row = $readings_result->fetchArray(SQLITE3_ASSOC)) {
        $meta_stmt->bindValue(':entry_id', $readings_row['entry_id']);
        $meta_result = $meta_stmt->execute();
        $meta_row = $meta_result->fetchArray(SQLITE3_ASSOC);

        unset($readings_row['entry_id']);
    
        $json = (object) [];
        $json->meta = $meta_row;
        $json->data = $readings_row;
        array_push($result_json, $json);
      
        $meta_result->finalize();
      }

    } catch (Exception $e) {
      error_log('LOG: ' . $e->getMessage());
    }

    return $result_json;
  }

  /**
  * Must be called upon clearing the database or moving script to new deploy
  */
  public function initializeDatabase() {
    $this->db = new SQLite3($this->database_file, SQLITE3_OPEN_READWRITE
      | SQLITE3_OPEN_CREATE);
    $this->db->enableExceptions(true);
    
    try {
      $this->db->exec('PRAGMA foreign_keys = ON');
      
      $this->db->exec('CREATE TABLE IF NOT EXISTS Readings (entry_id INTEGER, dist NUMERIC, dist2 NUMERIC, light NUMERIC, temp NUMERIC, hum NUMERIC, sound NUMERIC, PRIMARY KEY (entry_id ASC))');
      $this->db->exec('CREATE TABLE IF NOT EXISTS Meta (entry_id INTEGER, timestamp INTEGER, remote_addr TEXT, CONSTRAINT fk_readings FOREIGN KEY (entry_id) REFERENCES Readings(entry_id) ON DELETE CASCADE)');
    
    } catch (Exception $e) {
      error_log('LOG: ' . $e->getMessage());
    }
    
    $this->db = new SQLite3($this->database_file, SQLITE3_OPEN_READWRITE);
    $this->db->enableExceptions(true);
  }

  public function saveRecord($reading_json) {
    try {
      $readings_stmt = $this->db->prepare('INSERT INTO Readings (dist, dist2, light, temp, hum, sound) VALUES (:dist, :dist2, :light, :temp, :hum, :sound)');
      $data_json = $reading_json->data;
      $readings_stmt->bindValue(':dist', $data_json->dist);
      $readings_stmt->bindValue(':dist2', $data_json->dist2);
      $readings_stmt->bindValue(':light', $data_json->light);
      $readings_stmt->bindValue(':temp', $data_json->temp);
      $readings_stmt->bindValue(':hum', $data_json->hum);
      $readings_stmt->bindValue(':sound', $data_json->sound);
      
      $readings_result = $readings_stmt->execute();
      if (!$readings_result) {
        error_log('Problem with saving data to database');
        return false;
      }
      $readings_result->finalize();
    
      // used as a relation between tables
      $entry_id = $this->db->lastInsertRowId();
    
      $meta_stmt = $this->db->prepare('INSERT INTO Meta (entry_id, timestamp, remote_addr) VALUES (:entry_id, :timestamp, :remote_addr)');
      $meta_json = $reading_json->meta;
      $meta_stmt->bindValue(':entry_id', $entry_id);
      $meta_stmt->bindValue(':timestamp', $meta_json->timestamp);
      $meta_stmt->bindValue(':remote_addr', $meta_json->remote_addr, 
        SQLITE3_TEXT);
      
      $meta_result = $meta_stmt->execute();
      if (!$meta_result) {
        error_log('Problem with saving meta to database');
        return false;
      }
      $meta_result->finalize();
    
    } catch (Exception $e) {
      error_log('LOG: ' . $e->getMessage());
      return false;
    }

    return true;
  }
}

?>
