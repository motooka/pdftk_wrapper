<?php

class FileManager {
	protected $_day = null;
	protected $_key = null;
	protected $_filePrefix = null;
	function __construct($day = null, $key = null) {
		$tmpDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tmp';
		if(!is_writable($tmpDir)) {
			throw new Exception("temporary directory $tmpDir is not writable.");
		}
		if(!is_dir($tmpDir)) {
			throw new Exception("temporary directory $tmpDir is not a directory.");
		}
		
		
		if(!empty($day) && !empty($key)) {
			// file read mode
			
			if(!preg_match('/^[0-9]+$/', $day)) {
				throw new Exception("Invalid date : $day");
			}
			if(!preg_match('/^[0-9a-f\-]+$/', $key)) {
				throw new Exception("Invalid key : $key");
			}
			
			$this->_day = $day;
			$this->_key = $key;
			$dayDir = $tmpDir . DIRECTORY_SEPARATOR . $day;
			$this->_filePrefix = $dayDir . DIRECTORY_SEPARATOR . $key;
			
			$metaDataPath = $this->getMetaDataPath();
			if(!file_exists($metaDataPath) || !is_readable($metaDataPath) || is_dir($metaDataPath)) {
				throw new Exception("the meta data file is not found or readable : $metaDataPath");
			}
		}
		else {
			// file create mode
			
			$day = date('Ymd');
			$this->_day = $day;
			$dayDir = $tmpDir . DIRECTORY_SEPARATOR . $day;
			// create dayDir
			if(!file_exists($dayDir)) {
				$mkdirResult = mkdir($dayDir);
				if($mkdirResult === false) {
					throw new Exception("Failed to mkdir $dayDir");
				}
			}
			if(!is_writable($dayDir)) {
				throw new Exception("day directory $dayDir is not writable.");
			}
			if(!is_dir($dayDir)) {
				throw new Exception("day directory $dayDir is not a directory.");
			}
			
			// generate key
			$datePrefix = date('Ymd-His-');
			$maxAttempt = 10;
			$isNotDoneYet = true;
			for($i=0; $i<$maxAttempt; $i++) {
				$key = $datePrefix . bin2hex(openssl_random_pseudo_bytes(16));
				$this->_key = $key;
				$this->_filePrefix = $dayDir . DIRECTORY_SEPARATOR . $key;
				$lockFilePath = $this->_filePrefix . '.lock';
				if(file_exists($lockFilePath)) {
					continue;
				}
				$fh = fopen($lockFilePath, 'w');
				if($fh === false) {
					throw new Exception("failed to open the lock file $lockFilePath");
				}
				if(flock($fh, LOCK_EX | LOCK_NB)) {
					// lock succeeded
					
					// try to create meta data file 
					$metaDataPath = $this->getMetaDataPath();
					if(file_exists($metaDataPath)) {
						flock($fh, LOCK_UN);
						fclose($fh);
						continue;
					}
					$fh2 = fopen($metaDataPath, 'w');
					if($fh2 === false) {
						flock($fh, LOCK_UN);
						fclose($fh);
						throw new Exception("failed to create meta data file : $metaDataPath");
					}
					fclose($fh2);
					flock($fh, LOCK_UN);
					fclose($fh);
					unlink($lockFilePath);
					$isNotDoneYet = false;
					break;
				}
				else {
					// lock failed : collision
					fclose($fh);
				}
			}
			
			if($isNotDoneYet) {
				throw new Exception("failed to generate a unique key in $maxAttempt attempts.");
			}
		}
	}
	
	public function getDay() {
		return $this->_day;
	}
	public function getKey() {
		return $this->_key;
	}
	public function getMetaDataPath() {
		return $this->_filePrefix . '_meta.json';
	}
	public function getSourceFilePath() {
		return $this->_filePrefix . '_source.pdf';
	}
	//public function getStatusPath() {
	//	return $this->_filePrefix . '_status.json';
	//}
	public function getDumpFilePath() {
		return $this->_filePrefix . '_dump.txt';
	}
	public function getResultFilePath() {
		return $this->_filePrefix . '_result.pdf';
	}
}

