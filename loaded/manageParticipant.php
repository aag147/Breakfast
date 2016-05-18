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
			// Long inputs
			if (strlen($name) > 60 OR strlen($email) > 60){$errmsg[0] = -3; break;}	
			
			if($participant_asleep==1){
				/*** Wake up ***/
				$wake_participant = $conn->prepare("UPDATE breakfast_participants SET participant_asleep = '0', participant_name = :participant_name WHERE project_id = :project_id AND participant_id = :participant_id");
				$wake_participant->bindParam(':project_id', $cookie_project_id);		
				$wake_participant->bindParam(':participant_id', $participant_id);
				$wake_participant->bindParam(':participant_name', $name);
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

			$check_email_db = $conn->prepare("SELECT COUNT(participant_id) as C FROM breakfast_participants
											  WHERE project_id = :project_id AND participant_email = :email AND participant_asleep = '0' AND participant_id <> :participant_id LIMIT 1");
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
			// Long inputs
			if (strlen($name) > 60 OR strlen($email) > 60){$errmsg[0] = -3; break;}	
						
			/*** Delete asleep participant with same email ***/
			$delete_old_participant = $conn->prepare("DELETE FROM breakfast_participants WHERE project_id = :project_id AND participant_email = :email");
			$delete_old_participant->bindParam(':project_id', $cookie_project_id);		
			$delete_old_participant->bindParam(':email', $email);
			$delete_old_participant->execute();	
			
			/*** UPDATE ***/
			$edit_participant = $conn->prepare("UPDATE breakfast_participants SET participant_name = :name, participant_email = :email WHERE project_id = :project_id AND participant_id = :participant_id");
			$edit_participant->bindParam(':project_id', $cookie_project_id);		
			$edit_participant->bindParam(':name', $name);
			$edit_participant->bindParam(':email', $email);
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
			
		case 'changeChef':		
			// Variables from form
			$breakfast_id = (isset($_POST['breakfast_id']) ? $_POST['breakfast_id'] : '');
			$chef_id = (isset($_POST['chef_id']) ? $_POST['chef_id'] : '');
			$original_id = (isset($_POST['original_id']) ? $_POST['original_id'] : '');
			
			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($breakfast_id)){$errmsg[0] = -1; break;}		
			
			/*** EXTRACT INFO ***/
			// Previous chef id
			$chef_db = $conn->prepare("SELECT chef_id, chef_replacement_id FROM breakfast_chefs
											WHERE breakfast_id = :breakfast_id AND project_id = :project_id AND chef_id = :original_id");
			$chef_db->bindParam(':project_id', $cookie_project_id);	
			$chef_db->bindParam(':breakfast_id', $breakfast_id);
			$chef_db->bindParam(':original_id', $original_id);
			$chef_db->execute();
			$chef = $chef_db->fetch();
			if($chef['chef_replacement_id'] == 0){$previous_chef_id = $original_id;}
			else{$previous_chef_id = $chef['chef_replacement_id'];}

			// Replacement chef name
			if($chef_id==0){$new_chef_id = $original_id;}else{$new_chef_id = $chef_id;}
			$chef_db = $conn->prepare("SELECT participant_name FROM breakfast_participants WHERE participant_id = :participant_id AND project_id = :project_id LIMIT 1");
			$chef_db->bindParam(':project_id', $cookie_project_id);	
			$chef_db->bindParam(':participant_id', $new_chef_id);
			$chef_db->execute();
			$chef_name = $chef_db->fetchColumn();
	
			/*** UPDATE ***/
			$change_chef = $conn->prepare("UPDATE breakfast_chefs SET chef_replacement_id = :chef_id WHERE project_id = :project_id AND breakfast_id = :breakfast_id AND chef_id = :original_id ");
			$change_chef->bindParam(':project_id', $cookie_project_id);	
			$change_chef->bindParam(':breakfast_id', $breakfast_id);
			$change_chef->bindParam(':chef_id', $chef_id);
			$change_chef->bindParam(':original_id', $original_id);
			$change_chef->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Vært er ændret!";
			$errmsg[2] = $chef_name;
			$errmsg[3] = $previous_chef_id;
			$errmsg[4] = $new_chef_id;
			break;
			
		case 'delete':
			// Variables from form
			$participant_id = (isset($_POST['id']) ? $_POST['id'] : '');
			
			/*** DELETE ***/
			$delete_participant = $conn->prepare("UPDATE breakfast_participants SET participant_asleep = '1' WHERE participant_id = :participant_id");
			$delete_participant->bindParam(':participant_id', $participant_id);
			$delete_participant->execute();
			
			$delete_registrations = $conn->prepare("DELETE FROM breakfast_registrations WHERE participant_id = :participant_id");
			$delete_registrations->bindParam(':participant_id', $participant_id);
			$delete_registrations->execute();

			$delete_chefs = $conn->prepare("DELETE FROM breakfast_chefs
											WHERE chef_id = :participant_id
											AND breakfast_id IN
												(SELECT breakfast_id FROM breakfast_breakfasts
												 WHERE project_id = :project_id AND breakfast_date > DATE(NOW()) OR 
												 DATE(breakfast_created) = DATE(NOW()))");
			$delete_chefs->bindParam(':project_id', $cookie_project_id);
			$delete_chefs->bindParam(':participant_id', $participant_id);
			$delete_chefs->execute();
			
			// Reset chef replacements
			$reset_chef_replacements = $conn->prepare("UPDATE breakfast_chefs SET chef_replacement_id = '-1'
														WHERE chef_replacement_id = :participant_id
														AND breakfast_id IN
															(SELECT breakfast_id FROM breakfast_breakfasts
															 WHERE project_id = :project_id AND breakfast_date > DATE(NOW()) OR 
															 DATE(breakfast_created) = DATE(NOW()))");
			$reset_chef_replacements->bindParam(':project_id', $cookie_project_id);
			$reset_chef_replacements->bindParam(':participant_id', $participant_id);
			$reset_chef_replacements->execute();
			
			
			// Reduce chefs pr day
			$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
			$participants_db->bindParam(':project_id', $cookie_project_id);		
			$participants_db->execute();
			$participants_count = $participants_db->rowCount();
			
			if(0 < $participants_count AND $participants_count < 3){
				for($i = 0; $i < 7; $i++){
					$weekday = strtolower(jddayofweek($i, 1));	
					$update_weekdays = $conn->prepare("	UPDATE breakfast_options SET 
														".$weekday."_chefs = :max											
														WHERE project_id = :project_id AND ".$weekday."_chefs > :max");
					$update_weekdays->bindParam(':project_id', $cookie_project_id);		
					$update_weekdays->bindParam(':max', $participants_count);		
					$update_weekdays->execute();
				}
			}
			
			$errmsg[0] = 1;
			$errmsg[1] = 'Personen er blevet slettet.';
			break;
			
		default:
			echo json_encode(array(-10));
			exit;
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
		case '-3':
			$errmsg[1] .= "Navnet eller emailen er for lang. Systemet accepterer desværre ikke mere end 60 tegn!";
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