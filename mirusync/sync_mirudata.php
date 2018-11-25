<?php
include 'miru_common.php';
include 'sql_param.php';
$time_start = microtime(true);

$conn = connect_sql($host, $user, $pass, $schema);
/*lists*/
$lists = array(
	'monsterList' => 'MONSTER_NO',
	'monsterAddInfoList' => 'MONSTER_NO',
	'evolutionList' => 'TV_SEQ',
	'skillList' => 'TS_SEQ',
	'awokenSkillList' => 'TMA_SEQ',
	'skillLeaderDataList' => 'TS_SEQ'
);
foreach($lists as $tablename => $pk){
	$data = json_decode(file_get_contents("https://storage.googleapis.com/mirubot/paddata/padguide/$tablename.json"), true)['items'];
	$fieldnames = default_fieldnames($data[0]);
	// exclude search_data and timestamp
	$fieldnames = array_diff($fieldnames, ['SEARCH_DATA', 'TSTAMP']);
	recreate_table($conn, $data, $tablename, $fieldnames, $pk);
	populate_table($conn, $data, $tablename, $fieldnames);
}
$tablename = 'computedNames';
$pk = 'COMPUTED_NAME';
$pairs = json_decode(file_get_contents('https://storage.googleapis.com/mirubot/paddata/miru_data/computed_names.json'), true);
$data = array();
foreach($pairs as $computed_name => $monster_no){
	$data[] = array('COMPUTED_NAME' => $computed_name, 'MONSTER_NO' => $monster_no);
}
$fieldnames = array('COMPUTED_NAME', 'MONSTER_NO');
recreate_table($conn, $data, $tablename, $fieldnames, $pk);
populate_table($conn, $data, $tablename, $fieldnames);

/*nickname list*/
//$gid = array('2070615818', '0');
/*$fieldnames = array(
	'nickname',
	'MONSTER_NO'
);
$data = get_google_sheets_data('https://docs.google.com/spreadsheets/d/1EoZJ3w5xsXZ67kmarLE4vfrZSIIIAfj04HXeZVST3eY/pub?gid=0&single=true&output=csv', $fieldnames);
$tablename = 'nicknames';
$pk = 'nickname';
recreate_table($conn, $data, $tablename, $fieldnames, $pk);
populate_table($conn, $data, $tablename, $fieldnames);

$fieldnames = array(
	'MONSTER_NO',
	'basename'
);
$data = get_google_sheets_data('https://docs.google.com/spreadsheets/d/1EoZJ3w5xsXZ67kmarLE4vfrZSIIIAfj04HXeZVST3eY/pub?gid=2070615818&single=true&output=csv', $fieldnames);
$tablename = 'basenames';
$pk = '';
recreate_table($conn, $data, $tablename, $fieldnames, $pk);
populate_table($conn, $data, $tablename, $fieldnames);*/

$conn->close();

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>