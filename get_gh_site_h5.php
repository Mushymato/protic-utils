<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';

$input_str = array_key_exists('input', $_POST) ? 'https://pad.gungho.jp/member/' . $_POST['input'] . '.html': '';
$om = array_key_exists('o', $_POST) ? $_POST['o'] : 'shortcode';
$ev = array_key_exists('e', $_POST) ? $_POST['e'] : 'yes';
?>
<form method="post">
<p>Output Mode: <input type="radio" name="o" value="html" <?php if($om == 'html'){echo 'checked';}?>> HTML <input type="radio" name="o" value="shortcode" <?php if($om == 'shortcode'){echo 'checked';}?>> Shortcode <input type="submit"></p>
<p>Grab Evomats? <input type="radio" name="e" value="yes" <?php if($ev == 'yes'){echo 'checked';}?>> Yes <input type="radio" name="e" value="no" <?php if($ev == 'no'){echo 'checked';}?>> No</p>

<p>Paste partial URL Here:</p>
https://pad.gungho.jp/member/<input type='text' name="input" size="50" value="<?php echo array_key_exists('input', $_POST) ? $_POST['input'] : '';?>">.html
<input type="submit">
</form>
<?php
$time_start = microtime(true);

$handle = curl_init($input_str);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
$html = curl_exec($handle);
libxml_use_internal_errors(true); // Prevent HTML errors from displaying
$doc = new DOMDocument();
$doc->loadHTML($html);
$xpath = new DOMXpath($doc);

$headers = $xpath->query("//h5");
$output = '';
foreach ($headers as $hdr){
	$output .= $hdr->nodeValue . PHP_EOL;
}

echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
?>
<p>Output</p>
<?php echo '<textarea style="width:80vw;height:20vh;" readonly>' . $output . '</textarea>'; ?>
</body>
</html>