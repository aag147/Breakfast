<?php
include("../headers/setup.php");
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	
	/***************** UPDATE OLD BREAKFASTS *****************/
	$update_breakfast = $conn->prepare("UPDATE breakfast_breakfasts SET breakfast_done = '1'
										  WHERE project_id = :project_id AND breakfast_id = :breakfast_id");
	$update_breakfast->bindParam(':project_id', $cookie_project_id);
	$update_breakfast->bindParam(':breakfast_id', $breakfast_id);
	
	$update_participant_plus = $conn->prepare("	UPDATE breakfast_participants SET participant_attendance_count = participant_attendance_count + 1
												WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_participant_plus->bindParam(':project_id', $cookie_project_id);
	$update_participant_plus->bindParam(':participant_id', $participant_id);
	
	$update_chef_plus = $conn->prepare("UPDATE breakfast_participants SET participant_lastTime = CURRENT_TIMESTAMP,
										participant_attendance_count = participant_attendance_count + 1, participant_chef_count = participant_chef_count + 1
										WHERE project_id = :project_id AND participant_id = :participant_id");
	$update_chef_plus->bindParam(':project_id', $cookie_project_id);
	$update_chef_plus->bindParam(':participant_id', $participant_id);
	
	
	$participantsBehindCount_db = $conn->prepare("SELECT * FROM 
													(SELECT * FROM breakfast_breakfasts
													 WHERE project_id = :project_id AND breakfast_date < NOW() AND breakfast_done = '0') as B
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
	
	// Updating participants and chefs
	$current_breakfast = "";
	while($participant = $participantsBehindCount_db->fetch(PDO::FETCH_ASSOC)){
		$participant_id = $participant['participant_id'];
		$breakfast_id = $participant['breakfast_id'];
		
		// Set breakfast to done
		if($breakfast_id != $current_breakfast){
			$update_breakfast->execute();
			$current_breakfast = $breakfast_id;
		}
		
		// Updating attending participants
		if($participant['participant_attending'] == 1 AND $participant['breakfast_chef'] == $participant_id){
			// The chef
			$update_chef_plus->execute();	
		}elseif($participant['participant_attending'] == 1){
			// The rest
			$update_participant_plus->execute();
		}
	}
	
	
	/***************** PDO PREPARATIONS FOR BREAKFAST PLAN *****************/	
	$products_db = $conn->prepare("SELECT * FROM breakfast_products WHERE project_id = :project_id AND product_status = 0 ORDER BY product_name ASC");
	$products_db->bindParam(':project_id', $cookie_project_id);		
	$products_db->execute();
	
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_removed = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants = $participants_db->fetchAll();
		
	$dynamic_chefs_db = $conn->prepare("SELECT P.*, (case when registration_id is null then 0 else 1 end) as veteran FROM
											(SELECT *
											 FROM breakfast_participants WHERE project_id = :project_id AND participant_removed = '0') as P
										LEFT JOIN
											(SELECT * FROM breakfast_registrations) as R
										ON P.participant_id = R.participant_id
										GROUP BY P.participant_id
										ORDER BY veteran ASC, participant_lastTime ASC, participant_created DESC
										LIMIT 15");
	$dynamic_chefs_db->bindParam(':project_id', $cookie_project_id);		
	$dynamic_chefs_db->execute();
	$dynamic_chefs_count = $dynamic_chefs_db->rowCount();
	$dynamic_chefs = $dynamic_chefs_db->fetchAll();
	
	$static_chefs_db = $conn->prepare("	SELECT P.*, '1' as veteran FROM
											(SELECT * FROM breakfast_breakfasts
											 WHERE project_id = :project_id AND YEARWEEK(breakfast_date, 1) >= YEARWEEK(CURDATE(), 1) AND breakfast_chef <> '0'
											 ORDER BY breakfast_date ASC) as B
										JOIN
											breakfast_participants as P
										ON B.breakfast_chef = P.participant_id");
	$static_chefs_db->bindParam(':project_id', $cookie_project_id);
	$static_chefs_db->execute();
	$static_chefs_count = $static_chefs_db->rowCount();
	$static_chefs = $static_chefs_db->fetchAll();
	
	$breakfasts_db = $conn->prepare("SELECT * FROM breakfast_breakfasts
									 WHERE project_id = :project_id AND YEARWEEK(breakfast_date, 1) >= YEARWEEK(CURDATE(), 1)
									 ORDER BY breakfast_date ASC LIMIT 15");
	$breakfasts_db->bindParam(':project_id', $cookie_project_id);
	$breakfasts_db->execute();
	$breakfasts_count = $breakfasts_db->rowCount();
	$breakfasts = $breakfasts_db->fetchAll();
	
	// EXTRACT SINGLE ROW
	$participant_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_id = :participant_id");
	$participant_db->bindParam(':project_id', $cookie_project_id);
	$participant_db->bindParam(':participant_id', $participant_id);
	
	$registration_db = $conn->prepare("SELECT * FROM breakfast_registrations WHERE participant_id = :participant_id AND breakfast_id = :breakfast_id");
	$registration_db->bindParam(':participant_id', $participant_id);
	$registration_db->bindParam(':breakfast_id', $breakfast_id);
	
	// ADMINISTRE REGISTRATIONS AND BREAKFAST
	$new_registration = $conn->prepare("INSERT INTO breakfast_registrations (participant_id, breakfast_id, participant_attending)
									   VALUES (:participant_id, :breakfast_id, '1')");
	$new_registration->bindParam(':participant_id', $participant_id);
	$new_registration->bindParam(':breakfast_id', $breakfast_id);
	
	$new_breakfast = $conn->prepare("INSERT INTO breakfast_breakfasts (project_id, breakfast_date, breakfast_chef)
									   VALUES (:project_id, :breakfast_date, :breakfast_chef)");
	$new_breakfast->bindParam(':project_id', $cookie_project_id);
	$new_breakfast->bindParam(':breakfast_date', $breakfast_date);
	$new_breakfast->bindParam(':breakfast_chef', $chef_id);
	
	$change_chef = $conn->prepare("UPDATE breakfast_breakfasts SET 	breakfast_chef = :breakfast_chef
								   WHERE project_id = :project_id AND breakfast_id = :breakfast_id");
	$change_chef->bindParam(':project_id', $cookie_project_id);
	$change_chef->bindParam(':breakfast_id', $breakfast_id);
	$change_chef->bindParam(':breakfast_chef', $chef_id);
	
	
	/***** Create array of ordered chefs *****/
	if($static_chefs_count>3){
		$statics = array_slice($static_chefs, 0, 3);
	}else{
		$statics = $static_chefs;
	}
	
	// Extract id column
	$statics = array_unique(array_column($statics, 'participant_id'));
	$dynamics = array_column($dynamic_chefs, 'participant_id');	
	
	// Computing dynamics
	$dynamics = array_diff($dynamics, $statics);	
	
	// Final array of ordered chefs
	$complete_chefs = array_merge($statics, $dynamics);
	
?>
	<head>
		<title>
			Your breakfast plan
		</title>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Your breakfast plan
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<ul id="projectContent">
			<li id="title">
				Forthcoming breakfasts
			</li>
			<?php			
			$current_week = date("w");
			$dynamic_chefs_index = 0;
			for($i = 0; $i < 10; $i++){
				
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
				// Full date
				$gendate = new DateTime();
				$gendate->setISODate($year,$week,5); // creates date from week, day, year
				$breakfast_date = $gendate->format('Y-m-d');
				
				/***** BREAKFAST *****/
				if(COUNT($breakfasts) > $i){
					$hasBreakfast = true;
					$breakfast = $breakfasts[$i];
					$breakfast_id = $breakfast['breakfast_id'];
				}else{
					$hasBreakfast = false;
				}
				
				/***** CHEF *****/
				// Dynamic chef
				while(true){
					$chef_id = $participant_id = $complete_chefs[$dynamic_chefs_index % COUNT($complete_chefs)];
					$participant_db->execute();
					$chef = $participant_db->fetch();
					if($i==0 OR $chef['participant_removed']==0){break;}
					$dynamic_chefs_index++;
				}
				
				// Oldchef
				if($hasBreakfast AND $breakfast['breakfast_chef'] != 0){
					$hasChef = true;
				}else{
					$hasChef = false;
				}
				
				if($hasChef AND $breakfast['breakfast_chef'] != $chef_id AND strtotime($breakfast['breakfast_date']) <= time()){
					$chef['participant_name'] = "Unknown";
					goto view;
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
										
				/***** VIEW *****/
				view: 
				echo "<li class='week' id='week_".$week."'>";
					echo "<a href='javascript:;' class='showParticipants' id='".$week."'>";
						echo "<span class='weekTitle'>".$weekShow."</span>";
						echo "<span class='theChef'>".$chef['participant_name']."</span>";
					echo "</a>";
				echo "</li>";
				echo "<li class='participants hide' id='participants_".$week."'>";
				
					echo "<span class='participantsTitle'>Who else is coming?</span>";
				
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
							echo "<span class='status'><input id='".$reg_id."' class='editParticipantStatus' type='checkbox' ".$isComing."/></span>";
							echo "<span class='name'>".$participant['participant_name']."</span>";
						echo "</li>";					
					}
					echo "</ul>";
				echo "</li>";
			}				
			
			?>
		</ul>
	</div><?php
	?><div id="standardPanel">
		<ul id="projectPanel" >
			<li id="title">
				What to buy
			</li>
			<?php
			while($product = $products_db->fetch(PDO::FETCH_ASSOC)){
				if($product['product_status']){$inStore = "checked";}else{$inStore = "";}
				echo "<li id='product_".$product['product_id']."'>";
					echo "<span class='status'><input id='".$product['product_id']."' class='removeProductStatus' type='checkbox' ".$inStore."/></span>";
					echo "<span class='name'>".$product['product_name']."</span>";
				echo "</li>";
			}
			?>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>