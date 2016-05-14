<?php
// AJAX SECURITY CHECK
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if(!IS_AJAX) {die('Restricted access');}
$pos = strpos($_SERVER['HTTP_REFERER'],getenv('HTTP_HOST'));
if($pos===false)
  die('Restricted access');

// FIRST HEADER		
require('../headers/setup.php');

Header('Content-Type:text/html; charset=utf-8');
/*************** AJAX ***************/


try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	


	$type = isset($_POST['type']) ? $_POST['type'] : '';
	$errmsg = array(0);
	switch ($type){
		case 'login':			
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$password = filter_var(isset($_POST['password']) ? $_POST['password'] : '', FILTER_SANITIZE_STRING);
			$checkbox = isset($_POST['checkbox']) ? $_POST['checkbox'] : '';

			$project_db = $conn->prepare("SELECT * FROM breakfast_projects WHERE project_name = :name LIMIT 1");
			$project_db->bindParam(':name', $name);		
			$project_db->execute();
			$valid_project = $project_db->rowCount();
			$project = $project_db->fetch();
			
			/*** ERROR CHECKING ***/
			if (empty($name) || empty($password)){$errmsg[0] = -1; break;}
			elseif($valid_project==0){$errmsg[0] = -3; break;}
			
			// Error: No match between password and stored hash
			if (!password_verify($password, $project['project_password'])){$errmsg[0] = -4; break;}
						
			// Creating cookie hash
			$rand = rand(1, 1000);
			$project_id = $project['project_id'];
			$cookie_hash = password_hash($project_id.$rand, PASSWORD_BCRYPT);
			
			// Error: Failed hash
			if (!$cookie_hash) {$errmsg[0] = -5; break;}
		
			// Delete all outdated entries for all projects
			$delete = $conn->prepare("DELETE FROM breakfast_projects_sessions WHERE session_date < CURRENT_TIMESTAMP");
			$delete->execute();

			// Saves cookies
			if($checkbox==1){$time = 31536000;}
			else{$time = 43200;}
			setcookie("cookie_project_id",$project_id,time()+$time, '/', 'localhost');
			setcookie("cookie_hash",$cookie_hash,time()+$time, '/', 'localhost');					
			$insert = $conn->prepare("INSERT INTO breakfast_projects_sessions (session_hash, project_id, session_date) VALUES (:hash, :project_id, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $time SECOND))");
			$insert->bindParam(':hash', $cookie_hash);
			$insert->bindParam(':project_id', $project_id);
			$insert->execute();	

			$errmsg[0] = 1;
			$errmsg[1] = "Du er blevet logget ind!";
			break;
			
		case 'register':
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$password = filter_var(isset($_POST['password']) ? $_POST['password'] : '', FILTER_SANITIZE_STRING);

			$check_name_db = $conn->prepare("SELECT COUNT(project_id) as C FROM breakfast_projects WHERE project_name = :name LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name) OR empty($password)){$errmsg[0] = -1; break;}		
			// Double name
			if ($check_name > 0){$errmsg[0] = -2; break;}	
			// Long name
			if (strlen($name) > 75){$errmsg[0] = -7; break;}	
			
			// hasher
			$hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
			
			// Error: Weird hash
			if (!$hash) {$errmsg[0] = -5; break;}
			
			/*** INSERT ***/
			$new_project = $conn->prepare("INSERT INTO breakfast_projects (project_name, project_password) VALUES (:name, :hash)");
			$new_project->bindParam(':name', $name);
			$new_project->bindParam(':hash', $hash);
			$new_project->execute();
			
			$project_id = $conn->lastInsertId('breakfast_projects');
			
			$new_options = $conn->prepare("INSERT INTO breakfast_options (project_id, friday_checked) VALUES (:project_id, '1')");
			$new_options->bindParam(':project_id', $project_id);
			$new_options->execute();
			
			/*** LOG IN ***/
			//hash cookie
			$rand = rand(1, 1000);
			$project_hash = password_hash($project_id.$rand, PASSWORD_BCRYPT);
			
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
			$errmsg[1] = "Du er blevet registreret!";
			break;
			
		case 'logout':
			setcookie ("cookie_project_id", "", -1, '/', 'localhost');
			setcookie ("cookie_hash", "", -1, '/', 'localhost');

			$logout = $conn->prepare("DELETE FROM breakfast_projects_sessions WHERE session_hash = :hash AND project_id = :project_id");
			$logout->bindParam(':hash', $cookie_hash);
			$logout->bindParam(':project_id', $cookie_project_id);
			$logout->execute();
			
			$errmsg[0] = 1;
			$errmsg[1] = "Du er blevet logget ud!";
			break;
			
		case 'edit':
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);

			$check_name_db = $conn->prepare("SELECT COUNT(project_id) as C FROM breakfast_projects WHERE project_name = :name AND project_id <> :project_id LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->bindParam(':project_id', $cookie_project_id);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name)){$errmsg[0] = -1; break;}	
			// Double name
			if ($check_name > 0){$errmsg[0] = -2; break;}	
			// Long name
			if (strlen($name) > 75){$errmsg[0] = -7; break;}
	
			/*** UPDATE ***/
			$edit_project = $conn->prepare("UPDATE breakfast_projects SET project_name = :name WHERE project_id = :project_id");
			$edit_project->bindParam(':name', $name);
			$edit_project->bindParam(':project_id', $cookie_project_id);		
			$edit_project->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Navnet er ændret!";
			break;
			
		case 'delete':
			/*** DELETE ***/
			$delete_project = $conn->prepare("DELETE FROM breakfast_projects WHERE project_id = :project_id");
			$delete_project->bindParam(':project_id', $cookie_project_id);
			$delete_project->execute();
			
			$delete_options = $conn->prepare("DELETE FROM breakfast_options WHERE project_id = :project_id");
			$delete_options->bindParam(':project_id', $cookie_project_id);
			$delete_options->execute();
			
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
			
			setcookie ("cookie_project_id", "", -1, '/', 'localhost');
			setcookie ("cookie_hash", "", -1, '/', 'localhost');
			
			$errmsg[0] = 1;
			$errmsg[1] = "Projektet er slettet!";
			break;
			
		case 'weekdays':
			$weekdays = !empty($_POST['weekdays']) ? $_POST['weekdays'] : array();
						
			$update_weekdays = $conn->prepare("	UPDATE breakfast_options SET 
												monday_checked = :monday_checked, monday_chefs = :monday_chefs,
												tuesday_checked = :tuesday_checked, tuesday_chefs = :tuesday_chefs,
												wednesday_checked = :wednesday_checked, wednesday_chefs = :wednesday_chefs,
												thursday_checked = :thursday_checked, thursday_chefs = :thursday_chefs,
												friday_checked = :friday_checked, friday_chefs = :friday_chefs,
												saturday_checked = :saturday_checked, saturday_chefs = :saturday_chefs,
												sunday_checked = :sunday_checked, sunday_chefs = :sunday_chefs												
												WHERE project_id = :project_id");
	
			// Parameters for update
			$params = array('project_id' => $cookie_project_id);
			for($i = 0; $i < 7; $i++){
				$weekday = strtolower(jddayofweek($i, 1));
				$chefs = !empty($_POST['chefs_'.$i]) ? $_POST['chefs_'.$i] : 0;
				if(in_array($weekday, $weekdays)){$checked=1;}
				else{$checked=0; $chefs = 0;}
				
				$params[$weekday.'_checked'] = $checked;
				$params[$weekday.'_chefs'] = $chefs;
				
				if($checked==0){
					$kill_breakfasts = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_asleep = '1' WHERE project_id = :project_id AND breakfast_weekday = :weekday AND breakfast_date >= CURDATE()");
					$kill_breakfasts->bindParam(':project_id', $cookie_project_id);
					$kill_breakfasts->bindParam(':weekday', $weekday);
					$kill_breakfasts->execute();
				}
			}
			
			$update_weekdays->execute($params);		
			
			$errmsg[0] = 1;
			$errmsg[1] = "Arrangement dagene er ændret!";
			break;
			
			
		case 'forgotten':
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$security_code = filter_var(isset($_POST['security_code']) ? $_POST['security_code'] : '', FILTER_SANITIZE_STRING);
			$password = filter_var(isset($_POST['password']) ? $_POST['password'] : '', FILTER_SANITIZE_STRING);
			$security_code_hash = $_COOKIE['security_code'];
			
			$project_db = $conn->prepare("SELECT * FROM breakfast_projects WHERE project_name = :project_name");
			$project_db->bindParam(':project_name', $name);		
			$project_db->execute();	
			$valid_project = $project_db->rowCount();
			$project = $project_db->fetch();
			
			/*** ERROR CHECKING ***/
			if (empty($name) || empty($password) || empty($security_code)){$errmsg[0] = -1; break;}
			elseif($valid_project==0){$errmsg[0] = -6; break;}
			
			$project_id = $project['project_id'];
			
			// Error: No match between password and stored hash
			if (!password_verify($project_id.$security_code, $security_code_hash)){$errmsg[0] = -6; break;}
		
			// Hasher
			$hash = password_hash($password, PASSWORD_BCRYPT);
			
			// Error: Weird hash
			if (!$hash) {$errmsg[0] = -5; break;}
		
			/*** UPDATE ***/
			$edit_project = $conn->prepare("UPDATE breakfast_projects SET project_password = :hash WHERE project_id = :project_id");
			$edit_project->bindParam(':hash', $hash);
			$edit_project->bindParam(':project_id', $project_id);		
			$edit_project->execute();
		
			/*** LOG IN ***/
			//hash cookie
			$rand = rand(1, 1000);
			$project_hash = password_hash($project_id.$rand, PASSWORD_BCRYPT);
			
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
			$errmsg[1] = "Kodeordet er blevet ændret!";
			break;
			
		default:
			echo json_encode(array(-10));
			exit;
	}
	
	
	// Actual error message
	if($errmsg[0] != 1){$errmsg[1] = "<p class='error'>";}
	switch ($errmsg[0]){
		case '-1':
			$errmsg[1] .= "Alle felter skal udfyldes.";
			break;
		case '-2':
			$errmsg[1] .= "Navnet er optaget.";
			break;
		case '-3':
		case '-4':
			$errmsg[1] .= "Et projekt med det angivede navn og kodeord kunne ikke findes.<br>".
						  "<a href='javascript:;' data-id='register' class='adminShiftLinkDynamic blue'>Ønsker du i stedet at oprette projektet?</a>";
			break;
		case '-5':
			$errmsg[1] .= "Der opstod en intern fejl. Prøv igen.";
			break;
		case '-6':
			$errmsg[1] .= "Projektnavn og sikkerhedskode passer ikke sammen.<br>".
						  "<a href='javascript:;' class='sendForgottenEmailAgain blue'>Klik her for at få tilsendt en ny email.</a><br>".
						  "<a href='javascript:;' data-id='forgotten' class='adminShiftLinkDynamic blue'>Klik her for at prøve med en anden email.</a>";
			break;
		case '-7':
			$errmsg[1] .= "Projektnavnet for langt. Systemet accepterer desværre ikke mere end 75 tegn.";
			break;
		default:
			$errmsg[1] = "<p class='success'>".$errmsg[1];
			break;
	}
	$errmsg[1] .= "</p>";
	
	echo json_encode($errmsg);
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>