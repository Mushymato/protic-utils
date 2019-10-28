<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '4428';
$re = array_key_exists('r', $_POST) ? $_POST['r'] : 'jp';
$ids = search_ids($input_str, $re);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Region: <input type="radio" name="r" value="jp" <?php if($re == 'jp'){echo 'checked';}?>> JP <input type="radio" name="r" value="na" <?php if($re == 'na'){echo 'checked';}?>> NA</p>
<p>Enter search terms, one per line:</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => array(), 'shortcode' => array());
foreach($ids as $id){
	$card = get_card_summary($id[0], $re);
	$output_arr['html'][] = $card['html'];
	$output_arr['shortcode'][] = $card['shortcode'];
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . implode(PHP_EOL . PHP_EOL, $output_arr[$om]) . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . implode('<br/>', $output_arr['html']) . '</div>' . PHP_EOL; ?>
</body>
</html>