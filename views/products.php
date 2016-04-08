<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$products_db = $conn->prepare("SELECT * FROM breakfast_products WHERE project_id = :project_id ORDER BY product_name ASC");
	$products_db->bindParam(':project_id', $cookie_project_id);		
	$products_db->execute();
	$products_count = $products_db->rowCount();

?>
	<head>
		<title>
			Alle produkter
		</title>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Alle produkter
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<?php 
		if($products_count==0){
			echo "Du har ikke tilføjet nogle produkter endnu.";
		}else{
			?>
			<ul id="standardList">
				<li class="listLegend">
					<span class="name">Navn</span><?php
					?><span class="status">På lager</span><?php
					?><span class="admin">Admin</span>
				</li>
				<?php
				while($product = $products_db->fetch(PDO::FETCH_ASSOC)){
					if($product['product_status']){$inStore = "checked";}else{$inStore = "";}
					echo "<li id='product_".$product['product_id']."'>";
						echo "<span class='span2input'>";
							echo "<span class='name'>".$product['product_name']."</span>";
						echo "</span>";
						echo "<span class='status'><input id='".$product['product_id']."' class='editProductStatus' type='checkbox' ".$inStore."/></span>";
						echo "<span class='options'>";
							echo "<a href='javascript:;' id='".$product['product_id']."' class='saveProduct green hide'>[gem]</a>";
							echo "<a href='javascript:;' id='".$product['product_id']."' class='editProduct blue'>[ret]</a>";
							echo "<a href='javascript:;' id='".$product['product_id']."' class='deleteProduct red'>[X]</a>";
						echo "</span>";
					echo "</li>";
				}
				?>
			</ul>
			<?php
		}
		?>		
	</div><?php
	
	?><div id="standardPanel">
		<ul id="adminPanel">
			<li id="title">
				Administration
			</li>
			<li class="option">
			<form id="newProductForm" action="" method="POST">
				<span class="optionTitle">Tilføj produkt</span>
				<span class="optionInputs">
					<input name="name" type="text" placeholder="Indtast produktets navn" />
				</span>
				<span class="optionErrmsg" id="newErrmsg">
				</span>
				<span class="optionSubmit">
					<input type="submit" value="Send"/>
				</span>
			</form>
			</li>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>