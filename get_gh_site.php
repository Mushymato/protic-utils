<!DOCTYPE html>
<html>
<body>
<?php
# include 'miru_common.php';
$input_str = array_key_exists('input', $_POST) ? $_POST['input'] : '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'html';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Paste URL Here:</p>
<textarea name="input" style="width:80vw;height:20vh;">
<?php echo $input_str;?>
</textarea>
<input type="submit">
</form>
<?php
$time_start = microtime(true);
$output_arr = array('html' => '', 'shortcode' => '');

/*$handle = curl_init($input_str);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($handle);
libxml_use_internal_errors(true);*/ // Prevent HTML errors from displaying
$doc = new DOMDocument();
$doc->loadHTML(file_get_contents($input_str));
$xpath = new DOMXpath($doc);

$buff_tables = $xpath->query("//table[@class='monster_list twi_icon']");
foreach ($buff_tables as $tbl){
	foreach ($tbl->childNodes as $tr){
		foreach ($tr->childNodes as $td){
		echo $td->nodeValue . PHP_EOL;
		}
	}
}

echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output_arr[$om] . '</textarea>'; ?>
<p>Preview</p>
<?php echo '<div>' . $output_arr['html'] . '</div>'; ?>
</body>
</html>