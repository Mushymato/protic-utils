<!DOCTYPE html>
<html>
<body>
<?php
function search_id($conn){
	if(array_key_exists('s', $_GET) && $_GET['s'] != ''){
		if(ctype_digit($_GET['s'])){
			return $_GET['s'];
		}else{
			$mon = query_monster($conn, $_GET['s']);
			if($mon){
				if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
					$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
				}
				return $mon['MONSTER_NO'];
			}
		}
	}else{
		return '4428';
	}
}
include 'miru_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$id = search_id($conn);
?>
<form method="get">
Search: <input type="text" name="s" value="<?php echo $id;?>">
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$out = get_card_summary($conn, $id);
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $out . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $out . '</div>'; ?>
</body>
</html>