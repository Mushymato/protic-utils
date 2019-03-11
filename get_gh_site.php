<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
$awk = array_key_exists('awk', $_POST) ? $_POST['awk'] : 'yes';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<!-- <p>Show only 追加/変更 awakes?: <input type="radio" name="awk" value="yes" <?php if($awk == 'yes'){echo 'checked';}?>> Yes <input type="radio" name="awk" value="no" <?php if($awk == 'no'){echo 'checked';}?>> No <input type="submit"></p> -->

<p>Paste URL Here:</p>
<input type='text' name="input" style="width:80vw;" value="<?php echo $input_str;?>">
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => '', 'shortcode' => '');

$handle = curl_init($input_str);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($handle);
libxml_use_internal_errors(true); // Prevent HTML errors from displaying
$doc = new DOMDocument();
$doc->loadHTML(file_get_contents($input_str));
$xpath = new DOMXpath($doc);

$buff_tables = $xpath->query("//table[@class='monster_list twi_icon']");
foreach ($buff_tables as $tbl){
	$output_arr['html'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
	$output_arr['shortcode'] .= '<table><thead><tr><td>Card</td><td>Change</td></tr></thead>';
	$awakes = array(
		'added' => array(),
		'changed' => array(),
		'other' => array(),
	);
	$output_arr['html'] .= '<tr>';
	$output_arr['shortcode'] .= '<tr>';
	$first_card = TRUE;
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
										foreach($awakes as $type => $arr){
											if (sizeof($arr) == 0 || 
											($awk == 'yes' && $type == 'other' && 
												(sizeof($awakes['added']) > 0 || sizeof($awakes['changed']) > 0))){
												continue;
											}
											$output_arr['html'] .= '<td>' . $type . ' ';
											$output_arr['shortcode'] .= '<td>' . $type  . ' ';
											foreach($arr as $icon){
												$output_arr['html'] .= $icon['html'];
												$output_arr['shortcode'] .= $icon['shortcode'];
											}
											$output_arr['html'] .= '</td>';
											$output_arr['shortcode'] .= '</td>';
										}
										$awakes = array(
											'added' => array(),
											'changed' => array(),
											'other' => array(),
										);
										$output_arr['html'] .= '</tr><tr>';
										$output_arr['shortcode'] .= '</tr><tr>';
									}

									$card = card_icon_img(str_replace('.jpg', '', basename($src)));
									$output_arr['html'] .= '<td>' . $card['html'] . '</td>';
									$output_arr['shortcode'] .= '<td>' . $card['shortcode'] . '</td>';

									$first_card = FALSE;
								}else if (strpos($src, 'kakusei_icon') !== FALSE){
									$found_awake = TRUE;
									$awk_icon = awake_icon(intval(str_replace('.png', '', basename($src))) + 2);
									if (strpos($td->nodeValue, '追加') !== FALSE){
										array_push($awakes['added'], $awk_icon);
									}else if (strpos($td->nodeValue, '変更') !== FALSE){
										array_push($awakes['changed'], $awk_icon);
									}else{
										array_push($awakes['other'], $awk_icon);
									}
								}
							}
						}
					}
					if (!$found_card && !$found_awake){
						$output_arr['html'] .= '<td>' . $td->nodeValue . '</td>';
						$output_arr['shortcode'] .= '<td>' . $td->nodeValue . '</td>';	
					}
				}
			}
		}
	}
	foreach($awakes as $type => $arr){
		if (sizeof($arr) == 0 || 
		($type == 'other' && 
			(sizeof($awakes['added']) > 0 || sizeof($awakes['changed']) > 0))){
			continue;
		}
		$output_arr['html'] .= '<td>' . $type . ' ';
		$output_arr['shortcode'] .= '<td>' . $type  . ' ';
		foreach($arr as $icon){
			$output_arr['html'] .= $icon['html'];
			$output_arr['shortcode'] .= $icon['shortcode'];
		}
		$output_arr['html'] .= '</td>';
		$output_arr['shortcode'] .= '</td>';
	}
	$awakes = array(
		'added' => array(),
		'changed' => array(),
		'other' => array(),
	);
	$output_arr['html'] .= '</tr><tr>';
	$output_arr['shortcode'] .= '</tr><tr>';

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