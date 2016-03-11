<?php
include("../headers/setup.php");
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$products_db = $conn->prepare("SELECT * FROM breakfast_products WHERE project_id = :project_id AND product_status = 0 ORDER BY product_name ASC");
	$products_db->bindParam(':project_id', $cookie_project_id);		
	$products_db->execute();
	
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();
	$participants = $participants_db->fetchAll();
	
	$chefs_db = $conn->prepare("SELECT *, (case when participant_created > NOW() - INTERVAL 3 MONTH then 0 else 1 end) as veteran
								FROM breakfast_participants WHERE project_id = :project_id ORDER BY participant_lastTime ASC");
	$chefs_db->bindParam(':project_id', $cookie_project_id);		
	$chefs_db->execute();
	$chefs_count = $chefs_db->rowCount();
	$chefs = $chefs_db->fetchAll();
	
	$registration_db = $conn->prepare("SELECT * FROM breakfast_registrations WHERE project_id = :project_id AND participant_id = :participant_id AND breakfast_date = :breakfast_date");
	$registration_db->bindParam(':project_id', $cookie_project_id);		
	$registration_db->bindParam(':participant_id', $participant_id);
	$registration_db->bindParam(':breakfast_date', $breakfast_date);

	$specificChef_db = $conn->prepare("SELECT * FROM 
											(SELECT * FROM breakfast_registrations 
											 WHERE project_id = :project_id AND participant_chef = '1' AND breakfast_date = :breakfast_date) as C
										LEFT JOIN
											breakfast_participants as P
										ON P.participant_id = C.participant_id");
	$specificChef_db->bindParam(':project_id', $cookie_project_id);		
	$specificChef_db->bindParam(':breakfast_date', $breakfast_date);
	
	$new_registration = $conn->prepare("INSERT INTO breakfast_registrations (project_id, participant_id, breakfast_date, participant_chef, participant_attending)
									   VALUES (:project_id, :participant_id, :breakfast_date, :participant_chef, '1')");
	$new_registration->bindParam(':project_id', $cookie_project_id);
	$new_registration->bindParam(':participant_id', $participant_id);
	$new_registration->bindParam(':breakfast_date', $breakfast_date);
	$new_registration->bindParam(':participant_chef', $participant_chef);
	
	$change_chef = $conn->prepare("UPDATE breakfast_registrations SET participant_chef = :participant_chef
								   WHERE project_id = :project_id AND participant_id = :participant_id AND breakfast_date = :breakfast_date");
	$change_chef->bindParam(':project_id', $cookie_project_id);
	$change_chef->bindParam(':participant_id', $participant_id);
	$change_chef->bindParam(':breakfast_date', $breakfast_date);
	$change_chef->bindParam(':participant_chef', $participant_chef);
	
?>
	<head>
		<title>
			Your breakfast project
		</title>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Your breakfast project
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
			$specific_chefs = array();
			$dynamicChefIndex = 0;
			$dynamicChefCount = 0;
			
			for($i = 0; $i < 10; $i++){
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
				
				// The chef
				$specificChef_count = 0;
				
				// Checking for static chef
				$specificChef_db->execute();
				$specificChef_count = $chefs_db->rowCount();	
				
				// Count of specific chefs
				$specific_chefs_count = count($specific_chefs);
				
				if($specificChef_count > 0 AND $i<5){
					// Choosing static chef
					$chef = $specificChef_db->fetch();
					array_push($specific_chefs, $chef['participant_name']);
				}else{
					// Choosing next dynamic chef
					$chef = $chefs[$dynamicChefIndex%$chefs_count];	
					
					// Skipping the static chefs
					while($dynamicChefCount < $specific_chefs_count){	
						if(!in_array($chef['participant_name'], $specific_chefs)){break;}
						$dynamicChefIndex++;
						$dynamicChefCount++;
						$chef = $chefs[$dynamicChefIndex%$chefs_count];
					}					
					$dynamicChefIndex++;
				}
				
				
				// Updating new chefs
				if($i>=$specific_chefs_count){
					if($specificChef_count > 0){
						$specificChef = $specificChef_db->fetch();
					}
					if($specificChef_count == 0 OR $specificChef['participant_name']!=$chef['participant_name']){
						if($specificChef_count > 0){
							// Old chef
							$participant_id = $specificChef['participant_id'];
							$participant_chef = 0;
							$change_chef->execute();
						}
						// New chef
						$participant_id = $chef['participant_id'];
						$participant_chef = 1;
						$change_chef->execute();
						
						// Insert if not existing
						if($change_chef->rowCount() > 0){
							$new_registration->execute();
						}
					}
					
				}
				
			
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
						if($participant['participant_name']==$chef['participant_name']){$participant_chef = 1;}else{$participant_chef = 0;}

						// Continue for chef
						if($participant_chef){continue;}
						
						// Get reg info or insert new reg
						$registration_db->execute();
						$isReg = $registration_db->rowCount();
						if($isReg > 0){
							$reg = $registration_db->fetch();
							$attending = $reg['participant_attending'];
						}else{
							$new_registration->execute();
							$attending = 1;
						}
						if($attending){$isComing = "checked";}else{$isComing = "";}
						
						// Write out participant
						echo "<li id='participant_".$participant_id."'>";
							echo "<span class='status'><input id='".$participant_id."' class='changeParticipantStatus' type='checkbox' ".$isComing."/></span>";
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