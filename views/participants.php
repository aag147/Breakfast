<?php
include("../headers/setup.php");
if(empty($cookie_project_id)){header('Location: index.php'); exit;}
include("../headers/header.php");

try{ 
	$conn = new PDO("mysql:host=".DB_SERVER.";port=3306;dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	
	
	$participants_db = $conn->prepare("SELECT * FROM breakfast_participants WHERE project_id = :project_id AND participant_removed = '0' ORDER BY participant_name ASC");
	$participants_db->bindParam(':project_id', $cookie_project_id);		
	$participants_db->execute();

?>
	<head>
		<title>
			Your breakfast participants
		</title>		
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Your breakfast participants
			</li>
		</ul>
	</div>
	<div id="standardContent">
		<ul id="standardList">
			<li class="listLegend">
				<span class="name">Name</span><?php
				?><span class="status"></span><?php
				?><span class="admin">Admin</span>
			</li>
			<?php
			while($participant = $participants_db->fetch(PDO::FETCH_ASSOC)){
				echo "<li id='participant_".$participant['participant_id']."'>";
					echo "<span class='span2input'>";
						echo "<span class='name'>".$participant['participant_name']."</span>";
						echo "<span class='email'>Email: ".$participant['participant_email']."</span>";
					echo "</span>";
					echo "<span class='status'></span>";
					echo "<span class='options'>";
						echo "<a href='javascript:;' id='".$participant['participant_id']."' class='saveParticipant green hide'>[save]</a>";
						echo "<a href='javascript:;' id='".$participant['participant_id']."' class='editParticipant blue'>[edit]</a>";
						echo "<a href='javascript:;' id='".$participant['participant_id']."' class='deleteParticipant red'>[X]</a>";
					echo "</span>";
				echo "</li>";
			}
			?>
		</ul>
	</div><?php
	
	?><div id="standardPanel">
		<ul id="adminPanel">
			<li id="title">
				Administration
			</li>
			<li class="option">
			<form id="newParticipantForm" action="" method="POST">
				<span class="optionTitle">Add participant</span>
				<span class="optionInputs">
					<input name="name" type="text" placeholder="Enter participant name"/>
					<input name="email" type="email" placeholder="Enter participant email"/>
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

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>