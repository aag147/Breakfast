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
	$errmsgArray = array(0);
	
	// Recipients
	$participants = $conn->prepare("SELECT P.* FROM 
										(SELECT breakfast_id FROM breakfast_breakfasts WHERE breakfast_date = CURDATE() + INTERVAL 1 DAY) B
									JOIN
										(SELECT breakfast_id, participant_id FROM breakfast_registrations WHERE participant_attending = 1) R
									ON B.breakfast_id = R.breakfast_id
									JOIN
										(SELECT * FROM breakfast_participants WHERE project_id = :project_id) P
									ON R.participant_id = P.participant_id
									ORDER BY P.participant_name ASC");
	$participants->bindParam(':project_id', $cookie_project_id);		
	$participants->execute();
	$participants_count = $participants->rowCount();
	
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
		Antal tilmeldte: '.$participants_count.'</br>
		Indkøbsliste:</br>
		'.$shoppinglist.'</br></br>
		Ha\' en god dag.</br>';
		
	}elseif($type=="weekdays"){
		// Subject
		$subject = "Arrangementdagene er blevet ændret | ".date('j-m-Y');
		
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