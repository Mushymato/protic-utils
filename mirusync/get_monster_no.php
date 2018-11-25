<!DOCTYPE html>
<html>
<body>
<?php
function single_param_stmt($conn, $query, $q_str){
	$stmt = $conn->prepare($query);
	$stmt->bind_param('s', $q_str);
	$res = execute_select_stmt($stmt);
	$stmt->close();
	return $res;
}
include 'miru_common.php';
function query_name_for_monster_no($conn, $q_str){
	if($q_str == ''){
		return false;
	}
	/*if(ctype_digit($q_str)){
		$query = 'SELECT MONSTER_NO FROM monsterList WHERE MONSTER_NO=?;';
		$res = single_param_stmt($conn, $query, $q_str);
		if(sizeof($res) > 0){
			return $res[0];
		}
	}*/
	$queries = array(
		'SELECT MONSTER_NO, TM_NAME_JP FROM monsterList WHERE TM_NAME_JP' => ' ORDER BY MONSTER_NO DESC',
		'SELECT MONSTER_NO, COMPUTED_NAME FROM computedNames WHERE COMPUTED_NAME' => ' ORDER BY LENGTH(COMPUTED_NAME) ASC'
	);
	$matching = array(
		array('=?',$q_str),
		array(' LIKE ?', '%' . $q_str),
		array(' LIKE ?', '%' . $q_str . '%')
	);
	foreach($matching as $m){
		foreach($queries as $q => $o){
			$res = single_param_stmt($conn, $q . $m[0] . $o, $m[1]);
			if(sizeof($res) > 0){
				return $res[0];
			}
		}
	}
	return false;
}
$name = array_key_exists('name', $_GET) && $_GET['name'] != '' ? $_GET['name'] : 'volcano dragon';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
?>
<form method="get">
NAME: <input type="text" name="name" value="<?php echo $name;?>">
<input type="submit">
</form>
<?php
//$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/';
$img_url = '/portrait/';
//$utf_string = file_get_contents('mons.txt');
//foreach(explode(PHP_EOL, $utf_string) as $name){
$time_start = microtime(true);
$out = '';
$mon = query_name_for_monster_no($conn, $name);
if($mon){
	if($mon['MONSTER_NO'] > 10000){ // crows in computedNames
		$mon['MONSTER_NO'] = $mon['MONSTER_NO'] - 10000;
	}
	$key = array_keys($mon);
	$out = $out . '<div style="float:left;width:100px;height:200px;font-size:10pt;"><img src="' . $img_url . $mon['MONSTER_NO'] . '.png">';
	foreach($key as $k){
		$out = $out .  '[' . $mon[$k] . ']<br/>';
	}
	$out = $out .  '</div>';
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
echo '<div>' . $out . '</div>';
//}
?>
</body>
</html>