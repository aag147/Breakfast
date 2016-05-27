<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);		
?>
	<head>
		<title>
			Din morgenmadsplan
		</title>
		<script>
			window.onload = buildBreakfastPlan();
			window.onload = showContent("product", visuals = 'simple', db_filter = 'buy');
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
		<ul id="planContent">
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
		<ul id="planPanel" >
			<li id="title">
				Hvad skal købes?
			</li>
			<?php /* jscript */ ?>
			<li id="showAllProduct"></li>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>