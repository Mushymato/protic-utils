<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Rate Calculator</title>
	<link rel="stylesheet" type="text/css" href="rate_calc.css">
	<script src="rate_calc.js"></script>
</head>
<body>
<div id="egg-machine-region"></div>
<?php
include '../sql_param.php';
include '../miru_common.php';
mb_internal_encoding('UTF-8');
$jp_en_regex = array(
	'/スーパー/' => ' Super ',
	'/ゴッドフェス/' => ' Godfest ',
	'/魔法石(\d*?)個！/' => ' $1 Magic Stones!',
	'/コラボ/' => ' Collab ',
	'/ガチャ/' => ' Machine '
);
function rem_name_tl($jp_name){
	global $jp_en_regex;
	$result = $jp_name;
	foreach($jp_en_regex as $regex => $replace){
		$result = preg_replace($regex, $replace, $result);
	}
	return ($result != $jp_name ? trim($result) : null);
}
function load_rem_by_region($conn, $region = 'jp'){
	global $portrait_url;
	$data = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/protic/paddata/raw/' . $region . '/egg_machines.json'), true);
	$output_array = array();
	$output_tabs = array();
	foreach($data as $machine){
		$sorted = array();
		$is_pem = false;
		if(sizeof($machine['contents']) == 0){
			continue;
		}
		foreach($machine['contents'] as $card => $rate){
			$is_pem = ($rate == 0);
			$mon = query_monster($conn, trim($card));
			if(!$mon){
				continue;
			}
			if(!array_key_exists($mon['RARITY'], $sorted)){
				$sorted[$mon['RARITY']] = array();
			}
			$r_rate = intval($rate * 10000);
			if(!array_key_exists($r_rate, $sorted[$mon['RARITY']])){
				$sorted[$mon['RARITY']][$r_rate] = array();
			}
			$sorted[$mon['RARITY']][$r_rate][] = $mon;
		}
		krsort($sorted);
		$comments = explode('|', $machine['clean_comment']);
		$machine_id = ($is_pem ? 'pem' : 'rem') . '-' . $machine['egg_machine_row'];
		if($region == 'jp'){
			$tl_name = rem_name_tl($machine['clean_name']);
		}
		$output_tabs[] = '<li class="egg-machine-tab-link" data-machineid="' . $machine_id . '">' . (isset($tl_name) ? $tl_name : $machine['clean_name']) . '</li>';
		$out = '<form id="' . $machine_id . '" data-timestart="' . $machine['start_timestamp'] . ' data-timeend="' . $machine['end_timestamp'] . '"><h1>' . $machine['clean_name'] . '</h1>' . (isset($tl_name) ? '<h2/>' . $tl_name . '</h2>' : '') . ($is_pem ? '' : '<h2>Total Rates = <span class="total-rate">0.00</span>%  <button type="reset" class="clear-selected" data-machineid="' . $machine_id . '" value="Reset">Reset</button></h2>');
		foreach($sorted as $rarity => $rates){
			ksort($rates);
			foreach($rates as $r_rate => $cards){
				$rate = $r_rate/100;
				$out .= '<div class="' . ($is_pem ? 'pem' : 'rem') . '-wrapper-rarity">' . get_egg($rarity)['html'] . '<strong>' . $rarity . '★' . ($is_pem ? '</strong>' : ' | ' . $rate . '% each, ' . ($rate * sizeof($cards)) . '% total<br/></strong>') . '<div class="rate-group" data-rate="' . $rate . '">';
				if($is_pem){
					foreach($cards as $mon){
						$out .= '<div class="pem-icon"><img src="' . $portrait_url . $mon['MONSTER_NO'] . '.png" title="' . $mon['MONSTER_NO'] . '-' . $mon['TM_NAME_US'] . '"/></div>';
					}
				}else{
					foreach($cards as $mon){
						$out .= '<div class="rem-icon-check"><input type="checkbox" class="rem-icon-cb" id="remcard-' . $machine['egg_machine_row'] . '-' . $mon['MONSTER_NO'] . '"/><label for="remcard-' . $machine['egg_machine_row'] . '-' . $mon['MONSTER_NO'] . '"><img src="' . $portrait_url . $mon['MONSTER_NO'] . '.png" title="' . $mon['MONSTER_NO'] . '-' . $mon['TM_NAME_US'] . '"/></label></div>';
					}
				}
				$out .= '</div></div>';
			}
		}
		$output_array[$machine['clean_name']] = $out . '</form>';
	}	
	return '<ul class="egg-machine-tabs">' . implode($output_tabs) . '</ul><div class="egg-machines">' . implode($output_array) . '</div>';
}
function load_rems($conn){
	return '<div id="region-JP">' . load_rem_by_region($conn, 'jp') . '</div><div id="region-NA">' . load_rem_by_region($conn, 'na') . '</div>';
}
$conn = connect_sql($host, $user, $pass, $schema);
echo load_rems($conn);
$conn->close();
?>
</body>
</html>
