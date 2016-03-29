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
	
	// Subject
	$subject = "Tomorrows breakfast | ".date('j-m-Y');
	
	// Headers
	$headers  = "From: Breakfast Management <contactbreakfastmanagement@gmail.com>" . "\r\n";
	$headers .= "Content-type: text/html; charset=utf-8";	

	// Message
	$chef_db = $conn->prepare("SELECT * FROM 
										(SELECT breakfast_chef FROM breakfast_breakfasts WHERE project_id = :project_id AND breakfast_date = CURDATE() + INTERVAL 1 DAY) B
									LEFT JOIN
										breakfast_participants P
									ON B.breakfast_chef = participant_id");
	$chef_db->bindParam(':project_id', $cookie_project_id);		
	$chef_db->execute();
	$chef = $chef_db->fetch();
	
	$message = 
	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" class="noJS da-DK">
		<head> 
			<style>
				body  {
					font-size: 1.0em;
				}
			</style>
		</head>
		<body>
			Hello everyone!</br>
			Remember we have breakfast together tomorrow.</br>
			'.$chef['participant_name'].' is in charge.</br>
			If you can\'t come, you should already have registered this.</br>
			Have a good day.</br>
		</body>
	</html>';

	// Send emails
	mail($recipients, $subject, $message, $headers);
	$errmsgArray[0] = 1;
	
	// Update lastNotified
	$update = $conn->prepare("UPDATE breakfast_projects SET project_lastNotified = CURDATE() WHERE project_id = :project_id");
	$update->bindParam(':project_id', $cookie_project_id);		
	$update->execute();
	
	echo json_encode($errmsgArray);
 	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}

?>