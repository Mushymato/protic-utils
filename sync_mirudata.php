<?php
include 'miru_common.php';
$time_start = microtime(true);

$tablename = 'computedNames';
$pk = 'COMPUTED_NAME';
$pairs = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/protic/paddata/miru_data/computed_names.json'), true);
$data = array();
foreach($pairs as $computed_name => $monster_no){
	$data[] = array('COMPUTED_NAME' => $computed_name, 'MONSTER_NO' => $monster_no);
}
$fieldnames = array('COMPUTED_NAME', 'MONSTER_NO');
recreate_table($data, $tablename, $fieldnames, $pk);
populate_table($data, $tablename, $fieldnames);

$dadguide_sql_dump = file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/db/dadguide.mysql');
$miru->conn->multi_query($dadguide_sql_dump);
//Make sure this keeps php waiting for queries to be done
do{} while($miru->conn->more_results() && $miru->conn->next_result());
echo 'Imported dadguide db' . PHP_EOL;

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>