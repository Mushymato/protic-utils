<?php
include 'miru_common.php';

$sql = 'select title.TITLE_US, mon.MONSTER_NO from collectionTitleList title inner join collectionMonsterList mon on title.TCT_SEQ=mon.TCT_SEQ where title.DEL_YN=\'N\' and mon.DEL_YN=\'N\';';
$stmt = $miru->conn->prepare($sql);
$res_array = execute_select_stmt($stmt);

$show_array = array();
foreach($res_array as $entry){
	if(array_key_exists($entry['TITLE_US'], $show_array)){
		if(!in_array($entry['MONSTER_NO'], $show_array[$entry['TITLE_US']])){
			$show_array[$entry['TITLE_US']][] = $entry['MONSTER_NO'];
		}
	}else{
		$show_array[$entry['TITLE_US']] = array($entry['MONSTER_NO']);
	}
	foreach(select_evolutions($entry['MONSTER_NO']) as $id){
		if(!in_array($id, $show_array[$entry['TITLE_US']])){
			$show_array[$entry['TITLE_US']][] = $id;
		}
	}
}
foreach($show_array as $title => $cards){
	echo '<h2>' . $title . '</h2>';
	foreach($cards as $id){
		echo card_icon_img($id)['html'];
	}
	echo '</br>';
}

?>