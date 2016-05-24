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
					<li id="pageTitle">
						Log ind
					</li>
					<li id="adminContent">
						<form id="loginForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Dit projektnavn" id="name"/></span>
							<span><input type="password" name="password" placeholder="Dit kodeord"/></span>
							<span id="loginErrmsg"></span>
							<span><input type="submit" value="Log ind"/></span>
						</form>
					</li>
				</ul>
				<ul id="registerView" class="hide">
					<li id="pageTitle">
						Opret nyt projekt
					</li>
					<li id="adminContent">
						<form id="newAccountForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Nyt projektnavn" id="name"/></span>
							<span><input type="password" name="password" placeholder="Nyt kodeord"/></span>
							<span id="newErrmsg"></span>
							<span><input type="submit" value="Opret projekt"/></span>
						</form>
					</li>
				</ul>
				<ul id="forgottenView" class="hide">
					<li id="pageTitle">
						Glemt kodeord
					</li>
					<li id="pageSubTitle">
						Indtast projektnavn og en email tilknyttet en af deltagerne.
					</li>
					<li id="adminContent">
						<form id="forgottenForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Dit projektnavn" id="name"/></span>
							<span id="emailSpan"><input type="email" name="email" placeholder="Din email"/></span>
							<span class="hide" id="securitySpan"><input type="text" name="security_code" placeholder="Sikkerhedskode"/></span>
							<span class="hide" id="passwordSpan"><input type="password" name="password" placeholder="Nyt kodeord"/></span>
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