<!DOCTYPE html>
<?php include 'boards_common.php';?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="change_board_colors.js" type="text/javascript"></script>
<script>
window.onload = function(){
	<?php
		foreach($orb_list as $orb){
			if(array_key_exists($orb, $_GET)){
				echo 'changeColors("' . $orb . '", "' . $_GET[$orb] . '");';
			}
		}
	?>
}
</script>
</head>
<body>
<?php
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