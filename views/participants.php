<?php
include("../headers/setup.php");
include("../headers/header.php");
?>
	<head>
		<title>
			Your breakfast participants
		</title>	
		<script>
			window.onload = shiftLogin();
		</script>	
	</head>

	<div id="participantspage">
		<ul>
			<li id="pageTitle">
				<?php echo $project_name; ?>
			</li>
			<li id="pageSubTitle">
				Your breakfast participants
			</li>
			<li id="projectContent">
				Something here
			</li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>