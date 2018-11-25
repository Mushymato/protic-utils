<?php
$insert_size = 144;

function connect_sql($host, $user, $pass, $schema){
	// Create connection
	$conn = new mysqli($host, $user, $pass);
	// Check connection
	if ($conn->connect_error) {
		trigger_error('Connection failed: ' . $conn->connect_error);
		header( 'HTTP/1.0 403 Forbidden', TRUE, 403 );
		die('you cannot');
	}
	$conn->set_charset('utf8');
	$conn->select_db($schema);
	return $conn;
}
function default_fieldnames($entry){
	$fieldnames = array();
	foreach($entry as $field => $value){
		$fieldnames[] = $field;
	}
	return $fieldnames;
}
function recreate_table($conn, $data, $tablename, $fieldnames, $pk){
	$sql = 'DROP TABLE IF EXISTS ' . $tablename;
	if($conn->query($sql)){
		$sql = 'CREATE TABLE ' . $tablename . '(';
		foreach($fieldnames as $field){
			$sql = $sql . $field . ' ';
			if(ctype_digit($data[0][$field])){
				if(strlen($data[0][$field]) > 8){
					$sql = $sql . 'BIG';
				}
				$sql = $sql . 'INT ';
			}else{
				$maxlen = 11;
				foreach($data as $entry){
					$len = strlen($entry[$field]);
					if($len > $maxlen){
						$maxlen = $len;
					}
				}
				$maxlen = $maxlen * 2;
				$sql = $sql . 'VARCHAR(' . $maxlen . ') ';
			}
			if($field == $pk){
				$sql = $sql . 'PRIMARY KEY,';
			}else{
				$sql = $sql . 'NOT NULL,';
			}
		}
		
		$sql = substr($sql, 0, -1) . ');';
		if(!$conn->query($sql)){
			trigger_error('Table creation failed: ' . $conn->error);
			return false;
		}
	}else{
		trigger_error('Drop table failed: ' . $conn->error);
		return false;
	}
}
function populate_table($conn, $data, $tablename, $fieldnames){
	global $insert_size;
	$sql = 'INSERT INTO ' . $tablename . ' (';
	$paramtype = '';
	foreach($fieldnames as $field){
		$sql = $sql . $field . ',';
		if(ctype_digit($data[0][$field])){
			$paramtype = $paramtype . 'i';
		}else{
			$paramtype = $paramtype . 's';
		}
	}
	$valueGroup = '(' . substr(str_repeat('?,', sizeof($fieldnames)), 0, -1) . '),';
	$sql = substr($sql, 0, -1) . ') VALUES ';
	$sql_m = $sql . substr(str_repeat($valueGroup, $insert_size), 0, -1) . ';';
	$paramtype_m = str_repeat($paramtype, $insert_size);
	$stmt = $conn->prepare($sql_m);
	$count = 0;
	$value_arr = array();
	foreach($data as $entry){
		foreach($fieldnames as $field){
			if(ctype_digit($data[0][$field]) && $entry[$field] == ''){
				$value_arr[] = '0';
			}else{
				$value_arr[] = $entry[$field];
			}
		}
		if(sizeof($value_arr) == strlen($paramtype_m)){
			$stmt->bind_param($paramtype_m, ...$value_arr);
			if(!$stmt->execute()){
				trigger_error('Insert failed: ' . $conn->error);
				echo 'Insert failed: ' . $conn->error;
			}else{
				$count += $insert_size;
			}
			$value_arr = array();
		}
	}
	$stmt->close();
	if(sizeof($value_arr) > 0){
		$remaining = sizeof($value_arr) / sizeof($fieldnames);
		$sql = $sql . substr(str_repeat($valueGroup, $remaining), 0, -1) . ';';
		$stmt = $conn->prepare($sql);
		$stmt->bind_param(str_repeat($paramtype, $remaining), ...$value_arr);
		if(!$stmt->execute()){
			trigger_error('Insert failed: ' . $conn->error);
			echo 'Insert failed: ' . $conn->error;
		}else{
			$count += $remaining;
		}
		$value_arr = array();
		$stmt->close();
	}
	echo 'Imported ' . $count . ' records out of ' . sizeof($data) . ' to ' . $tablename . PHP_EOL;
}
function get_google_sheets_data($url, $fieldnames){
	$data = array();
	if ($fh = fopen($url, 'r')) {
		if(!feof($fh)){fgets($fh);}
		while (!feof($fh)) {
			$tmp = explode(',',fgets($fh));
			$data[] = array(
				$fieldnames[0] => trim($tmp[0]),
				$fieldnames[1] => trim($tmp[1])
			);
		}
		fclose($fh);
	}
	return $data;
}
function execute_select_stmt($stmt){
	if(!$stmt->execute()){
		trigger_error($conn->error . '[select]');
		return false;
	}
	$stmt->store_result();
	if($stmt->num_rows == 0){
		$stmt->free_result();
		return array();
	}
	$fields = array();
	$row = array();
	$meta = $stmt->result_metadata(); 
	while($f = $meta->fetch_field()){
		$fields[] = & $row[$f->name];
	}
	call_user_func_array(array($stmt, 'bind_result'), $fields);
	$res = array();
	while ($stmt->fetch()) { 
		foreach($row as $key => $val){
			$c[$key] = $val; 
		} 
		$res[] = $c; 
	}
	return $res;
}
function check_table_exists($conn, $tablename){
	$sql = 'DESCRIBE ' . $tablename;
	if(!$conn->query($sql)){
		trigger_error('Describe' . $tablename . 'failed: ' . $conn->error);
		return false;
	}
}
?>