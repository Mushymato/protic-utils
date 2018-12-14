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
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Paste Skillup Dungeon Lineup:</p>
<textarea name="input" style="width:80vw;height:20vh;"><?php echo $input_str;?></textarea>
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => array(), 'shortcode' => array());
foreach(explode(PHP_EOL, $input_str) as $line){
	if(strlen($line) == 0){
		continue;
	}
	$monsters = explode('/', $line);
	if(sizeof($monsters) > 1){
		foreach($monsters as $name){
			$mon = query_monster($conn, $name);
			if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
				$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
			}
			$card = card_icon_img($mon['MONSTER_NO'], $mon['TM_NAME_US']);
			$output_arr['html'][] = $card['html'];
			$output_arr['shortcode'][] = $card['shortcode'];
		}
	}else{
		if(sizeof($output_arr['html']) > 0){
			$output_arr['html'][] = '</p>' . PHP_EOL;
			$output_arr['shortcode'][] = '</p>' . PHP_EOL;
		}
		$output_arr['html'][] = '<p><strong> PLACEHOLDER PLEASE REPLACE' . $line . '</strong><br/>' . PHP_EOL;
		$output_arr['shortcode'][] = '<p><strong>' . $line . '</strong><br/>' . PHP_EOL;
	}
}
if(sizeof($output_arr['html']) > 0){
	$output_arr['html'][] = '</p>';
	$output_arr['shortcode'][] = '</p>';
}
$conn->close();
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . implode($output_arr[$om]) . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . implode($output_arr['html']) . '</div>' . PHP_EOL; ?>
</body>
</html>