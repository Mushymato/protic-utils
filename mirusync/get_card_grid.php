<!DOCTYPE html>
<html>
<body>
<?php
function search_id($conn){
	if(array_key_exists('s', $_GET) && $_GET['s'] != ''){
		$mon = query_monster($conn, $_GET['s']);
		if($mon){
			if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
				$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
			}
			return $mon['MONSTER_NO'];
		}
	}else{
		return '4428';
	}
}
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$id = search_id($conn);
$om = array_key_exists('o', $_GET) ? $_GET['o'] : 'html';
?>
<form method="get">
Search: <input type="text" name="s" value="<?php echo $id;?>">
Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$out = get_card_grid($conn, $id);
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $out[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $out['html'] . '</div>'; ?>
</body>
</html>