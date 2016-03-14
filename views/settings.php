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
			Your breakfast settings
		</title>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<span class="span2input">
					<span class="name"><?php echo $project_name; ?></span>
				</span>
			</li>
			<li id="subtitle">
				Your breakfast settings
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<ul id="settingsContent">
			<li id="title">
				Advanced admin settings
			</li>
			<li>
				<span class="options">
					<a href="javascript:;" class="saveAccount hide green">Save project name</a>
					<a href="javascript:;" class="editAccount">Edit project name</a>
					<a href="javascript:;" class="logOut">Log out</a>
					<a href="javascript:;" class="deleteAccount">Delete project</a>
				</span>
			</li>
		</ul>
	</div><?php
	
	?><div id="standardPanel">
		<ul id="adminPanel">
			<li id="title">
				Edit breakfast days
			</li>
			<li class="option">
			<form id="editBreakfastWeekdays" action="" method="POST">
				<span class="optionInputs">
					<?php				
					for($i = 0; $i < 7; $i++){
						$weekday = jddayofweek($i, 1);
						$weekday_checked = $project['project_'.strtolower($weekday)];
						if($weekday_checked){$isChecked = "checked";}else{$isChecked = "";}
						
						echo "<span><input name='weekdays[]' value='".strtolower($weekday)."' type='checkbox' ".$isChecked."/> ".$weekday."</span>";
						//echo "<span><input id='".strtolower($weekday)."' class='editWeekdaysStatus' type='checkbox' ".$isChecked."/> ".$weekday."</span>";
					}
					?>
				</span>
				<span class="optionErrmsg" id="weekdaysErrmsg">
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