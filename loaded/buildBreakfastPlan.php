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
	

	/***************** "SELECT" PDO PREPARATIONS FOR THE GRAND LOOP *****************/
	// Extract incasso balance between a specific chef and a participant
	$incasso_db = $conn->prepare("SELECT SUM(C) FROM
								  (
									SELECT (CASE WHEN chef_id = :chef_id THEN -1 ELSE 1 END) C FROM
										(SELECT breakfast_id FROM breakfast_breakfasts
										 WHERE project_id = :project_id AND DATE(breakfast_date) < CURDATE()) B
									JOIN
										(SELECT breakfast_id, chef_id FROM breakfast_chefs
										 WHERE chef_id = :chef_id AND chef_replacement_id = :participant_id OR 
											   chef_id = :participant_id AND chef_replacement_id = :chef_id) C
									ON B.breakfast_id = C.breakfast_id
								  ) AS CC");
	$incasso_db->bindParam(':project_id', $cookie_project_id);
	$incasso_db->bindParam(':chef_id', $chef_id);
	$incasso_db->bindParam(':participant_id', $participant_id);

	
	// Extract all chefs at a specific breakfast
	$breakfast_chefs_db = $conn->prepare("SELECT participant_id, participant_name, chef_replacement_id FROM 
											(SELECT * FROM breakfast_chefs WHERE project_id = :project_id AND breakfast_id = :breakfast_id) C
										  JOIN
											breakfast_participants
										  ON chef_id = participant_id");
	$breakfast_chefs_db->bindParam(':project_id', $cookie_project_id);
	$breakfast_chefs_db->bindParam(':breakfast_id', $breakfast_id);	
	
	
	// Extract single participant
	$participant_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_id = :participant_id LIMIT 1");
	$participant_db->bindParam(':project_id', $cookie_project_id);
	$participant_db->bindParam(':participant_id', $participant_id);
	
	// Extract single registration
	$registration_db = $conn->prepare("SELECT * FROM breakfast_registrations WHERE participant_id = :participant_id AND breakfast_id = :breakfast_id LIMIT 1");
	$registration_db->bindParam(':participant_id', $participant_id);
	$registration_db->bindParam(':breakfast_id', $breakfast_id);
	
	// Extract single breakfast
	$breakfast_db = $conn->prepare("SELECT * FROM breakfast_breakfasts WHERE breakfast_date = :breakfast_date AND project_id = :project_id LIMIT 1");
	$breakfast_db->bindParam(':project_id', $cookie_project_id);
	$breakfast_db->bindParam(':breakfast_date', $breakfast_date);
	
	// Extract single chef
	$chef_db = $conn->prepare(" SELECT participant_id, participant_name, chef_replacement_id FROM
									(SELECT * FROM breakfast_chefs WHERE project_id = :project_id AND chef_id = :chef_id) C
								JOIN
									breakfast_participants
								ON chef_id = participant_id LIMIT 1");
	$chef_db->bindParam(':project_id', $cookie_project_id);
	$chef_db->bindParam(':chef_id', $chef_id);
	
	// Extract registration count for single breakfast
	$registrations_count_db = $conn->prepare("SELECT COUNT(registration_id) C FROM breakfast_registrations WHERE breakfast_id = :breakfast_id AND participant_attending = '0'");
	$registrations_count_db->bindParam(':breakfast_id', $breakfast_id);	
	
	
	
	/***************** "ADMIN" PDO PREPARATIONS FOR THE GRAND LOOP *****************/
	// Insert new registration
	$new_registration = $conn->prepare("INSERT INTO breakfast_registrations (participant_id, project_id, breakfast_id, participant_attending)
									   VALUES (:participant_id, :project_id, :breakfast_id, '1')");
	$new_registration->bindParam(':participant_id', $participant_id);
	$new_registration->bindParam(':project_id', $cookie_project_id);
	$new_registration->bindParam(':breakfast_id', $breakfast_id);
	
	// Insert new breakfast
	$new_breakfast = $conn->prepare("INSERT INTO breakfast_breakfasts (project_id, breakfast_date, breakfast_weekday)
									   VALUES (:project_id, :breakfast_date, :breakfast_weekday)");
	$new_breakfast->bindParam(':project_id', $cookie_project_id);
	$new_breakfast->bindParam(':breakfast_date', $breakfast_date);
	$new_breakfast->bindParam(':breakfast_weekday', $breakfast_weekday);
	
	// Insert new chef
	$new_chef = $conn->prepare("INSERT INTO breakfast_chefs (project_id, breakfast_id, chef_id)
								   VALUES (:project_id, :breakfast_id, :chef_id)");
	$new_chef->bindParam(':project_id', $cookie_project_id);
	$new_chef->bindParam(':breakfast_id', $breakfast_id);
	$new_chef->bindParam(':chef_id', $chef_id);
	
	// Delete new chef
	$delete_chef = $conn->prepare("DELETE FROM breakfast_chefs
								   WHERE project_id = :project_id AND breakfast_id = :breakfast_id AND chef_id = :chef_id");
	$delete_chef->bindParam(':project_id', $cookie_project_id);
	$delete_chef->bindParam(':breakfast_id', $breakfast_id);
	$delete_chef->bindParam(':chef_id', $chef_id);
	
	// Update single chef replacement to limbo
	$limbo_replacement = $conn->prepare("UPDATE breakfast_chefs SET chef_replacement_id = '-1'
										 WHERE project_id = :project_id AND breakfast_id = :breakfast_id AND chef_id = :chef_id");
	$limbo_replacement->bindParam(':project_id', $cookie_project_id);
	$limbo_replacement->bindParam(':breakfast_id', $breakfast_id);
	$limbo_replacement->bindParam(':chef_id', $chef_id);
	
	
	
	/***************** UPDATE OLD BREAKFASTS *****************/	
	// Extract registrations to completed breakfasts
	$participantsBehindCount_db = $conn->prepare("SELECT B.breakfast_id, P.participant_id, R.participant_attending FROM 
													(SELECT breakfast_id FROM breakfast_breakfasts
													 WHERE project_id = :project_id AND breakfast_date < CURDATE() AND breakfast_done = '0') as B
												LEFT JOIN
													(SELECT * FROM breakfast_registrations
													 WHERE participant_attending = '1') as R
												ON B.breakfast_id = R.breakfast_id
												LEFT JOIN
													breakfast_participants as P
												ON P.participant_id = R.participant_id
												ORDER BY B.breakfast_id ASC");
	$participantsBehindCount_db->bindParam(':project_id', $cookie_project_id);		
	$participantsBehindCount_db->execute();
	$participantsBehindCount_count = $participantsBehindCount_db->rowCount();
	$participantsBehindCount = $participantsBehindCount_db->fetchAll();

	// Update single breakfast as DONE
	$update_breakfast = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_done = '1'
										  WHERE project_id = :project_id AND breakfast_date < CURDATE() AND breakfast_date >= DATE(breakfast_created) AND breakfast_done = '0'");
	$update_breakfast->bindParam(':project_id', $cookie_project_id);
	
	// Increment attendance count for single participant
	$update_participant_plus = $conn->prepare("	UPDATE breakfast_participants SET participant_attendance_count = participant_attendance_count + 1
												WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_participant_plus->bindParam(':project_id', $cookie_project_id);
	$update_participant_plus->bindParam(':participant_id', $participant_id);
	
	// Increment attendance count and chef count for single participant
	$update_chef_plus = $conn->prepare("UPDATE breakfast_participants SET participant_lastTime = CURDATE(),
										participant_attendance_count = participant_attendance_count + 1, participant_chef_count = participant_chef_count + 1
										WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_chef_plus->bindParam(':project_id', $cookie_project_id);
	$update_chef_plus->bindParam(':participant_id', $participant_id);
	

	// Updating breakfasts
	$update_breakfast->execute();
	
	// Updating participants and chefs
	$breakfast_id = 0;
	foreach($participantsBehindCount AS $participant){
		$participant_id = $participant['participant_id'];
		// Updates breakfast chefs
		if($participant['breakfast_id'] != $breakfast_id){
			$breakfast_id = $participant['breakfast_id'];
			$breakfast_chefs_db->execute();
			$breakfast_chefs = array_column($breakfast_chefs_db->fetchAll(), 'participant_id');
		}
		
		// Updating attending participants
		if(in_array($participant_id, $breakfast_chefs)){
			// The chefs
			$update_chef_plus->execute();
		}else{
			// The guests
			$update_participant_plus->execute();
		}
	}
	
	/***************** PARTICIPANTS AND CHEFS *****************/
	// Extract all participants
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_asleep = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants = $participants_db->fetchAll();
	
	// Extract all participants ordered by seniority creating a dynamic order of new chefs
	$dynamic_chefs_db = $conn->prepare("SELECT participant_id FROM breakfast_participants
										WHERE project_id = :project_id AND participant_asleep = '0'
										ORDER BY participant_lastTime ASC, participant_created ASC
										LIMIT 130");
	$dynamic_chefs_db->bindParam(':project_id', $cookie_project_id);		
	$dynamic_chefs_db->execute();
	$dynamic_chefs_count = $dynamic_chefs_db->rowCount();
	$dynamic_chefs = $dynamic_chefs_db->fetchAll();
	
	// Extract the ordered list of current chefs as static chefs
	$static_chefs_db = $conn->prepare("	SELECT C.chef_id FROM
											(SELECT * FROM breakfast_breakfasts
											 WHERE project_id = :project_id AND breakfast_date >= CURDATE()) as B
										JOIN
											breakfast_chefs C
										ON B.breakfast_id = C.breakfast_id
										ORDER BY breakfast_date ASC, C.rel_id ASC
										LIMIT 65");
	$static_chefs_db->bindParam(':project_id', $cookie_project_id);
	$static_chefs_db->execute();
	$static_chefs_count = $static_chefs_db->rowCount();
	$static_chefs = $static_chefs_db->fetchAll();
	
	
	/***************** DELETE GHOST BREAKFASTS *****************/
	// This have to be done after extracting static chefs
	$delete_breakfasts = $conn->prepare("DELETE FROM breakfast_breakfasts WHERE breakfast_asleep = '1'");
	$delete_breakfasts->bindParam(':project_id', $cookie_project_id);
	$delete_breakfasts->execute();
	
	
	/***************** BUILDING ARRAY OF ORDERED CHEFS *****************/
	// Counting a single weeks total breakfasts and chefs
	$current_weekday = date("N");
	$weekdays_count = $chefs_count = $done_count = 0;
	for($j = 0; $j < 7; $j++){
		$weekday = jddayofweek($j, 1);
		$check = $options[strtolower($weekday).'_checked'];
		$chefs = $options[strtolower($weekday).'_chefs'];
		$weekdays_count += $check;
		$chefs_count += $chefs;
		if($check AND $j+1 < $current_weekday){$done_count += $chefs;}
	}
	
	// Amount of future chefs the first three weeks
	$static_chefs_max = ($chefs_count * 3) - $done_count;
	
	// Computing / limiting static chefs
	$static_chefs_count = min($static_chefs_count, $static_chefs_max);
	$static_chefs_id = array_column($static_chefs, 'chef_id');
	$static_chefs_id = array_unique(array_slice($static_chefs_id, 0, $static_chefs_count));	
	
	// Computing / limiting dynamic chefs
	$dynamic_chefs_id = array_column($dynamic_chefs, 'participant_id');
	$dynamic_chefs_id = array_diff($dynamic_chefs_id, $static_chefs_id);
	
	// Final array of ordered chefs
	$complete_chefs = array_merge($static_chefs_id, $dynamic_chefs_id); 	
	
	
	/****************** LOOP THROUGH WEEKS-WEEKDAYS IN PLAN ******************/
	if(COUNT($complete_chefs)==0 OR $weekdays_count==0){
		echo "Du behøver både deltagere og aktive ugedage for at bygge morgenmadsplanen.";
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
			if($i==0){$weekShow = "Denne uge";}
			elseif($i==1){$weekShow = "Næste uge";}
			else{$weekShow = "Uge ".$week;}
			
			// WEEK VIEW
			echo "<li class='week' id='week_".$week."'>";
				echo "<span class='weekTitle'>".$weekShow."</span>";
			echo "</li>";
			echo "<li class='weekdays' id='weekdays_".$week."'><ul>";
			
				for($j = 0; $j < 7; $j++){
					// New week day
	
					/***** SETUP *****/
					$weekday = jddayofweek($j, 1);
					$breakfast_weekday = strtolower($weekday);
					$weekday_checked = $options[strtolower($weekday).'_checked'];
					$weekday_chefs_count = $options[strtolower($weekday).'_chefs'];
					
					// Full date
					$gendate = new DateTime();
					$gendate->setISODate($year,$week,$j+1); // creates date from week, day, year
					$breakfast_date = $gendate->format('Y-m-d');
					
					// Skip unchecked weekdays for future dates
					if(!$weekday_checked AND $breakfast_date >= $current_date){continue;}
					
					/***** BREAKFAST *****/
					$breakfast_db->execute();
					$hasBreakfast = $breakfast_db->rowCount();
					if($hasBreakfast){
						// Retrieves existing breakfast
						$breakfast = $breakfast_db->fetch();
						$breakfast_id = $breakfast['breakfast_id'];
						$breakfast_done = $breakfast['breakfast_done'];
						// The breakfast chefs
						$breakfast_chefs_db->execute();
						$breakfast_chefs_count = $breakfast_chefs_db->rowCount();
						$old_breakfast_chefs = $breakfast_chefs_db->fetchAll();
						$old_breakfast_chefs_id = array_column($old_breakfast_chefs, 'participant_id');	
					}else{
						// Skip new breakfast for old dates
						if($breakfast_date < $current_date){
							continue;
						}						
						// Creates new breakfast
						$new_breakfast->execute();
						$breakfast_id = $conn->lastInsertId('breakfast_breakfasts');						
						$breakfast_done = 0;
					}
					
					// Don't edit chef amount for older dates nor today
					if($hasBreakfast AND ($breakfast_done OR $breakfast_date == $current_date)){
						$weekday_chefs_count = $breakfast_chefs_count;
					}


					/***** ORIGINAL CHEFS *****/					
					if(!$breakfast_done){
						$breakfast_chefs = array();
						$breakfast_chefs_id = array();
						for($k = 0; $k < $weekday_chefs_count; $k++){
	
							// Dynamic chef
							while(true){
								$chef_id = $participant_id = $complete_chefs[$dynamic_chefs_index % COUNT($complete_chefs)];
								$participant_db->execute();
								$chef = $participant_db->fetch();
								
								// Only includes a potential removed participant for todays breakfast
								if($i==0 OR $chef['participant_asleep']==0){break;}
								$dynamic_chefs_index++;
							}
							
							// Check for existing chef
							if($hasBreakfast AND in_array($chef_id, $old_breakfast_chefs_id)){
								$index = array_search($chef_id, $old_breakfast_chefs_id);
								$chef = $old_breakfast_chefs[$index];
							}else{
								// Insert new chef
								$new_chef->execute();
								
								// Get chef info
								$chef_db->execute();
								$chef = $chef_db->fetch();
							}

							$dynamic_chefs_index++;
							
							array_push($breakfast_chefs, $chef);
							array_push($breakfast_chefs_id, $chef_id);
						}						
					}else{
						$breakfast_chefs = $old_breakfast_chefs;
						$breakfast_chefs_id = $old_breakfast_chefs_id;
					}
					
					/***** DELETE OLD CHEFS *****/					
					if($hasBreakfast){
						$old_breakfast_chefs = array_diff($old_breakfast_chefs_id, $breakfast_chefs_id);
						array_push($old_breakfast_chefs, 0);
						foreach($old_breakfast_chefs as $chef_id){
							$delete_chef->execute();
						}
					}
					
					/***** REPLACEMENT CHEF *****/
					$breakfast_chef_replacements = array();
					$breakfast_chef_replacements_id = array();
					foreach($breakfast_chefs as $chef){
						$chef_replacement_id = $chef['chef_replacement_id'];
						if(in_array($chef_replacement_id, $breakfast_chefs_id)){
							// Remove dated replacement
							$chef_id = $chef['participant_id'];
							$limbo_replacement->execute();
							$chef['chef_replacement_id'] = -1;
							array_push($breakfast_chef_replacements, array());
							array_push($breakfast_chef_replacements_id, -1);
						}elseif(!empty($chef_replacement_id)){
							// Add current replacement
							$participant_id = $chef_replacement_id;
							$participant_db->execute();
							$chef_replacement = $participant_db->fetch();
							array_push($breakfast_chef_replacements, $chef_replacement);
							array_push($breakfast_chef_replacements_id, $chef_replacement_id);
						}else{
							// No current replacement
							array_push($breakfast_chef_replacements, $chef);
							array_push($breakfast_chef_replacements_id, $chef['participant_id']);
						}
					}
					
					/***** REGISTRATION COUNT *****/
					$registrations_count_db->execute();
					$registrations_count = $registrations_count_db->fetchColumn();
					$registrations_count = COUNT($participants) - $registrations_count;
								
					/**** Specials for done breakfasts ****/
					if($breakfast_done){$doneClass = "done"; $doneDisabled = "disabled";}
					else{$doneClass = ""; $doneDisabled = "";}

								
					/***** VIEW *****/
					echo "<li class='weekday ".$doneClass."' id='breakfast_".$breakfast_id."'>";
						echo "<a href='javascript:;' class='showParticipants' data-id='".$breakfast_id."'>";
							echo "<span class='weekdayTitle'>".$weekdays_danish[$j]."</span>";
							echo "<span class='weekdayDate'>".$gendate->format('d/m/Y')."</span>";
							echo "<span class='weekdayToday'>";
								if($breakfast_date == $current_date){echo "(I dag)";}
								if($breakfast_date == $tomorrow_date){echo "(I morgen)";}
								if($breakfast_date < $current_date){echo "(Gennemført)";}
							echo "</span>";
							echo "<span class='theChefs'>";
								for($k = 0; $k < $weekday_chefs_count; $k++){
									if($breakfast_chef_replacements_id[$k]==-1){$limboClass = "limbo"; $chef_name = "Limbo";}
									else{$limboClass = ""; $chef_name = $breakfast_chef_replacements[$k]['participant_name'];}
									echo "<span class='chef_".$breakfast_chefs_id[$k]." ".$limboClass."'>".$chef_name."</span>";
								}
							echo "</span>";
						echo "</a>";
					echo "</li>";
					echo "<li class='participants hide' id='participants_".$breakfast_id."'>";
						echo "<ul class='allNewChefs'>";
							echo "<li class='newChefTitle'>Skift vært:</li>";						
							for($k = 0; $k < $weekday_chefs_count; $k++){
								echo "<li class='newChefs' id='changeChef_".$breakfast_id.$breakfast_chefs_id[$k]."'>";
									echo "<select class='newChefSelect' data-breakfast_id='".$breakfast_id."' data-original='".$breakfast_chefs_id[$k]."' ".$doneDisabled.">";
										if($breakfast_chef_replacements_id[$k] == -1){$selected = "selected";}
										else{$selected = "";}
										echo "<option value='0'>".$breakfast_chefs[$k]['participant_name']." (original)</option>";
										echo "<option value='-1' id='limbo' ".$selected.">Limbo</option>";
										foreach($participants as $participant){
											$chef_id = $breakfast_chefs_id[$k];
											$participant_id = $participant['participant_id'];
											if(in_array($participant_id, $breakfast_chefs_id)){continue;}
											
											// Get balance to chef
											$incasso_db->execute();
											$incasso = $incasso_db->fetchColumn();
											if(empty($incasso)){$incasso = 0;}
											
											// Is replacement chef?
											if($participant_id == $breakfast_chef_replacements_id[$k]){$selected = "selected";}
											else{$selected = "";}
											
											// Is replacement chef for other chef?
											if(in_array($participant_id, $breakfast_chef_replacements_id) AND $participant_id != $breakfast_chef_replacements_id[$k]){
												$disabled = "disabled";
											}else{$disabled = "";}
											
											$participant_name = substr($participant['participant_name'], 0, 30);
											if(strlen($participant['participant_name']) > 30){$participant_name .= "...";}
											
											echo "<option class='option_".$participant_id."' value='".$participant_id."' ".$selected." ".$disabled.">".$participant_name." (".$incasso.")</option>";
										}
									echo "</select>";
								echo "</li>";
							}
						echo "</ul>";
						
						echo "<span class='participantsCount'>".$registrations_count."</span>";
						echo "<span class='participantsTitle'>kommer. Men hvem (foruden værten)?</span>";
					
						echo "<ul class='attending'>";
						foreach($participants as $participant){
							$participant_id = $participant['participant_id'];
							if(in_array($participant_id, $breakfast_chef_replacements_id)){$isChef = 1;}else{$isChef = 0;}
							
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
							
							// Hide chef
							if($isChef){$hide = "class='hide'";}
							else{$hide = "";}

							$participant_name = $participant['participant_name'];
							
							// Write out participant
							echo "<li id='participant_".$participant_id."' ".$hide.">";
								echo "<span class='status'><input id='".$reg_id."' data-breakfast_id='".$breakfast_id."' class='editParticipantStatus' type='checkbox' ".$doneDisabled." ".$isComing."/></span>";
								echo "<span class='name'>".$participant_name."</span>";
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
				window.onload = sendNotifications(new FormData(), 'tomorrow');
			</script>
			<?php
		}
	}

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
?>