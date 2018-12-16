<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
<script type="text/javascript">
function previewBoard(){
	var pattern = document.getElementById("pattern");
	var preview = document.getElementById("preview-box");
	while (preview.firstChild) {
		preview.removeChild(preview.firstChild);
	}
	for(var o of pattern.value.split("")){
		var orb = document.createElement("div");
		orb.className = "orb " + o.toUpperCase();
		preview.appendChild(orb);
	}
}
window.onload = function(){
	document.getElementById("preview").addEventListener("click", previewBoard);
}
</script>
</head>
<body>
<form method="post" action="">
	<legend>Insert Board</legend>
<fieldset>
	<div>Combo: <input type="text" name="combo" required> Style: <input type="text" name="style" required> Style Count: <input type="text" name="styleCount" value="0"></div>
	<div>Pattern (<a id="preview" href="#">Preview</a>):<br/><textarea id="pattern" class="m" name="pattern" maxlength=30 required></textarea><div id="preview-box" class="board m" style="margin: 0"></div></div>
	<div><button type="reset" value="Reset">Reset</button><button type="submit" value="Submit">Submit</button></div>
</fieldset>
</form>
<?php
include 'boards_common.php';
include 'sql_param.php';
if(array_key_exists('combo', $_POST) &&
	array_key_exists('style', $_POST) &&
	array_key_exists('styleCount', $_POST) &&
	array_key_exists('pattern', $_POST)){
	$conn = connect_sql($host, $user, $pass, $schema);
	insert_board($conn, $_POST['combo'], $_POST['style'], $_POST['pattern']);
	$conn->close();
}
?>
</body>
</html>