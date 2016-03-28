<?php header('Content-Type:text/html; charset=utf-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<!--GLOBAL-->
<html xmlns="http://www.w3.org/1999/xhtml" class="noJS da-DK">

<!-- Splash -->
<head> 
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	
	<link rel="stylesheet" href="<?php echo "../stylesheets/breakfast.css"; ?>" type="text/css" >
	<link rel="stylesheet" href="<?php echo "../stylesheets/breakfastphone.css"; ?>" type="text/css" >
    
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/base/minified/jquery-ui.min.css" type="text/css" /> 	
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<script type="text/javascript" src="http://code.jquery.com/ui/1.10.1/jquery-ui.min.js"></script>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>

	<script src="<?php echo "../javascripts/breakfast.js"; ?>" type='text/javascript'></script>	
	
	<script type="text/javascript" src="../javascripts/jquery.navobile.min.js" ></script>
    <link rel="stylesheet" href="../stylesheets/jquery.navobile.css" type="text/css" />	
	
	
	<script type="text/javascript" src="http://agabel.dk/jscripts/hammer.min.js" ></script>
	<script type="text/javascript" src="http://agabel.dk/jscripts/jquery.hammer.js" ></script>
	<script type="text/javascript" src="http://agabel.dk/jscripts/modernizer.min.js" ></script>
	<script type="text/javascript" src="http://agabel.dk/jscripts/moment.min.js" ></script>
	
	<script>var project_id = "<?php echo $cookie_project_id; ?>";</script>
</head>
 
<body> 
<div id="content">
	<div id="contentOuterHeader">
		<div id="contentHeader">
			<?php /* PAGE HEADER */?>
			<div class="div_title">
				Breakfast management
			</div>
			<?php if(isset($cookie_project_id)){ ?>
				<nav id="navigation">
					<ul>
					  <li class='<?php if($filename=="project"){echo "current";} ?>'>
						<a href="project.php">Planner</a>
					  </li><li class='<?php if($filename=="products"){echo "current";} ?>'>
						<a href="products.php">Products</a>
					  </li><li class='<?php if($filename=="participants"){echo "current";} ?>'>
						<a href="participants.php">Participants</a>
					  </li><li class='<?php if($filename=="settings"){echo "current";} ?>'>
						<a href="settings.php">Settings</a>
					  </li>
					</ul>
				</nav>
			<?php } ?>
		</div>

		<!-- End Header -->
		<div id="contentMiddle">