<?php
include 'miru_common.php';
$time_start = microtime(true);

$tablename = 'computedNames';
$pk = 'COMPUTED_NAME';
$pairs = json_decode(file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/extra/computed_names.json'), true);
$data = array();
foreach($pairs as $computed_name => $monster_no){
	$data[] = array('COMPUTED_NAME' => $computed_name, 'MONSTER_NO' => $monster_no);
}
$fieldnames = array('COMPUTED_NAME', 'MONSTER_NO');
recreate_table($data, $tablename, $fieldnames, $pk);
populate_table($data, $tablename, $fieldnames);

// $dadguide_sql_dump = file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/db/dadguide.mysql');
// $miru->conn->multi_query($dadguide_sql_dump);
// do{} while($miru->conn->more_results() && $miru->conn->next_result());

include 'sql_param.php';
$file = fopen('dadguide.mysql', 'w');        
fwrite($file, file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/db/dadguide.mysql'));
fclose($file);
// $cmd = "mysql -h {$host} -u {$user} -p{$pass} {$schema} < dadguide.mysql";
// exec($cmd);
$templine = '';
$delim = FALSE;
$handle = fopen("dadguide.mysql", "r");
if ($handle) {
	$miru->conn->query('SET FOREIGN_KEY_CHECKS = 0;');
    while (($line = fgets($handle)) !== false) {
		// process the line read.
		if (trim($line) == 'DELIMITER ;;'){$delim = TRUE; continue;}
		if (trim($line) == 'DELIMITER ;'){$delim = FALSE; continue;}
		if ($delim || substr($line, 0, 2) == '--' || substr($line, 0, 2) == '/*' || $line == '') {continue;}
		$templine .= $line;
		if (substr(trim($templine), -1, 1) == ';'){
			// Perform the query
			if (!$miru->conn->query($templine)){
				echo 'Error performing query ' . $templine . ':' . PHP_EOL . $miru->conn->error . PHP_EOL;
			}
			// Reset temp variable to empty
			$templine = '';
		}
    }
	fclose($handle);
	$miru->conn->query('SET FOREIGN_KEY_CHECKS = 1;');
} else {
    // error opening the file.
}

$dungeon_icon_override = json_decode(file_get_contents('./guerrilla/dungeon_icon_overrides.json'), true);
$cond_types = array(
	'dungeon_id' => 'ii',
	'name_na' => 'is',
	'name_jp' => 'is'
);
foreach ($dungeon_icon_override as $override){
	if (strlen($override['icon_id']) == 0){
		trigger_error('Dungeon icon override failed: dungeon_id, name_na, name_jp all empty');
		continue;
	}
	foreach ($cond_types as $cond => $param_types){
		$sql = 'UPDATE dungeons SET icon_id=? WHERE '.$cond.'=?';
		$stmt = $miru->conn->prepare($sql);
		$stmt->bind_param($param_types, $override['icon_id'], $override[$cond]);
		$success = 'Set dungeon icon of '.$override[$cond].' to '.$override['icon_id'];
		if(!$stmt->execute()){
			trigger_error('Dungeon icon override failed: ' . $miru->conn->error);
		} else {
			if ($miru->conn->affected_rows == 0){
				echo 'Dungeon not found by '.$cond.'='.$override[$cond] . PHP_EOL;
			} else {
				echo $success . PHP_EOL;
				break;
			}
		}
	}
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>