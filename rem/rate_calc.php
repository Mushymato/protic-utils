<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Rate Calculator</title>
	<link rel="stylesheet" type="text/css" href="rate_calc.css">
	<script src="rate_calc.js"></script>
</head>
<body>
<?php
function get_rarity_group($egg, $title, $rate, $id_array, $override_array = array()){
	$total = $rate * sizeof($id_array);
	$out = "<div class=\"rem-wrapper-rarity\"><img src=\"https://pad.protic.site/wp-content/uploads/pad-eggs/$egg.png\" width=\"30\"> <strong>$title | $rate% each, $total% total<br/></strong></div>";
	$out = $out . "<div class=\"rate-group\" data-rate=\"$rate\">";
	foreach($id_array as $id){
		$url = array_key_exists($id, $override_array) ? $override_array[$id] : "http://puzzledragonx.com/en/img/book/$id.png?w=60";
		$out = $out . "<div class=\"icon-check\"><input type=\"checkbox\" id=\"pad-cb-$id\"/><label for=\"pad-cb-$id\"><img class=\"pdx-icon\" src=\"$url\"></label></div>";
	}
	$out = $out . "</div>";
	return $out;
}

function load_rem($url){
	$selected = isset($_GET['rem']) ? $_GET['rem'] : '';
	$data = json_decode(file_get_contents($url), true);
	$full_names = $data['FullNames'];
	$select_rem = '<select id="rem-select" name="rem"><option value=""></option>';
	foreach($full_names as $name => $full){
		$select_rem = $name == $selected ? $select_rem . "<option value=\"$name\" selected>$full</option>" : $select_rem . "<option value=\"$name\">$full</option>";
	}
	$select_rem = $select_rem . '</select><button type="submit" value="Submit">Submit</button>';
	$rem_groups = '';
	if($selected != ''){
		$rem = $data[$selected];
		foreach($rem as $rarity){
			$rem_groups = $rem_groups . get_rarity_group($rarity['egg'], $rarity['title'], $rarity['rate'], $rarity['id_array']);
		}
	}
	$tbar = '<h1>Total Rates = <span id="total-rate">0.00</span>%  <button type="reset" id="clear-selected" value="Reset">Reset</button></h1>';
	return '<form method="get">' . $select_rem . $tbar . $rem_groups . '</form>';
}
echo load_rem("./rem_rates.json");

?>
</body>
</html>
