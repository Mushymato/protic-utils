<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$re = array_key_exists('r', $_POST) ? $_POST['r'] : 'jp';
$ids = search_ids($input_str, $re);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
$tf = array_key_exists('tf', $_POST) ? $_POST['tf'] : 'rem';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Region: <input type="radio" name="r" value="jp" <?php if($re == 'jp'){echo 'checked';}?>> JP <input type="radio" name="r" value="na" <?php if($re == 'na'){echo 'checked';}?>> NA</p>
<p>Enter <input type="radio" name="tf" value="rem" <?php if($tf == 'rem'){echo 'checked';}?>> Target <input type="radio" name="tf" value="farmable" <?php if($tf == 'farmable'){echo 'checked';}?>> Fodder to include, one per line (leave blank to show all):</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
function placehold_req($req_id, $region) {
	return array(
		'monster_no_'.$region => $req_id,
		'name_en' => '<NEWCARD>',
		'name_ja' => '<NEWCARD>'
	);
}
function get_tradepost_rare($data, $region, $include = array()){
	$out = array(
		'html' => '<table class="mon-exchange-rem"><thead><tr><td colspan="2"><strong>Desired Card &amp; Exchange Requirements</strong></td></tr></thead>',
		'shortcode' => '<table class="mon-exchange-rem"><thead><tr><td colspan="2"><strong>Desired Card &amp; Exchange Requirements</strong></td></tr></thead><tbody>'
	);
	foreach($data as $d){
		if(sizeof($include) > 0 && !in_array($d['target_monster_id'], $include)){
			continue;
		}
		$mon = query_monster($d['target_monster_id'], $region);
		if(!$mon){
			$mon = placehold_req($d['target_monster_id'], $region);
		}
		if($mon){
			$card = card_icon_img($mon['monster_no_'.$region], $mon['name_en'], $region);
			$out['html'] .= '<tr class="mon-exchange-card"><td style="width: 80px"  rowspan="2">' . $card['html'] . '</td><td>[' . $mon['monster_no_'.$region] . '] <strong>' . $mon['name_en'] . ($mon['name_ja'] != $mon['name_en'] ? ' ' .  '<br>' . $mon['name_ja'] : '') . '</strong><br><strong><span style="color: #ff6600;">▼ Trade ' . $d['required_count'] . ' cards in.</span></strong><br/></td></tr><tr class="mon-exchange-list"><td>';
			$out['shortcode'] .= '<tr class="mon-exchange-card"><td style="width: 80px"  rowspan="2">' . $card['shortcode'] . '</td><td>[' . $mon['monster_no_'.$region] . '] <strong>' . $mon['name_en'] . ($mon['name_ja'] != $mon['name_en'] ? ' ' .  '<br>' . $mon['name_ja'] : '') . '</strong><br><strong><span style="color: #ff6600;">▼ Trade ' . $d['required_count'] . ' cards in.</span></strong></td></tr>' . PHP_EOL . '<tr class="mon-exchange-list"><td>' . PHP_EOL ;
			foreach($d['required_monster_ids'] as $req_id){
				$mon = query_monster($req_id, $region);
				if(!$mon){
					$mon = placehold_req($req_id, $region);
				}
				if($mon){
					$card = card_icon_img($mon['monster_no_'.$region], $mon['name_en'], $region);
					$out['html'] .= $card['html'];
					$out['shortcode'] .= $card['shortcode'];
				}
			}
			$out['html'] .= '</p></td></tr>';
			$out['shortcode'] .= PHP_EOL . '</p></td></tr>';
		}
	}
	$out['html'] .= '</tbody></table>';
	$out['shortcode'] .= PHP_EOL . '</tbody></table>';
	return $out;
}
function get_tradepost_farmable($data, $region, $include = array()){
	$out = array(
		'html' => '<table><thead><tr><td><strong>Desired Card</strong></td><td><strong>Exchange Requirements</strong></td></tr></thead><tbody>',
		'shortcode' => '<table><thead><tr><td><strong>Desired Card</strong></td><td><strong>Exchange Requirements</strong></td></tr></thead><tbody>' . PHP_EOL
	);
	$invert_arr = array();
	foreach($data as $d){
		if(sizeof($d['required_monster_ids']) > 1 || (sizeof($include) > 0 && !in_array($d['required_monster_ids'][0], $include))){
			continue;
		}
		if(!array_key_exists($d['required_monster_ids'][0], $invert_arr)){
			$invert_arr[$d['required_monster_ids'][0]] = array();
		}else if(!array_key_exists($d['required_count'], $invert_arr[$d['required_monster_ids'][0]])){
			$invert_arr[$d['required_monster_ids'][0]][$d['required_count']] = array();
		}
		$invert_arr[$d['required_monster_ids'][0]][$d['required_count']][] = $d['target_monster_id'];
	}
	foreach ($invert_arr as $req_id => $arr_1){
		// $req = query_monster($req_id, $region);
		$req = placehold_req($req_id, $region);
		if(!$req){continue;}
		$req_card = card_icon_img($req['monster_no_'.$region], $req['name_en'], $region);
		foreach ($arr_1 as $req_count => $arr_2){
			foreach ($arr_2 as $mon_id){
				// $mon = query_monster($mon_id, $region);
				$mon = placehold_req($mon_id, $region);
				if(!$mon){continue;}
				$mon_card = card_icon_img($mon['monster_no_'.$region], $mon['name_en'], $region);
				$out['html'] .= '<tr><td>' . $mon_card['html'] . '[' . $mon['monster_no_'.$region] . '] <strong>' . $mon['name_en'] . ($mon['name_ja'] != $mon['name_en'] ? ' ' . $mon['name_ja'] : '') . '</strong></td><td>' . $req_card['html'] . ' x ' . $req_count . '[' . $req['monster_no_'.$region] . '] <strong>' . $req['name_en'] . ' ' . $req['name_ja'] . '</strong></td></tr>';
				$out['shortcode'] .= '<tr><td>' . $mon_card['shortcode'] . '[' . $mon['monster_no_'.$region] . '] <strong>' . $mon['name_en'] . ($mon['name_ja'] != $mon['name_en'] ? ' ' . $mon['name_ja'] : '') . '</strong></td><td>' . $req_card['shortcode'] . ' x ' . $req_count . '[' . $req['monster_no_'.$region] . '] <strong>' . $req['name_en'] . ' ' . $req['name_ja'] . '</strong></td></tr>' . PHP_EOL;
			}
		}
	}
	$out['html'] .= '</table>';
	$out['shortcode'] .= PHP_EOL . '</table>';
	return $out;
}
# set TRUE to FALSE to show all trades, including permanent ones
$data = get_monster_exchange($re, TRUE);
$include = array_map(function($x) {return $x[1];}, $ids);
$output_arr = $tf == 'rem' ? get_tradepost_rare($data, $re, $include) : get_tradepost_farmable($data, $re, $include);?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>' . PHP_EOL; ?>