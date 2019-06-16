<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
function convert_awk($src){
	$id = intval(str_replace('.png', '', basename($src)));
	// $id = intval(str_replace('.png', '', basename($src))) + 2;
	// if(($id >= 38 && $id <= 40) || ($id >= 42 && $id <= 44)){
	// 	$id -= 1;
	// }else if($id == 37 || $id == 41){
	// 	$id += 3;
	// }
	return awake_icon($id);
}
function awk_td($awakes, $output_arr){
	if (sizeof($awakes) > 0){
		$output_arr['html'] .= '<td>';
		$output_arr['shortcode'] .= '<td>';
		foreach($awakes as $arr){
			$icon = $arr[0];
			$class = 'class="awk-' . $arr[1] . '"';
			$output_arr['html'] .= '<span ' . $class . '> ' . $icon['html'] . '</span>';
			$output_arr['shortcode'] .= '<span ' . $class . '> ' . $icon['shortcode'] . '</span>';
		}
		$output_arr['html'] .= '</td>';
		$output_arr['shortcode'] .= '</td>';
	}
	return $output_arr;	
}

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
foreach ($buff_tables as $tbl){
	$output_arr['html'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
	$output_arr['shortcode'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
	$awakes = array();
	$output_arr['html'] .= '<tr>';
	$output_arr['shortcode'] .= '<tr>';
	$first_card = TRUE;
	$current_card = 0;
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
									if(!$first_card){
										$output_arr = awk_td($awakes, $output_arr);
										$awakes = array();
										$output_arr['html'] .= '</tr><tr>';
										$output_arr['shortcode'] .= '</tr><tr>';
									}
									$current_card = intval(str_replace('.jpg', '', basename($src)));
									$card = card_icon_img($current_card);
									$output_arr['html'] .= '<td>' . $card['html'] . '</td>';
									$output_arr['shortcode'] .= '<td>' . $card['shortcode'] . '</td>';

									$first_card = FALSE;
								}else if (strpos($src, 'kakusei_icon') !== FALSE){
									$found_awake = TRUE;
									$awk_icon = convert_awk($src);
									if (strpos($td->nodeValue, '追加') !== FALSE){
										array_push($awakes, array($awk_icon, 'added'));
									}else if (strpos($td->nodeValue, '変更') !== FALSE){
										array_push($awakes, array($awk_icon, 'changed'));
									}else{
										array_push($awakes, array($awk_icon, 'same'));
									}
								}
							}
						}
					}
					if (!$found_card && !$found_awake){
						$new_card_info = $td->nodeValue;
						$compare_info = '';
						preg_match('/HP:(\d+).*?攻撃:(\d+).*?回復:(\d+).*?↓.*?HP:(\d+).*?攻撃:(\d+).*?回復:(\d+)/s', $new_card_info, $matches);
						if(sizeof($matches) === 7){
							$changed_stats = array();
							foreach (array('HP', 'ATK', 'RCV') as $i => $stat){
								$value = intval($matches[$i+4]) - intval($matches[$i+1]);
								if ($value > 0){
									array_push($changed_stats, '+' . $value . ' ' . $stat);
								}
							}
							$output_arr['html'] .= '<td>' . implode(', ', $changed_stats) . '</td>';
							$output_arr['shortcode'] .= '<td>' . implode(', ', $changed_stats) . '</td>';	
						}else{
							$old_card_info = select_card($current_card);
							if($old_card_info !== false){
								if (preg_match('/^\s*リーダースキル：/', $new_card_info) === 1){
									$compare_info .= '<span>Leader Skill: ' . $old_card_info['LS_DESC_US'] . '</span>';
								} 
								if(preg_match('/^\s*スキル：/', $new_card_info) === 1){
									$compare_info .= '<span>Active Skill: ' . $old_card_info['AS_DESC_US'] . '</span>';
								}	
							}	
							$output_arr['html'] .= '<td><span>' . $new_card_info . '</span>' . $compare_info . '</td>';
							$output_arr['shortcode'] .= '<td><span>' . $new_card_info . '</span>' . $compare_info . '</td>';	
						}
					}
				}
			}
		}
	}
	$output_arr = awk_td($awakes, $output_arr);

	$output_arr['html'] .= '</tr>';
	$output_arr['shortcode'] .= '</tr>';
	$output_arr['html'] .= '</table>';
	$output_arr['shortcode'] .= '</table>';
}

echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>