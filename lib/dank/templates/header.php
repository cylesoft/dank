<?php
require_once(__DIR__.'/../../../config/config.php');
require_once(__DIR__.'/../../../lib/dank/login_check.php');
?>

<div class="section group header">
	<div class="col s8 text-box">
		<h1><a href="/"><?php echo $site_header_title; ?></a></h1>
	</div>
	<div class="col s4 text-box">
		<?php
		if ($current_user['loggedin'] == false) {
		?>
		<p>got content? <a href="/login/">log in</a></p>
		<?php
		} else {
		?>
		<p>done here? <a href="/logout/">log out</a></p>
		<?php
		} // end login check
		?>
	</div>
</div>