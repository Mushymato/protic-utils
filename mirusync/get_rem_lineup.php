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
Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <br/>
<p>Paste In-Game Lineup Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$byrarity = array();
foreach(explode(PHP_EOL, $input_str) as $line){
	if($line == '★'){
		continue;
	}
	$parts = explode('    ', $line);
	if(sizeof($parts) < 1){
		continue;
	}else if(sizeof($parts) < 2){
		$q_str = $parts[0];
		$rate = '0';
	}else{
		$q_str = $parts[sizeof($parts)-2];
		$rate = $parts[sizeof($parts)-1];
	}
	$mon = query_monster($conn, $q_str);
	if($mon){
		$mon['EVOS'] = array();
		foreach(select_evolutions($conn, $mon['MONSTER_NO']) as $eid){
			$mon['EVOS'][] = query_monster($conn, $eid);
		}
		if(array_key_exists($mon['RARITY'] . '|' . $rate, $byrarity)){
			$byrarity[$mon['RARITY'] . '|' . $rate][] = $mon;
		}else{
			$byrarity[$mon['RARITY'] . '|' . $rate] = array($mon);
		}
	}
}
$conn->close();
$output_arr = array('html' => '', 'shortcode' => '');
foreach($byrarity as $key => $mons){
	$rr = explode('|', $key);
	$rare = $rr[0];
	$rate = $rr[1];
	$egg = get_egg($rare);
	$output_arr['html'] = $output_arr['html'] . '<div class="rem-wrapper-rarity">' . $egg['html'] . ' <strong>★' . $rare;
	$output_arr['shortcode'] = $output_arr['shortcode'] . '<div class="rem-wrapper-rarity">' . $egg['shortcode'] . ' <strong>★' . $rare;
	if($rate != '0'){
		$output_arr['html'] = $output_arr['html'] . ' <span style="color: #b5b3b3;">|</span> ' . $rate . ' each, ' . sizeof($mons) * floatval(str_replace('%', '', $rate)) . '% total';
		$output_arr['shortcode'] = $output_arr['shortcode'] . ' <span style="color: #b5b3b3;">|</span> ' . $rate . ' each, ' . sizeof($mons) * floatval(str_replace('%', '', $rate)) . '% total';
	}
	$output_arr['html'] = $output_arr['html'] . '</strong></div><div class="rem-wrapper-block">'  . PHP_EOL;
	$output_arr['shortcode'] = $output_arr['shortcode'] . '</strong></div><div class="rem-wrapper-block">'  . PHP_EOL;
	foreach($mons as $mon){
		$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
		$output_arr['html'] = $output_arr['html'] . '<div class="rem-detail"><div class="rem-card">' . $card['html'] . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		$output_arr['shortcode'] = $output_arr['shortcode'] . '<div class="rem-detail"><div class="rem-card">' . $card['shortcode'] . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		if(sizeof($mon['EVOS']) > 0){
			$output_arr['html'] = $output_arr['html'] . '<br/><span>';
			$output_arr['shortcode'] = $output_arr['shortcode'] . '<br/><span>';
			foreach($mon['EVOS'] as $evo){
				$card = card_icon_img($evo['MONSTER_NO'], $evo['TM_NAME_US'], '40', '40');
				$output_arr['html'] = $output_arr['html'] . $card['html'] . ' ';
				$output_arr['shortcode'] = $output_arr['shortcode'] . $card['shortcode'] . ' ';
			}
			$output_arr['html'] = $output_arr['html'] . '</span>';
			$output_arr['shortcode'] = $output_arr['shortcode'] . '</span>';
		}
		$output_arr['html'] = $output_arr['html'] . '</div></div>' . PHP_EOL;
		$output_arr['shortcode'] = $output_arr['shortcode'] . '</div></div>' . PHP_EOL;
	}
	$output_arr['html'] = $output_arr['html'] . '</div>' . PHP_EOL;
	$output_arr['shortcode'] = $output_arr['shortcode'] . '</div>'. PHP_EOL;
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>