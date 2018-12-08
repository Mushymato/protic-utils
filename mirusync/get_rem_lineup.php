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
Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <br/>
<p>Paste In-Game Lineup Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$byrarity = array('html' => '', 'shortcode' => '');
$check_rarity = false;
foreach(explode(PHP_EOL, $input_str) as $line){
	if($line == '★'){
		$check_rarity = true;
		continue;
	}
	$parts = explode('    ', $line);
	if($check_rarity){
		$check_rarity = false;
		if(strlen($byrarity['html']) > 0 || strlen($byrarity['shortcode']) > 0){
			$byrarity['html'] = $byrarity['html'] . '</div>';
			$byrarity['shortcode'] = $byrarity['shortcode'] . '</div>';
		}
		$rare = mb_convert_kana($parts[0], 'n');
		$egg = get_egg($rare);
		$byrarity['html'] = $byrarity['html'] . '<div class="rem-wrapper-rarity">' . $egg['html'] . ' <strong>★' . $rare . '</strong></div><div class="rem-wrapper-block">';
		$byrarity['shortcode'] = $byrarity['shortcode'] . '<div class="rem-wrapper-rarity">' . $egg['shortcode'] . ' <strong>★' . $rare . '</strong></div><div class="rem-wrapper-block">' . PHP_EOL;
	}
	if(sizeof($parts) < 2){
		$mon = query_monster($conn, $parts[0]);
	}else{
		$mon = query_monster($conn, $parts[sizeof($parts)-2]);
	}
	if($mon){
		if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
			$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
		}
		$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
		$byrarity['html'] = $byrarity['html'] . '<div class="rem-detail"><div class="rem-card">' . $card['html'] . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		$byrarity['shortcode'] = $byrarity['shortcode'] . '<div class="rem-detail"><div class="rem-card">' . $card['shortcode'] . '</div><div class="rem-name">[' . $mon['MONSTER_NO'] . '] <strong>' . $mon['TM_NAME_US'] . '</strong><br/>' . $mon['TM_NAME_JP'];
		$evo_ids = select_evolutions($conn, $mon['MONSTER_NO']);
		if(sizeof($evo_ids) > 0){
			$byrarity['html'] = $byrarity['html'] . '<br/><span>';
			$byrarity['shortcode'] = $byrarity['shortcode'] . '<br/><span>';
			foreach($evo_ids as $id){
				$evo = query_monster($conn, $id);
				if($evo){
					$card = card_icon_img($evo['MONSTER_NO'], $evo['TM_NAME_US'], '40', '40');
					$byrarity['html'] = $byrarity['html'] . $card['html'] . ' ';
					$byrarity['shortcode'] = $byrarity['shortcode'] . $card['shortcode'] . ' ';
				}
			}
			$byrarity['html'] = $byrarity['html'] . '</span>';
			$byrarity['shortcode'] = $byrarity['shortcode'] . '</span>';
		}
		$byrarity['html'] = $byrarity['html'] . '</div></div>';
		$byrarity['shortcode'] = $byrarity['shortcode'] . '</div></div>' . PHP_EOL;
	}
}
$conn->close();
if(strlen($byrarity['html']) > 0 || strlen($byrarity['shortcode']) > 0){
	$byrarity['html'] = $byrarity['html'] . '</div>';
	$byrarity['shortcode'] = $byrarity['shortcode'] . '</div>';
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $byrarity[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $byrarity['html'] . '</div>'; ?>
</body>
</html>