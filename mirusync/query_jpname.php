<?php
include 'miru_common.php';
function check_table_exists($conn, $tablename){
	$sql = 'DESCRIBE ' . $tablename;
	if(!$conn->query($sql)){
		trigger_error('Describe' . $tablename . 'failed: ' . $conn->error);
		return false;
	}
}
function query_table($conn, $tablename, $fieldnames, $query_field, $q_str, $comparator = ' LIKE '){
	$sql = 'SELECT ';
	$res = array();
	$ref = array();
	foreach($fieldnames as $field){
		$sql = $sql . $field . ',';
		$ref[] = & $res[$field];
	}
	$sql = substr($sql, 0, -1) . ' FROM ' . $tablename . ' WHERE ';
	$sql = $sql . $query_field . $comparator . '?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('s', $q_str);
	call_user_func_array(array($stmt, 'bind_result'), $res);
	if(!$stmt->execute()){
		trigger_error('Select failed: ' . $conn->error);
		$stmt->close();
		return false;
	}
	$result = array();
	while($stmt->fetch()){
		$result[] = $res;
		return $result;
	}
}

include 'sql_param.php';

$utf_string = file_get_contents('mons.txt');
$conn = connect_sql($host, $user, $pass, $schema);
$tablename = 'monsterList';
$fieldnames = array(
	'MONSTER_NO',
	'TM_NAME_JP',
	'TM_NAME_US'
);
foreach(explode(PHP_EOL, $utf_string) as $line){
	$res = query_table($conn, $tablename, $fieldnames, 'TM_NAME_JP', $line, '=');
	if($res){
		krsort($res);
		echo '[pdx id=' . $res[0]['MONSTER_NO'] . ']';
	}else{
		echo $line . '</br>';
	}
}
$conn->close();
?>