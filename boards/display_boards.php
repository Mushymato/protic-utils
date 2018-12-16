<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
</head>
<body>
<form>

</form>
<?php
include 'boards_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$boards = select_boards_by_size($conn);
foreach($boards as $board){
	$ratio = get_ratio($board);
	echo '<div style="float: left;" data-ratio="' . $ratio. '" data-style="' . $board['style'] . '" data-styleAtt="' . $board['styleCount'] . '" data-styleAtt="' . $board['styleCount'] . '"><p>' . $ratio . '</b> COMBO ' . $board['combo'] . ($board['style'] == 'MAXCOMBO' ? '' : ', <span class="orbbg ' . $orb_list[intval($board['styleAtt'])] . '" data-orb="' . $board['styleAtt'] . '">' . $board['style'] . ' ' . $board['styleCount'] . '</span>') . '</p>' . get_board($board['pattern']) . '</div>';
}
?>
</body>
</html>