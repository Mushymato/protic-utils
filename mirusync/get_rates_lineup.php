<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
//$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/';
$img_url = '/portrait/';
$utf_string = file_get_contents('mons.txt');
$out = '';
$time_start = microtime(true);
$byrates = array();
foreach(explode(PHP_EOL, $utf_string) as $line){
	$parts = explode('    ', $line);
	if(sizeof($parts) < 2){
		echo $line . PHP_EOL;
		continue;
	}
	$name = $parts[0];
	$rate = $parts[1];
	if(!array_key_exists($rate, $byrates)){
		$byrates[$rate] = array();
	}
	$mon = query_name_for_monster_no($conn, $name);
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$byrates[$rate][] = '[pdx id=' . $mon['MONSTER_NO'] . ']';
	}else{
		//$byrates[$rate][] = PHP_EOL . '<strong><span class="subtit_orange">' . $name . '</span></strong>' . PHP_EOL;
	}
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
//echo '<div>' . PHP_EOL . $out . PHP_EOL . '</div>';
foreach($byrates as $rate => $out){	
	echo PHP_EOL . '<strong>[shortcode_highlight]TITLE[/shortcode_highlight] | ' . $rate . ' each, ' . sizeof($out) * floatval(str_replace('%', '', $rate)) . '% total </strong>' . PHP_EOL . '<span>' . implode('', $out) . '</span>';
}
?>
</body>
</html>