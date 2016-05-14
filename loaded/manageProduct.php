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

			$check_name_db = $conn->prepare("SELECT COUNT(product_id) as C FROM breakfast_products WHERE product_name = :name AND project_id = :project_id LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->bindParam(':project_id', $cookie_project_id);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name)){$errmsg[0] = -1; break;}		
			// Double name
			if ($check_name > 0){$errmsg[0] = -2; break;}	
			// Long inputs
			if (strlen($name) > 60){$errmsg[0] = -3; break;}
			
			/*** INSERT ***/
			$new_product = $conn->prepare("INSERT INTO breakfast_products (product_name, project_id) VALUES (:name, :project_id)");
			$new_product->bindParam(':name', $name);
			$new_product->bindParam(':project_id', $cookie_project_id);
			$new_product->execute();
			
			$product_id = $conn->lastInsertId('breakfast_products');
		
			$errmsg[0] = 1;
			$errmsg[1] = "Produktet er tilføjet!";
			break;
			
		case 'edit':
			// Variables from form
			$name = filter_var(isset($_POST['name']) ? $_POST['name'] : '', FILTER_SANITIZE_STRING);
			$product_id = (isset($_POST['product_id']) ? $_POST['product_id'] : '');

			$check_name_db = $conn->prepare("SELECT COUNT(product_id) as C FROM breakfast_products WHERE product_name = :name AND project_id = :project_id AND product_id <> :product_id LIMIT 1");
			$check_name_db->bindParam(':name', $name);		
			$check_name_db->bindParam(':project_id', $cookie_project_id);		
			$check_name_db->bindParam(':product_id', $product_id);		
			$check_name_db->execute();
			$check_name = $check_name_db->fetchColumn();

			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($name) OR empty($product_id)){$errmsg[0] = -1; break;}		
			// Double name
			if ($check_name > 0){$errmsg[0] = -2; break;}	
			// Long inputs
			if (strlen($name) > 60){$errmsg[0] = -3; break;}
	
			/*** UPDATE ***/
			$new_product = $conn->prepare("UPDATE breakfast_products SET product_name = :name WHERE project_id = :project_id AND product_id = :product_id");
			$new_product->bindParam(':name', $name);
			$new_product->bindParam(':project_id', $cookie_project_id);		
			$new_product->bindParam(':product_id', $product_id);
			$new_product->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Produktet er ændret!";
			break;
			
		case 'changeStatus':		
			// Variables from form
			$product_id = (isset($_POST['product_id']) ? $_POST['product_id'] : '');
			$value = (isset($_POST['value']) ? $_POST['value'] : '');
			if($value=="true"){$value=1;}else{$value=0;}
			
			/*** ERROR CHECKING ***/	
			// Empty inputs
			if (empty($product_id)){$errmsg[0] = -1; break;}		
	
			/*** UPDATE ***/
			$change_status = $conn->prepare("UPDATE breakfast_products SET product_status = :status WHERE project_id = :project_id AND product_id = :product_id");
			$change_status->bindParam(':status', $value);
			$change_status->bindParam(':project_id', $cookie_project_id);		
			$change_status->bindParam(':product_id', $product_id);
			$change_status->execute();
					
			$errmsg[0] = 1;
			$errmsg[1] = "Status er ændret!";
			break;
			
		case 'delete':
			// Variables from form
			$product_id = (isset($_POST['id']) ? $_POST['id'] : '');
			
			/*** DELETE ***/
			$delete_product = $conn->prepare("DELETE FROM breakfast_products WHERE product_id = :product_id");
			$delete_product->bindParam(':product_id', $product_id);
			$delete_product->execute();
			
			$errmsg[0] = 1;
			$errmsg[1] = 'Produktet er blevet slettet.';
			break;
			
		default:
			echo json_encode(array(-10));
			exit;
	}
	
	
	// Actual error message
	if($errmsg[0] != 1){$errmsg[1] = "<p class='error'>";}
	switch ($errmsg[0]){
		case '-1':
			$errmsg[1] .= "Navnet skal udfyldes!";
			break;
		case '-2':
			$errmsg[1] .= "Produktet er allerede tilføjet!";
			break;
		case '-3':
			$errmsg[1] .= "Navnet er for lang. Systemet accepterer desværre ikke mere end 60 tegn!";
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