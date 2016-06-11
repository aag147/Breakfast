<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']."/breakfast");
require_once("db.php");

// SET TIMEZONE
date_default_timezone_set('Europe/Copenhagen');

// GET FILENAME
$filename = pathinfo(htmlentities($_SERVER['PHP_SELF']))['filename'];

// Danish translation
$weekdays_danish = array("Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag", "Søndag");


try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
		
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
			
			$options_db = $conn->prepare("SELECT * FROM breakfast_options WHERE project_id = :project_id LIMIT 1");
			$options_db->bindParam(':project_id', $cookie_project_id);		
			$options_db->execute();
			$options = $options_db->fetch();
		}else{
			setcookie ("cookie_project_id", "", -1, '/', 'localhost');
			setcookie ("cookie_hash", "", -1, '/', 'localhost');
			$cookie_project_id	= "";
			$cookie_hash	= "";			
		}
	}
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>