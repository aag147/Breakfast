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
			Din morgenmadsplan
		</title>
		<script>
			window.onload = buildBreakfastPlan();
			window.onload = showContent("product", visuals = 'simple', db_filter = 'buy');
		</script>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Din morgenmadsplan
			</li>
		</ul>
	</div>
	
	<div id="standardContent">
		<ul id="planContent">
			<li id="title">
				Kommende arrangementer
			</li>
			<li id="breakfastPlan">
				<?php /* jscript */ ?>
				<span class="loadingText">Bygger morgenmadsplanen... Dette kan tage et øjeblik.</span>
			</li>
		</ul>
	</div><?php
	?><div id="standardPanel">
		<ul id="planPanel" >
			<li class="title">
			Guide til planen
			</li>
			<li class="help">
				<span>Bjælkerne til venstre fortæller dig:</span>
				<ul>
					<li>
						Dato og dag for arrangementet
					</li><li>
						Hvem der er vært(er) ved arrangementet
					</li>
				</ul>
				<span>Åbner du arrangementet kan du:</span>
				<ul>
					<li>
						Give besked om du kan komme til et
						arrangement eller ej.
					</li><li>
						Blive orienteret om hvor mange,
						der kommer til et arrangement
					</li><li>
						Skifte vært / vælge afløser
						<ul>
							<li>
								'Limbo' betyder ingen vært/afløser er valgt
							</li>
							<li>
								Nummeret ud for potentielle afløsere beskriver
								afløser-forholdet mellem værten og afløseren:
								Hvis tallet er positivt skylder afløseren en afløsning,
								hvis tallet er negativt skylder værten en afløsning.
							</li>
						</ul>
					</li>
				</ul>
				<span>For at gøre følgende skal du klikke ind på <a href='../views/settings.php' class='blue'>Indstillinger</a>:</span>
				<ul>
					<li>
						Redigere arrangmentdage
					</li><li>
						Redigere antallet af værter
					</li>
				</ul>
			</li>
		</ul>
	</div>
<?php

} catch(PDOException $e) {
	echo 'ERROR: ' . $e->getMessage();
}
include("../headers/footer.php");
?>