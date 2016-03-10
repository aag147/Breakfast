<?php
include("../headers/setup.php");
include("../headers/header.php");
?>
	<head>
		<title>
			Your breakfast products
		</title>	
		<script>
			window.onload = shiftLogin();
		</script>	
	</head>

	<div id="productspage">
		<ul>
			<li id="pageTitle">
				<?php echo $project_name; ?>
			</li>
			<li id="pageSubTitle">
				Your breakfast products
			</li>
			<li id="projectContent">
				Something here
			</li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>