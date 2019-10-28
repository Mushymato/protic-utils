<?php
include 'miru_common.php';
$time_start = microtime(true);

$sql = 'SELECT monster_id, monster_no_jp, monster_no_na FROM monsters';
$stmt = $miru->conn->prepare($sql);
$res = execute_select_stmt($stmt);
$stmt->close();

$miru_portrait_url = 'https://f002.backblazeb2.com/file/miru-data/padimages/jp/portrait/';
$miru_full_url = 'https://f002.backblazeb2.com/file/miru-data/padimages/jp/full/';
$miru_portrait_url_na = 'https://f002.backblazeb2.com/file/miru-data/padimages/na/portrait/';
$miru_full_url_na = 'https://f002.backblazeb2.com/file/miru-data/padimages/na/full/';

function download_image($source, $target, $id, $type){
	if(grab_img_if_exists($source, $id, $_SERVER['DOCUMENT_ROOT'] . $target)){
		echo $type . ' - ' . $id . PHP_EOL;
	}else{
		echo 'No ' . $type . ' - ' . $id . PHP_EOL;
	}
}

foreach($res as $r){
	download_image($miru_portrait_url, $portrait_url, $r['monster_no_jp'], 'Portrait');
	download_image($miru_full_url, $fullimg_url, $r['monster_no_jp'], 'Full Img');
	if ($r['monster_id'] != $r['monster_no_na']) {
		download_image($miru_portrait_url_na, $portrait_url_na, $r['monster_no_na'], 'Portrait');
		download_image($miru_full_url_na, $fullimg_url_na, $r['monster_no_na'], 'Full Img');
	}
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);

?>