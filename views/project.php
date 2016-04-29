<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$products_missing_db = $conn->prepare("SELECT * FROM breakfast_products WHERE project_id = :project_id AND product_status = 0 ORDER BY product_name ASC");
	$products_missing_db->bindParam(':project_id', $cookie_project_id);		
	$products_missing_db->execute();

	
?>
	<head>
		<title>
			Din morgenmadsplan
		</title>
		<script>
			window.onload = buildBreakfastPlan();
		</script>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Din morgenmadsplan
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<ul id="projectContent">
			<li id="title">
				Kommende arrangementer
			</li>
			<li id="breakfastPlan">
				<?php /* jscript */ ?>
				<span class="loadingText">Bygger morgenmadsplanen... Dette kan tage et øjeblik.</span>
			</li>
		</ul>
	</div><?php
	?><div id="standardPanel">
		<ul id="projectPanel" >
			<li id="title">
				Hvad skal købes?
			</li>
			<?php
			while($product = $products_missing_db->fetch(PDO::FETCH_ASSOC)){
				if($product['product_status']){$inStore = "checked";}else{$inStore = "";}
				echo "<li id='product_".$product['product_id']."'>";
					echo "<span class='status'><input data-id='".$product['product_id']."' class='removeProductStatus' type='checkbox' ".$inStore."/></span>";
					echo "<span class='name'>".$product['product_name']."</span>";
				echo "</li>";
			}
			?>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>