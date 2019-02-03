<?php
include 'miru_common.php';
$time_start = microtime(true);

$sql = 'SELECT MONSTER_NO FROM monsterList';
$stmt = $miru->conn->prepare($sql);
$res = execute_select_stmt($stmt);
$stmt->close();

$miru_portrait_url = 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/';
$miru_full_url = 'https://storage.googleapis.com/mirubot/padimages/jp/full/';
$pad_illust_url = 'https://pad.gungho.jp/member/img/graphic/illust/';
foreach($res as $r){
	if(grab_img_if_exists($miru_portrait_url, $r['MONSTER_NO'], $_SERVER['DOCUMENT_ROOT'] . $portrait_url)){
		echo 'Portrait - ' . $r['MONSTER_NO'] . PHP_EOL;
	}else{
		echo 'No Portrait - ' . $r['MONSTER_NO'] . PHP_EOL;
	}
	if(grab_img_if_exists($miru_full_url, $r['MONSTER_NO'], $_SERVER['DOCUMENT_ROOT'] . $fullimg_url)){
		echo 'Full Img - ' . $r['MONSTER_NO'] . PHP_EOL;
	}else{
		echo 'No Full Img - ' . $r['MONSTER_NO'] . PHP_EOL;
	}
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);

?>