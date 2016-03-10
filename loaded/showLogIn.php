<?php
$currentClass = isset($_POST['currentClass']) ? $_POST['currentClass'] : '';

if($currentClass=="register"){
?>
	<ul>
		<li id="pageTitle">
			REGISTER
		</li>
		<li id="adminContent">
			<form id="registerForm" action="" method="POST">
				<span><input type="text" name="name" placeholder="Project name"/></span>
				<span><input type="password" name="password" placeholder="Password"/></span>
				<span><input type="submit" value="Register"/></span>
			</form>
		</li>
		<li id="adminShift">
			<a href="javascript:;" id="adminShiftLink" class="logIn blue">Already have an account?</a>
		</li>
	</ul>
<?php
}else{
?>
	<ul>
		<li id="pageTitle">
			LOG IN
		</li>
		<li id="adminContent">
			<form id="logInForm" action="" method="POST">
				<span><input type="text" name="name" placeholder="Username"/></span>
				<span><input type="password" name="password" placeholder="Password"/></span>
				<span><input type="submit" value="Log in"/></span>
			</form>
		</li>
		<li id="adminShift">
			<a href="javascript:;" id="adminShiftLink" class="register blue">Register?</a>
		</li>
	</ul>
<?php
}
?>