<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
</head>
<body>
<?php
include 'boards_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$boards = select_boards_by_size($conn);
foreach($boards as $board){
	$ratio = get_ratio($board);
	echo '<div style="float: left;" data-ratio="' . $ratio. '" data-style="' . $board['style'] . '" data-styleAtt="' . $board['styleCount'] . '" data-styleAtt="' . $board['styleCount'] . '"><p>' . $ratio . '</b> COMBO ' . $board['combo'] . ($board['style'] == 'MAXCOMBO' ? '' : ', <span class="orbbg ' . $board['styleAtt'] . '" data-orb="' . $board['styleAtt'] . '">' . $board['style'] . ' ' . $board['styleCount'] . '</span>') . '</p><a href="solve_boards.php?pattern=' . $board['pattern'] . '">' . get_board($board['pattern']) . '</a></div>';
}
?>
</body>
</html>