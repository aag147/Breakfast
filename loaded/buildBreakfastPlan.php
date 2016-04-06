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
	
	
	/***************** UPDATE OLD BREAKFASTS *****************/	
	$participantsBehindCount_db = $conn->prepare("SELECT * FROM 
													(SELECT * FROM breakfast_breakfasts
													 WHERE project_id = :project_id AND breakfast_date < DATE(NOW()) AND breakfast_done = '0') as B
												LEFT JOIN
													breakfast_registrations as R
												ON B.breakfast_id = R.breakfast_id
												LEFT JOIN
													breakfast_participants as P
												ON P.participant_id = R.participant_id
												ORDER BY B.breakfast_id ASC");
	$participantsBehindCount_db->bindParam(':project_id', $cookie_project_id);		
	$participantsBehindCount_db->execute();
	$participantsBehindCount_count = $participantsBehindCount_db->rowCount();


	$update_breakfast = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_done = '1'
										  WHERE project_id = :project_id AND breakfast_date < DATE(NOW()) AND breakfast_date >= DATE(breakfast_created) AND breakfast_done = '0'");
	$update_breakfast->bindParam(':project_id', $cookie_project_id);
	
	
	$update_participant_plus = $conn->prepare("	UPDATE breakfast_participants SET participant_attendance_count = participant_attendance_count + 1
												WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_participant_plus->bindParam(':project_id', $cookie_project_id);
	$update_participant_plus->bindParam(':participant_id', $participant_id);
	
	$update_chef_plus = $conn->prepare("UPDATE breakfast_participants SET participant_lastTime = CURRENT_TIMESTAMP,
										participant_attendance_count = participant_attendance_count + 1, participant_chef_count = participant_chef_count + 1
										WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_chef_plus->bindParam(':project_id', $cookie_project_id);
	$update_chef_plus->bindParam(':participant_id', $participant_id);
	

	// Updating breakfasts
	$update_breakfast->execute();
	
	// Updating participants and chefs
	$current_breakfast = "";
	while($participant = $participantsBehindCount_db->fetch(PDO::FETCH_ASSOC)){
		$participant_id = $participant['participant_id'];
		echo $participant['breakfast_id'];
		echo $participant['breakfast_date'];
		
		// Updating attending participants
		if($participant['participant_attending'] == 1 AND $participant['breakfast_chef'] == $participant_id){
			// The chef
			$update_chef_plus->execute();	
		}elseif($participant['participant_attending'] == 1){
			// The rest
			$update_participant_plus->execute();
		}
	}
	
	// YEARWEEK(breakfast_date, 1) >= YEARWEEK(CURDATE(), 1)
	/***************** PDO PREPARATIONS FOR BREAKFAST PLAN *****************/
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants = $participants_db->fetchAll();
		
	$dynamic_chefs_db = $conn->prepare("SELECT P.*, (case when registration_id is null then 0 else 1 end) as veteran FROM
											(SELECT *
											 FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0') as P
										LEFT JOIN
											(SELECT * FROM breakfast_registrations) as R
										ON P.participant_id = R.participant_id
										GROUP BY P.participant_id
										ORDER BY veteran ASC, participant_lastTime ASC, participant_created ASC
										LIMIT 40");
	$dynamic_chefs_db->bindParam(':project_id', $cookie_project_id);		
	$dynamic_chefs_db->execute();
	$dynamic_chefs_count = $dynamic_chefs_db->rowCount();
	$dynamic_chefs = $dynamic_chefs_db->fetchAll();
	
	$static_chefs_db = $conn->prepare("	SELECT P.*, breakfast_date, '1' as veteran FROM
											(SELECT * FROM breakfast_breakfasts
											 WHERE project_id = :project_id AND breakfast_date >= DATE(NOW()) AND breakfast_chef <> '0') as B
										JOIN
											breakfast_participants as P
										ON B.breakfast_chef = P.participant_id
										ORDER BY breakfast_date ASC");
	$static_chefs_db->bindParam(':project_id', $cookie_project_id);
	$static_chefs_db->execute();
	$static_chefs_count = $static_chefs_db->rowCount();
	$static_chefs = $static_chefs_db->fetchAll();
	
	/***************** DELETE GHOST BREAKFASTS *****************/
	$delete_breakfasts = $conn->prepare("DELETE FROM breakfast_breakfasts WHERE breakfast_asleep = '1'");
	$delete_breakfasts->bindParam(':project_id', $cookie_project_id);
	$delete_breakfasts->execute();
	
	
	// EXTRACT SINGLE ROW
	$participant_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_id = :participant_id LIMIT 1");
	$participant_db->bindParam(':project_id', $cookie_project_id);
	$participant_db->bindParam(':participant_id', $participant_id);
	
	$registration_db = $conn->prepare("SELECT * FROM breakfast_registrations WHERE participant_id = :participant_id AND breakfast_id = :breakfast_id LIMIT 1");
	$registration_db->bindParam(':participant_id', $participant_id);
	$registration_db->bindParam(':breakfast_id', $breakfast_id);
	
	$breakfast_db = $conn->prepare("SELECT * FROM breakfast_breakfasts WHERE breakfast_date = :breakfast_date AND project_id = :project_id LIMIT 1");
	$breakfast_db->bindParam(':project_id', $cookie_project_id);
	$breakfast_db->bindParam(':breakfast_date', $breakfast_date);
	
	//EXTRACT SINGLE BREAKFAST REGISTRATIONS
	$registrations_count_db = $conn->prepare("SELECT COUNT(registration_id) C FROM breakfast_registrations WHERE breakfast_id = :breakfast_id AND participant_attending = '0'");
	$registrations_count_db->bindParam(':breakfast_id', $breakfast_id);	
	
	// ADMINISTRE REGISTRATIONS AND BREAKFAST
	$new_registration = $conn->prepare("INSERT INTO breakfast_registrations (participant_id, project_id, breakfast_id, participant_attending)
									   VALUES (:participant_id, :project_id, :breakfast_id, '1')");
	$new_registration->bindParam(':participant_id', $participant_id);
	$new_registration->bindParam(':project_id', $cookie_project_id);
	$new_registration->bindParam(':breakfast_id', $breakfast_id);
	
	$new_breakfast = $conn->prepare("INSERT INTO breakfast_breakfasts (project_id, breakfast_date, breakfast_weekday, breakfast_chef)
									   VALUES (:project_id, :breakfast_date, :breakfast_weekday, :breakfast_chef)");
	$new_breakfast->bindParam(':project_id', $cookie_project_id);
	$new_breakfast->bindParam(':breakfast_date', $breakfast_date);
	$new_breakfast->bindParam(':breakfast_weekday', $breakfast_weekday);
	$new_breakfast->bindParam(':breakfast_chef', $chef_id);
	
	$change_chef = $conn->prepare("UPDATE breakfast_breakfasts SET 	breakfast_chef = :breakfast_chef
								   WHERE project_id = :project_id AND breakfast_id = :breakfast_id");
	$change_chef->bindParam(':project_id', $cookie_project_id);
	$change_chef->bindParam(':breakfast_id', $breakfast_id);
	$change_chef->bindParam(':breakfast_chef', $chef_id);
	
	
	/***** Counting amount of weekdays with breakfast *****/
	$current_weekday = date("N");
	$weekdays_count = $done_count = 0;
	for($j = 0; $j < 7; $j++){
		$weekday = jddayofweek($j, 1);
		$valid = $project['project_'.strtolower($weekday)];
		$weekdays_count += $valid;
		if($valid AND $j+1 < $current_weekday){$done_count += 1;}
	}
	$static_chefs_max = ($weekdays_count * 3) - $done_count; // Future breakfasts this week and the next two
	
	/***** Create array of ordered chefs *****/
	if($static_chefs_count>$static_chefs_max){
		$static_chefs_id = array_slice($static_chefs, 0, $static_chefs_max);
	}else{
		$static_chefs_id = $static_chefs;
	}
	
	// Extract id column
	$static_chefs_id = array_unique(array_column($static_chefs_id, 'participant_id'));
	$dynamic_chefs_id = array_column($dynamic_chefs, 'participant_id');	
	
	// Computing dynamics
	$dynamic_chefs_id = array_diff($dynamic_chefs_id, $static_chefs_id);	
	
	// Final array of ordered chefs
	$complete_chefs = array_merge($static_chefs_id, $dynamic_chefs_id);
	

	/******* PRINT PLAN *******/
	if(COUNT($complete_chefs)==0 OR $weekdays_count==0){
		echo "You need both participants and active weekdays to build breakfast plan.";
	}else{
		
		$current_week = date("w");
		$current_date = date("Y-m-d");
		$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
		$dynamic_chefs_index = 0;
		echo "<ul>";
		for($i = 0; $i < 6; $i++){
			// New week
			
			/***** DATE *****/
			// week
			$week = date("W", strtotime("+".($i*7)." days"));
			// year
			if($week >= $current_week){$year = date("Y");}
			else{$year = date("Y", strtotime("+ 1 year"));}
			// week title
			if($i==0){$weekShow = "This week";}
			elseif($i==1){$weekShow = "Next week";}
			else{$weekShow = "Week ".$week;}
			
			// WEEK VIEW
			echo "<li class='week' id='week_".$week."'>";
				echo "<span class='weekTitle'>".$weekShow."</span>";
			echo "</li>";
			echo "<li class='weekdays' id='weekdays_".$week."'><ul>";
			
				
				for($j = 0; $j < 7; $j++){
					// New week day
					
					$weekday = jddayofweek($j, 1);
					$breakfast_weekday = strtolower($weekday);
					$weekday_checked = $project['project_'.strtolower($weekday)];
						
					// Full date
					$gendate = new DateTime();
					$gendate->setISODate($year,$week,$j+1); // creates date from week, day, year
					$breakfast_date = $gendate->format('Y-m-d');
					
					// Skip unchecked weekdays for future dates
					if(!$weekday_checked AND $breakfast_date >= $current_date){continue;}
					
					/***** BREAKFAST *****/
					$breakfast_db->execute();
					$hasBreakfast = $breakfast_db->rowCount();
					$breakfast_done = 0;
					if($hasBreakfast){
						$breakfast = $breakfast_db->fetch();
						$breakfast_id = $breakfast['breakfast_id'];
						$breakfast_done = $breakfast['breakfast_done'];
					}
					if($breakfast_done){$doneClass = "done";}else{$doneClass = "";}
					
					// Skip new breakfast for old dates
					if(!$breakfast_done AND $breakfast_date < $current_date){
						continue;
					}

					
					/***** CHEF *****/
					if(!$breakfast_done){
						// Dynamic chef
						while(true){
							$chef_id = $participant_id = $complete_chefs[$dynamic_chefs_index % COUNT($complete_chefs)];
							$participant_db->execute();
							$chef = $participant_db->fetch();
							// Only includes a potential removed participant for todays breakfast
							if($i==0 OR $chef['participant_asleep']==0){break;}
							$dynamic_chefs_index++;
						}
						
						// Oldchef
						if($hasBreakfast AND $breakfast['breakfast_chef'] != 0){
							$hasChef = true;
						}else{
							$hasChef = false;
						}			
						
						// Insert new og update old chef
						if(!$hasChef OR $breakfast['breakfast_chef'] != $chef_id){
							if($hasBreakfast){
								// New chef in existing breakfast
								$change_chef->execute();
							}else{
								// New chef in new breakfast
								$new_breakfast->execute();
								$breakfast_id = $conn->lastInsertId('breakfast_breakfasts');
							}
						}
					
						$dynamic_chefs_index++;
					}else{
						$chef_id = $participant_id = $breakfast['breakfast_chef'];
						$participant_db->execute();
						$chef = $participant_db->fetch();
					}
					
					/***** REGISTRATION COUNT *****/
					$registrations_count_db->execute();
					$registrations_count = $registrations_count_db->fetchColumn();
					$registrations_count = COUNT($participants) - $registrations_count;
											
					/***** VIEW *****/
					view: 
					echo "<li class='weekday ".$doneClass."' id='weekday_".$week.$weekday."'>";
						echo "<a href='javascript:;' class='showParticipants' id='".$week.$weekday."'>";
							echo "<span class='weekdayTitle'>".$weekday."</span>";
							echo "<span class='weekdayDate'>".$gendate->format('d/m/Y')."</span>";
							echo "<span class='weekdayToday'>";
								if($breakfast_date == $current_date){echo "(TODAY)";}
								if($breakfast_date == $tomorrow_date){echo "(TOMORROW)";}
							echo "</span>";
							echo "<span class='theChef'>".$chef['participant_name']."</span>";
						echo "</a>";
					echo "</li>";
					echo "<li class='participants hide' id='participants_".$week.$weekday."'>";
					
						echo "<span class='participantsCount'>".$registrations_count."</span>";
						echo "<span class='participantsTitle'>is coming. But who (besides the host)?</span>";
					
						echo "<ul>";
						foreach($participants as $participant){
							$participant_id = $participant['participant_id'];
							if($participant['participant_name']==$chef['participant_name']){$isChef = 1;}else{$isChef = 0;}
							
							// Get registration info or insert new registration
							$registration_db->execute();
							$isReg = $registration_db->rowCount();
							if($isReg > 0){
								$reg = $registration_db->fetch();
								$attending = $reg['participant_attending'];
								$reg_id = $reg['registration_id'];
							}else{
								$new_registration->execute();
								$attending = 1;
								$reg_id = $conn->lastInsertId('breakfast_registrations');
							}
							if($attending){$isComing = "checked";}else{$isComing = "";}
							
							// Continue for chef
							if($isChef){continue;}

							// Write out participant
							echo "<li id='participant_".$participant_id."'>";
								echo "<span class='status'><input id='".$reg_id."' data-id='".$week.$weekday."' class='editParticipantStatus' type='checkbox' ".$isComing."/></span>";
								echo "<span class='name'>".$participant['participant_name']."</span>";
							echo "</li>";					
						}
						echo "</ul>";
					echo "</li>";
				}
			
			echo "</ul></li>";
		}
		echo "</ul>";
	}
	
	
	// Sending notifications
	$breakfast_date = date("Y-m-d", strtotime("+ 1 day"));
	$breakfast_db->execute();
	$hasBreakfast = $breakfast_db->rowCount();
	if($hasBreakfast){
		$breakfast = $breakfast_db->fetch();
		if($breakfast['breakfast_notified'] == 0){
			// Send notification for tomorrows breakfasts only once
			?>
			<script>
				window.onload = sendNotifications('tomorrow');
			</script>
			<?php
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>