<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']."/breakfast");

//Loader passwordhash
require_once(ROOT."/headers/PasswordHash.php");
 

// SET TIMEZONE
date_default_timezone_set('Europe/Copenhagen');

// GET FILENAME
$filename = pathinfo(htmlentities($_SERVER['PHP_SELF']))['filename'];

// Defines database specifications
define('DB_SERVER', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '123456');
define('DB_NAME', 'breakfast2');

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	

	// Create missing tables
	$createProjectsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_projects` (
			  `project_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_name` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `project_password` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `project_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `project_lastNotified` date NOT NULL,
			  `project_monday` int(11) NOT NULL,
			  `project_tuesday` int(11) NOT NULL,
			  `project_wednesday` int(11) NOT NULL,
			  `project_thursday` int(11) NOT NULL,
			  `project_friday` int(11) NOT NULL,
			  `project_saturday` int(11) NOT NULL,
			  `project_sunday` int(11) NOT NULL,
			  PRIMARY KEY (`project_id`),
			  UNIQUE KEY (`project_name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createProjectsTable->execute();
	$createBreakfastsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_breakfasts` (
			  `breakfast_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `breakfast_date` date NOT NULL,
			  `breakfast_weekday` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `breakfast_chef` int(11) NOT NULL,
			  `breakfast_done` int(11) NOT NULL,
			  `breakfast_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `breakfast_asleep` int(11) NOT NULL,
			  PRIMARY KEY (`breakfast_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createBreakfastsTable->execute();
	$createParticipantsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_participants` (
			  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `participant_name` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `participant_email` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `participant_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `participant_lastTime` date NOT NULL,
			  `participant_attendance_count` int(11) NOT NULL,
			  `participant_chef_count` int(11) NOT NULL,
			  `participant_asleep` int(11) NOT NULL,
			  PRIMARY KEY (`participant_id`),
			  UNIQUE KEY (`participant_name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createParticipantsTable->execute();
	$createProductsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_products` (
			  `product_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `product_name` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `product_status` int(11) NOT NULL,
			  `product_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`product_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createProductsTable->execute();
	$createProjectsSessionsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_projects_sessions` (
			  `session_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `session_hash` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `session_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`session_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createProjectsSessionsTable->execute();
	$createRegistrationsTable = $conn->prepare("
			CREATE TABLE IF NOT EXISTS `breakfast_registrations` (
			  `registration_id` int(11) NOT NULL AUTO_INCREMENT,
			  `project_id` int(11) NOT NULL,
			  `breakfast_id` int(11) NOT NULL,
			  `participant_id` int(11) NOT NULL,
			  `participant_attending` int(11) NOT NULL,
			  `registration_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`registration_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci");
	$createRegistrationsTable->execute();
	
	// Handling login/session cookies
	if(!empty($_COOKIE['cookie_project_id'])){
		$cookie_project_id		= $_COOKIE['cookie_project_id'];
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