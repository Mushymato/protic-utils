<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Rate Calculator</title>
	<link rel="stylesheet" type="text/css" href="rate_calc.css">
	<script src="rate_calc.js"></script>
</head>
<body>
<?php
include '../sql_param.php';
include '../miru_common.php';
function load_rems($conn, $region = 'jp'){
	global $portrait_url;
	$data = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/paddata/raw/' . $region . '/egg_machines.json'), true);
	$output_array = array();
	$output_tabs = array();
	foreach($data as $machine){
		$sorted = array();
		$is_pem = false;
		foreach($machine['contents'] as $card => $rate){
			$is_pem = ($rate == 0);
			$mon = query_monster($conn, $card);
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
		$machine_id = ($is_pem ? 'pem' : 'rem') . '-' . $machine['egg_machine_id'];
		$output_tabs[] = '<li class="egg-machine-tab-link" data-machineid="' . $machine_id . '">' . $machine['clean_name'] . '</li>';
		$out = '<form id="' . $machine_id . '" data-timestart="' . $machine['start_timestamp'] . ' data-timeend="' . $machine['end_timestamp'] . '"><h1>' . $machine['clean_name'] . '</h1><p>' . $machine['clean_comment'] . '</p>' . ($is_pem ? '' : '<h2>Total Rates = <span class="total-rate">0.00</span>%  <button type="reset" class="clear-selected" data-machineid="' . $machine_id . '" value="Reset">Reset</button></h2>');
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
						$out .= '<div class="rem-icon-check"><input type="checkbox" class="rem-icon-cb" id="remcard-' . $machine['egg_machine_id'] . '-' . $mon['MONSTER_NO'] . '"/><label for="remcard-' . $machine['egg_machine_id'] . '-' . $mon['MONSTER_NO'] . '"><img src="' . $portrait_url . $mon['MONSTER_NO'] . '.png" title="' . $mon['MONSTER_NO'] . '-' . $mon['TM_NAME_US'] . '"/></label></div>';
					}
				}
				$out .= '</div></div>';
			}
		}
		$output_array[$machine['clean_name']] = $out . '</form>';
	}	
	return '<ul class="egg-machine-tabs">' . implode($output_tabs) . '</ul><div class="egg-machines">' . implode($output_array) . '</div>';
}
$conn = connect_sql($host, $user, $pass, $schema);
$region = array_key_exists('region', $_GET) && ($_GET['region'] == 'na' || $_GET['region'] == 'jp') ? $_GET['region'] : 'jp';
echo load_rems($conn, $region);
$conn->close();
?>
</body>
</html>
