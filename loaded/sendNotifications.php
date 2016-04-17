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

	$type = $_POST['type'];
	$errmsgArray = array(0);
	
	// Recipients
	$participants = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id");
	$participants->bindParam(':project_id', $cookie_project_id);		
	$participants->execute();
	$errmsgArray[1] = $participants->rowCount();
	
	$emails = array();
	while($row = $participants->fetch(PDO::FETCH_ASSOC)){
		$emails[] = $row['participant_name']." <".$row['participant_email'].">";
	}	
	$recipients = implode(", ", $emails);
	
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
				ul, li, ol{
					list-style: none outside none;
					padding: 0;
					margin: 0;
				}
				ul {display: block;}
				li {display: block;}
				span:first-child{display: inline-block; width: 90px; text-align: left;}
				span:last-child{display: inline-block; width: 20px; text-align: right;}
			</style>
		</head>
		<body>';
			
	if($type=="tomorrow"){
		// Subject
		$subject = "Tomorrows breakfast | ".date('j-m-Y');
		
		// Message CONTENT
		$chef_db->execute();
		$chef = $chef_db->fetch();	
		
		$message .= 
		'Hello everyone!</br>
		Remember we have breakfast together tomorrow.</br>
		'.$chef['participant_name'].' is in charge.</br>
		If you can\'t come, you should already have registered this.</br>
		Have a good day.</br>';
		
	}elseif($type=="weekdays"){
		// Subject
		$subject = "Breakfast days have been changed | ".date('j-m-Y');
		
		// Message CONTENT
		$message .= 
		'Hello everyone!</br>
		Breakfast days have been changed today.</br>
		The new days are as follows:</br>
		<ul>';
		
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
		Have a good day.</br>';
		
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
	}
	
	echo json_encode($errmsgArray);
 	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}

?>