<?php header('Content-Type:text/html; charset=utf-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!--GLOBAL-->
<html xmlns="http://www.w3.org/1999/xhtml" class="noJS da-DK">

<!-- Splash -->
<head> 
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
	
	<link rel="stylesheet" href="<?php echo "../stylesheets/breakfast.css"; ?>" type="text/css" >
	<link rel="stylesheet" href="<?php echo "../stylesheets/breakfastphone.css"; ?>" type="text/css" >
	<script src="<?php echo "../javascripts/breakfast.js"; ?>" type='text/javascript'></script>	
</head>

<body> 
<div id="content">
	<div id="contentOuterHeader">
		<div id="contentHeader">
			<?php /* PAGE HEADER */?>
			<div id="siteTitle"><a href="project.php">
				Morgenmadsplanlægger
			</a></div>
			<?php if(!empty($cookie_project_id)){ ?>
				<nav id="navigation">
					<ul>
					  <li class='<?php if($filename=="project"){echo "current";} ?>'>
						<a href="project.php">Plan</a>
					  </li><li class='<?php if($filename=="products"){echo "current";} ?>'>
						<a href="products.php">Produkter</a>
					  </li><li class='<?php if($filename=="participants"){echo "current";} ?>'>
						<a href="participants.php">Deltagere</a>
					  </li><li class='<?php if($filename=="settings"){echo "current";} ?>'>
						<a href="settings.php">Indstillinger</a>
					  </li>
					</ul>
				</nav>
			<?php } ?>
		</div>

		<!-- End Header -->
		<div id="contentMiddle">