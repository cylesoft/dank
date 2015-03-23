<?php

// dankmeme admin invite codes

$login_required = true;
require_once(__DIR__.'/../../lib/dank/login_check.php');

?><!doctype html>
<html>
<head>
<?php require_once(__DIR__.'/../../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once(__DIR__.'/../../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s12">
		<?php if (isset($_GET['deleted'])) { ?><p><b>invite code deleted</b></p><?php } ?>
		<?php if (isset($_GET['sent'])) { ?><p><b>invite code sent!</b></p><?php } ?>
		<p>invite codes</p>
		<?php
		$get_invite_codes = $mysqli->query("SELECT * FROM invite_codes ORDER BY tsc DESC");
		if ($get_invite_codes->num_rows > 0) {
			?><table>
			<tr><th>email</th><th>created</th><th>used</th></tr>
			<?php
			while ($code_row = $get_invite_codes->fetch_assoc()) {
				echo '<tr>';
				echo '<td>'.$code_row['theemail'].'</td>';
				echo '<td>'.date('Y-m-d h:i A', $code_row['tsc']).'</td>';
				if ($code_row['beenused'] == 1 && isset($code_row['tsu'])) {
					echo '<td>'.date('Y-m-d h:i A', $code_row['tsu']).'</td>';
				} else {
					echo '<td><form action="invite_codes_process.php" method="post"><input type="hidden" name="a" value="d" /><input type="hidden" name="id" value="'.$code_row['code_id'].'" /><input type="submit" value="delete" class="small" /></form></td>';
				}
				
				echo '</tr>'."\n";
			}
			?>
			</table>
			<?php
		} else {
			?>
			<p>none here</p>
			<?php
		} // end code check
		?>
		<form action="invite_codes_process.php" method="post">
			<input type="hidden" name="a" value="n" />
			<p>Send a new code: <input type="email" name="e" placeholder="email address" /> <input type="submit" class="small" value="Send code &raquo;" /></p>
		</form>
	</div>
</div>

</div>
<?php require_once(__DIR__.'/../../lib/dank/templates/foot.php'); ?>
</body>
</html>