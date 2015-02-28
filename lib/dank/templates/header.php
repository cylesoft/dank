<?php
$header_color1 = rand(0, 360);
$header_color2 = abs(180 - $color1);
$header_saturation = rand(20,80);
$header_lightness = rand(20,60);
$header_color1_css = 'hsl('.$header_color1.', '.$header_saturation.'%, '.$header_lightness.'%)';
$header_color2_css = 'hsl('.$header_color2.', '.$header_saturation.'%, '.$header_lightness.'%)';	
?>

<div class="section group header">
	<div class="col s8 text-box">
		<h1><a href="/"><span style="color:<?php echo $header_color1_css; ?>;">dankest</span>.<span style="color:<?php echo $header_color2_css; ?>;">website</span></a></h1>
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