<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';

$input_str = array_key_exists('input', $_POST) ? 'https://pad.gungho.jp/member/' . $_POST['input'] . '.html': '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
$ev = array_key_exists('e', $_POST) ? $_POST['e'] : 'yes';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Grab Evomats? <input type="radio" name="e" value="yes" <?php if($ev == 'yes'){echo 'checked';}?>> Yes <input type="radio" name="e" value="no" <?php if($ev == 'no'){echo 'checked';}?>> No</p>

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

$buff_tables = $xpath->query("//table[contains(@class, 'monster_list')]");
$monster_output = array();
$current_card = 0;
foreach ($buff_tables as $tbl){
	foreach ($tbl->childNodes as $tr){
		if (isset($tr->childNodes)){
			$awk_key = 'SA';
			foreach ($tr->childNodes as $td){
				if (isset($td->tagName) && $td->tagName == 'td'){
					foreach ($td->childNodes as $n){
						if (isset($n->tagName)){
							if ($n->tagName == 'img'){
								$src = $n->getAttribute('src');
								if (strpos($src, 'm_icon') !== FALSE){
									$current_card = intval(str_replace('.jpg', '', basename($src)));
									if($n->getAttribute('rowspan') == '2'){
										$awk_key = 'SA';
									}
									if(!array_key_exists($current_card, $monster_output)){
										$old_card_info = select_card($current_card);
										if($old_card_info !== false){
											$name = $old_card_info['name_en'];
										}else{
											$name = '';
										}
										$monster_output[$current_card] = array(
											'NAME_JP' => '',
											'NAME_EN' => $name,
											'OLD_AWK' => array(),
											'NEW_AWK' => array(),
											'SA' => array(),
											'INFO' => '',
											'COMP' => '',
											'STAT_DIF' => array(),
											'STAT_MAX' => array(),
											'EVO_MATS' => array(),
											'EVO_TO' => 0
										);
									}
									continue;
								}else if (strpos($src, 'kakusei_icon') !== FALSE){
									$aid = intval(str_replace('.png', '', basename($src)));
									if($aid != 0){
										array_push($monster_output[$current_card][$awk_key], $aid);
									}
									continue;
								}
							}else if ($n->tagName == 'span'){
								if ($n->getAttribute('class') == 'name'){
									$monster_output[$current_card]['NAME_JP'] = $n->nodeValue;
									continue;
								}else if($n->nodeValue == '覚醒' || $n->nodeValue == '調整後'){
									$awk_key = 'NEW_AWK';
									continue;
								}else if($n->nodeValue == '調整前'){
									$awk_key = 'OLD_AWK';
									continue;
								}
							}
						}
					}
					
					$new_info = $td->nodeValue;
					preg_match('/HP:(\d+).*?攻撃:(\d+).*?回復:(\d+).*?↓.*?HP:(\d+).*?攻撃:(\d+).*?回復:(\d+)/s', $new_info, $matches);
					if(sizeof($matches) === 7){
						foreach (array('HP', 'ATK', 'RCV') as $i => $stat){
							$value = intval($matches[$i+4]) - intval($matches[$i+1]);
							if ($value > 0){
								array_push($monster_output[$current_card]['STAT_DIF'], '+' . $value . ' ' . $stat);
							}
							array_push($monster_output[$current_card]['STAT_MAX'], intval($matches[$i+4]) . ' ' . $stat);
						}
						if (sizeof($monster_output[$current_card]['STAT_DIF']) > 0){
							$monster_output[$current_card]['INFO'] .= $td->nodeValue . PHP_EOL;
						}
					}else{
						$old_card_info = select_card($current_card);
						if (preg_match('/\nリーダースキル：/', $new_info) === 1){
							$monster_output[$current_card]['INFO'] .= $td->nodeValue . PHP_EOL;
							if($old_card_info !== false){
								$monster_output[$current_card]['COMP'] .= '<u>Leader Skill</u>: ' . $old_card_info['ls_desc_en'] . PHP_EOL;
							}
						} 
						if(preg_match('/\nスキル：/', $new_info) === 1){
							$monster_output[$current_card]['INFO'] .= $td->nodeValue . PHP_EOL;
							if($old_card_info !== false){
								$monster_output[$current_card]['COMP'] .= '<u>Active Skill</u>: ' . $old_card_info['as_desc_en'] . '('. $old_card_info['turn_max'] . ' &#10151; ' . $old_card_info['turn_min'] . ')' . PHP_EOL;
							}
						}
					}
				}
			}
		}
	}
}
if ($ev == 'yes'){
	$evomat_tables = $xpath->query("//table[contains(@class, 'sozai_list')]");
	$current_card = 0;
	foreach ($evomat_tables as $tbl){
		foreach ($tbl->childNodes as $tr){
			if (isset($tr->childNodes)){
				$is_base = TRUE;
				foreach ($tr->childNodes as $td){
					if (isset($td->tagName) && $td->tagName == 'td'){
						foreach ($td->childNodes as $n){
							if (isset($n->tagName) && $n->tagName == 'img'){
								if($is_base){
									$src = $n->getAttribute('src');
									if (strpos($src, 'm_icon') !== FALSE){
										$current_card = intval(str_replace('.jpg', '', basename($src)));
										if(array_key_exists($current_card, $monster_output)){
											$monster_output[$current_card]['EVO_MATS'] = array();
										}
									}
									$is_base = FALSE;
									continue;
								} else {
									$src = $n->getAttribute('src');
									if (strpos($src, 'm_icon') !== FALSE){
										if(array_key_exists($current_card, $monster_output)){
											$monster_output[$current_card]['EVO_TO'] = intval(str_replace('.jpg', '', basename($src)));
										}
									}
								}
							}
							if (isset($n->tagName) && $n->tagName == 'ul'){
								foreach ($n->childNodes as $li){
									if (isset($li->childNodes)){
										foreach ($li->childNodes as $img){
											if (isset($img->tagName) && $img->tagName == 'img'){
												$src = $img->getAttribute('src');
												if (strpos($src, 'm_icon') !== FALSE){
													$monster_output[$current_card]['EVO_MATS'][] = intval(str_replace('.jpg', '', basename($src)));
												}
											}
										}
									}
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
	$output = '';
	$rowspan = 0;
	if (sizeof($mons['STAT_DIF']) > 0){
		$output .= '<div class="card-change-stats"><div class="card-change-stats-name">Stat Changes</div><div>' . implode(', ', $mons['STAT_DIF']) . '</div><div>(Now ' . implode(' / ', $mons['STAT_MAX']) . ')</div></div>';
		$rowspan += 1;
	}
	if ($mons['INFO'] !== ''){
		$output .= '<div class="card-change-info">' . $mons['INFO'];
		$rowspan += 1;
		if ($mons['COMP'] !== ''){
			$output .= PHP_EOL . $mons['COMP'];
		}
		$output .= '</div>';
	}
	$awake_output = '';
	if (sizeof($mons['OLD_AWK']) > 0){
		$awake_output .= '<div class="card-change-old-awakes"><u>Old:</u> ';
		foreach($mons['OLD_AWK'] as $ak){
			$icon = awake_icon($ak);
			$awake_output .= $icon[$mode];
		}
		$awake_output .= '</div>';
		$rowspan += 1;
	}
	if (sizeof($mons['NEW_AWK']) > 0){
		$awake_output .= '<div class="card-change-new-awakes"><u>New:</u> ';
		foreach($mons['NEW_AWK'] as $ak){
			$icon = awake_icon($ak);
			$awake_output .= $icon[$mode];
		}
		$awake_output .= '</div>';
		$rowspan += 1;
	}
	if (sizeof($mons['SA']) > 0){
		$awake_output .= '<div class="card-change-sa"><u>SA:</u> ';
		foreach($mons['SA'] as $ak){
			$icon = awake_icon($ak);
			$awake_output .= $icon[$mode];
		}
		$awake_output .= '</div>';
	}
	if (strlen($awake_output) > 0){
		$output .= '<div class="card-change-awakes"><div class="card-change-awakes-name">Awakening Changes</div>' . $awake_output . '</div>';
	}
	$output = '<tr><td class="card-change-icon">' . card_icon_img($id)[$mode] . ($mons['NAME_EN'] !== '' ? '<p class="card-change-name">' . $mons['NAME_EN'] . '</p>' : '') . ($mons['NAME_JP'] !== '' ? '<p class="card-change-name">' . $mons['NAME_JP'] . '</p>' : '') . '</td><td>' . $output . '</td></tr>';
	return $output;
}


function fmt_evo_mat($id, $mons, $mode){
	if ($mons['EVO_TO'] == 0){
		return '';
	}
	$evo_mats_output = '<tr><td>' . card_icon_img($id)[$mode] . '<b>Base</b></td><td><div class="card-evomats">';
	foreach($mons['EVO_MATS'] as $mat){
		$evo_mats_output .= card_icon_img($mat)[$mode];
	}
	$evo_mats_output .= '</div><ul>';
	foreach($mons['EVO_MATS'] as $mat){
		$evo_mons = query_monster($mat);
		$mat_name = $evo_mons['name_ja'];
		if ($evo_mons['name_en_override'] != ''){
			$mat_name = $evo_mons['name_en_override'];
		} else if ($evo_mons['name_en'] != ''){
			$mat_name = $evo_mons['name_en'];
		}
		$evo_mats_output .= '<li>' . $mat_name . '</li>';
	}
	$evo_to_comment = '';
	if (in_array(3911, $mons['EVO_MATS'])){
		$evo_to_comment = '<b>Assist Evo</b>';
	}
	$evo_mats_output .= '</ul></td><td>' . card_icon_img($mons['EVO_TO'])[$mode] . $evo_to_comment . '</td></tr>';
	return $evo_mats_output;
}

$output_arr['html'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
$output_arr['shortcode'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
ksort($monster_output);
foreach($monster_output as $id => $mons){
	$output_arr['html'] .= fmt_card_buff($id, $mons, 'html');
	$output_arr['shortcode'] .= fmt_card_buff($id, $mons, 'shortcode');
}
$output_arr['html'] .= '</table><table>';
$output_arr['shortcode'] .= '</table><table>';
foreach($monster_output as $id => $mons){
	$output_arr['html'] .= fmt_evo_mat($id, $mons, 'html');
	$output_arr['shortcode'] .= fmt_evo_mat($id, $mons, 'shortcode');
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