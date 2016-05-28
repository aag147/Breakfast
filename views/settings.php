<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

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
					<a href="javascript:;" class="annulAccount hide blue">Annuller</a>
					<a href="javascript:;" class="editAccount">Ret projektnavn</a>
					<a href="javascript:;" class="logOut">Log ud</a>
					<a href="javascript:;" class="deleteAccount">Slet projekt</a>
				</span>
			</li>
		</ul>
	</div><?php
	
	?><div id="standardPanel">
		<ul id="settingsPanel">
			<li class="title">
				Ret arrangement dage
			</li>
			<?php /* jscript */ ?>
			<li id="showAllSettings"></li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>