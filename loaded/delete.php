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
if(empty($cookie_project_id)){header('Location: index.php'); exit;}

Header('Content-Type:text/html; charset=utf-8');
/*************** AJAX ***************/


try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	


	$type = isset($_POST['type']) ? $_POST['type'] : '';
	$errmsg = array(0);
	
	switch ($type){
		case 'participant':
			// Variables from form
			$participant_id = (isset($_POST['id']) ? $_POST['id'] : '');
			
			/*** DELETE ***/
			$delete_participant = $conn->prepare("UPDATE breakfast_participants SET participant_removed = '1' WHERE participant_id = :participant_id");
			$delete_participant->bindParam(':participant_id', $participant_id);
			$delete_participant->execute();
			
			$delete_registrations = $conn->prepare("DELETE FROM breakfast_registrations WHERE participant_id = :participant_id");
			$delete_registrations->bindParam(':participant_id', $participant_id);
			$delete_registrations->execute();

			$delete_chefs = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_chef = '0' WHERE breakfast_chef = :participant_id AND (breakfast_date > DATE(NOW()) OR DATE(breakfast_created) = DATE(NOW()))");
			$delete_chefs->bindParam(':participant_id', $participant_id);
			$delete_chefs->execute();
			
			$errmsg[0] = 1;
			echo json_encode($errmsg);
			exit;
			
		case 'product':
			// Variables from form
			$product_id = (isset($_POST['id']) ? $_POST['id'] : '');
			
			/*** DELETE ***/
			$delete_product = $conn->prepare("DELETE FROM breakfast_products WHERE product_id = :product_id");
			$delete_product->bindParam(':product_id', $product_id);
			$delete_product->execute();
			
			$errmsg[0] = 1;
			echo json_encode($errmsg);
			exit;
	}
	
	$conn = null;
} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>