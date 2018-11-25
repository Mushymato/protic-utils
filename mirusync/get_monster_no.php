<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
function query_name_for_monster_no($conn, $q_str){
	if($q_str == ''){
		return array();
	}
	$queries = array(
		'SELECT MONSTER_NO FROM monsterList WHERE MONSTER_NO' => '',
		'SELECT MONSTER_NO, TM_NAME_JP FROM monsterList WHERE TM_NAME_JP' => ' ORDER BY MONSTER_NO DESC',
		'SELECT MONSTER_NO, COMPUTED_NAME FROM computedNames WHERE COMPUTED_NAME' => ' ORDER BY LENGTH(COMPUTED_NAME) ASC',
		'SELECT MONSTER_NO, TM_NAME_US FROM monsterList WHERE TM_NAME_US' => ' ORDER BY MONSTER_NO DESC'
	);
	$matching = array(
		array('=?',$q_str),
		array(' LIKE ?', '%' . $q_str),
		array(' LIKE ?', '%' . $q_str . '%')
	);
	$order = ' ORDER BY MONSTER_NO DESC';
	foreach($matching as $m){
		foreach($queries as $q => $o){
			$stmt = $conn->prepare($q . $m[0] . $o);
			$stmt->bind_param('s', $m[1]);
			$res = execute_select_stmt($stmt);
			if(sizeof($res) > 0){
				return $res;
			}
			$stmt->close();
		}
	}
	return array();
}
$name = array_key_exists('name', $_GET) && $_GET['name'] != '' ? $_GET['name'] : 'volcano dragon';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
?>
<form method="get">
NAME: <input type="text" name="name" value="<?php echo $name;?>">
<input type="submit">
<?php
//$utf_string = file_get_contents('mons.txt');
//foreach(explode(PHP_EOL, $utf_string) as $name){
	$mons = query_name_for_monster_no($conn, $name);
	foreach($mons as $mon){
		//print_r($mon);
		//echo '<h3>' . $mon['MONSTER_NO'] . '</h3><p><img src="https://storage.googleapis.com/mirubot/padimages/jp/portrait/' . $mon['MONSTER_NO'] . '.png"></p>';
		echo '<h3>' . $mon['MONSTER_NO'] . '</h3><p><img src="/portrait/' . $mon['MONSTER_NO'] . '.png"></p>';
	}
//}
?>
</form>
</body>
</html>