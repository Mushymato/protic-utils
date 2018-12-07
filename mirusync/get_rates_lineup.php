<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$utf_string = array_key_exists('input', $_POST) ? $_POST['input'] : '';
?>
<form method="post">
<p>Paste In-Game Lineup Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $utf_string;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$byrates = array();
foreach(explode(PHP_EOL, $utf_string) as $line){
	$parts = explode('    ', $line);
	if(sizeof($parts) < 2){
		continue;
	}
	$name = $parts[sizeof($parts)-2];
	$rate = $parts[sizeof($parts)-1];
	if(!array_key_exists($rate, $byrates)){
		$byrates[$rate] = array();
	}
	$mon = query_monster($conn, $name);
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$byrates[$rate][] = card_icon_img($portrait_url, $mon['MONSTER_NO'], $mon['TM_NAME_US']);
	}
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
$output_arr = array();
foreach($byrates as $rate => $out){	
	$output_arr[] = '<strong><span class="su-highlight" style="background:#ddff99;color:#000000">PLACEHOLDER PLEASE CHANGE</span> | ' . $rate . ' each, ' . sizeof($out) * floatval(str_replace('%', '', $rate)) . '% total </strong><br/><span>' . implode(' ', $out) . '</span>';
}
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . implode('<br/>', $output_arr) . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . implode('<br/>', $output_arr) . '</div>'; ?>

</body>
</html>