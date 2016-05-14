<?php
// AJAX SECURITY CHECK
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if(!IS_AJAX) {die('Restricted access');}
$pos = strpos($_SERVER['HTTP_REFERER'],getenv('HTTP_HOST'));
if($pos===false)
  die('Restricted access');

// FIRST HEADER		
require('../headers/setup.php');

// LOGGED IN CHECK
if(empty($cookie_project_id)){exit;}

Header('Content-Type:text/html; charset=utf-8');
/*************** AJAX ***************/

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants_count = $participants_db->rowCount();
	
	echo "<ul id='settingsPanelContent'><li>";
	echo "<form id='editBreakfastWeekdays' action='' method='POST'>";
		echo "<ul class='optionLegend'>";
			echo "<li>";
				echo "<span>Valgte dage</span>";
				echo "<span>Antal værter</span>";
			echo "</li>";
		echo "</ul>";
		echo "<ul class='optionInputs'>";
			$max_chefs = min(3, $participants_count);
			echo "<li>";
				echo "<span><input class='checkAll' value='0' type='checkbox' /> Alle dage</span>";
				echo "<span><input class='chefsAll' type='number' min='1' max='".$max_chefs."'/></span>";
			echo "</li>";
			for($i = 0; $i < 7; $i++){
				$weekday = jddayofweek($i, 1);
				$weekday_checked = $options[strtolower($weekday).'_checked'];
				$weekday_chefs = $options[strtolower($weekday).'_chefs'];
				if($weekday_checked){$isChecked = "checked";}else{$isChecked = "";}
				if($weekday_checked){$isDisabled = "";}else{$isDisabled = "disabled";}
				
				echo "<li>";
					echo "<span><input class='weekdayChecked' data-id='".$weekday."' name='weekdays[]' value='".strtolower($weekday)."' type='checkbox' ".$isChecked."/> ".$weekdays_danish[$i]."</span>";
					echo "<span><input class='weekdayChefs' id='".$weekday."_disabled' name='chefs_".$i."' type='number' min='1' max='".$max_chefs."' value='".$weekday_chefs."' ".$isDisabled."/></span>";
				echo "</li>";
			}
		echo "</ul>";
		echo "<span class='optionErrmsg' id='weekdaysErrmsg'>";
		echo "</span>";
		echo "<span class='optionSubmit'>";
			echo "<input type='submit' value='Gem'/>";
		echo "</span>";
	echo "</form>";
	echo "</li></ul>";

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>