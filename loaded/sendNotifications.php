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
	$errmsgArray = array(0);
	
	
	if(empty($cookie_project_id)){
		$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
		$email = filter_var(isset($_POST['email']) ? $_POST['email'] : '', FILTER_SANITIZE_STRING);
		
		$project_db = $conn->prepare("SELECT P.* FROM
										(SELECT project_id FROM breakfast_projects WHERE project_name = :project_name) Prj
									  JOIN
										(SELECT project_id, participant_id, participant_name FROM breakfast_participants WHERE participant_email = :participant_email AND participant_asleep = '0') P
									  ON Prj.project_id = P.project_id");
		$project_db->bindParam(':project_name', $name);		
		$project_db->bindParam(':participant_email', $email);		
		$project_db->execute();	
		$project_count = $project_db->rowCount();
		
		if($project_count==0){
			$errmsgArray[1] = "<p class='error'>Projektet kunne ikke findes.</p>";
			echo json_encode($errmsgArray);
			exit;
		}
		
		$project = $project_db->fetch();
		$cookie_project_id = $project['project_id'];
		$participant_name = $project['participant_name'];
		$participant_id = $project['participant_id'];
	}
	
	
	// Recipients
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants_count = $participants_db->rowCount();
	
	// Attendees
	if($type=="forgotten"){
		$recipients = $participant_name." <".$email.">";
		
	} else{
		$attendees_db = $conn->prepare("SELECT P.* FROM 
											(SELECT breakfast_id FROM breakfast_breakfasts WHERE breakfast_date = CURDATE() + INTERVAL 1 DAY) B
										JOIN
											(SELECT breakfast_id, participant_id FROM breakfast_registrations WHERE participant_attending = 1) R
										ON B.breakfast_id = R.breakfast_id
										JOIN
											(SELECT * FROM breakfast_participants WHERE project_id = :project_id) P
										ON R.participant_id = P.participant_id
										ORDER BY P.participant_name ASC");
		$attendees_db->bindParam(':project_id', $cookie_project_id);		
		$attendees_db->execute();
		$attendees_count = $attendees_db->rowCount();
		
		$emails = array();
		while($row = $participants_db->fetch(PDO::FETCH_ASSOC)){
			$emails[] = $row['participant_name']." <".$row['participant_email'].">";
		}	
		$recipients = implode(", ", $emails);
	}
	
	// Headers
	$headers  = "From: Breakfast Management <contactbreakfastmanagement@gmail.com>" . "\r\n";
	$headers .= "Content-type: text/html; charset=utf-8";	
	
	// Message BEGINNING
	$chef_db = $conn->prepare("SELECT * FROM 
										(SELECT breakfast_chef FROM breakfast_breakfasts WHERE project_id = :project_id AND breakfast_date = CURDATE() + INTERVAL 1 DAY AND breakfast_notified = '0') B
									LEFT JOIN
										breakfast_participants P
									ON B.breakfast_chef = participant_id");
	$chef_db->bindParam(':project_id', $cookie_project_id);		
	
	$message = 
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" class="noJS da-DK">
		<head> 
			<style>
				body  {
					font-size: 1.0em;
				}
				ul, li{
					list-style: none outside none;
					padding: 0;
					margin: 0;
					display: block;
				}
				#weekdays span:first-child{display: inline-block; width: 90px; text-align: left;}
				#weekdays span:last-child{display: inline-block; width: 20px; text-align: right;}
				
			</style>
		</head>
		<body>';

	if($type=="tomorrow"){
		// Subject
		$subject = "I morgens morgenmad | ".date('j-m-Y');
		
		// Message CONTENT
		$products_missing_db = $conn->prepare("SELECT * FROM breakfast_products WHERE project_id = :project_id AND product_status = 0 ORDER BY product_name ASC");
		$products_missing_db->bindParam(':project_id', $cookie_project_id);		
		$products_missing_db->execute();

		$shoppinglist = "<ul id='products'>";
		while($product = $products_missing_db->fetch(PDO::FETCH_ASSOC)){
			$shoppinglist .= "<li>".$product['product_name']."</li>";
		}
		$shoppinglist .= "</ul>";
		
		$chef_db->execute();
		$chef = $chef_db->fetch();	
		
		$message .= 
		'Hej allesammen!</br>
		Husk at vi spiser morgenmad sammen i morgen.</br>
		'.$chef['participant_name'].' skal sørge for at handle ind.</br>
		Hvis du ikke kan komme, bør du allerede nu have meldt dig fra.</br></br>
		Antal tilmeldte: '.$attendees_count.'</br>
		Indkøbsliste:</br>
		'.$shoppinglist.'</br></br>
		Ha\' en god dag.</br>';
		
	}elseif($type=="weekdays"){
		// Subject
		$subject = "Arrangementdagene er blevet redigeret | ".date('j-m-Y');
		
		// Message CONTENT
		$message .= 
		'Hej allesammen!</br>
		Arrangementdagene er blevet ændret.</br>
		De nye dage er som følgende:</br>
		<ul id="weekdays">';
		
		for($j = 0; $j < 7; $j++){
			$weekday = jddayofweek($j, 1);
			$valid = $project['project_'.strtolower($weekday)];
			if($valid){$checked = "X";}else{$checked = "";}
			$message .=
			'<li>
				<span>'.$weekday.'</span>
				<span>'.$checked.'</span>
			</li>';
		}
		
		$message .= 
		'</ul>
		Ha\' en god dag.</br>';
	
	
	}elseif($type=="forgotten"){
		// Subject
		$subject = "Sikkerhedskode til at rette kodeord | ".date('j-m-Y');
		
		// Message CONTENT
		$keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$security_code = '';
		$max = mb_strlen($keyspace, '8bit') - 1;		
		for ($i = 0; $i < 16; ++$i) {
			$security_code .= $keyspace[rand(0, $max)];
		}
		$errmsgArray[1] = "<p class='success'>Emailen er blevet sendt.</p>";
		$errmsgArray[2] = $security_code;
		
		$message .= 
		'Hej allesammen!</br>
		Denne email indeholder en sikkerhedskode til at ændre dit kodeord.</br>
		Hvis du ikke har bedt om at få tilsendt denne email, kan den bare ignores.</br>
		Sikkehedskoden er:</br>
		'.$security_code.'</br>
		Ha\' en god dag.</br>';
		
	}else{
		exit;
	}
	
	// Message END
	$message .= 
	'	</body>
	</html>';	
			
	// Send emails
	mail($recipients, $subject, $message, $headers);
	$errmsgArray[0] = 1;
	
	
	if($type=="tomorrow"){
		// Update lastNotified
		$breakfast_date = date("Y-m-d");
		$update = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_notified = 1 WHERE project_id = :project_id AND breakfast_date = :breakfast_date");
		$update->bindParam(':project_id', $cookie_project_id);		
		$update->bindParam(':breakfast_date', $breakfast_date);		
		$update->execute();
	}elseif($type=="forgotten"){
		// Insert security code hash
		$security_code_hash = password_hash($cookie_project_id.$security_code, PASSWORD_BCRYPT);	
		setcookie("security_code", $security_code_hash, time()+60*10, '/', 'localhost');	
	}
	
	echo json_encode($errmsgArray);
	
 	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}

?>