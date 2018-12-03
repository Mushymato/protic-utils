<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/';
//$img_url = '/portrait/';
$utf_string = file_get_contents('mons.txt');
$out = '';
$time_start = microtime(true);
foreach(explode(PHP_EOL, $utf_string) as $line){
	$parts = explode('    ', $line);
	$mon = query_monster($conn, $parts[0]);
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$out = $out . '<div class="rem-detail"><div class="rem-card">' . card_icon_img($img_url, $mon['MONSTER_NO'], $mon['TM_NAME_US']) . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		if(sizeof($evo_ids = get_evolutions($conn, $mon['MONSTER_NO'])) > 0){
			$out = $out . '<br/><span>';
			foreach($evo_ids as $id){
				$evo = query_monster($conn, $id);
				if($evo){
					$out = $out . card_icon_img($img_url, $evo['MONSTER_NO'], $evo['TM_NAME_US'], '40', '40') . ' ';					
				}
			}
			$out = $out . '</span>';
		}
		$out = $out . '</div></div>';
	}else{
		if(strlen($out) > 0){
			$out . '</div>';
		}
		$rare = str_replace('★', '', $line);
		$out = $out . '<div class="rem-wrapper-rarity">' . get_egg($rare) . ' <strong>' . $line . '</strong></div><div class="rem-wrapper-block">';
	}
}
if(strlen($out) > 0){
	$out . '</div>';
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
echo '<div>' . PHP_EOL . $out . PHP_EOL . '</div>';
?>
</body>
</html>