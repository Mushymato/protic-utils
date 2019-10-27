<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="card-stats-table.css">
</head>
<body>
<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '4428';
$re = array_key_exists('r', $_POST) ? $_POST['r'] : 'JP';
$ids = search_ids($input_str, $re);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
$tb = array_key_exists('t', $_POST) ? $_POST['t'] : 'left';
$hd = array_key_exists('h', $_POST) ? $_POST['h'] : 'tocOnly';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Region: <input type="radio" name="r" value="JP" <?php if($re == 'JP'){echo 'checked';}?>> JP <input type="radio" name="r" value="US" <?php if($re == 'US'){echo 'checked';}?>> US</p>
<p>Table Side: <input type="radio" name="t" value="left" <?php if($tb == 'left'){echo 'checked';}?>> Left <input type="radio" name="t" value="right" <?php if($tb == 'right'){echo 'checked';}?>> Right</p>
<p>ToC and Headings? <input type="radio" name="h" value="yes" <?php if($hd == 'yes'){echo 'checked';}?>> Yes <input type="radio" name="h" value="tocOnly" <?php if($hd == 'tocOnly'){echo 'checked';}?>> ToC only <input type="radio" name="h" value="no" <?php if($hd == 'no'){echo 'checked';}?>> No</p>
<p>Enter search terms, one per line:</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => array(), 'shortcode' => array());
foreach($ids as $id){
	$card = get_card_grid($id, $re, $tb == 'right', $hd);
	$output_arr['html'][] = $card['html'];
	$output_arr['shortcode'][] = $card['shortcode'];
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php 
	echo '<textarea style="width:80vw;height:20vh;" readonly>';
	if($om == 'shortcode' && $hd != 'no'){
		echo '[shortcode_box title="Click on the icon to jump to the card:"]';
		foreach($ids as $id){
			echo '[pdx id=' . $id . ' a=#card_' . $id . ' r=' . $re . ']';
		}
		echo '[/shortcode_box]' . PHP_EOL . PHP_EOL;
	}
	echo implode(PHP_EOL . PHP_EOL, $output_arr[$om]) . '</textarea>'; 
?>
<p>Preview</p>
<?php echo '<div>' . implode('<br/>', $output_arr['html']) . '</div>' . PHP_EOL; ?>
</body>
</html>