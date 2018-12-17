<?php
$size_list = array('s' => array(5,4), 'm' => array(6,5), 'l' => array(7,6));
$orb_list = array('R', 'B', 'G', 'L', 'D', 'H', 'J', 'X', 'P', 'M');
$var_orb_list = array('R', 'B', 'G', 'L', 'D');
function get_board($pattern, $size = 'm'){
	global $orb_list;
	$out = '<div class="board ' . $size . '">';
	foreach(str_split($pattern) as $o){
		$out = $out . '<div class="orb ' . $o . '" data-orb="' . $o . '"></div>';
	}
	$out = $out . '</div>';
	return $out;
}
function count_orbs($pattern, $ol = null){
	if($ol == null){
		global $orb_list;
		$ol = $orb_list;
	}
	$counts = array();
	foreach($ol as $orb){
		$counts[$orb] = 0;
	}
	foreach(str_split($pattern) as $o){
		if(in_array($o, $ol)){
			$counts[$o] += 1;
		}
	}
	return $counts;
}
function get_ratio($board){
	global $orb_list;
	$out = '';
	foreach($orb_list as $orb){
		if($board[$orb] != 0){
			$out = $out . $board[$orb] . '-';
		}
	}
	return substr($out, 0, -1);
}
function normalize($entry){
	global $var_orb_list;
	$entry['size'] = strtolower($entry['size']);
	$entry['pattern'] = strtoupper($entry['pattern']);
	$counts = array_filter(count_orbs($entry['pattern'], $var_orb_list));
	arsort($counts);
	$i = 0;
	foreach($counts as $orb => $count){
		$entry['pattern'] = str_replace($orb, strval($i), $entry['pattern']);
		if(in_array($entry['styleAtt'], $var_orb_list) && $entry['styleAtt'] == $orb){
			$entry['styleAtt'] = strval($i);
		}
		$i++;
	}
	foreach($var_orb_list as $idx => $orb){
		$entry['pattern'] = str_replace($idx, $orb, $entry['pattern']);
		if($entry['styleAtt'] == strval($idx)){
			$entry['styleAtt'] = $orb;
		}
	}
	$res = solve_board(str_split($entry['pattern']));
	$entry['combo'] = count_combos($res[1]);
	$entry = array_merge($entry, count_orbs($entry['pattern']));
	return $entry;
}
function invert($entry){
	global $var_orb_list;
	$entry['size'] = strtolower($entry['size']);
	$entry['pattern'] = strtoupper($entry['pattern']);
	$counts = array_filter(count_orbs($entry['pattern'], $var_orb_list));
	asort($counts);
	$i = 0;
	foreach($counts as $orb => $count){
		$entry['pattern'] = str_replace($orb, strval($i), $entry['pattern']);
		if(in_array($entry['styleAtt'], $var_orb_list) && $entry['styleAtt'] == $orb){
			$entry['styleAtt'] = strval($i);
		}
		$i++;
	}
	foreach($var_orb_list as $idx => $orb){
		$entry['pattern'] = str_replace($idx, $orb, $entry['pattern']);
		if($entry['styleAtt'] == strval($idx)){
			$entry['styleAtt'] = $orb;
		}
	}
	$res = solve_board(str_split($entry['pattern']));
	$entry['combo'] = count_combos($res[1]);
	$entry = array_merge($entry, count_orbs($entry['pattern']));
	return $entry;
}
class FloodFill{
	public $p_arr = array();
	public $comboColor = '';
	public $wh = array();
	public $minimumMatched;
	public $comboPositionList = array();
	public $comboTracker = array();
	public $stack = array();
	public $solutions = array();
	public $track = array();
	function __construct($p_arr, $wh, $minimumMatched, $comboPositionList) {
		$this->p_arr = $p_arr;
		$this->minimumMatched = $minimumMatched;
		$this->wh = $wh;
		$this->comboPositionList = $comboPositionList;
		foreach($comboPositionList as $key => $value){
			$this->comboTracker[$value] = $key;
		}
	}
	function convertXY($p){
		return array($p%$this->wh[0], floor($p/$this->wh[0]));
	}
	function convertPosition($x, $y){
		return intval($y * $this->wh[0] + $x);
	}
	function alreadyFilled($x, $y){
		if ($x<0 || $y<0 || $x>$this->wh[0]-1 || $y>$this->wh[1]-1){
			return true;
		}
		if (!array_key_exists($this->convertPosition($x, $y), $this->comboTracker)){
			return true;
		}
		if ($this->p_arr[$this->convertPosition($x, $y)] != $this->comboColor){
			return true;
		}
		return false;
	}
	function fillPosition ($x, $y){
		if(!$this->alreadyFilled($x, $y)) {
			$p = $this->convertPosition($x, $y);
			unset($this->comboTracker[$p]);
			$this->track[] = $p;
		}
		if(!$this->alreadyFilled($x, $y-1)){
			$this->stack[] = array($x, $y-1);
		}
		if(!$this->alreadyFilled($x+1, $y)){
			$this->stack[] = array($x+1, $y);
		}
		if(!$this->alreadyFilled($x, $y+1)){
			$this->stack[] = array($x, $y+1);
		}
		if(!$this->alreadyFilled($x-1, $y)){
			$this->stack[] = array($x-1, $y);
		}
	}
	function floodFill($p){
		$this->comboColor = $this->p_arr[$p];
		if (!array_key_exists($p, $this->comboTracker)){
			return;
		}
		if($this->comboColor == '-'){
			return;
		}
		$this->track = array();
		$xy = $this->convertXY($p);
		$this->fillPosition($xy[0], $xy[1]);
		while(sizeof($this->stack)>0){
			$toFill = array_pop($this->stack);
			$this->fillPosition($toFill[0], $toFill[1]);
		}
		if(sizeof($this->track) > $this->minimumMatched){
			$this->solutions[] = array('color' => $this->comboColor, 'positions' => $this->track);
		}
	}
}
function solve_board($p_arr, $size = 'm', $minimumMatched = 2){
	global $size_list;
	$wh = $size_list[$size];

	$board_states = array();
	$board_states[] = implode($p_arr);
	
	$comboPositionList = array();
	$comboColor = '';
	$comboPosition = array();
	for($f = 0; $f < $wh[1]; $f++){
		$comboColor = '';
		$comboPosition = array();
		for($i = $f*$wh[0]; $i < $f*$wh[0]+$wh[0]; $i++){
			if ($p_arr[$i] != $comboColor){
				if (sizeof($comboPosition) > $minimumMatched){
					$comboPositionList = array_merge($comboPositionList, $comboPosition);
				}
				$comboColor = $p_arr[$i];
				$comboPosition = array();
			}
			$comboPosition[] = $i;
			if (sizeof($comboPosition) > $minimumMatched && $i == $f*$wh[0]+$wh[0]-1){
				$comboPositionList = array_merge($comboPositionList, $comboPosition);
			}
		}
	}
	for($f = 0; $f < $wh[0]; $f++){
		$comboColor = '';
		$comboPosition = [];
		for($i = 0+$f; $i < $wh[0]*$wh[1]; $i=$i+$wh[0]){
			if ($p_arr[$i] != $comboColor){
				if (sizeof($comboPosition) > $minimumMatched){
					$comboPositionList = array_merge($comboPositionList, $comboPosition);
				}
				$comboColor = $p_arr[$i];
				$comboPosition = array();
			}
			$comboPosition[] = $i;
			if (sizeof($comboPosition) > $minimumMatched && $i > $wh[0]*($wh[1]-1)-1){
				$comboPositionList = array_merge($comboPositionList, $comboPosition);
			}
		}
	}
	
	if (sizeof($comboPositionList) == 0){
		return false;
	}
	$ff = new FloodFill($p_arr, $wh, $minimumMatched, $comboPositionList);
	foreach($comboPositionList as $p){
		$ff->floodFill($p);
	}
	if(sizeof($ff->solutions) == 0){
		return false;
	}
	foreach($ff->solutions as $combo){
		foreach($combo['positions'] as $p){
			$p_arr[$p] = '-';
		}
	}
	for($f = 0; $f < $wh[0]; $f++){
		for($i = $wh[0]*$wh[1] - $f - 1; $i >= 0 + $f; $i=$i-$wh[0]){
			if($p_arr[$i] != '-'){
				continue;
			}
			$n = $i;
			while($n-$wh[0] >= 0 && $p_arr[$n] == '-'){
				$n = $n-$wh[0];
			}
			$p_arr[$i] = $p_arr[$n];
			$p_arr[$n] = '-';
		}
	}

	$res = solve_board($p_arr);
	if($res){
		return array(array_merge($board_states, $res[0]), array_merge(array($ff->solutions), $res[1]));
	}else{
		$board_states[] = implode($p_arr);
		return array($board_states, array($ff->solutions));
	}
}
function count_combos($solution){
	$count = 0;
	foreach($solution as $pass){
		$count += sizeof($pass);
	}
	return $count;
}
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
function insert_board($conn, $combo, $style, $pattern, $styleCount = 0, $size = 'm', $description = ''){
	global $size_list;
	global $orb_list;
	if(!in_array($size, $size_list)){
		return false;
	}
	$pattern = strtoupper($pattern);
	foreach(str_split($pattern) as $o){
		if(!in_array($o, $orb_list)){
			return false;
		}
	}
	if($styleCount == ''){
		$styleCount = 0;
	}
	$sql = 'INSERT INTO `Boards` (`size`,`pattern`,`combo`,`style`,`styleCount`,`description`) VALUES (?,?,?,?,?,?);';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('ssisis', $size, $pattern, $combo, $style, $styleCount, $description);
	if(!$stmt->execute()){
		trigger_error('Insert failed: ' . $conn->error);
		$stmt->close();
		return false;
	}
	$stmt->close();
	return true;
}
function load_boards_from_google_sheets($conn){
	global $orb_list;
	$url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQkDdwvr-R6t4SbqlLddS302UtKWvMx-rGIRDKD8_6AszcvNNv_N56SOoffaw1eRZbP0cUmM3eges1G/pub?gid=0&single=true&output=csv';
	$data = array();
	$fieldnames = array();
	if ($fh = fopen($url, 'r')) {
		if(!feof($fh)){
			$fieldnames = explode(',',trim(fgets($fh)));
		}
		while (!feof($fh)) {
			$tmp = explode(',',trim(fgets($fh)));
			$entry = array();
			for($i = 0; $i < sizeof($fieldnames); $i++){
				$entry[$fieldnames[$i]] = $tmp[$i] == '' ? null : $tmp[$i];
			}
			$data[] = normalize($entry);
			$data[] = invert($entry);
		}
		fclose($fh);
	}else{
		trigger_error('Failed to open google sheet.');
		return false;
	}
	$fieldnames = array_merge($fieldnames, $orb_list);
	$tablename = 'Boards';
	$sql = 'TRUNCATE TABLE ' . $tablename;
	if(!$conn->query($sql)){
		trigger_error('Truncate ' . $tablename . ' failed: ' . $conn->error);
		return false;
	}
	$insert_size = 1;
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
	$valueGroup = ' (' . substr(str_repeat('?,', sizeof($fieldnames)), 0, -1) . '),';
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
		}else{
			$count += $remaining;
		}
		$value_arr = array();
		$stmt->close();
	}
	echo 'Imported ' . $count . ' records out of ' . sizeof($data) . ' to ' . $tablename . PHP_EOL;
}
function select_boards_by_size($conn, $size = 'm'){
	$sql = 'SELECT `Boards`.`bID`,`Boards`.`size`,`Boards`.`pattern`,`Boards`.`combo`,`Boards`.`style`,`Boards`.`styleAtt`,`Boards`.`styleCount`,`Boards`.`R`,`Boards`.`B`,`Boards`.`G`,`Boards`.`L`,`Boards`.`D`,`Boards`.`H`,`Boards`.`J`,`Boards`.`X`,`Boards`.`P`,`Boards`.`M`,`Boards`.`description` FROM `Boards` WHERE `Boards`.`size`=? ORDER BY `Boards`.`R` DESC;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('s', $size);
	$res = execute_select_stmt($stmt);
	$stmt->close();
	return $res;
}
function get_filters($conn){
	$out = '<form>';
	
}
?>