<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Paste In-Game Reward List Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => '', 'shortcode' => '');
foreach(explode("\n", $input_str) as $line){
	$parts = array_values(array_filter(explode(' ', trim($line))));
	$reward = $parts[0];
	if(sizeof($parts) == 2){
		if(strlen($output_arr['html']) != 0){
			$output_arr['html'] .= '</td></tr>';
			$output_arr['shortcode'] .= '</td></tr>';
		}
		$output_arr['html'] .= '<tr><td>' . $parts[0] . '</td><td>';
		$output_arr['shortcode'] .= '<tr><td>' . $parts[0] . '</td><td>';
		$reward = $parts[1];
	}
	$mon = query_monster($reward);
	if($mon){
		$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
		$output_arr['html'] .= '<div class="ranking-reward">' . $card['html'] . '</div>';
		$output_arr['shortcode'] .= '<div class="ranking-reward">' . $card['shortcode'] . '</div>';
	}else{
		$output_arr['html'] .= '<div class="ranking-reward">' . $reward . '</div>';
		$output_arr['shortcode'] .= '<div class="ranking-reward">' . $reward . '</div>';
	}
	
}
$output_arr['html'] = '<table width="660px"><thead><tr><td style="width: 120px;"><strong>Percentile</strong></td><td><strong>Rewards</strong></td></tr></thead><tbody>' . $output_arr['html'] . '</tbody></table>';
$output_arr['shortcode'] = '<table width="660px"><thead><tr><td style="width: 120px;"><strong>Percentile</strong></td><td><strong>Rewards</strong></td></tr></thead><tbody>' . $output_arr['shortcode'] . '</tbody></table>';
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>
