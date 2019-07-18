<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';

$input_str = array_key_exists('input', $_POST) ? 'https://pad.gungho.jp/member/' . $_POST['input'] . '.html': '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
$awk = array_key_exists('awk', $_POST) ? $_POST['awk'] : 'yes';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>

<p>Paste partial URL Here:</p>
https://pad.gungho.jp/member/<input type='text' name="input" size="50" value="<?php echo array_key_exists('input', $_POST) ? $_POST['input'] : '';?>">.html
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => '', 'shortcode' => '');

$handle = curl_init($input_str);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
$html = curl_exec($handle);
libxml_use_internal_errors(true); // Prevent HTML errors from displaying
$doc = new DOMDocument();
$doc->loadHTML($html);
$xpath = new DOMXpath($doc);

$buff_tables = $xpath->query("//table[@class='monster_list twi_icon']");
$monster_output = array();
$found_card = FALSE;
$current_card = 0;
$awk_key = 'AWK';
foreach ($buff_tables as $tbl){
	foreach ($tbl->childNodes as $tr){
		if (isset($tr->childNodes)){
			$found_awake = FALSE;
			foreach ($tr->childNodes as $td){
				if (isset($td->tagName) && $td->tagName == 'td'){
					$found_card = FALSE;
					if (isset($td->childNodes)){
						foreach ($td->childNodes as $img){
							if (isset($img->tagName) && $img->tagName == 'img'){
								$src = $img->getAttribute('src');
								if (strpos($src, 'm_icon') !== FALSE){
									$found_card = TRUE;
									$current_card = intval(str_replace('.jpg', '', basename($src)));
									if(!array_key_exists($current_card, $monster_output)){
										$monster_output[$current_card] = array(
											'AWK' => array(),
											'SA' => array(),
											'INFO' => '',
											'COMP' => '',
											'STAT' => array()
										);
									}else if (sizeof($monster_output[$current_card]['AWK']) > 0){
										# array_push($monster_output[$current_card]['AWK'], array(-1, 'SA'));
										$awk_key = 'SA';
									}
								}else if (strpos($src, 'kakusei_icon') !== FALSE){
									$found_awake = TRUE;
									$aid = intval(str_replace('.png', '', basename($src)));
									if (strpos($td->nodeValue, '追加') !== FALSE){
										array_push($monster_output[$current_card][$awk_key], array($aid, 'added'));
									}else if (strpos($td->nodeValue, '変更') !== FALSE){
										array_push($monster_output[$current_card][$awk_key], array($aid, 'changed'));
									}else{
										array_push($monster_output[$current_card][$awk_key], array($aid, 'same'));
									}
								}
							}
						}
					}
					if (!$found_card && !$found_awake){
						$monster_output[$current_card]['INFO'] .= $td->nodeValue . PHP_EOL;
						$new_info = $td->nodeValue;
						preg_match('/HP:(\d+).*?攻撃:(\d+).*?回復:(\d+).*?↓.*?HP:(\d+).*?攻撃:(\d+).*?回復:(\d+)/s', $new_info, $matches);
						if(sizeof($matches) === 7){
							foreach (array('HP', 'ATK', 'RCV') as $i => $stat){
								$value = intval($matches[$i+4]) - intval($matches[$i+1]);
								if ($value > 0){
									array_push($monster_output[$current_card]['STAT'], '+' . $value . ' ' . $stat);
								}
							}
						}else{
							$old_card_info = select_card($current_card);
							if($old_card_info !== false){
								if (preg_match('/\nリーダースキル：/', $new_info) === 1){
									$monster_output[$current_card]['COMP'] .= 'Leader Skill: ' . $old_card_info['LS_DESC_US'] . PHP_EOL;
								} 
								if(preg_match('/\nスキル：/', $new_info) === 1){
									$monster_output[$current_card]['COMP'] .= '<span>Active Skill: ' . $old_card_info['AS_DESC_US'] . PHP_EOL;
								}	
							}	
						}
					}
				}
			}
		}
	}
}
function fmt_card_buff($id, $mons, $mode){
	$card_icon = card_icon_img($id);
	$output = '';
	$rowspan = 0;
	if ($mons['STAT'] !== ''){
		$output .= '<tr><td class="card-change-stats">' . implode(', ', $mons['STAT']) . '</td></tr>';
		$rowspan += 1;
	}
	if ($mons['INFO'] !== ''){
		$output .= '<tr><td class="card-change-info">' . $mons['INFO'];
		$rowspan += 1;
		if ($mons['COMP'] !== ''){
			$output .= PHP_EOL . $mons['COMP'];
		}
		$output .= '</td></tr>';
	}
	if (sizeof($mons['AWK']) > 0){
		$output .= '<tr><td class="card-change-awakes">';
		foreach($mons['AWK'] as $ak){
			$icon = awake_icon($ak[0]);
			$output .= '<span class="card-awk-' . $ak[1] . '"> ' . $icon[$mode] . '</span>';
		}
		$output .= '</td></tr>';
		$rowspan += 1;
	}
	if (sizeof($mons['SA']) > 0){
		$output .= '<tr><td class="card-change-sa">';
		foreach($mons['SA'] as $ak){
			$icon = awake_icon($ak[0]);
			$output .= '<span class="card-awk-sa"> ' . $icon[$mode] . '</span>';
		}
		$output .= '</td></tr>';
		$rowspan += 1;
	}
	$output = '<tr><td class="card-change-icon" rowspan="' . $rowspan . '">' . card_icon_img($id)[$mode] . '</td>' . substr($output, 4);
	return $output;
}
$output_arr['html'] .= '<table><thead><tr><td>Card</td><td>Change</td></thead>';
$output_arr['shortcode'] .= '<table><thead><tr><td>Card</td><td>Change</td></thead>';
foreach($monster_output as $id => $mons){
	$output_arr['html'] .= fmt_card_buff($id, $mons, 'html');
	$output_arr['shortcode'] .= fmt_card_buff($id, $mons, 'shortcode');
}
$output_arr['html'] .= '</table>';
$output_arr['shortcode'] .= '</table>';

echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>