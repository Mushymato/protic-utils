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
foreach(explode(PHP_EOL, $utf_string) as $name){
	if(strlen($name) == 0){
		continue;
	}
	$mon = query_name_for_monster_no($conn, $name);
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$out = $out . '[pdx id=' . $mon['MONSTER_NO'] . ']';
	}else{
		$out = $out . PHP_EOL . '<strong><span class="subtit_orange">' . $name . '</span></strong>' . PHP_EOL;
	}
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
echo '<div>' . PHP_EOL . $out . PHP_EOL . '</div>';
?>
</body>
</html>