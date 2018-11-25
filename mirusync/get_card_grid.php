<!DOCTYPE html>
<html>
<body>
<?php
include 'miru_common.php';
function select_card($conn, $id){
	$sql = 'SELECT monsterList.ATK_MAX, monsterList.HP_MAX, monsterList.RCV_MAX, monsterList.LEVEL, monsterList.LIMIT_MULT, monsterList.TA_SEQ ATT_1, monsterList.TA_SEQ_SUB ATT_2, monsterList.TE_SEQ, monsterList.TM_NAME_JP, monsterList.TM_NAME_US, monsterList.TT_SEQ TYPE_1, monsterList.TT_SEQ_SUB TYPE_2, monsterAddInfoList.SUB_TYPE TYPE_3, leadSkill.TS_DESC_US LS_DESC_US, leadSkillData.LEADER_DATA, active.TS_DESC_US AS_DESC_US, active.TURN_MAX AS_TURN_MAX, active.TURN_MIN AS_TURN_MIN FROM monsterList LEFT JOIN skillList leadSkill ON monsterList.TS_SEQ_LEADER=leadSkill.TS_SEQ LEFT JOIN skillLeaderDataList leadSkillData ON monsterList.TS_SEQ_LEADER=leadSkillData.TS_SEQ LEFT JOIN skillList active ON monsterList.TS_SEQ_SKILL=active.TS_SEQ LEFT JOIN monsterAddInfoList ON monsterList.MONSTER_NO=monsterAddInfoList.MONSTER_NO WHERE monsterList.MONSTER_NO=?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res = execute_select_stmt($stmt)[0];
	$stmt->free_result();
	$stmt->close();
	$sql = 'SELECT awokenSkillList.IS_SUPER, awokenSkillList.TS_SEQ FROM awokenSkillList WHERE awokenSkillList.monster_no=?;';
	$stmt = $conn->prepare($sql);
	$stmt->bind_param('i', $id);
	$res['AWAKENINGS'] = execute_select_stmt($stmt);
	$stmt->free_result();
	$stmt->close();

	return $res;
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
	$array = explode('///|',$lead);
	foreach($array as $value){
		$seg = explode('/',$value);
		$ls[$seg[0]] = ctype_digit($seg[1]) ? intval($seg[1]) : floatval($seg[1]);
	}
	return '[' . $ls['1'] * $ls['1'] . '/' . $ls['2'] * $ls['2'] . '/' . $ls['3'] * $ls['3'] . ($ls['4'] == 0 ? '' : ', ' . round(100 * (1 - (1-$ls['4']) * (1-$ls['4'])), 2) . '%') . ']';
}
$aw = array(2765 => 3, 2766 => 4, 2767 => 5, 2768 => 6, 2769 => 7, 2770 => 8, 2771 => 9, 2772 => 10, 2773 => 11, 2774 => 12, 2775 => 13, 2776 => 14, 2777 => 15, 2778 => 16, 2779 => 17, 2780 => 18, 2781 => 19, 2782 => 20, 2783 => 21, 2784 => 22, 2785 => 23, 2786 => 24, 2787 => 25, 2788 => 26, 2789 => 27, 2790 => 28, 2791 => 29, 3897 => 30, 7593 => 31, 7878 => 33, 7879 => 35, 7880 => 36, 7881 => 34, 7882 => 32, 9024 => 37, 9025 => 38, 9026 => 39, 9113 => 40, 9224 => 41, 9397 => 43, 9481 => 42, 10261 => 44, 11353 => 45, 11619 => 46, 12490 => 47, 12735 => 48, 12736 => 49, 13057 => 50, 13567 => 51, 13764 => 52, 13765 => 53, 13898 => 54, 13899 => 55, 13900 => 56, 13901 => 57, 13902 => 58, 14073 => 59, 14074 => 60, 14075 => 61, 14076 => 62, 14950 => 63, 15821 => 64, 15822 => 65 );
function awake_list($awakenings){
	global $aw;
	$awakes = '<p>';
	$supers = '<p>';
	foreach($awakenings as $awk){
		$id =  $aw[$awk['TS_SEQ']];
		if($awk['IS_SUPER'] == 1){
			$supers = $supers . '<a href="http://www.puzzledragonx.com/en/awokenskill.asp?s=' . $id . '"><img src="http://www.puzzledragonx.com/en/img/awoken/' . $id . '.png"/></a>';
		}else{
			$awakes = $awakes . '<a href="http://www.puzzledragonx.com/en/awokenskill.asp?s=' . $id . '"><img src="http://www.puzzledragonx.com/en/img/awoken/' . $id . '.png"/></a>';
		}
	}
	$awakes = $awakes . '</p>';
	$supers = $supers . '</p>';
	return $awakes . $supers;
}
function get_card_grid($id){	
	include 'sql_param.php';
	
	$conn = connect_sql($host, $user, $pass, $schema);
	$data = select_card($conn, $id);
	//$img_url = 'https://storage.googleapis.com/mirubot/padimages/jp/full/';
	$img_url = '/portrait/';
		
	return '<div class="cardgrid" id="' . $id . '"><div class="col1"><img src="'. $img_url . $id . '.png"/><table style="width:100%"><thead><tr><td>Stat</td><td>Lv.' . $data['LEVEL'] . '</td><td>' . ($data['LIMIT_MULT'] == 0 ? '' : 'Lv.110') . '+297</td></tr></thead><tbody><tr><td>HP</td><td>' . $data['HP_MAX'] . '</td><td>' . (lb_stat($data['HP_MAX'], $data['LIMIT_MULT']) + 990) . '</td></tr><tr><td>ATK</td><td>' . $data['ATK_MAX'] . '</td><td>' . (lb_stat($data['ATK_MAX'], $data['LIMIT_MULT']) + 495) . '</td></tr><tr><td>RCV</td><td>' . $data['RCV_MAX'] . '</td><td>' . (lb_stat($data['RCV_MAX'], $data['LIMIT_MULT']) + 297) . '</td></tr></tbody></table></div><div class="col-cardinfo"><p>[' . $id . ']<strong>' . att_orbs($data['ATT_1'], $data['ATT_2']) . $data['TM_NAME_US'] . '<br/>' . $data['TM_NAME_JP'] . '</strong><br/><p>' . typings($data['TYPE_1'], $data['TYPE_2'], $data['TYPE_3']) . '</p>' . awake_list($data['AWAKENINGS']) . '<p><u>Active Skill</u>: ' . $data['AS_DESC_US'] . ' <strong>(' . $data['AS_TURN_MAX'] . ' &#10151; ' . $data['AS_TURN_MIN'] . ')</strong></p><p><u>Leader Skill</u>: ' . $data['LS_DESC_US'] . ' <strong>' . lead_mult($data['LEADER_DATA']) . '</strong></p></div></div>';
}

$id = array_key_exists('id', $_GET) && $_GET['id'] != '' ? $_GET['id'] : '23';?>
<form method="get">
ID: <input type="text" name="id" value="<?php echo $id;?>">
<input type="submit">
<?php
echo get_card_grid($id);
?>

</form>
</body>
</html>