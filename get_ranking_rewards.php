<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
$jp_en_regex = array(
	'王冠' => '[icon id=crown]',
	'\+ポイント\(\+(\d+)\)' => '[icon id=plus] Plus Points (+$1)',
	'魔法石(\d+)個' => '[icon id=ms] x$1'
);
function rewards_name_tl($jp_name){
	global $jp_en_regex;
	$result = $jp_name;
	foreach($jp_en_regex as $regex => $replace){
		$result = preg_replace('/'.$regex.'/', ' '.$replace.' ', $result);
	}
	return ($result != $jp_name ? trim($result) : null);
}
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
	if (sizeof($parts) == 0){
		continue;
	}
	$reward = $parts[0];
	$extra_str = implode('', array_slice($parts, 1));
	if(endsWith($parts[0], '%')){
		if(strlen($output_arr['html']) != 0){
			$output_arr['html'] .= '</td></tr>';
			$output_arr['shortcode'] .= '</td></tr>';
		}
		$output_arr['html'] .= '<tr><td>' . $parts[0] . '</td><td>';
		$output_arr['shortcode'] .= '<tr><td>' . $parts[0] . '</td><td>';
		$reward = $parts[1];
		$extra_str = implode('', array_slice($parts, 2));
	}
	$mon = query_monster($reward);
	if($mon){
		$card = card_icon_img($mon['monster_id'], $mon['name_en']);
		$output_arr['html'] .= '<div class="ranking-reward"><b>' . $card['html'] . $extra_str . '</b></div>';
		$output_arr['shortcode'] .= '<div class="ranking-reward"><b>' . $card['shortcode'] . $extra_str . '</b></div>';
	}else{
		$reward = rewards_name_tl($reward . $extra_str);
		$output_arr['html'] .= '<div class="ranking-reward"><b>' . $reward . '</b></div>';
		$output_arr['shortcode'] .= '<div class="ranking-reward"><b>' . $reward . '</b></div>';
	}
	
}
$output_arr['html'] = '<table width="660px"><thead><tr><td style="width: 120px;"><strong>Percentile</strong></td><td><strong>Rewards</strong></td></tr></thead><tbody>' . $output_arr['html'] . '</tr></tbody></table>';
$output_arr['shortcode'] = '<table width="660px"><thead><tr><td style="width: 120px;"><strong>Percentile</strong></td><td><strong>Rewards</strong></td></tr></thead><tbody>' . $output_arr['shortcode'] . '</tr></tbody></table>';
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>
