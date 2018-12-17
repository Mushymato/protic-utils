<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="change_board_colors.js" type="text/javascript"></script>
<script>
window.onload = function(){
	refreshAllColors();
}
</script>
</head>
<body>
<?php
include 'boards_common.php';
$time_start = microtime(true);
if(array_key_exists('pattern', $_GET)){
	$pattern_array = str_split($_GET['pattern']);
	$solve = solve_board($pattern_array, $size_list['m']);
	$step_boards = array();
	$match_boards = array();
	$styles = array();
	foreach($solve as $step){
		$step_boards[] = get_board_arr($step['board']);
		if($step['solution']){
			$match_boards[] = get_board_arr(get_match_pattern($step['solution']));
			foreach($step['solution'] as $combo){
				if($combo['styles']){
					foreach($combo['styles'] as $style){
						$styles[] = '<div data-orb="' . $combo['color'] . '" class="border-box orb-bg ' . $combo['color'] . '">' . $style . '</div>';
					}
				}
			}
		}
	}
	echo '<div>Total Combos ' . count_combos($solve) . '</div>';
	echo 'Steps:<div class="float">' . implode($step_boards) , '</div>';
	if(sizeof($match_boards) > 0){echo 'Matched:<div class="float">' . implode($match_boards) , '</div>';}
	if(sizeof($styles) > 0){echo 'Styles:<div class="float">' . implode($styles) , '</div>';}
}
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
?>
<div><a href="display_boards.php">Back</a></div>
</body>
</html>