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
	
	
	if($participants_count==0){
		echo "Du har ikke tilføjet nogle deltagere endnu.";
	}else{
		echo "<span id='showAmount'>Viser <span id='totalAmount'>".$participants_count."</span> deltager(e)...</span>";
		
		echo "<ul id='standardList'>";
			echo "<li class='listLegend'>";
				echo "<span class='name'>Navn</span>";
				echo "<span class='status'></span>";
				echo "<span class='admin'>Admin</span>";
			echo "</li>";
			while($participant = $participants_db->fetch(PDO::FETCH_ASSOC)){
				echo "<li id='participant_".$participant['participant_id']."'>";
					echo "<span class='main'>";
						echo "<span class='span2input'>";
							echo "<span class='name'>".$participant['participant_name']."</span>";
							echo "<span class='email'>Email: ".$participant['participant_email']."</span>";
						echo "</span>";
						echo "<span class='participantErrmsg' id='".$participant['participant_id']."Errmsg'></span>";
					echo "</span>";
					echo "<span class='status'></span>";
					echo "<span class='edit'>";
						echo "<a href='javascript:;' data-id='".$participant['participant_id']."' class='saveParticipant green hide'>[gem]</a>";
						echo "<a href='javascript:;' data-id='".$participant['participant_id']."' class='annulParticipant blue hide'>[annul]</a>";
						echo "<a href='javascript:;' data-id='".$participant['participant_id']."' class='editParticipant blue'>[ret]</a>";
					echo "</span>";
					echo "<span class='delete'>";
						echo "<a href='javascript:;' data-id='".$participant['participant_id']."' data-name='".$participant['participant_name']."' class='deleteParticipant red' >[X]</a>";
					echo "</span>";
				echo "</li>";
			}
		echo "</ul>";
	
	}

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>