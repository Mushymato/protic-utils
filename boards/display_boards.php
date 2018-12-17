<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="change_board_colors.js" type="text/javascript"></script>
<script>
$(document).ready(function(){
	addChangeColorListeners("data-attribute");
	refreshAllColors();
});
</script>
</head>
<body>
<?php
include 'boards_common.php';
include 'sql_param.php';
$conn = connect_sql($host, $user, $pass, $schema);
$boards = select_boards_by_size($conn);
?>
<form><?php echo get_att_change_radios($boards);?></form>
<?php foreach($boards as $board){
	$ratio = get_ratio($board);
	echo '<div class="board-box" style="float: left;" data-ratio="' . $ratio. '" data-style="' . $board['style'] . '" data-styleAtt="' . $board['styleCount'] . '" data-styleAtt="' . $board['styleCount'] . '"><p>' . $ratio . '</b> COMBO ' . $board['combo'] . ($board['style'] == 'MAXCOMBO' ? '' : ', <span data-orb="' . $board['styleAtt'] . '" class="orb-bg ' . $board['styleAtt'] . '">' . $board['style'] . ' ' . $board['styleCount'] . '</span>') . '</p><a class="board-url" href="solve_boards.php?pattern=' . $board['pattern'] . '">' . get_board($board['pattern']) . '</a></div>';
}
?>
</body>
</html>