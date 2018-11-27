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
	$matching = array(
		array('=?',$q_str),
		array(' LIKE ?', $q_str . '%'),
		array(' LIKE ?', '%' . $q_str . '%')
	);
	$query = array();
	if(!mb_check_encoding($q_str, 'ASCII')){
		$query['SELECT MONSTER_NO, TM_NAME_JP FROM monsterList WHERE TM_NAME_JP'] = ' ORDER BY MONSTER_NO DESC';
	}else{
		$query['SELECT MONSTER_NO, COMPUTED_NAME FROM computedNames WHERE COMPUTED_NAME'] = ' ORDER BY LENGTH(COMPUTED_NAME) ASC';		
		$query['SELECT MONSTER_NO_US MONSTER_NO, TM_NAME_US FROM monsterList WHERE TM_NAME_US'] = ' ORDER BY MONSTER_NO DESC';		
	}
	foreach($matching as $m){
		foreach($query as $q => $o){
			$res = single_param_stmt($conn, $q . $m[0] . $o, $m[1]);
			if(sizeof($res) > 0){
				return $res[0];
			}
		}
	}
	return false;
}
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