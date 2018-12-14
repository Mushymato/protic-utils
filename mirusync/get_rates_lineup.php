<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"><br/>
<p>Paste In-Game Lineup Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
</form>
<?php
$time_start = microtime(true);
$byrates = array('html' => array(), 'shortcode' => array());
foreach(explode("\n", $input_str) as $line){
	if(trim($line) == '★'){
		continue;
	}
	$parts = explode('    ', trim($line));
	if(sizeof($parts) < 2){
		$name = $parts[0];
		$rate = '0';
	}else{
		$name = $parts[sizeof($parts)-2];
		$rate = $parts[sizeof($parts)-1];
	}
	$mon = query_monster($conn, $name);
	if($mon){
		if(!array_key_exists($rate . '|' . $mon['RARITY'], $byrates)){
			$byrates[$rate . '|' . $mon['RARITY']] = array();
		}
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
		$byrates['html'][$rate . '|' . $mon['RARITY']][] = $card['html'];
		$byrates['shortcode'][$rate . '|' . $mon['RARITY']][] = $card['shortcode'];
	}
}
$conn->close();
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
$output_arr = array('html' => array(), 'shortcode' => array());
foreach($byrates as $mode => $rate_group){
	$title = $mode == 'html' ? '<span class="su-highlight" style="background:#ddff99;color:#000000">★</span>' : '[shortcode_highlight]★[/shortcode_highlight]';
	foreach($rate_group as $rate_rarity => $out){
		$parts = explode('|', $rate_rarity);
		$rate = $parts[0];
		$rarity = $parts[1];
		$output_arr[$mode][] = '<strong>' . str_replace('★', $rarity . '★', $title) . ' | ' . $rate . ' each, ' . sizeof($out) * floatval(str_replace('%', '', $rate)) . '% total </strong><br/><span>' . implode(' ', $out) . '</span>';
	}
}
ksort($output_arr['html']);
ksort($output_arr['shortcode']);
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . implode(($om == 'html' ? '<br/>' . PHP_EOL : PHP_EOL), $output_arr[$om]) . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . implode('<br/>', $output_arr['html']) . '</div>'; ?>

</body>
</html>