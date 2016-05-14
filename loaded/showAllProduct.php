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
	
	$visuals = !empty($_POST['visuals']) ? $_POST['visuals'] : '';
	$db_filter = !empty($_POST['db_filter']) ? $_POST['db_filter'] : '';

	switch ($db_filter){
		case 'buy':
			$products_query = "SELECT * FROM breakfast_products WHERE project_id = :project_id AND product_status = 0 ORDER BY product_name ASC";
			break;
		default:
			$products_query = "SELECT * FROM breakfast_products WHERE project_id = :project_id ORDER BY product_name ASC";
	}
	
	$products_db = $conn->prepare($products_query);
	$products_db->bindParam(':project_id', $cookie_project_id);		
	$products_db->execute();
	$products_count = $products_db->rowCount();
	
	
	if($products_count==0){
		switch ($db_filter){
			case 'buy':
				echo "Der mangler ikke at blive købt noget.";
				break;
			case 'full':
				echo "Du har ikke tilføjet nogle produkter endnu.";
		}
	}else{	
		switch ($visuals){
			case 'simple':
				echo "<ul class='projectPanelContent'>";
					echo "<li id='showAmount'>Vi mangler <span id='totalAmount'>".$products_count."</span> produkt(er)...</li>";
					while($product = $products_db->fetch(PDO::FETCH_ASSOC)){
						if($product['product_status']){$inStore = "checked";}else{$inStore = "";}
						echo "<li id='product_".$product['product_id']."'>";
							echo "<span class='status'><input data-id='".$product['product_id']."' class='removeProductStatus' type='checkbox' ".$inStore."/></span>";
							echo "<span class='name'>".$product['product_name']."</span>";
						echo "</li>";
					}
				echo "</ul>";
				break;
				
				
			case 'full':
				echo "<ul id='standardList'>";
					echo "<li id='showAmount'>Viser <span id='totalAmount'>".$products_count."</span> produkt(er)...</li>";
					echo "<li class='listLegend'>";
						echo "<span class='name'>Navn</span>";
						echo "<span class='status'>På lager</span>";
						echo "<span class='admin'>Admin</span>";
					echo "</li>";
					while($product = $products_db->fetch(PDO::FETCH_ASSOC)){
						if($product['product_status']){$inStore = "checked";}else{$inStore = "";}
						echo "<li id='product_".$product['product_id']."'>";
							echo "<span class='main'>";
								echo "<span class='span2input'>";
									echo "<span class='name'>".$product['product_name']."</span>";
								echo "</span>";
								echo "<span class='elementErrmsg' id='".$product['product_id']."Errmsg'></span>";
							echo "</span>";
							echo "<span class='status'><input data-id='".$product['product_id']."' class='editProductStatus' type='checkbox' ".$inStore."/></span>";
							echo "<span class='edit'>";
								echo "<a href='javascript:;' data-id='".$product['product_id']."' class='saveProduct green hide'>[gem]</a>";
								echo "<a href='javascript:;' data-id='".$product['product_id']."' class='annulProduct blue hide'>[annul]</a>";
								echo "<a href='javascript:;' data-id='".$product['product_id']."' class='editProduct blue'>[ret]</a>";
							echo "</span>";
							echo "<span class='delete'>";						
								echo "<a href='javascript:;' data-id='".$product['product_id']."' data-name='".$product['product_name']."' class='deleteProduct red'>[X]</a>";
							echo "</span>";
						echo "</li>";
					}
				echo "</ul>";
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>