<?php
include("../headers/setup.php");
include("../headers/header.php");
?>
	<head>
		<title>
			Your breakfast products
		</title>	
	</head>

	<div id="standardTitle">
		<ul>
			<li id="title">
				<?php echo $project_name; ?>
			</li>
			<li id="subtitle">
				Your breakfast products
			</li>
		</ul>
	</div>
	<div id="standardContent">
		<ul>
			<li>
				Something here
			</li>
		</ul>
	</div><?php
	?><div id="standardPanel">
		<ul>
			<li id="title">
				Administration
			</li>
			<li class="option">
			<form id="newProductForm" action="" method="POST">
				<span class="optionTitle">Add product</span>
				<span class="optionInputs">
					<input name="name" type="text" placeholder="Enter product name"/>
				</span>
				<span class="optionErrmsg" id="newErrmsg">
				</span>
				<span class="optionSubmit">
					<input name="project_id" type="hidden" value="<?php echo $cookie_project_id; ?>" />
					<input type="submit" value="Submit"/>
				</span>
			</form>
			</li>

		</ul>
	</div>
<?php
include("../headers/footer.php");
?>