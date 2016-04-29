<?php
include("../headers/setup.php");
if(!empty($cookie_project_id)){header('Location: project.php'); exit;}
include("../headers/header.php");

?>
	<head>
		<title>
			Morgenmadsplanlægger
		</title>
	</head>

	<div id="frontpage">
		<ul>
			<li id="adminAllContent">
				<ul id="logInView">
					<li id="adminShift">
						<a href="javascript:;" class="adminShiftLink blue">Opret nyt projekt?</a>
						<a href="javascript:;" class="forgottonPassword blue">Glemt dit kodeord? (ikke implementeret)</a>
					</li>
					<li id="pageTitle">
						LOG IND
					</li>
					<li id="adminContent">
						<form id="logInForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Dit projektnavn"/></span>
							<span><input type="password" name="password" placeholder="Dit kodeord"/></span>
							<span id="loginErrmsg"></span>
							<span><input type="submit" value="Log ind"/></span>
						</form>
					</li>
				</ul>
				<ul id="registerView" class="hide">
					<li id="adminShift">
						<a href="javascript:;" class="adminShiftLink blue">Log ind på et eksisterende projekt?</a>
					</li>
					<li id="pageTitle">
						Opret nyt projekt
					</li>
					<li id="adminContent">
						<form id="registerForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Nyt projektnavn"/></span>
							<span><input type="password" name="password" placeholder="Nyt kodeord"/></span>
							<span id="registerErrmsg"></span>
							<span><input type="submit" value="Opret projekt"/></span>
						</form>
					</li>
				</ul>
			</li>
			<li id="about">
				<span class="title">Omkring denne webapp</span>
				<span class="content">
					Her kan du få hjælp til at planlægge morgenmadsarrangementer på din arbejdsplads, skole eller noget helt tredje.<br>
					Bare opret et nyt projekt eller log ind på et allerede eksisterende projekt.
				</span>
			</li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>