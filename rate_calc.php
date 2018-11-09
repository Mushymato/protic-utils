<!DOCTYPE html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Rate Calculator</title>
	<link rel="stylesheet" type="text/css" href="rate_calc.css">
	<script src="rate_calc.js"></script>
</head>
<body>
<form>
<h1>Total Rates = <span id="total-rate">0.00</span>%  <button type="reset" id="clear-selected" value="Reset">Reset</button></h1>
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
	$data = file_get_contents($url);
	$rem = json_decode($data, true)['items'];
	foreach($rem as $rarity){
		echo get_rarity_group($rarity['egg'], $rarity['title'], $rarity['rate'], $rarity['id_array'], $rarity['override_array']);
	}
}
load_rem("./rem_dbdc.json");

/*echo get_rarity_group("Diamond","★7", 2.00, array(4796,4798,4800,4186,3930));
echo get_rarity_group("Diamond","★6", 4.50, array(4802,4804,4806,4188,3932,3934,3940,3946,3949));
echo get_rarity_group("Gold1","★5", 7.07, array(4809,4190,3936,3938,3942,3944,3950));*/

?>
</form>
</body>
</html>
