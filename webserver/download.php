<?php

require_once('./FileManager.php');

$day = $_GET['day'];
$key = $_GET['key'];

if(empty($day) || empty($key) || !preg_match('/^[0-9]{8}$/', $day) || !preg_match('/^[0-9a-z\-\.]+$/', $key)) {
	throw new Exception("Invalid download request. day=$day, key=$key");
}
$manager = new FileManager($day, $key);
$metaDataPath = $manager->getMetaDataPath();
$resultFilePath = $manager->getResultFilePath();

if(!file_exists($resultFilePath)) {
	throw new Exception("Result file does not exist. day=$day, key=$key");
}
if(!is_readable($resultFilePath) || is_dir($resultFilePath)) {
	throw new Exception("Result file not readable. day=$day, key=$key");
}

//header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="result.pdf"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($resultFilePath));
readfile($resultFilePath);
