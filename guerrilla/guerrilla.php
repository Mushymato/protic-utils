<html>
<title>Guerilla Dungeons</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.23/moment-timezone-with-data.min.js" type="text/javascript"></script>
<script src="./guerrilla.js" type="text/javascript"></script>
<style>
.highlight{
	background-color:powderblue;
}
.highlight .highlight{
	font-weight: bold;
}
.group-tag{
	display: block;
	text-align: center;
	line-height: 1em;
}
.dungeon-icon{
	width: 50px;
	height: 50px;
}
.float{
	float: left;
}
</style>

</head>
<body>
<div>

<button onclick="switchTimezone();">Timezone: <span id="timezone"></span></button>

<button onclick="switchRegion();">Switch Region: <span id="region"></span></button><button onclick="pickMode('group');">By Group</button><button onclick="pickMode('schedule');">By Time</button><button onclick="pickMode('next');">By Countdown</button>

<?php 

include '../miru_common.php';

date_default_timezone_set('UTC');
ini_set('allow_url_fopen', 1);

$dungeon_info = array();
function tag($tag, $inner, $attributes = ''){
	return "<$tag $attributes>$inner</$tag>";
}
function get_json($url){
	$data = file_get_contents($url);
	return json_decode($data, true);
}
function get_icon($dungeon_id){
	global $dungeon_info;
	$dungeon = $dungeon_info[$dungeon_id];
	$icon_url = '/wp-content/uploads/pad-portrait/'.$dungeon['icon_id'].'.png';
	$name = $dungeon['name'];
	$pdx_search = 'http://www.puzzledragonx.com/en/search.asp?q='.$name;
	return tag('a', "<img src=\"$icon_url\" class=\"dungeon-icon\">", "href=\"$pdx_search\" title=\"$name\"");
}
function get_orb($orb){
	$orb_id = ['red' => '1', 'blue' => '2', 'green' => '3'];
	if(array_key_exists($orb, $orb_id)){
		$id = $orb_id[$orb];
		return "<img src=\"https://pad.protic.site/wp-content/uploads/pad-orbs/$id.png\" width=\"15\" height=\"15\">";
	}else{
		return $orb;
	}
}
$tform = 'm/d H:i e';
function get_table_group_rows($dungeon_id, $d_entries, $group_list){
	global $tform;
	$empty = true;
	$row = tag('td', get_icon($dungeon_id));
	foreach($group_list as $group){
		if(array_key_exists($group, $d_entries)){
			$cells = array();
			$cell_highlight = '';
			foreach($d_entries[$group] as $entry){
				if($entry['start_timestamp'] <= time() && $entry['end_timestamp'] >= time()){
					$cell_highlight = 'class="highlight"';
					$cells[$entry['start_timestamp']] = tag('div', date($tform, $entry['start_timestamp']), 'class="highlight timestamp" data-timestamp="' . (String) $entry['start_timestamp'] . '"');
				}else{
					$cells[$entry['start_timestamp']] = tag('div', date($tform, $entry['start_timestamp']), 'class="timestamp" data-timestamp="' . (String) $entry['start_timestamp'] . '"');
				}
			}
			$row = $row . tag('td', implode($cells), $cell_highlight);
			$empty = false;
		}else{
			$row = $row . tag('td', '');
		}
	}
	return $empty ? '' :tag('tr', $row);
}
function get_table_time_rows($start_time, $t_entries, $start_end, $group_list){
	global $tform;
	$row = tag('td', date($tform, $start_time), 'class="timestamp" data-timestamp="' . (String) $start_time . '"');
	$empty = true;
	foreach($group_list as $group){
		if(array_key_exists($group, $t_entries)){
			$cells = array();
			foreach($t_entries[$group] as $entry){
				$cells[$entry['dungeon_id']] = tag('div', get_icon($entry['dungeon_id']));
			}
			$row = $row . tag('td', implode($cells));
			$empty = false;
		}else{
			$row = $row . tag('td', '');
		}
	}
	if($empty){
		return '';
	}
	if($start_time <= time() && $start_end[$start_time] >= time()){
		return tag('tr', $row, 'class="highlight"');
	}else{
		return tag('tr', $row);
	}
}

function select_guerrilla_data($server, $start_time, $end_time){
	$query = 'SELECT d_servers.name as server, start_timestamp, end_timestamp, group_name, dungeons.dungeon_id as dungeon_id, dungeons.icon_id as icon_id, name_na, name_jp FROM schedule INNER JOIN dungeons on schedule.dungeon_id=dungeons.dungeon_id INNER JOIN d_servers on schedule.server_id=d_servers.server_id WHERE group_name IS NOT NULL AND d_servers.name=? AND start_timestamp>=? AND end_timestamp<=?;';
	global $miru;
	$stmt = $miru->conn->prepare($query);
	if (!$stmt){
		echo $query . PHP_EOL;
	}
	$stmt->bind_param('sii', $server, $start_time, $end_time);
	$res = execute_select_stmt($stmt);
	$stmt->close();
	return $res;
}

function get_guerrilla_tables(){
	global $dungeon_info;
	$by_dungeon_group = array('JP' => array(), 'NA' => array());
	$by_time = array('JP' => array(), 'NA' => array());
	$start_end = array();
	$day = array(
		'JP' => array(
			'start' => (new DateTime('today', new DateTimeZone('+0900')))->getTimestamp(), 
			'end' => (new DateTime('tomorrow', new DateTimeZone('+0900')))->getTimestamp()
		),
		'NA' => array(
			'start' => (new DateTime('today', new DateTimeZone('-0800')))->getTimestamp(), 
			'end' => (new DateTime('tomorrow', new DateTimeZone('-0800')))->getTimestamp()
		)
	);
	$now = time();
	
	$out = '';
	foreach(['JP', 'NA'] as $server){
		$gd = select_guerrilla_data($server, $day[$server]['start'], $day[$server]['end']);
		$dungeon_name = 'name_'.strtolower($server);

		foreach ($gd as $dungeon){
			$by_dungeon_group[$server][$dungeon['dungeon_id']][$dungeon['group_name']][] = $dungeon;
			if($dungeon['end_timestamp'] >= $now){
				$by_time[$server][$dungeon['start_timestamp']][$dungeon['group_name']][] = $dungeon;
			}
			$start_end[$dungeon['start_timestamp']] = $dungeon['end_timestamp'];
			$dungeon_info[$dungeon['dungeon_id']] = array('icon_id' => $dungeon['icon_id'], 'name' => $dungeon[$dungeon_name]);
		}

		$server_out = '';
		$tbl_gs = '<tr><td>Dungeon</td><td>' . get_orb('red') . '</td><td>' . get_orb('blue') . '</td><td>' . get_orb('green') . '</td></tr>';
		foreach($by_dungeon_group[$server] as $dungeon_id => $d_entries){
			$tbl_gs .= get_table_group_rows($dungeon_id, $d_entries, ['red', 'blue', 'green']);
		}

		ksort($by_time[$server]);
		$tbl_ts = '<tr><td>Time</td><td>' . get_orb('red') . '</td><td>' . get_orb('blue') . '</td><td>' . get_orb('green') . '</td></tr>';
		$tbl_tr = '<tr><td>Time Remaining</td><td>Dungeon</td></tr><tr class="tr-none"><td>--h --m</td><td>None</td></tr>';
		$tbl_tu = '<tr><td>Time Until</td><td>Dungeon</td></tr><tr class="tu-none"><td>--h --m</td><td>None</td></tr>';
		foreach($by_time[$server] as $start_time => $t_entries){
			if($start_end[$start_time] >= time()){
				$tbl_ts .= get_table_time_rows($start_time, $t_entries, $start_end, ['red', 'blue', 'green']);
				
				$row_tru = '';
				foreach(['red', 'blue', 'green'] as $group){
					if(array_key_exists($group, $t_entries)){
						foreach($t_entries[$group] as $entry){
							$row_tru = $row_tru . tag('div', tag('span', get_orb($entry['group_name']), 'class="group-tag"') . get_icon($entry['dungeon_id']), 'class="float"');
						}
					}
				}
				$tbl_tr = $tbl_tr . tag('tr', tag('td', '', 'class="time-remain" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
				$tbl_tu = $tbl_tu . tag('tr', tag('td', '', 'class="time-until" data-timestart="' . (String) $start_time . '" data-timeend="' . (String) $start_end[$start_time] . '"') . tag('td', $row_tru));
			}
		}

		$server_out .= tag('div', tag('table', $tbl_gs), 'class="group"');
		$server_out .= tag('div', tag('table', $tbl_ts), 'class="schedule"');
		$server_out .= tag('table', $tbl_tr . $tbl_tu, 'class="next"');
		$server_out = tag('div', $server_out, "class=\"$server\"");

		$out = $out . $server_out;

	}
	return $out;
}
echo get_guerrilla_tables();
?>
</body>
</html>
