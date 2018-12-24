<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="card-stats-table.css">
</head>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '4428';
$ids = search_ids($conn, $input_str);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
$tb = array_key_exists('t', $_POST) ? $_POST['t'] : 'left';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Table Side: <input type="radio" name="t" value="left" <?php if($tb == 'left'){echo 'checked';}?>> Left <input type="radio" name="t" value="right" <?php if($tb == 'right'){echo 'checked';}?>> Right</p>
<p>Enter search terms, one per line:</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => array(), 'shortcode' => array());
foreach($ids as $id){
	$card = get_card_grid($conn, $id, $tb == 'right');
	$output_arr['html'][] = $card['html'];
	$output_arr['shortcode'][] = $card['shortcode'];
}
$conn->close();
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php 
	echo '<textarea style="width:80vw;height:20vh;" readonly>';
	if(sizeof($ids) > 1 && $om == 'shortcode'){
		echo '[shortcode_box title="Click on the icon to jump to the card:"]';
		foreach($ids as $id){
			echo '[pdx id=' . $id . ' a=#' . $id . ']';
		}
		echo '[/shortcode_box]' . PHP_EOL . PHP_EOL;
	}
	echo implode(PHP_EOL . PHP_EOL, $output_arr[$om]) . '</textarea>'; 
?>
<p>Preview</p>
<?php echo '<div>' . implode('<br/>', $output_arr['html']) . '</div>' . PHP_EOL; ?>
</body>
</html>