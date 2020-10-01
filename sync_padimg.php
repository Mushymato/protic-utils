<?php
include 'miru_common.php';
$time_start = microtime(true);

$sql = 'SELECT monster_id, monster_no_jp, monster_no_na FROM monsters';
$stmt = $miru->conn->prepare($sql);
$res = execute_select_stmt($stmt);
$stmt->close();

// $miru_portrait_url = 'https://f002.backblazeb2.com/file/miru-data/padimages/jp/portrait/';
// $miru_full_url = 'https://f002.backblazeb2.com/file/miru-data/padimages/jp/full/';
// $miru_portrait_url_na = 'https://f002.backblazeb2.com/file/miru-data/padimages/na/portrait/';
// $miru_portrait_url = 'https://f002.backblazeb2.com/file/miru-data/padimages/na/full/';
//$miru_portrait_url = 'https://f002.backblazeb2.com/file/dadguide-data/media/icons/%05d.png';
//$miru_full_url = 'https://f002.backblazeb2.com/file/dadguide-data/media/portraits/%05d.png';
$miru_portrait_url = 'https://d1kpnpud0qoyxf.cloudfront.net/media/icons/%05d.png';
$miru_full_url = 'https://d1kpnpud0qoyxf.cloudfront.net/media/portraits/%05d.png';
function download_image($source, $target, $id, $type){
	if(grab_img_if_exists($source, $id, $_SERVER['DOCUMENT_ROOT'] . $target)){
		echo $type . ' - ' . $id . PHP_EOL;
	}else{
		echo 'No ' . $type . ' - ' . $id . PHP_EOL;
	}
}

foreach($res as $r){
	download_image($miru_portrait_url, $portrait_url, $r['monster_id'], 'Portrait');
	download_image($miru_full_url, $fullimg_url, $r['monster_id'], 'Full Img');
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start);

?>