<?php
// AJAX SECURITY CHECK
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if(!IS_AJAX) {die('Restricted access');}
$pos = strpos($_SERVER['HTTP_REFERER'],getenv('HTTP_HOST'));
if($pos===false)
  die('Restricted access');

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
			if ($check_name > 0){$errmsg[0] = -2; goto registerError;}	
			// Too long password
			if (strlen($password) > 30){$errmsg[0] = -3; goto registerError;}
			
			// hasher
			$hasher = new PasswordHash(8, false);
			$hash = $hasher->HashPassword($_POST['password']);
			unset($hasher);
			
			// Error: Weird hash
			if (strlen($hash) < 20) {$errmsg[0] = -5; goto registerError;}
			
			/*** INSERT ***/
			$new_project = $conn->prepare("INSERT INTO breakfast_projects (project_name, project_password, project_friday) VALUES (:name, :hash, '1')");
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
			setcookie ("cookie_project_id", "", time() -36000000000, '/', 'localhost');
			setcookie ("cookie_hash", "", time() -36000000000, '/', 'localhost');

			$logout = $conn->prepare("DELETE FROM breakfast_projects_sessions WHERE session_hash = :hash AND project_id = :project_id");
			$logout->bindParam(':hash', $cookie_hash);
			$logout->bindParam(':project_id', $cookie_project_id);
			$logout->execute();
			
			$errmsg[0] = 1;
			echo json_encode($errmsg);
			exit;
			
		case 'edit':
			// Variables from form
			$name = utf8_decode(isset($_POST['name']) ? $_POST['name'] : '');

			$check_name_db = $conn->prepare("SELECT COUNT(project_id) as C FROM breakfast_projects WHERE project_name = :name AND project_id <> :project_id LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->bindParam(':project_id', $cookie_project_id);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name)){$errmsg[0] = -1; goto editError;}		
			// Double name
			if ($check_name > 0){$errmsg[0] = -2; goto editError;}	
	
			/*** UPDATE ***/
			$edit_project = $conn->prepare("UPDATE breakfast_projects SET project_name = :name WHERE project_id = :project_id");
			$edit_project->bindParam(':name', $name);
			$edit_project->bindParam(':project_id', $cookie_project_id);		
			$edit_project->execute();
					
			$errmsg[0] = 1;
			editError:
			echo json_encode($errmsg);
			exit;
			
		case 'delete':
			/*** DELETE ***/
			$delete_project = $conn->prepare("DELETE FROM breakfast_projects WHERE project_id = :project_id");
			$delete_project->bindParam(':project_id', $cookie_project_id);
			$delete_project->execute();
			
			$delete_breakfasts = $conn->prepare("DELETE FROM breakfast_breakfasts WHERE project_id = :project_id");
			$delete_breakfasts->bindParam(':project_id', $cookie_project_id);
			$delete_breakfasts->execute();
			
			$delete_participants = $conn->prepare("DELETE FROM breakfast_participants WHERE project_id = :project_id");
			$delete_participants->bindParam(':project_id', $cookie_project_id);
			$delete_participants->execute();
			
			$delete_registrations = $conn->prepare("DELETE FROM breakfast_registrations WHERE project_id = :project_id");
			$delete_registrations->bindParam(':project_id', $cookie_project_id);
			$delete_registrations->execute();

			$delete_products = $conn->prepare("DELETE FROM breakfast_products WHERE project_id = :project_id");
			$delete_products->bindParam(':project_id', $cookie_project_id);
			$delete_products->execute();
			
			$delete_sessions = $conn->prepare("DELETE FROM breakfast_projects_sessions WHERE project_id = :project_id");
			$delete_sessions->bindParam(':project_id', $cookie_project_id);
			$delete_sessions->execute();
			
			setcookie ("cookie_project_id", "", time() -36000000000, '/', 'localhost');
			setcookie ("cookie_hash", "", time() -36000000000, '/', 'localhost');
			
			$errmsg[0] = 1;
			echo json_encode($errmsg);
			exit;
			
		case 'weekdays':
			$weekdays = !empty($_POST['weekdays']) ? $_POST['weekdays'] : array();
						
			$update_weekdays = $conn->prepare("	UPDATE breakfast_projects SET project_monday = :monday, project_tuesday = :tuesday, project_wednesday = :wednesday,
										project_thursday = :thursday, project_friday = :friday, project_saturday = :saturday, project_sunday = :sunday
										WHERE project_id = :project_id");
	
			// Parameters for update
			$params = array('project_id' => $cookie_project_id);
			for($i = 0; $i < 7; $i++){
				$weekday = strtolower(jddayofweek($i, 1));
				if(in_array($weekday, $weekdays)){$value=1;}else{$value=0;}
				$params[$weekday] = $value;
				
				if($value==0){
					$kill_breakfasts = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_dead = '1' WHERE project_id = :project_id AND breakfast_weekday = :weekday AND breakfast_date >= DATE(NOW())");
					$kill_breakfasts->bindParam(':project_id', $cookie_project_id);
					$kill_breakfasts->bindParam(':weekday', $weekday);
					$kill_breakfasts->execute();
				}
			}
			
			$update_weekdays->execute($params);
			
			$errmsg[0] = 1;
			echo json_encode($errmsg);
			exit;
			
		case 'changeStatus':
			// Variables from form
			$weekday = utf8_decode(isset($_POST['account_id']) ? $_POST['account_id'] : '');
			$value = utf8_decode(isset($_POST['value']) ? $_POST['value'] : '');
			if($value=="true"){$value=1;}else{$value=0;}
			
			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($weekday)){$errmsg[0] = -1; goto changeStatusError;}		
	
			/*** UPDATE ***/
			$change_status = $conn->prepare("UPDATE breakfast_projects SET project_".$weekday." = :status WHERE project_id = :project_id");
			$change_status->bindParam(':status', $value);
			$change_status->bindParam(':project_id', $cookie_project_id);		
			$change_status->execute();
					
			$errmsg[0] = 1;
			changeStatusError:
			echo json_encode($weekday);
			exit;
	}
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>