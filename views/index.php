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
				<ul id="loginView">
					<li id="adminShift">
						<a href="javascript:;" id="register" class="adminShiftLink blue">Opret nyt projekt?</a>
						<a href="javascript:;" id="forgotten" class="adminShiftLink blue">Glemt dit kodeord?</a>
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
						<a href="javascript:;" id="login" class="adminShiftLink blue">Log ind på et eksisterende projekt?</a>
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
				<ul id="forgottenView" class="hide">
					<li id="adminShift">
						<a href="javascript:;" id="login" class="adminShiftLink blue">Log ind på normal vis?</a>
					</li>
					<li id="pageTitle">
						Glemt kodeord
					</li>
					<li id="pageSubTitle">
						Indtast projektnavn og en email tilknyttet en af deltagerne.
					</li>
					<li id="adminContent">
						<form id="forgottenForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Dit projektnavn"/></span>
							<span id="emailSpan"><input type="email" name="email" placeholder="Din email"/></span>
							<span class="hide" id="securitySpan"><input type="text" name="security_code" placeholder="Sikkerhedskode"/></span>
							<span class="hide"><input type="password" name="password" placeholder="Nyt kodeord"/></span>
							<span id="forgottenErrmsg"></span>
							<span><input type="submit" value="Send email"/></span>
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