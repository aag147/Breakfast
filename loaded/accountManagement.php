<?php

// FIRST HEADER		
require('../headers/setup.php');


Header('Content-Type:text/html; charset=ISO-8859-1');
/*************** AJAX ***************/


try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	


	$type = isset($_POST['type']) ? $_POST['type'] : '';
	$errmsg = array(0);
	switch ($type){
		case 'login':			
			// Variables from form
			$name = utf8_decode(isset($_POST['name']) ? $_POST['name'] : '');
			$password = utf8_decode(isset($_POST['password']) ? $_POST['password'] : '');
			$checkbox = isset($_POST['checkbox']) ? $_POST['checkbox'] : '';

			$project_db = $conn->prepare("SELECT * FROM breakfast_projects WHERE project_name = :name LIMIT 1");
			$project_db->bindParam(':name', $name);		
			$project_db->execute();
			$valid_project = $project_db->rowCount();
			$project = $project_db->fetch();
			
			/*** ERROR CHECKING ***/
			if (empty($name) || empty($password)){$errmsg[0] = -1; goto loginError;}
			elseif($valid_project==0){$errmsg[0] = -2; goto loginError;}
			elseif(strlen($password) > 30){$errmsg[0] = -4; goto loginError;}
			
			// Getting database hash
			$stored_hash = $project['project_password'];
			$hasher = new PasswordHash(8, false);
			$check = $hasher->CheckPassword($password, $stored_hash);
			unset($hasher);
			
			// Error: No match between password and name
			if (!$check){$errmsg[0] = -3; goto loginError;}
						
			// Creating cookie hash
			$rand = rand(1, 1000);
			$project_id = $project['project_id'];
			$hasher = new PasswordHash(8, false);
			$project_hash = $hasher->HashPassword($project_id.$rand);
			unset($hasher);
			
			// Error: Weird hash
			if (strlen($project_hash) < 20) {$errmsg[0] = -5; goto loginError;}
		
			// Delete all outdated entries for all projects
			$delete = $conn->prepare("DELETE FROM breakfast_projects_sessions WHERE session_date < CURRENT_TIMESTAMP");
			$delete->execute();

			// Saves cookies
			if($checkbox==1){$time = 31536000;}
			else{$time = 43200;}
			setcookie("cookie_project_id",$project_id,time()+$time, '/', 'localhost');
			setcookie("cookie_hash",$project_hash,time()+$time, '/', 'localhost');					
			$insert = $conn->prepare("INSERT INTO breakfast_projects_sessions (session_hash, project_id, session_date) VALUES (:hash, :project_id, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $time SECOND))");
			$insert->bindParam(':hash', $project_hash);
			$insert->bindParam(':project_id', $project_id);
			$insert->execute();	

			$errmsg[0] = 1;
			
			loginError:
			echo json_encode($errmsg);
			exit;
			
		case 'register':
			// Variables from form
			$name = utf8_decode(isset($_POST['name']) ? $_POST['name'] : '');
			$password = utf8_decode(isset($_POST['password']) ? $_POST['password'] : '');

			$check_name_db = $conn->prepare("SELECT COUNT(project_id) as C FROM breakfast_projects WHERE project_name = :name LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name) OR empty($password)){$errmsg[0] = -1; goto registerError;}		
			// Double name
			if ($check_name > 0){$errmsg[0] = -3; goto registerError;}	
			// Too long password
			if (strlen($password) > 30){$errmsg[0] = -4; goto registerError;}
			
			// hasher
			$hasher = new PasswordHash(8, false);
			$hash = $hasher->HashPassword($_POST['password']);
			unset($hasher);
			
			// Error: Weird hash
			if (strlen($hash) < 20) {$errmsg[0] = -5; goto registerError;}
			
			/*** INSERT ***/
			$new_project = $conn->prepare("INSERT INTO breakfast_projects (project_name, project_password) VALUES (:name, :hash)");
			$new_project->bindParam(':name', $name);
			$new_project->bindParam(':hash', $hash);
			$new_project->execute();
			
			$project_id = $conn->lastInsertId('breakfast_projects');
			
			/*** LOG IN ***/
			//hash cookie
			$rand = rand(1, 1000);
			$hasher = new PasswordHash(8, false);
			$project_hash = $hasher->HashPassword($project_id.$rand);
			unset($hasher);
			if (strlen($project_hash) >= 20) {			
				// Set cookies
				$time = 43200;
				setcookie("cookie_project_id",$project_id,time()+$time, '/', 'localhost');
				setcookie("cookie_hash",$project_hash,time()+$time, '/', 'localhost');					
				$insert = $conn->prepare("INSERT INTO breakfast_projects_sessions (session_hash, project_id, session_date) VALUES (:hash, :project_id, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $time SECOND))");
				$insert->bindParam(':hash', $project_hash);
				$insert->bindParam(':project_id', $project_id);
				$insert->execute();
			}
			
			$errmsg[0] = 1;
			
			registerError:
			echo json_encode($errmsg);
			exit;
			
		case 'logout':
			echo json_encode("not implemented");
			exit;
			
		case 'delete':
			echo json_encode("not implemented");
			exit;
	}
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>