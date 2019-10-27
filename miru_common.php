<?php 
$insert_size = 250;
$portrait_url = '/wp-content/uploads/pad-portrait/';
$fullimg_url = '/wp-content/uploads/pad-img/';
$portrait_url_na = '/wp-content/uploads/na/pad-portrait/';
$fullimg_url_na = '/wp-content/uploads/na/pad-img/';
class mySQLConn{
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
	public $conn;
    function __construct() {
		include 'sql_param.php';
        $this->conn = $this->connect_sql($host, $user, $pass, $schema);
    }
    function __destruct() {
        $this->conn->close();
    }
}
$miru = new mySQLConn();
function default_fieldnames($entry){
	$fieldnames = array();
	foreach($entry as $field => $value){
		$fieldnames[] = $field;
	}
	return $fieldnames;
}
function recreate_table($data, $tablename, $fieldnames, $pk){
	global $miru;
	$sql = 'DROP TABLE IF EXISTS ' . $tablename;
	if($miru->conn->query($sql)){
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
		if(!$miru->conn->query($sql)){
			trigger_error('Table creation failed: ' . $miru->conn->error);
			return false;
		}
	}else{
		trigger_error('Drop table failed: ' . $miru->conn->error);
		return false;
	}
}
function populate_table($data, $tablename, $fieldnames){
	global $miru;
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
	$stmt = $miru->conn->prepare($sql_m);
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
				trigger_error('Insert failed: ' . $miru->conn->error);
				echo 'Insert failed: ' . $miru->conn->error;
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
		$stmt = $miru->conn->prepare($sql);
		$stmt->bind_param(str_repeat($paramtype, $remaining), ...$value_arr);
		if(!$stmt->execute()){
			trigger_error('Insert failed: ' . $miru->conn->error);
			echo 'Insert failed: ' . $miru->conn->error;
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
	global $miru;
	if(!$stmt->execute()){
		trigger_error($miru->conn->error . '[select]');
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
function check_table_exists($tablename){
	global $miru;
	$sql = 'DESCRIBE ' . $tablename;
	if(!$miru->conn->query($sql)){
		trigger_error('Describe' . $tablename . 'failed: ' . $miru->conn->error);
		return false;
	}
}
function single_param_stmt($query, $q_str){
	global $miru;
	$stmt = $miru->conn->prepare($query);
	if (!$stmt){
		echo $query . PHP_EOL;
	}
	$stmt->bind_param('s', $q_str);
	$res = execute_select_stmt($stmt);
	$stmt->close();
	return $res;
}
function query_monster($q_str, $region = 'JP'){
	global $miru;
	$q_str = trim($q_str);
	if($q_str == ''){
		return false;
	}
	if(ctype_digit($q_str)){
		$sql = 'SELECT MONSTER_NO, MONSTER_NO_JP, MONSTER_NO_US, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE MONSTER_NO_'.$region.'=? ORDER BY MONSTER_NO DESC';
		$res = single_param_stmt($sql, $q_str);
		if(sizeof($res) > 0){
			if(sizeof($res) > 1){
				foreach($res as $r){
					if (($region == 'US' && $r['MONSTER_NO'] !== $r['MONSTER_NO_US']) ||
							($region == 'JP' && $r['MONSTER_NO'] === $r['MONSTER_NO_JP'])){
					return $r;
					}
				}
			}
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
		$query['SELECT MONSTER_NO, MONSTER_NO_JP, MONSTER_NO_US, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE TM_NAME_JP'] = ' ORDER BY MONSTER_NO DESC';
	}else{
		$query['SELECT monsterList.MONSTER_NO as MONSTER_NO, MONSTER_NO_JP, MONSTER_NO_US, TM_NAME_JP, TM_NAME_US, RARITY, COMPUTED_NAME FROM monsterList LEFT JOIN computedNames ON monsterList.MONSTER_NO_'.$region.'=computedNames.MONSTER_NO WHERE COMPUTED_NAME'] = ' ORDER BY LENGTH(COMPUTED_NAME) ASC';
		$query['SELECT MONSTER_NO, MONSTER_NO_JP, MONSTER_NO_US, TM_NAME_JP, TM_NAME_US, RARITY FROM monsterList WHERE TM_NAME_US'] = ' ORDER BY MONSTER_NO DESC';
	}
	foreach($matching as $m){
		foreach($query as $q => $o){
			$res = single_param_stmt($q . $m[0] . $o, $m[1]);
			if(sizeof($res) > 0){
				if($res[0]['MONSTER_NO'] > 10000){ // crows in computedNames
					$res[0]['MONSTER_NO'] = $res[0]['MONSTER_NO'] - 10000;
				}
				if(sizeof($res) > 1){
					foreach($res as $r){
						if (($region == 'US' && $r['MONSTER_NO'] !== $r['MONSTER_NO_US']) ||
								($region == 'JP' && $r['MONSTER_NO'] === $r['MONSTER_NO_JP'])){
							return $r;
						}
					}
				}
				return $res[0];
			}
		}
	}
	return false;
}
function select_awakenings($id){
	global $miru;
	$sql = 'SELECT awokenSkillList.IS_SUPER, awokenSkillList.TS_SEQ FROM awokenSkillList WHERE awokenSkillList.monster_no=?;';
	$stmt = $miru->conn->prepare($sql);
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
function select_evolutions($id){
	global $miru;
	$sql = 'select MONSTER_NO, TO_NO from evolutionList where MONSTER_NO=?';
	$stmt = $miru->conn->prepare($sql);
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
			$evo_ids = array_merge($evo_ids, select_evolutions($eid));
		}
		sort($evo_ids);
		return $evo_ids;
	}
}
function select_card($id){
	global $miru;
	$sql = 'SELECT monsterList.MONSTER_NO, monsterList.MONSTER_NO_JP, monsterList.MONSTER_NO_US, monsterList.ATK_MAX, monsterList.HP_MAX, monsterList.RCV_MAX, monsterList.LEVEL, monsterList.LIMIT_MULT, monsterList.TA_SEQ ATT_1, monsterList.TA_SEQ_SUB ATT_2, monsterList.TE_SEQ, monsterList.TM_NAME_JP, monsterList.TM_NAME_US, monsterList.TT_SEQ TYPE_1, monsterList.TT_SEQ_SUB TYPE_2, monsterAddInfoList.SUB_TYPE TYPE_3, leadSkill.TS_DESC_US LS_DESC_US, leadSkillData.LEADER_DATA, active.TS_DESC_US AS_DESC_US, active.TURN_MAX AS_TURN_MAX, active.TURN_MIN AS_TURN_MIN FROM monsterList LEFT JOIN skillList leadSkill ON monsterList.TS_SEQ_LEADER=leadSkill.TS_SEQ LEFT JOIN skillLeaderDataList leadSkillData ON monsterList.TS_SEQ_LEADER=leadSkillData.TS_SEQ LEFT JOIN skillList active ON monsterList.TS_SEQ_SKILL=active.TS_SEQ LEFT JOIN monsterAddInfoList ON monsterList.MONSTER_NO=monsterAddInfoList.MONSTER_NO WHERE monsterList.MONSTER_NO=?;';
	$stmt = $miru->conn->prepare($sql);
	if (!$stmt){
		echo $sql . PHP_EOL;
	}
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	if(sizeof($res) == 0){
		return false;
	}else{
		$res = $res[0];
	}
	
	$res['AWAKENINGS'] = select_awakenings($id);
	//$res['EVOLUTIONS'] = select_evolutions($id);
	
	return $res;
}
function grab_img_if_exists($url, $id, $savedir, $override = false){
	$saveto = $savedir . $id . '.png';
	// echo realpath($saveto) . PHP_EOL;
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
		$fp = fopen($saveto ,'w');
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
function weighted($data, $level){
	if($level == 99){
		return round($data['HP_MAX'] / 10 + $data['ATK_MAX'] / 5 + $data['RCV_MAX'] / 3);
	}else if ($level == 110){
		return round(lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) / 10 + lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) / 5 + lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) / 3);
	}
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
// $aw = array(2765 => 3, 2766 => 4, 2767 => 5, 2768 => 6, 2769 => 7, 2770 => 8, 2771 => 9, 2772 => 10, 2773 => 11, 2774 => 12, 2775 => 13, 2776 => 14, 2777 => 15, 2778 => 16, 2779 => 17, 2780 => 18, 2781 => 19, 2782 => 20, 2783 => 21, 2784 => 22, 2785 => 23, 2786 => 24, 2787 => 25, 2788 => 26, 2789 => 27, 2790 => 28, 2791 => 29, 3897 => 30, 7593 => 31, 7878 => 33, 7879 => 35, 7880 => 36, 7881 => 34, 7882 => 32, 9024 => 37, 9025 => 38, 9026 => 39, 9113 => 40, 9224 => 41, 9397 => 43, 9481 => 42, 10261 => 44, 11353 => 45, 11619 => 46, 12490 => 47, 12735 => 48, 12736 => 49, 13057 => 50, 13567 => 51, 13764 => 52, 13765 => 53, 13898 => 54, 13899 => 55, 13900 => 56, 13901 => 57, 13902 => 58, 14073 => 59, 14074 => 60, 14075 => 61, 14076 => 62, 14950 => 63, 15821 => 64, 15822 => 65, 15823 => 66);
$aw = array(
	2765 => 1, 
	2766 => 2,
	2767 => 3,
	2768 => 4,
	2769 => 5,
	2770 => 6,
	2771 => 7,
	2772 => 8,
	2773 => 9,
	2774 => 10,
	2775 => 11,
	2776 => 12,
	2777 => 13,
	2778 => 14,
	2779 => 15,
	2780 => 16,
	2781 => 17,
	2782 => 18,
	2783 => 19,
	2784 => 20,
	2785 => 21,
	2786 => 22,
	2787 => 23,
	2788 => 24,
	2789 => 25,
	2790 => 26,
	2791 => 27,
	3897 => 28,
	7593 => 29,
	7882 => 30,
	7878 => 31,
	7881 => 32,
	7879 => 33,
	7880 => 34,
	9113 => 35,
	9024 => 36,
	9025 => 37,
	9026 => 38,
	10261 => 39,
	9224 => 40,
	9481 => 41,
	9397 => 42,
	11353 => 43,
	11619 => 44,
	12490 => 45,
	12735 => 46,
	12736 => 47,
	13057 => 48,
	13567 => 49,
	13764 => 50,
	13765 => 51,
	13898 => 52,
	13899 => 53,
	13900 => 54,
	13901 => 55,
	13902 => 56,
	14073 => 57,
	14074 => 58,
	14075 => 59,
	14076 => 60,
	14950 => 61,
	15821 => 62,
	15822 => 63,
	15823 => 64,
	16460 => 65,
	16461 => 66,
	16462 => 67,
	16675 => 68,
	16676 => 69,
	16677 => 70,
	16678 => 71,
	16679 => 72
);
function awake_icon($id, $w = '31', $h = '32', $awake_url = '/wp-content/uploads/pad-awks/', $info_url = 'http://www.puzzledragonx.com/en/awokenskill.asp?s='){
	return array('html' => '<a href="' . $info_url . $id . '"><img src="' . $awake_url . $id . '.png" width="' . $w. '" height="' . $h. '"/></a>', 'shortcode' => '[awk id=' . $id . ($w != '31' ? ' w=' . $w . ' h=' . $h : '') . ']');
}
function awake_list($awakenings, $w = '31', $h = '32'){
	if(!$awakenings){
		return array('', '');
	}
	global $aw;
	$awakes = array('<div>', '');
	$supers = array('<div>', '');
	
	foreach($awakenings as $awk){
		$id = $aw[$awk['TS_SEQ']];
		$awks = awake_icon($id);
		if($awk['IS_SUPER'] == 1){
			$supers[0] = $supers[0] . $awks['html'];
			$supers[1] = $supers[1] . $awks['shortcode'];
		}else{
			$awakes[0] = $awakes[0] . $awks['html'];
			$awakes[1] = $awakes[1] . $awks['shortcode'];
		}
	}
	$awakes[0] = $awakes[0] . '</div>';
	$supers[0] = $supers[0] . '</div>';
	return array($awakes[0] . $supers[0], $awakes[1]  . '<br/>' . PHP_EOL . $supers[1]);
}
function get_card_grid($id, $region = 'JP', $right_side_table = false, $headings = false){
	global $fullimg_url;
	global $fullimg_url_na;
	$data = select_card($id);
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
	$head = '';
	if ($headings == 'yes'){
		$head = '<h2 id="card_' . $id . '">' . end($name_arr) . '</h2>' . PHP_EOL . '<div class="cardgrid">';
	} else if ($headings == 'tocOnly'){
		$head = '<div class="cardgrid" id="card_' . $id . '">';
	} else {
		$head = '<div class="cardgrid">';
	}
	$monster_no = $data['MONSTER_NO_'.$region];
	$regional_img_url = ($region == 'JP') ? $fullimg_url : $fullimg_url_na;
	echo $regional_img_url;
	return array(
		'html' => $head . '<div class="col1"><img src="'. $regional_img_url . $monster_no . '.png"/>' . $stat1 . '</div><div class="col-cardinfo"><p>[' . $monster_no . ']<b>' . $atts[0] . htmlentities($data['TM_NAME_US']) . '<br/>' . $data['TM_NAME_JP'] . '</b></p><p>' . $types[0] . '</p>' . $awakes[0] . $stat2 . '<p><u>Active Skill:</u> ' . htmlentities($data['AS_DESC_US']) . '<br/><b>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</b></p>' . (strlen($data['LS_DESC_US']) == 0 ? '' : '<p><u>Leader Skill:</u> ' . htmlentities($data['LS_DESC_US']) . '<br/><b>' . lead_mult($data['LEADER_DATA']) . '</b></p>') . '</div></div>', 
		'shortcode' => $head . PHP_EOL . '<div class="col1">[pdxp id=' . $monster_no . ' r=' . $region . ']' . $stat1 . '</div>' . PHP_EOL . '<div class="col-cardinfo">' . PHP_EOL . '[' . $monster_no . ']<b>' . $atts[1] . htmlentities($data['TM_NAME_US']) . PHP_EOL . $data['TM_NAME_JP'] . '</b>' . PHP_EOL . $types[1] . '<br/><br/>' . PHP_EOL . $awakes[1] . '<br/><br/>' . PHP_EOL . $stat2 . '<u>Active Skill:</u> ' . htmlentities($data['AS_DESC_US'] . '<br/>' . PHP_EOL . '<b>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</b>') . (strlen($data['LS_DESC_US']) == 0 ? '' : '<br/><br/>' . PHP_EOL .'<u>Leader Skill:</u> ' . htmlentities($data['LS_DESC_US']) . '<br/>' . PHP_EOL . '<b>' . lead_mult($data['LEADER_DATA']) . '</b>') . PHP_EOL . '</div>' . PHP_EOL . '</div>');
}
function get_card_summary($id){
	global $portrait_url;
	$data = select_card($id);
	if(!$data){
		return array('html' => 'NO CARD FOUND', 'shortcode' => 'NO CARD FOUND');
	}

	$card = card_icon_img($id, $data['TM_NAME_US']);
	$awakes = awake_list($data['AWAKENINGS']);
	if($data['LIMIT_MULT']){
		$stats = ' <p><b>Lv.110</b> <b>HP</b> ' . lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) . ' <b>ATK</b> ' . lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) . ' <b>RCV</b> ' . lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) . ' (' . weighted($data, 110) . ' weighted)</p>';
	}else{
		$stats = ' <p><b>Lv.99</b> <b>HP</b> ' . $data['HP_MAX'] . ' <b>ATK</b> ' . $data['ATK_MAX'] . ' <b>RCV</b> ' . $data['RCV_MAX'] . ' (' . weighted($data, 99) . ' weighted)</p>';
	}
	
	return array(
		'html' => '<h2 id="card_' . $id . '">' . $card['html'] . ' ' . htmlentities($data['TM_NAME_US']) .'</h2>' . $stats . $awakes[0], 
		'shortcode' => '<h2 id="card_' . $id . '">' . $card['shortcode'] . ' ' . htmlentities($data['TM_NAME_US']) .'</h2>' . $stats . $awakes[1]);
}
function get_lb_stats_row($id, $sa){
	global $portrait_url;
	$data = select_card($id);
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
				$supers[1] = $supers[1] . '[awk id=' . $id . ' w=' . $w . ' h=' . $h . ']';
			}
		}
		$supers[0] = $supers[0] . '</td>';
		$supers[1] = $supers[1] . '</td>';
	}
	
	$stats = '<td>' . weighted($data, 110) . '</td><td>' . lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) . '</td><td>' . lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) . '</td><td>' . lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) . '</td>';
	
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
function search_ids($input_str, $region = 'JP'){
	$ids = array();
	foreach(explode("\n", $input_str) as $line){
		$mon = query_monster(trim($line), $region);
		if($mon){
			$ids[] = $mon['MONSTER_NO'];
		}
	}
	return $ids;
}

function get_button_info($id, $button_type_name){
	global $portrait_url;
	$data = select_card($id);
	if(!$data){
		return array('html' => 'NO CARD FOUND', 'shortcode' => 'NO CARD FOUND');
	}
	$card = card_icon_img($id, $data['TM_NAME_US']);	
	return array(
		'html' => '<tr><td> <h2 id="card_' . $id . '">' . $card['html'] . '</h2></td>' . '<td>' . htmlentities($button_type_name) . '</td></tr>');
}

function retrieve_some_buttons($button_type_id, $button_type_name)	{	
	global $miru;
	$sql = 'SELECT buttonList.MONSTER_NO from buttonList WHERE buttonList.SKILL_TYPE=? AND buttonList.INHERITABLE = 1;';
	$stmt = $miru->conn->prepare($sql);
	$stmt->bind_param('i', $button_type_id);
	$res = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();
	echo ('<h1><span id=' . $button_type_name . '>' . $button_type_name . '</span></h1><table><thead><tr><td>Card</td><td>Active Skill</td></tr></thead><tbody>');
	foreach ($res as $id)	{
		echo (get_button_info($id['MONSTER_NO'], $button_type_name)['html']);
	}
echo ('</tbody></table>');
}

?>
