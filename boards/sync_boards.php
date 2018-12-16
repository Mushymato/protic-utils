<?php
include 'boards_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
load_boards_from_google_sheets($conn);
$conn->close();
?>