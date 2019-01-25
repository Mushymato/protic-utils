<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
$id_str = array_key_exists('id', $_GET) ? $_GET['id'] : '4428';
$id = search_ids($id_str)[0];
$card = get_card_grid($id);
 echo '<div>' . $card['html'] . '</div>' . PHP_EOL; ?>
</body>
</html>