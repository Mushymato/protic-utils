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
foreach(explode(PHP_EOL, $utf_string) as $line){
	$mon = query_monster($conn, $line);
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		//$out = $out . '[pdx id=' . $mon['MONSTER_NO'] . ']';
		$out = $out . '<div class="rem-detail"><div class="rem-card">[pdx id=' . $mon['MONSTER_NO'] . ']</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'] . '</div></div>';
		//$out = $out . '[pdx id=' . $mon['MONSTER_NO'] . '] ' . $mon['TM_NAME_US'] . PHP_EOL;
	}else{
		//$out = $out . PHP_EOL . $line . PHP_EOL;
		$out = $out . '</div><div class="rem-wrapper-rarity">[egg id=dia] <strong>' . $line . '</strong></div><div class="rem-wrapper-block">';
	}
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
echo '<div>' . PHP_EOL . $out . PHP_EOL . '</div>';
?>
</body>
</html>