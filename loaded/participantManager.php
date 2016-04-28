<?php
// AJAX SECURITY CHECK
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if(!IS_AJAX) {die('Restricted access');}
$pos = strpos($_SERVER['HTTP_REFERER'],getenv('HTTP_HOST'));
if($pos===false)
  die('Restricted access');

// FIRST HEADER		
require('../headers/setup.php');

// LOGGED IN CHECK
if(empty($cookie_project_id)){exit;}

Header('Content-Type:text/html; charset=utf-8');
/*************** AJAX ***************/


try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	


	$type = isset($_POST['type']) ? $_POST['type'] : '';
	$errmsg = array(0);
	switch ($type){
		case 'new':			
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$email = filter_var(isset($_POST['email']) ? $_POST['email'] : '', FILTER_SANITIZE_STRING);

			$participant_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE participant_email = :email AND project_id = :project_id LIMIT 1");
			$participant_db->bindParam(':email', $email);		
			$participant_db->bindParam(':project_id', $cookie_project_id);		
			$participant_db->execute();
			$participant_count = $participant_db->rowCount();
			$participant_asleep = 0;
			if ($participant_count > 0){
				$participant = $participant_db->fetch();
				$participant_id = $participant['participant_id'];
				$participant_asleep = $participant['participant_asleep'];
			}
			
			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name) OR empty($email)){$errmsg[0] = -1; break;}		
			// Double email
			if ($participant_count > 0 AND $participant_asleep==0){$errmsg[0] = -2; break;}	

			if($participant_asleep==1){
				/*** Wake up ***/
				$wake_participant = $conn->prepare("UPDATE breakfast_participants SET participant_asleep = '0' WHERE project_id = :project_id AND participant_id = :participant_id");
				$wake_participant->bindParam(':project_id', $cookie_project_id);		
				$wake_participant->bindParam(':participant_id', $participant_id);
				$wake_participant->execute();			
			}else{
				/*** New ***/
				$new_participant = $conn->prepare("INSERT INTO breakfast_participants (participant_name, participant_email, project_id) VALUES (:name, :email, :project_id)");
				$new_participant->bindParam(':name', $name);
				$new_participant->bindParam(':email', $email);
				$new_participant->bindParam(':project_id', $cookie_project_id);
				$new_participant->execute();			
				$participant_id = $conn->lastInsertId('breakfast_participants');
				
			}
		
			$errmsg[0] = 1;
			$errmsg[1] = "Deltageren er tilføjet!";
			break;
			
		case 'edit':
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$email = filter_var(isset($_POST['email']) ? $_POST['email'] : '', FILTER_SANITIZE_STRING);
			$participant_id = (isset($_POST['participant_id']) ? $_POST['participant_id'] : '');

			$check_email_db = $conn->prepare("SELECT COUNT(participant_id) as C FROM breakfast_participants WHERE participant_email = :email AND project_id = :project_id AND participant_id <> :participant_id LIMIT 1");
			$check_email_db->bindParam(':email', $email);		
			$check_email_db->bindParam(':project_id', $cookie_project_id);		
			$check_email_db->bindParam(':participant_id', $participant_id);		
			$check_email_db->execute();
			$check_email = $check_email_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name) OR empty($email) OR empty($participant_id)){$errmsg[0] = -1; break;}		
			// Double email
			if ($check_email > 0){$errmsg[0] = -2; break;}	
	
			/*** UPDATE ***/
			$edit_participant = $conn->prepare("UPDATE breakfast_participants SET participant_name = :name, participant_email = :email WHERE project_id = :project_id AND participant_id = :participant_id");
			$edit_participant->bindParam(':name', $name);
			$edit_participant->bindParam(':email', $email);
			$edit_participant->bindParam(':project_id', $cookie_project_id);		
			$edit_participant->bindParam(':participant_id', $participant_id);
			$edit_participant->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Deltageren er ændret!";
			break;
			
		case 'changeStatus':		
			// Variables from form
			$registration_id = (isset($_POST['participant_id']) ? $_POST['participant_id'] : '');
			$value = (isset($_POST['value']) ? $_POST['value'] : '');
			if($value=="true"){$value=1;}else{$value=0;}
			
			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($registration_id)){$errmsg[0] = -1; break;}		
	
			/*** UPDATE ***/
			$change_status = $conn->prepare("UPDATE breakfast_registrations SET participant_attending = :status WHERE registration_id = :registration_id");
			$change_status->bindParam(':status', $value);
			$change_status->bindParam(':registration_id', $registration_id);
			$change_status->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Status er ændret!";
			break;
	}

	
	// Actual error message
	if($errmsg[0] != 1){$errmsg[1] = "<p class='error'>";}
	switch ($errmsg[0]){
		case '-1':
			$errmsg[1] .= "Alle felter skal udfyldes!";
			break;
		case '-2':
			$errmsg[1] .= "En deltager med angivede email er allerede tilføjet!";
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