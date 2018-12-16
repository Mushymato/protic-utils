<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
</head>
<body>
<?php
include 'boards_common.php';
include 'sql_param.php';
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
$conn = connect_sql($host, $user, $pass, $schema);
$boards = select_all_boards($conn);
foreach($boards as $board){
	echo '<div style="float: left;"><p><b>' . get_ratio($board) . '</b> COMBO ' . $board['combo'] . ($board['style'] == 'MAXCOMBO' ? '' : ', ' . $board['styleAtt'] . ' ' . $board['style'] . ' ' . $board['styleCount']) . '</p>' . get_board($board['pattern']) . '</div>';
}
?>
</body>
</html>