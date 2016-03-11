<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']."/breakfast");

//Loader passwordhash
require_once(ROOT."/headers/PasswordHash.php");
//require_once(ROOT."/headers/class.upload.php");
//require_once(ROOT."/headers/functions.php");
require_once(ROOT."/headers/Mobile_Detect.php");
 
// SCREEN SIZE / DEVICE TYPE
$detect = new Mobile_Detect;
$device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
$script_version = $detect->getScriptVersion();
 

// SET TIMEZONE
date_default_timezone_set('Europe/Copenhagen');


// Defines database specifications
define('DB_SERVER', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '123456');
define('DB_NAME', 'breakfast');

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	

	// Create missing tables
	$createProjectsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_projects` (
			  `project_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_name` varchar(100) NOT NULL,
			  `project_password` varchar(100) NOT NULL,
			  `project_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`project_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
	$createProjectsTable->execute();
	$createParticipantsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_participants` (
			  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `participant_name` varchar(100) NOT NULL,
			  `participant_email` text NOT NULL,
			  `participant_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `participant_lastTime` date NOT NULL,
			  PRIMARY KEY (`participant_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
	$createParticipantsTable->execute();
	$createProductsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_products` (
			  `product_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `product_name` varchar(100) NOT NULL,
			  `product_status` int(11) NOT NULL,
			  `product_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
	$createProductsTable->execute();
	$createProjectsSessionsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_projects_sessions` (
			  `session_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `session_hash` varchar(100) NOT NULL,
			  `session_date` timestamp NOT NULL,
			  PRIMARY KEY (`session_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1");
	$createProjectsSessionsTable->execute();
	
	
	
	// Handling login/session cookies
	if(!empty($_COOKIE['cookie_project_id'])){
		$cookie_project_id			= $_COOKIE['cookie_project_id'];
		$cookie_hash			= $_COOKIE['cookie_hash'];

		$session_db = $conn->prepare("SELECT COUNT(project_id) C FROM breakfast_projects_sessions WHERE project_id = :project_id AND session_hash = :hash AND session_date > CURRENT_TIMESTAMP LIMIT 1");
		$session_db->bindParam(':hash', $cookie_hash);		
		$session_db->bindParam(':project_id', $cookie_project_id);		
		$session_db->execute();
		$valid_session = $session_db->fetchColumn();
		if($valid_session > 0){
			$project_db = $conn->prepare("SELECT * FROM breakfast_projects WHERE project_id = :project_id LIMIT 1");
			$project_db->bindParam(':project_id', $cookie_project_id);		
			$project_db->execute();
			$project = $project_db->fetch();
			$project_name = str_replace ( "\"", "&quot;", $project['project_name']);
		}else{
			setcookie ("cookie_project_id", "", time() -36000000000, '/', 'localhost');
			setcookie ("cookie_hash", "", time() -36000000000, '/', 'localhost');
			$cookie_project_id	= "";
			$cookie_hash	= "";			
		}
	}
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>