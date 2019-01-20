<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$ids = search_ids($conn, $input_str);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
$rg = array_key_exists('r', $_POST) ? $_POST['r'] : 'jp';
$tf = array_key_exists('tf', $_POST) ? $_POST['tf'] : 'rem';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Region: <input type="radio" name="r" value="jp" <?php if($rg == 'jp'){echo 'checked';}?>> JP <input type="radio" name="r" value="na" <?php if($rg == 'na'){echo 'checked';}?>> NA</p>
<p>Enter (<input type="radio" name="tf" value="rem" <?php if($tf == 'rem'){echo 'checked';}?>> Target <input type="radio" name="tf" value="farmable" <?php if($tf == 'farmable'){echo 'checked';}?>> Fodder) to include, one per line (leave blank to show all):</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
function get_tradepost_rare($conn, $data, $include = array()){
	$out = array(
		'html' => '<table class="mon-exchange-rem"><thead><tr><td colspan="2"><strong>Desired Card &amp; Exchange Requirements</strong></td></tr></thead>',
		'shortcode' => '<table class="mon-exchange-rem"><thead><tr><td colspan="2"><strong>Desired Card &amp; Exchange Requirements</strong></td></tr></thead><tbody>'
	);
	foreach($data as $d){
		if(sizeof($include) > 0 && !in_array($d['monster_id'], $include)){
			continue;
		}
		$mon = query_monster($conn, $d['monster_id']);
		if($mon){
			$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
			$out['html'] .= '<tr class="mon-exchange-card"><td style="width: 80px">' . $card['html'] . '</td><td>[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . ' ' . $mon['TM_NAME_JP'] . '</strong></td></tr><tr class="mon-exchange-list"><td colspan="2"><p class="1"><strong>▼ Trade ' . $d['required_count'] . ' cards in.</strong>';
			$out['shortcode'] .= '<tr class="mon-exchange-card"><td style="width: 80px">' . $card['shortcode'] . '</td><td>[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . ' ' . $mon['TM_NAME_JP'] . '</strong></td></tr>' . PHP_EOL . '<tr class="mon-exchange-list"><td colspan="2"><p class="1"><strong>▼ Trade ' . $d['required_count'] . ' cards in.</strong>' . PHP_EOL ;
			foreach($d['required_monsters'] as $req_id){
				$mon = query_monster($conn, $req_id);
				if($mon){
					$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
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
function get_tradepost_farmable($conn, $data, $include = array()){
	$out = array(
		'html' => '<table><thead><tr><td><strong>Desired Card</strong></td><td><strong>Exchange Requirements</strong></td></tr></thead><tbody>',
		'shortcode' => '<table><thead><tr><td><strong>Desired Card</strong></td><td><strong>Exchange Requirements</strong></td></tr></thead><tbody>' . PHP_EOL
	);
	$invert_arr = array();
	foreach($data as $d){
		if(sizeof($d['required_monsters']) > 1 || (sizeof($include) > 0 && !in_array($d['required_monsters'][0], $include))){
			continue;
		}
		if(!array_key_exists($d['required_monsters'][0], $invert_arr)){
			$invert_arr[$d['required_monsters'][0]] = array();
		}else if(!array_key_exists($d['required_count'], $invert_arr[$d['required_monsters'][0]])){
			$invert_arr[$d['required_monsters'][0]][$d['required_count']] = array();
		}
		$invert_arr[$d['required_monsters'][0]][$d['required_count']][] = $d['monster_id'];
	}
	foreach ($invert_arr as $req_id => $arr_1){
		$req =query_monster($conn, $req_id);
		if(!$req){continue;}
		$req_card = card_icon_img($req['MONSTER_NO'], $req['TM_NAME_US']);
		foreach ($arr_1 as $req_count => $arr_2){
			foreach ($arr_2 as $mon_id){
				$mon = query_monster($conn, $mon_id);
				if(!$mon){continue;}
				$mon_card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
				$out['html'] .= '<tr><td>' . $mon_card['html'] . '[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . ' ' . $mon['TM_NAME_JP'] . '</strong></td><td>' . $req_card['html'] . ' x ' . $req_count . '[' . $req['MONSTER_NO'] . '] <strong>' . $req['TM_NAME_US'] . ' ' . $req['TM_NAME_JP'] . '</strong></td></tr>';
				$out['shortcode'] .= '<tr><td>' . $mon_card['shortcode'] . '[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . ' ' . $mon['TM_NAME_JP'] . '</strong></td><td>' . $req_card['shortcode'] . ' x ' . $req_count . '[' . $req['MONSTER_NO'] . '] <strong>' . $req['TM_NAME_US'] . ' ' . $req['TM_NAME_JP'] . '</strong></td></tr>' . PHP_EOL;
			}
		}
	}
	$out['html'] .= '</table>';
	$out['shortcode'] .= PHP_EOL . '</table>';
	return $out;
}
$data = json_decode(file_get_contents("https://storage.googleapis.com/mirubot-data/paddata/processed/{$rg}_exchange.json"), true);
$conn = connect_sql($host, $user, $pass, $schema);
$output_arr = $tf == 'rem' ? get_tradepost_rare($conn, $data, search_ids($conn, $input_str)) : get_tradepost_farmable($conn, $data, search_ids($conn, $input_str));
$conn->close();?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>' . PHP_EOL; ?>