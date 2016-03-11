<?php

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
		case 'participant':
			// Variables from form
			$participant_id = (isset($_POST['id']) ? $_POST['id'] : '');
			
			/*** DELETE ***/
			$delete_participant = $conn->prepare("DELETE FROM breakfast_participants WHERE participant_id = :participant_id");
			$delete_participant->bindParam(':participant_id', $participant_id);
			$delete_participant->execute();
			
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