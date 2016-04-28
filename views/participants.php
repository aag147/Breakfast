<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

?>
	<head>
		<title>
			Alle deltagere
		</title>
		<script>
			window.onload = showContent("participant");
		</script>		
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Alle deltagere
			</li>
		</ul>
	</div>
	
	<div id="standardContent">	
		<div id="showAllParticipant"></div>
	</div><?php
	
	?><div id="standardPanel">
		<ul id="adminPanel">
			<li id="title">
				Administration
			</li>
			<li class="option">
			<form id="newParticipantForm" action="" method="POST">
				<span class="optionTitle">Tilføj deltager</span>
				<span class="optionInputs">
					<input name="name" type="text" placeholder="Indtast deltagers navn" />
					<input name="email" type="email" placeholder="Indtast deltagers email"/>
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