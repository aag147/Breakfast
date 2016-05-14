<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants_count = $participants_db->rowCount();


?>
	<head>
		<title>
			Indstillinger
		</title>
		<script>
			window.onload = showContent("settings");
		</script>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<span class="span2input">
					<span class="name"><?php echo $project_name; ?></span>
				</span>
			</li>
			<span id="Errmsg"></span>
			<li id="subtitle">
				Indstillinger
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<ul id="settingsContent">
			<li id="title">
				Advancerede indstillinger
			</li>
			<li>
				<span class="options">
					<a href="javascript:;" class="saveAccount hide green">Gem projektnavn</a>
					<a href="javascript:;" class="editAccount">Ret projektnavn</a>
					<a href="javascript:;" class="logOut">Log ud</a>
					<a href="javascript:;" class="deleteAccount">Slet projekt</a>
				</span>
			</li>
		</ul>
	</div><?php
	
	?><div id="standardPanel">
		<ul id="settingsPanel">
			<li id="title">
				Ret arrangement dage
			</li>
			<?php /* jscript */ ?>
			<li id="showAllSettings"></li>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>