<?php header('Content-Type:text/html; charset=utf-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!--GLOBAL-->
<html xmlns="http://www.w3.org/1999/xhtml" class="noJS da-DK">

<!-- Splash -->
<head> 
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	
	<script type="text/javascript" src="../javascripts/jquery-1.12.3.min.js"></script>
	<script type='text/javascript' src="../javascripts/functions.js"></script>	
	<script type='text/javascript' src="../javascripts/events.js"></script>	
	
	<link rel="stylesheet" href="../stylesheets/breakfast.css" type="text/css" >
	<link rel="stylesheet" href="../stylesheets/breakfastphone.css" type="text/css" >
</head>

<body> 
<div id="content">
	<div id="contentOuterHeader">
		<div id="contentHeader">
			<?php /* PAGE HEADER */?>
			<div id="siteTitle"><a href="plan.php">
				<span>Morgenmads</span><span>planlægger</span>
			</a></div>
			<?php if(!empty($cookie_project_id)){ ?>
				<nav id="navigation">
					<ul>
					  <li class='<?php if($filename=="plan"){echo "current";} ?>'>
						<a href="plan.php">Plan</a>
					  </li><li class='<?php if($filename=="products"){echo "current";} ?>'>
						<a href="products.php">Produkter</a>
					  </li><li class='<?php if($filename=="participants"){echo "current";} ?>'>
						<a href="participants.php">Deltagere</a>
					  </li><li class='<?php if($filename=="settings"){echo "current";} ?>'>
						<a href="settings.php">Indstillinger</a>
					  </li>
					</ul>
				</nav>
			<?php }else{ ?>
				<nav id="navigation" class='frontpage'>
					<ul>
					  <li class='loginMenu current'>
						<a href="javascript:;" id="login" class="adminShiftLink">Log ind på et projekt?</a>
					  </li><li class='registerMenu'>
						<a href="javascript:;" id="register" class="adminShiftLink">Opret nyt projekt?</a>
					  </li><li class='forgottenMenu'>
						<a href="javascript:;" id="forgotten" class="adminShiftLink">Glemt dit kodeord?</a>
					  </li>
					</ul>
				</nav>			
			<?php }?>
		</div>

		<!-- End Header -->
		<div id="contentMiddle">