<?php
include("../headers/setup.php");
include("../headers/header.php");
?>
	<div id="frontpage">
		<ul>
			<li id="pageTitle">
				LOG IN
			</li>
			<li id="logIn">
				<form>
					<span><input type="text" placeholder="Username"/></span>
					<span><input type="text" placeholder="Password"/></span>
					<span><input type="submit" value="Log in"/></span>
				</form>
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