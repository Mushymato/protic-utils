<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Rate Calculator</title>
	<link rel="stylesheet" type="text/css" href="rate_calc.css">
	<script src="rate_calc.js"></script>
	<script>
	window.onload=function(){
		initializeRateCalc();
	}
	</script>
</head>
<body>
<div id="egg-machine-region"></div>
<?php
include '../miru_common.php';
mb_internal_encoding('UTF-8');
$jp_en_regex = json_decode(file_get_contents('jp_en_tl.json'), true);
function rem_name_tl($jp_name){
	global $jp_en_regex;
	$result = $jp_name;
	foreach($jp_en_regex as $regex => $replace){
		$result = preg_replace('/'.$regex.'/', ' '.$replace.' ', $result);
	}
	return ($result != $jp_name ? trim($result) : null);
}
function load_rem_by_region($region = 'jp'){
	global $portrait_url;
	global $portrait_url_na;
	$data = get_egg_machine_lineups($region);
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
			$mon = query_monster(trim($card), $region);
			if(!$mon){
				continue;
			}
			if(!array_key_exists($mon['rarity'], $sorted)){
				$sorted[$mon['rarity']] = array();
			}
			$r_rate = intval($rate * 10000);
			if(!array_key_exists($r_rate, $sorted[$mon['rarity']])){
				$sorted[$mon['rarity']][$r_rate] = array();
			}
			$sorted[$mon['rarity']][$r_rate][] = $mon;
		}
		krsort($sorted);
		$machine_id = ($is_pem ? 'pem' : 'rem') . '-' . $region . '-' . $machine['machine_row'];
		if($region == 'jp'){
			$tl_name = rem_name_tl($machine['name']);
		}
		$output_tabs[] = '<li class="egg-machine-tab-link" data-machineid="' . $machine_id . '">' . (isset($tl_name) ? $tl_name : $machine['name']) . '</li>';
		$out = '<form id="' . $machine_id . '" data-timestart="' . $machine['start_timestamp'] . ' data-timeend="' . $machine['end_timestamp'] . '"><h1>' . $machine['name'] . '</h1>' . (isset($tl_name) ? '<h2/>' . $tl_name . '</h2>' : '') . ($is_pem ? '' : '<h2 >Total Rate = <span class="total-rate" data-machineid="' . $machine_id . '">0.00</span>%  <button type="reset" class="clear-selected" data-machineid="' . $machine_id . '" value="Reset">Reset</button></h2><h3>Chance to get at least 1 desired card, given <input type="text" class="stone-count" data-machineid="' . $machine_id . '" value="0"  maxlength = "3" data-cost="' . $machine['cost'] . '"> <img src="/wp-content/uploads/pad-icons/Magic-Stone.png">: <span class="cumulative-rate" data-machineid="' . $machine_id . '">0.00</span>%</h3>');
		foreach($sorted as $rarity => $rates){
			ksort($rates);
			foreach($rates as $r_rate => $cards){
				$regional_img_url = $region == 'jp' ? $portrait_url : $portrait_url_na;
				if (!file_exists($_SERVER['DOCUMENT_ROOT'].$regional_img_url.$mon['monster_no_'.$region].'.png')){
					# fallback to jp
					$regional_img_url = $portrait_url;
				}			
				$rg_id = $machine_id . '-' . $rarity . '-' . $r_rate;
				$rate = $r_rate/100;
				$out .= '<div class="' . ($is_pem ? 'pem' : 'rem') . '-wrapper-rarity">' . get_egg($rarity)['html'] . '<strong>' . $rarity . '★' . ($is_pem ? '</strong>' : ' | ' . $rate . '% each, ' . ($rate * sizeof($cards)) . '% total</strong> <input type="checkbox" class="select-group" id="' . $rg_id . '"><label for="' . $rg_id . '">Select All</label>') . '<br/><div class="rate-group" data-rate="' . $rate . '" data-rategroupid="' . $rg_id . '">';
				if($is_pem){
					foreach($cards as $mon){
						$out .= '<div class="pem-icon"><img src="' . $regional_img_url . $mon['monster_no_'.$region] . '.png" title="' . $mon['monster_no_'.$region] . '-' . $mon['name_na'] . '"/></div>';
					}
				}else{
					foreach($cards as $mon){
						$out .= '<div class="rem-icon-check"><input type="checkbox" class="rem-icon-cb" id="remcard-' . $machine['machine_row'] . '-' . $mon['monster_no_'.$region] . '"/><label for="remcard-' . $machine['machine_row'] . '-' . $mon['monster_no_'.$region] . '"><img src="' . $regional_img_url . $mon['monster_no_'.$region] . '.png" title="' . $mon['monster_no_'.$region] . '-' . $mon['name_na'] . '"/></label></div>';
					}
				}
				$out .= '</div></div>';
			}
		}
		$output_array[$machine['name']] = $out . '</form>';
	}	
	return '<ul class="egg-machine-tabs">' . implode($output_tabs) . '</ul><div class="egg-machines">' . implode($output_array) . '</div>';
}
function load_rems(){
	return '<div id="region-JP">' . load_rem_by_region('jp') . '</div><div id="region-NA">' . load_rem_by_region('na') . '</div>';
}
echo load_rems();
?>
</body>
</html>
