<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
</head>
<body>
<?php
include 'boards_common.php';
if(array_key_exists('pattern', $_GET)){
	$pattern_array = str_split($_GET['pattern']);
	$res = solve_board($pattern_array);
	echo 'Total Combos ' . count_combos($res[1]) . '<br/>';
	echo 'Steps:<div class="float">';
	foreach($res[0] as $boards){
		echo get_board($boards);
	}
	echo '</div>Combos Matched:<div class="float">';
	foreach($res[1] as $pass){
		$str_arr = str_split(str_repeat('-', 30));
		foreach($pass as $combo){
			foreach($combo['positions'] as $p){
				$str_arr[$p] = $combo['color'];
			}
		}
		echo get_board(implode($str_arr));
	}
	echo'</div>';
}
?>
</body>
</html>