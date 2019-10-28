<?php
include 'miru_common.php';
$time_start = microtime(true);

$dadguide_sql_dump = file_get_contents('https://f002.backblazeb2.com/file/dadguide-data/db/dadguide.mysql');
$miru->conn->multi_query($dadguide_sql_dump);
//Make sure this keeps php waiting for queries to be done
do{} while($miru->conn->more_results() && $miru->conn->next_result());

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);
?>