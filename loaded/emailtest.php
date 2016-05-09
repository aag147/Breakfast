<?php

// FIRST HEADER		
require('../headers/setup.php');

Header('Content-Type:text/html; charset=utf-8');
/*************** AJAX ***************/

try{
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$type = isset($_POST['type']) ? $_POST['type'] : '';
	$errmsgArray = array(0);
	
	// Attendees
	$recipients = "Du der"." <aag147@live.dk>";
	
	// Headers
	$headers  = "From: Breakfast Management <contactbreakfastmanagement@gmail.com>" . "\r\n";
	$headers .= "Content-type: text/html; charset=utf-8";	
	
	// Message BEGINNING	
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

		// Subject
		$subject = "Email test | ".date('j-m-Y');
		
		// Message CONTENT		
		$message .= 
		'Hej allesammen!</br>
		Dette er en awesome test!!!</br>
		Ha\' en god dag.</br>';
		

	
	// Message END
	$message .= 
	'	</body>
	</html>';	
			
	// Send emails
	mail($recipients, $subject, $message, $headers);
	$errmsgArray[0] = 1;
	
	echo json_encode($errmsgArray);
	
 	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}

?>