<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

?>
	<head>
		<title>
			Alle produkter
		</title>
		<script>
			window.onload = showContent("product");
		</script>	
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
		<div id="showAllProduct"></div>
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
include("../headers/footer.php");
?>