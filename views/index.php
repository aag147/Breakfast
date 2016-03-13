<?php
include("../headers/setup.php");
if(!empty($cookie_project_id)){header('Location: project.php'); exit;}
include("../headers/header.php");

?>
	<head>
		<title>
			Breakfast management
		</title>	
		<script>
			window.onload = shiftLogin();
		</script>	
	</head>

	<div id="frontpage">
		<ul>
			<li id="adminAllContent">
			</li>
			<li id="about">
				<span class="title">About this webapp</span>
				<span class="content">Here you can do all these smart things managing breakfasts at work/school/whatever.<br>
				Just log in and you are good to go.</span>
			</li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>