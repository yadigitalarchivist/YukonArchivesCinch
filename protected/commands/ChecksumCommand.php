<?php
Yii::import('application.models.Utils');

/**
* ChecksumCommand class file
*
* This is the command for creation of checksums for a user's downloaded files.
* @category Checksum
* @package Checksum
* @author State Library of North Carolina - Digital Information Management Program <digital.info@ncdcr.gov>
* @author Dean Farrell
* @version 1.4
* @license Unlicense {@link http://unlicense.org/}
*/

/**
* This is the command for creation of checksums for a user's downloaded files.
* @author State Library of North Carolina - Digital Information Management Program <digital.info@ncdcr.gov>
* @author Dean Farrell
* @version 1.4
* @license Unlicense {@link http://unlicense.org/}
*/
class ChecksumCommand extends CConsoleCommand {
	/**
	* Implements the Checksum model class
	* @var $checksum
	*/
	public $checksum;
	
	/**
	* Instantiates Checksum class for use in checksum creation
	*/
	public function __construct() {
		$this->checksum = new Checksum;
	}
	
	/**
	* Create an MD5 or SHA1 checksum.  Default is SHA1
	* Escapes weird filename characters
	* @param $file
	* @param $type
	* @param $remote
	* @access protected
	* @return string
	*/
	public function createChecksum($file, $type = 'sha1', $remote = false) {
		if($remote == true || file_exists("$file")) {
			$checksum = ($type == 'sha1') ? sha1_file("$file") : md5_file("$file");	
		} else {
			return false;	
		}
		
		return $checksum;
	}
	
	/**
	* Create remote checksum to compare with downloaded version.  
	* Also acts as check to see if file exists.  
	* Supress file open warning on failure.
	* @param $file
	* @access public
	* @return string
	*/
	public function createRemoteChecksum($file) {
		$fh = @fopen($file, 'r');
		if($fh != false) {
			$remote_checksum = $this->createChecksum($file, 'sha1', true);
			@fclose($fh);
		} else {
			$remote_checksum = false;
		}
	
		return $remote_checksum;
	}
	
	/**
	* Checks to see if a file has a duplicate file name.
	* @param $file_path
	* @access protected
	* @return boolean
	*/
	protected function dupeFileName($file_path) {
		return preg_match('/_dupname_[0-9]{1,10}/', $file_path);
	}
	
	/**
	* Writes appropriate error to db
	* Error code 3 - Duplicate checksum found
	* Error code 17 - Duplicate filename found
	* @param $checksum_dup
	* @param $filename_dup
	* @param $file_id
	* @access protected
	*/
	protected function errorWrite($checksum_dup, $filename_dup, $file_id) {
		if($checksum_dup > 0 && $filename_dup == 0) {
			$error_id = array(3);
		} elseif($checksum_dup > 0 && $filename_dup > 0) {
			$error_id = array(3, 17);
		} else {
			$error_id = array(17);
		}
		
		Utils::setProblemFile($file_id);
		
		foreach($error_id as $error) {
			Utils::writeError($file_id, $error);
		}
	}
	
	/**
	* Determines if a file's local and remote checksums are the same.
	* Error code 5 - Checksum mismatch found
	* 11 is event code for checksum file integrity check
	* @param $file_id
	* @access protected
	*/
	protected function compareLocalRemote($file_id) {
		$remote = $this->checksum->getOneFileChecksum($file_id, true);
		$local = $this->checksum->getOneFileChecksum($file_id);
		
		if($remote != $local) {
			Utils::writeError($file_id, 5);
			Utils::setProblemFile($file_id);
		}
		Utils::writeEvent($file_id, 11);
	}
	
	/**
	* Calculates a checksum for each file and compares it to the file's initial checksum
	* Write error to DB if mismatch detected.
	* 2 is error code for "Could not create checksum"
	* 5 is error code for file checksum mismatch
	* 11 is event code for checksum file integrity check
	* @access public 
	*/
	public function actionCheck() {
		$file_list = $this->checksum->getFileChecksums();
		if(empty($file_list)) { echo "Nothing to check\n"; exit; }
			
		foreach($file_list as $file) {
			$current_checksum = $this->createChecksum($file['temp_file_path']);
			Utils::writeEvent($file['id'], 11);
				
			if($current_checksum == false) {
				Utils::writeError($file['id'], 2);
				echo 'comparison checksum could not be created for: ' . $file['id'] . "\r\n";
			} elseif($current_checksum != $file['checksum']) {
				Utils::writeError($file['id'], 5);
				Utils::setProblemFile($file['id']);
				echo 'checksum not ok for: ' . $file['id'] . "\r\n";
			} else {
				echo 'checksum ok for: ' . $file['id'] . "\r\n";
			}
		}
	}
	
	/**
	* Run checksum command 
	* Default is to create new checksum for downloaded files
	* Writes checksum error on failure. 
	* 2 is error code for "Could not create checksum"
	* Event type 5 is checksum created.
	*/
	public function actionCreate() { 
		$file_lists = $this->checksum->getFileList();
		
		if(count($file_lists) > 0) {
			foreach($file_lists as $file_list) { 
				$checksum = $this->createChecksum($file_list['temp_file_path']);
				Utils::writeEvent($file_list['id'], 5);
				
				if($checksum != false) {
					$is_dup_checksum = $this->checksum->getDupChecksum($checksum, $file_list['user_id']);
					$is_dup_filename = $this->dupeFileName($file_list['temp_file_path']);
					
					if($is_dup_checksum != 0 || $is_dup_filename != 0) {
						$this->errorWrite($is_dup_checksum, $is_dup_filename, $file_list['id']);
					}
					
					echo "checksum for:" . $file_list['id'] . " is " . $checksum . "\r\n";
				} else {
					$checksum = NULL;
					Utils::writeError($file_list['id'], 2);
					Utils::setProblemFile($file_list['id']);
					echo "Checksum not created. for: " . $file_list['id'] . "\r\n";
				}	
				
				$this->checksum->writeSuccess($checksum, $file_list['id']);
				$this->compareLocalRemote($file_list['id']);
			}
		}
	}
} 