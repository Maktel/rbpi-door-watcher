<?php

require 'db_helper.php';

$db_helper = new DatabaseHelper('grove_db.db');

$file_content = file_get_contents('grove_api.json');
$old_json = json_decode($file_content);

foreach ($old_json as $row) {
  $db_helper->saveRecord($row);
  // echo json_encode($row);
}

echo json_encode($db_helper->getAllRecords());

?>
