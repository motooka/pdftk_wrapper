<?php

require_once('./FileManager.php');

$manager = new FileManager();
$today = $manager->getDay();
$fileKey = $manager->getKey();
$metaDataPath = $manager->getMetaDataPath();
$sourceFilePath = $manager->getSourceFilePath();
//$statusFilePath = $manager->getStatusPath();
$dumpFilePath = $manager->getDumpFilePath();
$resultFilePath = $manager->getResultFilePath();

// collect information of uploaded file
$fileInfo = $_FILES['files'];
if(empty($fileInfo) || empty($fileInfo['tmp_name'])) {
	throw new Exception("Invalid Upload");
}
$fileInfoTmpName = $fileInfo['tmp_name'];
if(is_array($fileInfoTmpName) || !is_uploaded_file($fileInfoTmpName)) {
	
}
if(!isset($fileInfo['error']) || $fileInfo['error'] !== UPLOAD_ERR_OK) {
	throw new Exception("Upload Error");
}

// move to temporary dir
$sourceMD5 = md5_file($fileInfo['tmp_name']);
$sourceClientPath = $fileInfo['name'];
$metaDataArray = [
	'clientPath' => $sourceClientPath,
	'size' => $fileInfo['size'],
	'md5' => $sourceMD5,
];
$metaPutResult = file_put_contents($metaDataPath, json_encode($metaDataArray));
if($metaPutResult === false) {
	throw new Exception("Failed to write meta json : $metaDataPath");
}
$moveResult = move_uploaded_file($fileInfoTmpName, $sourceFilePath);
if($moveResult !== true) {
	throw new Exception("Failed to move uploaded file from $fileInfoTmpName to $sourceFilePath");
}

// dump
$command = "pdftk \"$sourceFilePath\" dump_data output \"$dumpFilePath\" 2>&1";
$commandResult = array();
exec($command, $commandResult);
if(!file_exists($dumpFilePath)) {
	$resultArray = [
		'errorMessage' => 'Not a PDF File',
	];
	echo json_encode($resultArray);
	exit;
}

// output
$command = "pdftk \"$sourceFilePath\" output \"$resultFilePath\" 2>&1";
exec($command, $commandResult);
if(!file_exists($resultFilePath) || !is_readable($resultFilePath) || is_dir($resultFilePath)) {
	throw new Exception("Failed to output a PDF. today=$today, key=$fileKey");
}
$resultMD5 = md5_file($resultFilePath);
$resultSize = filesize($resultFilePath);

// build download URL : relative
$downloadURL_relative = "./download.php?day=$today&key=$fileKey";

// build download URL : absolute
// These judges MAY not by correct especially if proxy servers are not under your control.
$x_forwarded_support = false;
$port = $_SERVER['SERVER_PORT'];
if($x_forwarded_support && isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
	$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
}
$proto = ($port == 443) ? 'https' : 'http';
if($x_forwarded_support && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
	$proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' ? 'https' : 'http';
}
$portStr = (($port==80&&$proto=='http') || ($port==443&&$proto=='https')) ? '' : ':'.$port;
$host = $_SERVER['SERVER_NAME'];
if(empty($host)) {
	$host = $_SERVER['SERVER_ADDR'];
}
if($x_forwarded_support && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
	$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
}
$path = $_SERVER['REQUEST_URI'];
$path = preg_replace('/upload\.php/', 'download.php', $path);
$downloadURL_absolute = "${proto}://${host}${portStr}${path}?day=${today}&key=${fileKey}";


$resultArray = [
	'day' => $today,
	'key' => $fileKey,
	'sourceClientPath' => $sourceClientPath,
	'sourceSize' => $fileInfo['size'],
	'sourceMD5' => $sourceMD5,
	'resultURL_relative' => $downloadURL_relative,
	'resultURL' => $downloadURL_absolute,
	'resultSize' => $resultSize,
	'resultMD5' => $resultMD5,
];
header('Content-Type: application/json');
echo json_encode($resultArray, (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

