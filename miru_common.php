<?php 
$insert_size = 250;
$portrait_url = '/wp-content/uploads/pad-portrait/';
$fullimg_url = '/wp-content/uploads/pad-img/';

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
		$sql = 'CREATE TABLE ' . $tablename . ' (';
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
	$valueGroup = ' (' . substr(str_repeat('?,', sizeof($fieldnames)), 0, -1) . '),';
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
function execute_select_stmt($stmt, $pk = null){
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
	while ($stmt->fetch()){ 
		foreach($row as $key => $val){
			$c[$key] = $val; 
		} 
		if($pk != null){
			if(array_key_exists($c[$pk], $res)){
				$res[$c[$pk]][] = $c;
			}else{
				$res[$c[$pk]] = $c;
			}
		}else{
			$res[] = $c; 
		}
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
		$sql = 'SELECT MONSTER_NO, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE MONSTER_NO=? ORDER BY MONSTER_NO DESC';
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
		$query['SELECT MONSTER_NO, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE TM_NAME_JP'] = ' ORDER BY MONSTER_NO DESC';
	}else{
		$query['SELECT monsterList.MONSTER_NO, TM_NAME_JP, TM_NAME_US, RARITY, COMPUTED_NAME FROM monsterList LEFT JOIN computedNames ON monsterList.MONSTER_NO=computedNames.MONSTER_NO WHERE COMPUTED_NAME'] = ' ORDER BY LENGTH(COMPUTED_NAME) ASC';
		$query['SELECT MONSTER_NO_US MONSTER_NO, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE TM_NAME_US'] = ' ORDER BY MONSTER_NO DESC';
	}
	foreach($matching as $m){
		foreach($query as $q => $o){
			$res = single_param_stmt($conn, $q . $m[0] . $o, $m[1]);
			if(sizeof($res) > 0){
				if($res[0]['MONSTER_NO'] > 10000){ // crows in computedNames
					$res[0]['MONSTER_NO'] = $res[0]['MONSTER_NO'] - 10000;
				}
				return $res[0];
			}
		}
	}
	return false;
}
function select_awakenings($conn, $id){
	$sql = 'SELECT awokenSkillList.IS_SUPER, awokenSkillList.TS_SEQ FROM awokenSkillList WHERE awokenSkillList.monster_no=?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	if(sizeof($res) == 0){
		return false;
	}else{
		return $res;
	}
}
function select_evolutions($conn, $id){
	$sql = 'select MONSTER_NO, TO_NO from evolutionList where MONSTER_NO=?';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	if(sizeof($res) == 0){
		return array();
	}else{
		$evo_ids = array();
		foreach($res as $r){
			$evo_ids[] = $r['TO_NO'];
		}
		foreach($evo_ids as $eid){
			$evo_ids = array_merge($evo_ids, select_evolutions($conn, $eid));
		}
		sort($evo_ids);
		return $evo_ids;
	}
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
	
	$res['AWAKENINGS'] = select_awakenings($conn, $id);
	//$res['EVOLUTIONS'] = select_evolutions($conn, $id);
	
	return $res;
}
function grab_img_if_exists($url, $id, $savedir, $override = false){
	$saveto = $savedir . $id . '.png';
	if (!file_exists($savedir)) {
		mkdir($savedir, 0777, true);
	}else if(file_exists($saveto) && !$override){
		return true;
	}
	$ch = curl_init ($url . $id . '.png');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	$raw = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($httpCode >= 200 && $httpCode < 300){
		$fp = fopen($saveto ,'x');
		fwrite($fp, $raw);
		fclose($fp);
		return true;
	}
	return false;
}
function card_icon_img($id, $name = '', $w = '63', $h = '63', $href = 'http://www.puzzledragonx.com/en/monster.asp?n='){
	global $portrait_url;
	return array(
		'html' => '<a href="' . $href . $id . '"><img src="' . $portrait_url . $id . '.png" title="' . $id . ($name == '' ? '' : '-' . $name) . '" width="' . $w . '" height="' . $h . '"/></a>', 
		'shortcode' => '[pdx id=' . $id . ($w == $h && $w == '63' ? '' : ' w=' . $w . ' h=' . $h) . ']');
}
function lb_stat($base, $mult){
	return round($base * (100 + $mult)/100);
}
function weighted($data){
	return array(99 => round($data['HP_MAX'] / 10 + $data['ATK_MAX'] / 5 + $data['RCV_MAX'] / 3), 110 => round(lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) / 10 + lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) / 5 + lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) / 3));
}
function stat_table($data, $plus = false){
	if($data['LIMIT_MULT'] == 0){
		return '<table style="width:100%"><thead><tr><td>Stat</td><td>Lv.' . $data['LEVEL'] . '</td><td>+297</td></tr></thead><tbody><tr><td>HP</td><td>' . $data['HP_MAX'] . '</td><td>' . ($data['HP_MAX'] + 990) . '</td></tr><tr><td>ATK</td><td>' . $data['ATK_MAX'] . '</td><td>' . ($data['ATK_MAX'] + 495) . '</td></tr><tr><td>RCV</td><td>' . $data['RCV_MAX'] . '</td><td>' . ($data['RCV_MAX'] + 297) . '</td></tr></tbody></table>';
	}else{
		if($plus){
			return '<table class="card-stats-table"><thead><tr><td>Stat</td><td>Lv.99 (+297)</td><td>Lv.110 (+297)</td></tr></thead><tbody><tr><td>HP</td><td>' . $data['HP_MAX'] . ' (' . ($data['HP_MAX'] + 990) . ')</td><td>' . lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) . ' (' . (lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) + 990) . ')</tr><tr><td>ATK</td><td>' . $data['ATK_MAX'] . ' (' . ($data['ATK_MAX'] + 495) . ')</td><td>' . lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) . ' (' . (lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) + 495) . ')</td></tr><tr><td>RCV</td><td>' . $data['RCV_MAX'] . ' (' . ($data['RCV_MAX'] + 297) . ')</td><td>' . lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) . ' (' . (lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) + 297) . ')</td></tr></tbody></table>';
		}else{
			return '<table style="width:100%" class="base-stat"><thead><tr><td>Stat</td><td>Lv.99</td><td>Lv.110</td></tr></thead><tbody><tr><td>HP</td><td>' . $data['HP_MAX'] . '</td><td>' . lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) . '</td></tr><tr><td>ATK</td><td>' . $data['ATK_MAX'] . '</td><td>' . lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) . '</td></tr><tr><td>RCV</td><td>' . $data['RCV_MAX'] . '</td><td>' . lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) . '</td></tr></tbody></table>';
		}
	}
}
function att_orbs($att1, $att2){
	return array('<img width="20" height="20" src="/wp-content/uploads/pad-orbs/' . $att1 . '.png">' . ($att2 == 0 ? '' : '<img width="20" height="20" src="/wp-content/uploads/pad-orbs/' . $att2 . '.png">'), '[orb id=' . $att1 . ']' . ($att2 == 0 ? '' : '[orb id=' . $att2 . ']'));
}
$type = array('', 'Dragon', 'Balanced', 'Physical', 'Healer', 'Attacker', 'God', 'Evolve', 'Enhance', 'Protected', 'Devil', '', '', 'Awoken', 'Machine', 'Vendor');
function typings($t1, $t2, $t3){
	global $type;
	return $type[$t1] . ($t2 == 0 ? '' : ' / ' . $type[$t2]) . ($t3 == 0 ? '' : ' / ' . $type[$t3]);
}
function typing_killer_tooltip($t1, $t2, $t3){
	global $type;
	$types = array_filter(array($t1, $t2, $t3));
	$types_out = array();
	$latents = array();
	foreach($types as $t){
		$add = array();
		$types_out[] = $type[$t];
		if(!in_array('All', $latents)){
			switch($t){
				case 1: case 3: $add = array('Machine', 'Healer'); break; //dragon phys
				case 2: $latents = array('All'); break; //balance
				case 4: $add = array('Dragon', 'Attacker'); break; //healer
				case 5: $add = array('Devil', 'Physical'); break; //attacker
				case 6: $add = array('Devil'); break; //god
				case 10: $add = array('God'); break; //devil
				case 14: $add = array('God', 'Balanced'); break; //machine
			}
			$latents = array_unique(array_merge($latents, $add));
		}
	}
	$type_txt = implode(' / ', $types_out);
	if(sizeof($latents) == 0){
		return array($type_txt, $type_txt);
	}else{
		$latent_txt = implode(' / ', $latents);
		return array('<span class="su-tooltip" data-close="no" data-behavior="hover" data-my="bottom center" data-at="top center" data-classes="su-qtip qtip-light su-qtip-size-default" data-title="" data-hasqtip="0" oldtitle="Available Killers: ' . $latent_txt . '" title="" aria-describedby="qtip-0">' . $type_txt . '</span>', '[shortcode_tooltip style="light" content="Available Killers: ' . $latent_txt . '"]' . $type_txt . '[/shortcode_tooltip]');
	}
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
$aw = array(2765 => 3, 2766 => 4, 2767 => 5, 2768 => 6, 2769 => 7, 2770 => 8, 2771 => 9, 2772 => 10, 2773 => 11, 2774 => 12, 2775 => 13, 2776 => 14, 2777 => 15, 2778 => 16, 2779 => 17, 2780 => 18, 2781 => 19, 2782 => 20, 2783 => 21, 2784 => 22, 2785 => 23, 2786 => 24, 2787 => 25, 2788 => 26, 2789 => 27, 2790 => 28, 2791 => 29, 3897 => 30, 7593 => 31, 7878 => 33, 7879 => 35, 7880 => 36, 7881 => 34, 7882 => 32, 9024 => 37, 9025 => 38, 9026 => 39, 9113 => 40, 9224 => 41, 9397 => 43, 9481 => 42, 10261 => 44, 11353 => 45, 11619 => 46, 12490 => 47, 12735 => 48, 12736 => 49, 13057 => 50, 13567 => 51, 13764 => 52, 13765 => 53, 13898 => 54, 13899 => 55, 13900 => 56, 13901 => 57, 13902 => 58, 14073 => 59, 14074 => 60, 14075 => 61, 14076 => 62, 14950 => 63, 15821 => 64, 15822 => 65, 15823 => 66);
function awake_list($awakenings, $w = '31', $h = '32'){
	if(!$awakenings){
		return array('', '');
	}
	global $aw;
	$info_url = 'http://www.puzzledragonx.com/en/awokenskill.asp?s=';
	$awake_url = '/wp-content/uploads/pad-awakenings/';
	$awakes = array('<div>', '');
	$supers = array('<div>', '');
	
	foreach($awakenings as $awk){
		$id =  $aw[$awk['TS_SEQ']];
		if($awk['IS_SUPER'] == 1){
			$supers[0] = $supers[0] . '<a href="' . $info_url . $id . '"><img src="' . $awake_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>';
			$supers[1] = $supers[1] . '[awak id=' . $id . ($w != '31' ? ' w=' . $w . ' h=' . $h : '') . ']';
		}else{
			$awakes[0] = $awakes[0] . '<a href="' . $info_url . $id . '"><img src="' . $awake_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>';
			$awakes[1] = $awakes[1] . '[awak id=' . $id . ($w != '31' ? ' w=' . $w . ' h=' . $h : '') . ']';
		}
	}
	$awakes[0] = $awakes[0] . '</div>';
	$supers[0] = $supers[0] . '</div>';
	return array($awakes[0] . $supers[0], $awakes[1] . PHP_EOL . $supers[1]);
}
function get_card_grid($conn, $id, $right_side_table = false, $headings = true){
	global $fullimg_url;
	$data = select_card($conn, $id);
	if(!$data){
		return array('html' => 'NO CARD FOUND', 'shortcode' => 'NO CARD FOUND');
	}

	$atts = att_orbs($data['ATT_1'], $data['ATT_2']);
	$types = typing_killer_tooltip($data['TYPE_1'], $data['TYPE_2'], $data['TYPE_3']);
	$awakes = awake_list($data['AWAKENINGS']);
	
	$stat1 = '';
	$stat2 = '';
	if($right_side_table){
		$stat2 = stat_table($data, true) . '<br/>' . PHP_EOL;
	}else{
		$stat1 = stat_table($data, true);
	}
	$name_arr = explode(', ', $data['TM_NAME_US']);
	$head = $headings ? '<h2 id="card_' . $id . '">' . end($name_arr) . '</h2>' : '';
	return array(
		'html' => $head . '<div class="cardgrid"><div class="col1"><img src="'. $fullimg_url . $id . '.png"/>' . $stat1 . '</div><div class="col-cardinfo"><p>[' . $id . ']<b>' . $atts[0] . htmlentities($data['TM_NAME_US']) . '<br/>' . $data['TM_NAME_JP'] . '</b></p><p>' . $types[0] . '</p>' . $awakes[0] . $stat2 . '<p><u>Active Skill:</u> ' . htmlentities($data['AS_DESC_US']) . '<br/><b>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</b></p>' . (strlen($data['LS_DESC_US']) == 0 ? '' : '<p><u>Leader Skill:</u> ' . htmlentities($data['LS_DESC_US']) . '<br/><b>' . lead_mult($data['LEADER_DATA']) . '</b></p>') . '</div></div>', 
		'shortcode' => $head . PHP_EOL . '<div class="cardgrid"><div class="col1">[pdxp id=' . $id . ']' . $stat1 . '</div>' . PHP_EOL . '<div class="col-cardinfo">' . PHP_EOL . '[' . $id . ']<b>' . $atts[1] . htmlentities($data['TM_NAME_US']) . PHP_EOL . $data['TM_NAME_JP'] . '</b>' . PHP_EOL . $types[1] . '<br/><br/>' . PHP_EOL . $awakes[1] . '<br/><br/>' . PHP_EOL . $stat2 . '<u>Active Skill:</u> ' . htmlentities($data['AS_DESC_US']) . '<br/>' . PHP_EOL . '<b>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</b>' . (strlen($data['LS_DESC_US']) == 0 ? '' : '<br/><br/>' . PHP_EOL .'<u>Leader Skill:</u> ' . htmlentities($data['LS_DESC_US'])) . '<br/>' . PHP_EOL . '<b>' . lead_mult($data['LEADER_DATA']) . '</b>' . PHP_EOL . '</div>' . PHP_EOL . '</div>');
}
function get_card_summary($conn, $id){
	global $portrait_url;
	$data = select_card($conn, $id);
	if(!$data){
		return array('html' => 'NO CARD FOUND', 'shortcode' => 'NO CARD FOUND');
	}

	$card = card_icon_img($id, $data['TM_NAME_US']);
	$awakes = awake_list($data['AWAKENINGS']);
	
	return array(
		'html' => '<div><b>' . $card['html'] . ' ' . htmlentities($data['TM_NAME_US']) . '</b></div>' . $awakes[0], 
		'shortcode' => '<b>' . $card['shortcode'] . ' ' . htmlentities($data['TM_NAME_US']) . '</b>' . $awakes[1]);
}
function get_lb_stats_row($conn, $id, $sa){
	global $portrait_url;
	$data = select_card($conn, $id);
	if(!$data){
		return array('html' => '', 'shortcode' => '');
	}

	$card = card_icon_img($id, $data['TM_NAME_US']);
	$supers = array('','');
	if($sa){
		global $aw;
		$w = '31';
		$h = '32';
		$awakenings = $data['AWAKENINGS'];
		$info_url = 'http://www.puzzledragonx.com/en/awokenskill.asp?s=';
		$awake_url = '/wp-content/uploads/pad-awakenings/';
		$supers = array('<td>', '<td>');
		foreach($awakenings as $awk){
			$id =  $aw[$awk['TS_SEQ']];
			if($awk['IS_SUPER'] == 1){
				$supers[0] = $supers[0] . '<a href="' . $info_url . $id . '"><img src="' . $awake_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>';
				$supers[1] = $supers[1] . '[awak id=' . $id . ' w=' . $w . ' h=' . $h . ']';
			}
		}
		$supers[0] = $supers[0] . '</td>';
		$supers[1] = $supers[1] . '</td>';
	}
	
	$stats = '<td>' . weighted($data)[110] . '</td><td>' . lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) . '</td><td>' . lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) . '</td><td>' . lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) . '</td>';
	
	return array(
		'html' => '<tr><td>' . $card['html'] . '</td>' . $stats . $supers[0] . '</tr>', 
		'shortcode' => '<tr><td>' . $card['shortcode'] . '</td>' . $stats . $supers[1] . '</tr>'
	);
}
function get_egg($rare){
	$url = '/wp-content/uploads/pad-eggs/';
	if(is_numeric($rare)){
		$rare = intval($rare);
		$img_name = '';
		$sc_name = '';
		if($rare > 5){
			$img_name = 'Diamond';
			$sc_name = 'dia';
		}else if($rare == 5){
			$img_name = 'Gold1';
			$sc_name = 'gold';
		}else if($rare == 4){
			$img_name = 'Silver1';
			$sc_name = 'silver';
		}else{
			$img_name = 'Star';
			$sc_name = 'star';
		}
		return array('html' => '<img src="' . $url . $img_name . '.png" width="30"/>', 'shortcode' => '[egg id=' . $sc_name . ' w=30]');
	}else{
		return array('html' => '[EGG]', 'shortcode' => '[EGG]');
	}
}
function search_ids($conn, $input_str){
	$ids = array();
	foreach(explode("\n", $input_str) as $line){
		$mon = query_monster($conn, trim($line));
		if($mon){
			$ids[] = $mon['MONSTER_NO'];
		}
	}
	return $ids;
}
?>