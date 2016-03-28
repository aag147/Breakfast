<?php
include("../headers/setup.php");
if(!empty($cookie_project_id)){header('Location: project.php'); exit;}
include("../headers/header.php");

?>
	<head>
		<title>
			Breakfast management
		</title>
	</head>

	<div id="frontpage">
		<ul>
			<li id="adminAllContent">
				<ul id="logInView">
					<li id="pageTitle">
						LOG IN
					</li>
					<li id="adminContent">
						<form id="logInForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Username"/></span>
							<span><input type="password" name="password" placeholder="Password"/></span>
							<span id="loginErrmsg"></span>
							<span><input type="submit" value="Log in"/></span>
						</form>
					</li>
					<li id="adminShift">
						<a href="javascript:;" class="adminShiftLink blue">Register?</a>
					</li>
				</ul>
				<ul id="registerView" class="hide">
					<li id="pageTitle">
						REGISTER
					</li>
					<li id="adminContent">
						<form id="registerForm" action="" method="POST">
							<span><input type="text" name="name" placeholder="Project name"/></span>
							<span><input type="password" name="password" placeholder="Password"/></span>
							<span id="registerErrmsg"></span>
							<span><input type="submit" value="Register"/></span>
						</form>
					</li>
					<li id="adminShift">
						<a href="javascript:;" class="adminShiftLink blue">Already have an account?</a>
					</li>
				</ul>
			</li>
			<li id="about">
				<span class="title">About this webapp</span>
				<span class="content">Here you can do all these smart things managing breakfasts at work/school/whatever.<br>
				Just log in and you are good to go.</span>
			</li>
		</ul>
	</div>
<?php
include("../headers/footer.php");
?>