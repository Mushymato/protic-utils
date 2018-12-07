<!DOCTYPE html>
<html>
<body>
<form method="post">
<p>Paste In-Game Lineup Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
</textarea>
<input type="submit">
</form>
<?php
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$utf_string = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$out = '';
$time_start = microtime(true);
foreach(explode(PHP_EOL, $utf_string) as $line){
	if($line == '★'){
		continue;
	}
	$parts = explode('    ', $line);
	if(sizeof($parts) < 2){
		$mon = query_monster($conn, $parts[0]);
	}else{
		$mon = query_monster($conn, $parts[sizeof($parts)-2]);
	}
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$out = $out . '<div class="rem-detail"><div class="rem-card">' . card_icon_img($portrait_url, $mon['MONSTER_NO'], $mon['TM_NAME_US']) . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		$evo_ids = select_evolutions($conn, $mon['MONSTER_NO']);
		if(sizeof($evo_ids) > 0){
			$out = $out . '<br/><span>';
			foreach($evo_ids as $id){
				$evo = query_monster($conn, $id);
				if($evo){
					$out = $out . card_icon_img($portrait_url, $evo['MONSTER_NO'], $evo['TM_NAME_US'], '40', '40') . ' ';					
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
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $out . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $out . '</div>'; ?>
</body>
</html>