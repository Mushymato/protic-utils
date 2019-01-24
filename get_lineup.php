<!DOCTYPE html>
<header>
<script type="text/javascript">
function showHide(self, target){
	if(document.getElementById(self).checked){
		console.log(self, target);
		document.getElementById(target).style.display = 'block';
	}else{
		document.getElementById(target).style.display = 'none';
	}
}
window.onload = function(){
	document.getElementById('ir_mirubot').addEventListener('change', function(){showHide('ir_mirubot', 'rem_selector');showHide('ir_ingame', 'txt_input');});
	document.getElementById('ir_ingame').addEventListener('change', function(){showHide('ir_mirubot', 'rem_selector');showHide('ir_ingame', 'txt_input');});
	showHide('ir_mirubot', 'rem_selector');
	showHide('ir_ingame', 'txt_input');
}
</script>
</header>
<html>
<?php
include 'miru_common.php';
$region = 'jp';
$data = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/protic/paddata/raw/' . $region . '/egg_machines.json'), true);
function machine_selector($selected = ''){
	global $data;
	$output = '';
	foreach($data as $machine){
		$output .= '<option value="' . $machine['clean_name'] . '"' . ($machine['clean_name'] == $selected ? ' selected' : '' ) . '>' . $machine['clean_name'] . '</option>';
	}
	return '<select id="rem_selector" name="rem_name" style="width:80vw;height:2em;">' . $output . '</select>';
}
function populate_from_input($input_str){
	$mons_array = array();
	foreach(explode("\n", $input_str) as $line){
		if(trim($line) == '★'){
			continue;
		}
		$parts = explode('    ', trim($line));
		if(sizeof($parts) < 1){
			continue;
		}else if(sizeof($parts) < 2){
			$q_str = $parts[0];
			$rate = 0;
		}else{
			$q_str = $parts[sizeof($parts)-2];
			$rate = floatval(str_replace('%', '', $parts[sizeof($parts)-1]));
		}
		$mon = query_monster($q_str);
		if($mon){
			$mon['EVOS'] = array();
			foreach(select_evolutions($mon['MONSTER_NO']) as $eid){
				$mon['EVOS'][] = query_monster($eid);
			}
			if(array_key_exists($mon['RARITY'] . '|' . $rate, $mons_array)){
				$mons_array[$mon['RARITY'] . '|' . $rate][] = $mon;
			}else{
				$mons_array[$mon['RARITY'] . '|' . $rate] = array($mon);
			}
		}
	}
	return sort_mons_array($mons_array);
}
function populate_from_mirubot($rem_name){
	global $data;
	$contents = false;
	foreach($data as $machine){
		if(sizeof($machine['contents']) == 0){
			continue;
		}
		if($machine['clean_name'] == $rem_name){
			$contents = $machine['contents'];
			break;
		}
	}
	if(!$contents){
		return array();
	}
	$mons_array = array();
	foreach($contents as $id => $rate){
		$rate = $rate * 100;
		$mon = query_monster($id);
		if($mon){
			$mon['EVOS'] = array();
			foreach(select_evolutions($mon['MONSTER_NO']) as $eid){
				$mon['EVOS'][] = query_monster($eid);
			}
			if(array_key_exists($mon['RARITY'] . '|' . $rate, $mons_array)){
				$mons_array[$mon['RARITY'] . '|' . $rate][] = $mon;
			}else{
				$mons_array[$mon['RARITY'] . '|' . $rate] = array($mon);
			}
		}
	}
	return sort_mons_array($mons_array);
}
function sort_mons_array($mons_array){
	uksort($mons_array, function($a, $b){
		$ra = explode('|', $a);$rare_a = intval($ra[0]);$rate_a = floatval($ra[1]);
		$rb = explode('|', $b);$rare_b = intval($rb[0]);$rate_b = floatval($rb[1]);
		if($rare_a > $rare_b){
			return -1;
		}else if($rare_a == $rare_b){
			if($rate_a < $rate_b){
				return -1;
			}else{
				return 1;
			}
		}else{
			return 1;
		}
	});
	return $mons_array;
}
function detailed_lineup($mons_array){
	$output_arr = array('html' => '', 'shortcode' => '');
	foreach($mons_array as $key => $mons){
		$rr = explode('|', $key);
		$rare = $rr[0];
		$rate = floatval($rr[1]);
		$egg = get_egg($rare);
		$output_arr['html'] = $output_arr['html'] . '<div class="rem-wrapper-rarity">' . $egg['html'] . ' <strong>★' . $rare;
		$output_arr['shortcode'] = $output_arr['shortcode'] . '<div class="rem-wrapper-rarity">' . $egg['shortcode'] . ' <strong>★' . $rare;
		if($rate != 0){
			$output_arr['html'] = $output_arr['html'] . ' <span style="color: #b5b3b3;">|</span> ' . $rate . '% each, ' . (sizeof($mons) * $rate) . '% total';
			$output_arr['shortcode'] = $output_arr['shortcode'] . ' <span style="color: #b5b3b3;">|</span> ' . $rate . '% each, ' . (sizeof($mons) * $rate) . '% total';
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
	return $output_arr;
}
function rate_groups_lineup($mons_array){
	$output_arr = array('html' => '', 'shortcode' => '');
	foreach($mons_array as $key => $mons){
		$rr = explode('|', $key);
		$rare = $rr[0];
		$rate = floatval($rr[1]);
		$output_arr['html'] = $output_arr['html'] . '<strong><span class="su-highlight" style="background:#ddff99;color:#000000">★' . $rare . '</span>';
		$output_arr['shortcode'] = $output_arr['shortcode'] . '<strong>[shortcode_highlight]★' . $rare . '[/shortcode_highlight]';
		if($rate != 0){
			$output_arr['html'] = $output_arr['html'] . ' | ' . $rate . '% each, ' . (sizeof($mons) * $rate) . '% total </strong>';
			$output_arr['shortcode'] = $output_arr['shortcode'] . ' | ' . $rate . '% each, ' . (sizeof($mons) * $rate) . '% total </strong>';
		}
		$output_arr['html'] .= '<div>';
		$output_arr['shortcode'] .= PHP_EOL . PHP_EOL;
		foreach($mons as $mon){
			$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
			$output_arr['html'] .= $card['html'];
			$output_arr['shortcode'] .= $card['shortcode'];
		}
		$output_arr['html'] .= '</div>';
		$output_arr['shortcode'] .= PHP_EOL . PHP_EOL;
	}
	return $output_arr;
}
?>
<body>
<?php
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('om', $_POST) ? $_POST['om'] : 'html';
$st = array_key_exists('st', $_POST) ? $_POST['st'] : 'rates';
$ir = array_key_exists('ir', $_POST) ? $_POST['ir'] : 'mirubot';
$rem = array_key_exists('rem_name', $_POST) ? $_POST['rem_name'] : '';
?>
<form method="post">
Output Mode: <input type="radio" name="om" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="om" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"><br/>
Output Style: <input type="radio" name="st" value="rates" <?php if($st == 'rates'){echo 'checked';}?>> Rate Groups <input type="radio" name="st" value="cards" <?php if($st == 'cards'){echo 'checked';}?>> Card Details<br/>
<p>Enter <input type="radio" name="ir" value="mirubot" id="ir_mirubot" <?php if($ir == 'mirubot'){echo 'checked';}?>> REM Name <input type="radio" name="ir" value="ingame" id="ir_ingame" <?php if($ir == 'ingame'){echo 'checked';}?>> In-game Lineup</p>
<?php echo machine_selector($rem);?>
<textarea id="txt_input" name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
</form>
<?php
$time_start = microtime(true);

$mons_array = $ir == 'mirubot' ? populate_from_mirubot($rem) : populate_from_input($input_str);
$output_arr = $st == 'rates' ? rate_groups_lineup($mons_array) : detailed_lineup($mons_array);

echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>