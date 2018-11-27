<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$id = array_key_exists('id', $_GET) && $_GET['id'] != '' ? $_GET['id'] : '4428';?>
<form method="get">
ID: <input type="text" name="id" value="<?php echo $id;?>">
<input type="submit">
<?php
$time_start = microtime(true);
$out = get_card_grid($id);
echo '<p>Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>' . PHP_EOL;
echo $out;
?>

</form>
</body>
</html>