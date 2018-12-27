<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '4428';
$ids = search_ids($conn, $input_str);
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Enter search terms, one per line:</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => array(), 'shortcode' => array());
foreach($ids as $id){
	$card = get_lb_stats_row($conn, $id);
	$output_arr['html'][] = $card['html'];
	$output_arr['shortcode'][] = $card['shortcode'];
}
$conn->close();
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
$thead = '<thead><tr><td>Card</td><td>Weighted</td><td>HP</td><td>ATK</td><td>RCV</td><td>Super Awakes</td></tr></thead>' . PHP_EOL;
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly><table>' . $thead . '<tbody>' . implode(PHP_EOL, $output_arr[$om]) . '</tbody></table></textarea>'; ?>
<p>Preview</p>
<?php echo '<div><table>' . $thead . '<tbody>' . implode('', $output_arr['html']) . '</tbody></table></div>' . PHP_EOL; ?>
</body>
</html>