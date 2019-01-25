<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$id_str = array_key_exists('id', $_GET) ? $_GET['id'] : '4428';
$mon = query_monster(trim($id_str));
if($mon){
	$id = $mon['MONSTER_NO'];
}else{
	die($id_str . ' not found');
}
$card = get_card_grid($id);
 echo '<div>' . $card['html'] . '</div>' . PHP_EOL; ?>
</body>
</html>