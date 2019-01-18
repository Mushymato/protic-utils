<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
<p>Paste In-Game Reward List Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$output = '';
foreach(explode("\n", $input_str) as $line){
	$parts = explode('    ', trim($line));
	$reward = $parts[0];
	if(sizeof($parts) == 2){
		if(strlen($output) != 0){
			$output = $output . '</td></tr>';
		}
		$output = $output . '<tr><td>' . $parts[0] . '</td><td>';
		$reward = $parts[1];
	}
	$mon = query_monster($conn, $reward);
	if($mon){
		$output = $output . '<div class="ranking-reward">[pdx id=' . $mon['MONSTER_NO'] . ']</div>';
	}else{
		$output = $output . '<div class="ranking-reward">' . $reward . '</div>';
	}
	
}
$conn->close();
$output = '<table width="660px"><thead><tr><td style="width: 120px;"><strong>Percentile</strong></td><td><strong>Rewards</strong></td></tr></thead><tbody>' . $output . '</tbody></table>';
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output . '</div>'; ?>
</body>
</html>
