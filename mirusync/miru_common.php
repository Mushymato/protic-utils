<?php
$insert_size = 250;

function connect_sql($host, $user, $pass, $schema){
	// Create connection
	$conn = new mysqli($host, $user, $pass);
	// Check connection
	if ($conn->connect_error) {
		trigger_error('Connection failed: ' . $conn->connect_error);
		header( 'HTTP/1.0 403 Forbidden', TRUE, 403 );
		die('you cannot');
	}
	$conn->set_charset('utf8');
	$conn->select_db($schema);
	return $conn;
}
function default_fieldnames($entry){
	$fieldnames = array();
	foreach($entry as $field => $value){
		$fieldnames[] = $field;
	}
	return $fieldnames;
}
function recreate_table($conn, $data, $tablename, $fieldnames, $pk){
	$sql = 'DROP TABLE IF EXISTS ' . $tablename;
	if($conn->query($sql)){
		$sql = 'CREATE TABLE ' . $tablename . '(';
		foreach($fieldnames as $field){
			$sql = $sql . $field . ' ';
			if(ctype_digit($data[0][$field])){
				if(strlen($data[0][$field]) > 8){
					$sql = $sql . 'BIG';
				}
				$sql = $sql . 'INT ';
			}else{
				$maxlen = 11;
				foreach($data as $entry){
					$len = strlen($entry[$field]);
					if($len > $maxlen){
						$maxlen = $len;
					}
				}
				$maxlen = $maxlen * 2;
				$sql = $sql . 'VARCHAR(' . $maxlen . ') ';
			}
			if($field == $pk){
				$sql = $sql . 'PRIMARY KEY,';
			}else{
				$sql = $sql . 'NOT NULL,';
			}
		}
		
		$sql = substr($sql, 0, -1) . ');';
		if(!$conn->query($sql)){
			trigger_error('Table creation failed: ' . $conn->error);
			return false;
		}
	}else{
		trigger_error('Drop table failed: ' . $conn->error);
		return false;
	}
}
function populate_table($conn, $data, $tablename, $fieldnames){
	global $insert_size;
	$sql = 'INSERT INTO ' . $tablename . ' (';
	$paramtype = '';
	foreach($fieldnames as $field){
		$sql = $sql . $field . ',';
		if(ctype_digit($data[0][$field])){
			$paramtype = $paramtype . 'i';
		}else{
			$paramtype = $paramtype . 's';
		}
	}
	$valueGroup = '(' . substr(str_repeat('?,', sizeof($fieldnames)), 0, -1) . '),';
	$sql = substr($sql, 0, -1) . ') VALUES ';
	$sql_m = $sql . substr(str_repeat($valueGroup, $insert_size), 0, -1) . ';';
	$paramtype_m = str_repeat($paramtype, $insert_size);
	$stmt = $conn->prepare($sql_m);
	$count = 0;
	$value_arr = array();
	foreach($data as $entry){
		foreach($fieldnames as $field){
			if(ctype_digit($data[0][$field]) && $entry[$field] == ''){
				$value_arr[] = '0';
			}else{
				$value_arr[] = $entry[$field];
			}
		}
		if(sizeof($value_arr) == strlen($paramtype_m)){
			$stmt->bind_param($paramtype_m, ...$value_arr);
			if(!$stmt->execute()){
				trigger_error('Insert failed: ' . $conn->error);
				echo 'Insert failed: ' . $conn->error;
			}else{
				$count += $insert_size;
			}
			$value_arr = array();
		}
	}
	$stmt->close();
	if(sizeof($value_arr) > 0){
		$remaining = sizeof($value_arr) / sizeof($fieldnames);
		$sql = $sql . substr(str_repeat($valueGroup, $remaining), 0, -1) . ';';
		$stmt = $conn->prepare($sql);
		$stmt->bind_param(str_repeat($paramtype, $remaining), ...$value_arr);
		if(!$stmt->execute()){
			trigger_error('Insert failed: ' . $conn->error);
			echo 'Insert failed: ' . $conn->error;
		}else{
			$count += $remaining;
		}
		$value_arr = array();
		$stmt->close();
	}
	echo 'Imported ' . $count . ' records out of ' . sizeof($data) . ' to ' . $tablename . PHP_EOL;
}
function get_google_sheets_data($url, $fieldnames){
	$data = array();
	if ($fh = fopen($url, 'r')) {
		if(!feof($fh)){fgets($fh);}
		while (!feof($fh)) {
			$tmp = explode(',',fgets($fh));
			$data[] = array(
				$fieldnames[0] => trim($tmp[0]),
				$fieldnames[1] => trim($tmp[1])
			);
		}
		fclose($fh);
	}
	return $data;
}
function execute_select_stmt($stmt){
	if(!$stmt->execute()){
		trigger_error($conn->error . '[select]');
		return false;
	}
	$stmt->store_result();
	if($stmt->num_rows == 0){
		$stmt->free_result();
		return array();
	}
	$fields = array();
	$row = array();
	$meta = $stmt->result_metadata(); 
	while($f = $meta->fetch_field()){
		$fields[] = & $row[$f->name];
	}
	call_user_func_array(array($stmt, 'bind_result'), $fields);
	$res = array();
	while ($stmt->fetch()) { 
		foreach($row as $key => $val){
			$c[$key] = $val; 
		} 
		$res[] = $c; 
	}
	return $res;
}
function check_table_exists($conn, $tablename){
	$sql = 'DESCRIBE ' . $tablename;
	if(!$conn->query($sql)){
		trigger_error('Describe' . $tablename . 'failed: ' . $conn->error);
		return false;
	}
}
function single_param_stmt($conn, $query, $q_str){
	$stmt = $conn->prepare($query);
	$stmt->bind_param('s', $q_str);
	$res = execute_select_stmt($stmt);
	$stmt->close();
	return $res;
}
function query_monster($conn, $q_str){
	if($q_str == ''){
		return false;
	}
	if(ctype_digit($q_str)){
		$sql = 'SELECT MONSTER_NO, TM_NAME_JP, TM_NAME_US FROM monsterList WHERE MONSTER_NO=? ORDER BY MONSTER_NO DESC';
		$res = single_param_stmt($conn, $sql, $q_str);
		if(sizeof($res) > 0){
			return $res[0];
		}
	}
	$matching = array(
		array('=?',$q_str),
		array(' LIKE ?', $q_str . '%'),
		array(' LIKE ?', '%' . $q_str . '%')
	);
	$query = array();
	if(!mb_check_encoding($q_str, 'ASCII')){
		$query['SELECT MONSTER_NO, TM_NAME_JP, TM_NAME_US FROM monsterList WHERE TM_NAME_JP'] = ' ORDER BY MONSTER_NO DESC';
	}else{
		$query['SELECT monsterList.MONSTER_NO, TM_NAME_JP, TM_NAME_US, COMPUTED_NAME FROM monsterList LEFT JOIN computedNames ON monsterList.MONSTER_NO=computedNames.MONSTER_NO WHERE COMPUTED_NAME'] = ' ORDER BY LENGTH(COMPUTED_NAME) ASC';
		$query['SELECT MONSTER_NO_US MONSTER_NO, TM_NAME_JP, TM_NAME_US FROM monsterList WHERE TM_NAME_US'] = ' ORDER BY MONSTER_NO DESC';
	}
	foreach($matching as $m){
		foreach($query as $q => $o){
			$res = single_param_stmt($conn, $q . $m[0] . $o, $m[1]);
			if(sizeof($res) > 0){
				return $res[0];
			}
		}
	}
	return false;
}
function select_card($conn, $id){
	$sql = 'SELECT monsterList.ATK_MAX, monsterList.HP_MAX, monsterList.RCV_MAX, monsterList.LEVEL, monsterList.LIMIT_MULT, monsterList.TA_SEQ ATT_1, monsterList.TA_SEQ_SUB ATT_2, monsterList.TE_SEQ, monsterList.TM_NAME_JP, monsterList.TM_NAME_US, monsterList.TT_SEQ TYPE_1, monsterList.TT_SEQ_SUB TYPE_2, monsterAddInfoList.SUB_TYPE TYPE_3, leadSkill.TS_DESC_US LS_DESC_US, leadSkillData.LEADER_DATA, active.TS_DESC_US AS_DESC_US, active.TURN_MAX AS_TURN_MAX, active.TURN_MIN AS_TURN_MIN FROM monsterList LEFT JOIN skillList leadSkill ON monsterList.TS_SEQ_LEADER=leadSkill.TS_SEQ LEFT JOIN skillLeaderDataList leadSkillData ON monsterList.TS_SEQ_LEADER=leadSkillData.TS_SEQ LEFT JOIN skillList active ON monsterList.TS_SEQ_SKILL=active.TS_SEQ LEFT JOIN monsterAddInfoList ON monsterList.MONSTER_NO=monsterAddInfoList.MONSTER_NO WHERE monsterList.MONSTER_NO=?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	if(sizeof($res) == 0){
		return false;
	}else{
		$res = $res[0];
	}
	$sql = 'SELECT awokenSkillList.IS_SUPER, awokenSkillList.TS_SEQ FROM awokenSkillList WHERE awokenSkillList.monster_no=?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res['AWAKENINGS'] = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();

	return $res;
}
function card_icon_img($img_url, $id, $name, $w = '63', $h = '63'){
	return '<img src="' . $img_url . $id . '.png" title="' . $id . '-' . $name . '" width="' . $w . '" height="' . $h . '"/>';
}
function lb_stat($base, $mult){
	return round($base * (100 + $mult)/100);
}
function att_orbs($att1, $att2){
	return '<img width="20" height="20" src="https://pad.protic.site/wp-content/uploads/pad-orbs/' . $att1 . '.png">' . ($att2 == 0 ? '' : '<img width="20" height="20" src="https://pad.protic.site/wp-content/uploads/pad-orbs/' . $att2 . '.png">');
}
$type = array('', 'Dragon', 'Balance', 'Physical', 'Healer', 'Attacker', 'God', 'Evolve', 'Enhance', 'Protected', 'Devil', '', '', 'Awoken', 'Machine', 'Vendor');
function typings($t1, $t2, $t3){
	global $type;
	return $type[$t1] . ($t2 == 0 ? '' : ' / ' . $type[$t2]) . ($t3 == 0 ? '' : ' / ' . $type[$t3]);
}
function lead_mult($lead){
	$ls = array('1' => 1, '2' => 1, '3' => 1, '4' => 0);
	$lead = str_replace('///', '', $lead);
	$array = explode('|',$lead);
	foreach($array as $value){
		$seg = explode('/',$value);
		if(sizeof($seg) != 2){
			continue;
		}
		$ls[$seg[0]] = ctype_digit($seg[1]) ? intval($seg[1]) : floatval($seg[1]);
	}
	return '[' . $ls['1'] * $ls['1'] . '/' . $ls['2'] * $ls['2'] . '/' . $ls['3'] * $ls['3'] . ($ls['4'] == 0 ? '' : ', ' . round(100 * (1 - (1-$ls['4']) * (1-$ls['4'])), 2) . '%') . ']';
}
$aw = array(2765 => 3, 2766 => 4, 2767 => 5, 2768 => 6, 2769 => 7, 2770 => 8, 2771 => 9, 2772 => 10, 2773 => 11, 2774 => 12, 2775 => 13, 2776 => 14, 2777 => 15, 2778 => 16, 2779 => 17, 2780 => 18, 2781 => 19, 2782 => 20, 2783 => 21, 2784 => 22, 2785 => 23, 2786 => 24, 2787 => 25, 2788 => 26, 2789 => 27, 2790 => 28, 2791 => 29, 3897 => 30, 7593 => 31, 7878 => 33, 7879 => 35, 7880 => 36, 7881 => 34, 7882 => 32, 9024 => 37, 9025 => 38, 9026 => 39, 9113 => 40, 9224 => 41, 9397 => 43, 9481 => 42, 10261 => 44, 11353 => 45, 11619 => 46, 12490 => 47, 12735 => 48, 12736 => 49, 13057 => 50, 13567 => 51, 13764 => 52, 13765 => 53, 13898 => 54, 13899 => 55, 13900 => 56, 13901 => 57, 13902 => 58, 14073 => 59, 14074 => 60, 14075 => 61, 14076 => 62, 14950 => 63, 15821 => 64, 15822 => 65 );
function awake_list($awakenings, $w = '31', $h = '32'){
	global $aw;
	$info_url = 'http://www.puzzledragonx.com/en/awokenskill.asp?s=';
	//$icon_url = 'wp-content/uploads/pad-awakenings/';
	$icon_url = 'https://pad.protic.site/wp-content/uploads/pad-awakenings/';
	$awakes = '<div>';
	$supers = '<div>';
	foreach($awakenings as $awk){
		$id =  $aw[$awk['TS_SEQ']];
		if($awk['IS_SUPER'] == 1){
			$supers = $supers . '<a href="' . $info_url . $id . '"><img src="' . $icon_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>';
		}else{
			$awakes = $awakes . '<a href="' . $info_url . $id . '"><img src="' . $icon_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>';
		}
	}
	$awakes = $awakes . '</div>';
	$supers = $supers . '</div>';
	return $awakes . $supers;
}
function get_card_grid($conn, $id){	
	$data = select_card($conn, $id);
	if(!$data){
		return '<div>NO CARD FOUND</div>';
	}
	//$img_url = 'https://pad.gungho.jp/member/img/graphic/illust/';
	$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/full/';
	//$img_url = '/portrait/';
		
	return '<div class="cardgrid" id="' . $id . '"><div class="col1"><img src="'. $img_url . $id . '.png"/><table style="width:100%"><thead><tr><td>Stat</td><td>Lv.' . $data['LEVEL'] . '</td><td>' . ($data['LIMIT_MULT'] == 0 ? '' : 'Lv.110') . '+297</td></tr></thead><tbody><tr><td>HP</td><td>' . $data['HP_MAX'] . '</td><td>' . (lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) + 990) . '</td></tr><tr><td>ATK</td><td>' . $data['ATK_MAX'] . '</td><td>' . (lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) + 495) . '</td></tr><tr><td>RCV</td><td>' . $data['RCV_MAX'] . '</td><td>' . (lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) + 297) . '</td></tr></tbody></table></div><div class="col-cardinfo"><p>[' . $id . ']<strong>' . att_orbs($data['ATT_1'], $data['ATT_2']) . $data['TM_NAME_US'] . '<br/>' . $data['TM_NAME_JP'] . '</strong><br/><p>' . typings($data['TYPE_1'], $data['TYPE_2'], $data['TYPE_3']) . '</p>' . awake_list($data['AWAKENINGS']) . '<p><u>Active Skill</u>: ' . $data['AS_DESC_US'] . ' <strong>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</strong></p><p><u>Leader Skill</u>: ' . $data['LS_DESC_US'] . ' <strong>' . lead_mult($data['LEADER_DATA']) . '</strong></p></div></div>';
}
function get_card_summary($conn, $id){	
	$data = select_card($conn, $id);
	if(!$data){
		return '<div>NO CARD FOUND</div>';
	}

	$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/';
	//$img_url = '/portrait/';
		
	return '<div><strong>' . card_icon_img($img_url, $data['MONSTER_NO'], $data['TM_NAME_US']) . ' ' . $data['TM_NAME_US'] . '</strong></div>' . awake_list($data['AWAKENINGS']);
}
function get_egg($str){
	//$url = 'wp-content/uploads/pad-eggs/';
	$url = 'https://pad.protic.site/wp-content/uploads/pad-eggs/';
	if(ctype_digit($str)){
		$rare = intval($str);
		if($rare > 5){
			return '<img src="' . $url . 'Diamond.png" width="30"/>';
		}else if($rare == 5){
			return '<img src="' . $url . 'Gold1.png" width="30"/>';
		}else if($rare == 4){
			return '<img src="' . $url . 'Silver1.png" width="30"/>';
		}else{
			return '<img src="' . $url . 'Star.png" width="30"/>';
		}
	}else{
		return '';
	}
}
function get_evolutions($conn, $id){
	$sql = 'select MONSTER_NO, TO_NO from evolutionlist where MONSTER_NO=?';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	if(sizeof($res) == 0){
		return false;
	}else{
		$evo_ids = array();
		foreach($res as $r){
			$evo_ids[] = $r['TO_NO'];
		}
		return $evo_ids;
	}
}
?>