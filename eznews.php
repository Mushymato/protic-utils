<?php
include 'miru_common.php';
global $miru;
$query = "SELECT * FROM proticsi_dadguide.awoken_skills;";
$res = $miru->conn->prepare($query);
if (is_array($res))
    $awaksize = sizeof($res);
else
    $awaksize = 72;
$typesize = 13;

if (array_key_exists('oneline', $_POST) && $_POST['oneline']){
    $conversion = array(
        'att' => array(
            'na' => '',
            'r' => 1,
            'b' => 2,
            'g' => 3,
            'l' => 4,
            'd' => 5,
        ),
        'type' => array(
            'na' => '',
            'dr' => 1,
            'ba' => 2,
            'ph' => 3,
            'he' => 4,
            'at' => 5,
            'go' => 6,
            'ev' => 7,
            'en' => 8,
            'de' => 9,
            'sp' => 10,
            'aw' => 11,
            'ma' => 12,
            're' => 13,
        ),
        'awak' => array(
            'hp'	=> 1,
            'atk'	=> 2,
            'rcv'	=> 3,
            'rres'	=> 4,
            'bres'	=> 5,
            'gres'	=> 6,
            'lres'	=> 7,
            'dres'	=> 8,
            'ah'	=> 9,
            'brs'	=> 10,
            'blrs'	=> 11,
            'jrs'	=> 12,
            'prs'	=> 13,
            'roe'	=> 14,
            'boe'	=> 15,
            'goe'	=> 16,
            'loe'	=> 17,
            'doe'	=> 18,
            'te'	=> 19,
            'brc'	=> 20,
            'sb'	=> 21,
            'rre'	=> 22,
            'bre'	=> 23,
            'gre'	=> 24,
            'lre'	=> 25,
            'dre'	=> 26,
            'tpa'	=> 27,
            'sbr'	=> 28,
            'hoe'	=> 29,
            'coop'	=> 30,
            'drk'	=> 31,
            'gok'	=> 32,
            'dek'	=> 33,
            'mak'	=> 34,
            'bak'	=> 35,
            'aak'	=> 36,
            'phk'	=> 37,
            'hek'	=> 38,
            'awk'	=> 39,
            'enk'	=> 40,
            'rek'	=> 41,
            'evk'	=> 42,
            '7c'	=> 43,
            'gbk'	=> 44,
            'fua'	=> 45,
            'thp'	=> 46,
            'trcv'	=> 47,
            'vdp'	=> 48,
            'eqp'	=> 49,
            'sfua'	=> 50,
            'sc'	=> 51,
            'brs+'	=> 52,
            'te+'	=> 53,
            'clrs'	=> 54,
            'imrs'	=> 55,
            'sb+'	=> 56,
            '80'	=> 57,
            '50'	=> 58,
            'ls'	=> 59,
            'la'	=> 60,
            '10c'	=> 61,
            'co'	=> 62,
            'voice'	=> 63,
            'solo'	=> 64,
            '-hp'	=> 65,
            '-atk'	=> 66,
            '-rcv'	=> 67,
            'bls+'	=> 68,
            'jrs+'	=> 69,
            'prs+'	=> 70,
            'jbs'	=> 71,
            'pbs'	=> 72,
            'rco'   => 73,
            'bco'   => 74,
            'gco'   => 75,
            'lco'   => 76,
            'dco'   => 77,
            'cab'   => 78,
            'na'    => '',
        ),
    );

    $_POST['oneline'] = str_replace('\\', '', $_POST['oneline']);

    $onelinearr = explode(' ',$_POST['oneline']);
    $inputsize = sizeof($onelinearr);
    $_POST['id'] = $onelinearr[0];
    $_POST['att'] = array_key_exists(strtolower($onelinearr[1]), $conversion['att']) ? $conversion['att'][strtolower($onelinearr[1])] : '';
    $_POST['subatt'] = array_key_exists(strtolower($onelinearr[2]), $conversion['att']) ? $conversion['att'][strtolower($onelinearr[2])] : '';
    $name = $onelinearr[3];
    if (substr($onelinearr[3], -1) != ';'){
        for($nameindex = 4;; $nameindex++){
            if (substr($onelinearr[$nameindex], -1) == ';'){
                $name .= ' '.substr($onelinearr[$nameindex], 0, -1);
                break;
            } else
                $name .= ' '.$onelinearr[$nameindex];
        }
    } else {
        $nameindex = 3;
        $name = substr($name, 0, -1);
    }

    $_POST['mon_name'] = $name;
    for ($typeindex = 1; $typeindex <= 3; $typeindex++){
        if ($nameindex+$typeindex >= $inputsize) break;
        $_POST['type'.$typeindex] = $conversion['type'][$onelinearr[$nameindex+$typeindex]];
        if ($onelinearr[$nameindex+$typeindex] == 'na') break;
    }

    $_POST['awak'] = $conversion['awak'][$onelinearr[$nameindex+$typeindex]];
    for ($awakindex = 1; $awakindex < 9; $awakindex++){
        if ($nameindex+$typeindex+$awakindex >= $inputsize) break;
        $_POST['awak'] .= ','.$conversion['awak'][$onelinearr[$nameindex+$typeindex+$awakindex]];
        if ($onelinearr[$nameindex+$typeindex+$awakindex] == 'na') break;
    }

    $_POST['sa'] = $conversion['awak'][$onelinearr[$nameindex+$typeindex+$awakindex]];
    for ($saindex = 1; $saindex < 9; $saindex++){
        if ($nameindex+$typeindex+$awakindex+$saindex >= $inputsize) break;
        $_POST['sa'] .= ','.$conversion['awak'][$onelinearr[$nameindex+$typeindex+$awakindex+$saindex]];
        if ($onelinearr[$nameindex+$typeindex+$awakindex+$saindex] == 'na') break;
    }

    $_POST['as'] = $_POST['ls'] = $_POST['cd'] = '';
} else $_POST['oneline'] = '';

$attlist = "";
$subattlist = "";
$attsel = '4px solid darkred';
if (!array_key_exists('att',$_POST)) $_POST['att'] = 1;
for($i = 0; $i <= 5; $i++){
    if (array_key_exists('att', $_POST) && $_POST['att'] == $i)
        $attlist .= "<a onclick='selectAtt($i)'><img id='att_$i' style='height: 32px; margin-right: 5px; border-radius: 250px; border: $attsel;' src='/wp-content/uploads/pad-orbs/$i.png'></a>";
    else
        $attlist .= "<a onclick='selectAtt($i)'><img id='att_$i' style='height: 32px; margin-right: 5px;border-radius: 250px;' src='/wp-content/uploads/pad-orbs/$i.png'></a>";
    if ($i == 0) continue;
    if (array_key_exists('subatt', $_POST) && $_POST['subatt'] == $i)
        $subattlist .= "<a onclick='selectAtt($i, true)'><img id='subatt_$i' style='height: 32px; border-radius: 250px; border: $attsel;' src='/wp-content/uploads/pad-orbs/$i.png'></a>";
    else
        $subattlist .= "<a onclick='selectAtt($i, true)'><img id='subatt_$i' style='height: 32px; margin-right: 5px; border-radius: 250px;' src='/wp-content/uploads/pad-orbs/$i.png'></a>";
}

$typelist = "";
for($i = 1; $i <= $typesize; $i++){
    $typelist .= "<a onclick='selectType(\"$i\");'><img src='/wp-content/uploads/pad-types/$i.png'></a>";
}

$awaksort = array(
    21, 19, 43, 45, 10, 11, 12, 13, 49,
    56, 53, 61, 50, 52, 68, 69, 70, 28,
    27, 48, 62, 57, 58, 60, 59, 54, 55,
    14, 15, 16, 17, 18, 29, 20, 44, 51,
    22, 23, 24, 25, 26, 32, 31, 33, 34,
     4,  5,  6,  7,  8, 35, 36, 37, 38,
     1,  2,  3, 46, 47, 39, 40, 41, 42,
    65, 66, 67,  9, 71, 72, 30, 64, 63,
    73, 74, 75, 76, 77, 78,
);
$graylist = array();
$allattawak = array();
$attawaklist = array(
    1 => array(14,22,73),
    2 => array(15,23,74),
    3 => array(16,24,75),
    4 => array(17,25,76),
    5 => array(18,26,77),
);
foreach($attawaklist as $k => $v) $allattawak = array_merge($allattawak, $v);
if (array_key_exists($_POST['att'], $attawaklist)) $graylist = array_merge($graylist, $attawaklist[$_POST['att']]);
if (array_key_exists($_POST['subatt'], $attawaklist)) $graylist = array_merge($graylist, $attawaklist[$_POST['subatt']]);
$graylist = array_diff($allattawak, $graylist);

$awaklist = "";
$j = 1;
foreach($awaksort as $i){
    $gray = in_array($i, $graylist) ? "style='filter: grayscale(1)'" : "style='filter: grayscale(0)'";

    $awaklist .= "<a onclick='selectAwak(\"$i\");'><img id='awak_img_$i' $gray src='/wp-content/uploads/pad-awks/$i.png'></a>";
    if ($j % 9 == 0) $awaklist.='<br>';
    $j++;
}

$awakcnt = $sacnt = 0;
$type1 = $type2 = $type3 = $awak = $sa = "";
if (array_key_exists('type1', $_POST) && $_POST['type1']) $type1 = "<img src='/wp-content/uploads/pad-types/{$_POST['type1']}.png'>";
if (array_key_exists('type2', $_POST) && $_POST['type2']) $type2 = "<img src='/wp-content/uploads/pad-types/{$_POST['type2']}.png'>";
if (array_key_exists('type3', $_POST) && $_POST['type3']) $type3 = "<img src='/wp-content/uploads/pad-types/{$_POST['type3']}.png'>";

$awaks = array_key_exists('awak', $_POST) ? explode(',', $_POST['awak']) : array();
$sas = array_key_exists('sa', $_POST) ? explode(',', $_POST['sa']) : array();
if (sizeof($awaks)){
    foreach($awaks as $k => $v){
        if ($v){
            $awak .= "<img src='/wp-content/uploads/pad-awks/$v.png'>";
            $awakcnt++;
        }
    }
}
if (sizeof($sas)){
    foreach($sas as $k => $v){
        if ($v){
            $sa .= "<img src='/wp-content/uploads/pad-awks/$v.png'>";
            $sacnt++;
        }
    }
}

$darr = array(
    'orb' => array(
        0 => '',
        1 => ':orb_fire:',
        2 => ':orb_water:',
        3 => ':orb_wood:',
        4 => ':orb_light:',
        5 => ':orb_dark:',
    ),
    'awak' => array(
        ':boost_hp:', ':boost_atk:', ':boost_rcv:', ':reduce_fire:', ':reduce_water:', ':reduce_wood:', ':reduce_light:', ':reduce_dark:',
        ':misc_autoheal:', ':res_bind:', ':res_blind:', ':res_jammer:', ':res_poison:', ':oe_fire:', ':oe_water:', ':oe_wood:', ':oe_light:', ':oe_dark:',
        ':misc_te:', ':misc_bindclear:', ':misc_sb:', ':row_fire:', ':row_water:', ':row_wood:', ':row_light:', ':row_dark:', ':misc_tpa:', ':res_skillbind:',
        ':oe_heart:', ':misc_multiboost:', ':killer_dragon:', ':killer_god:', ':killer_devil:', ':killer_machine:', ':killer_balance:', ':killer_attacker:',
        ':killer_physical:', ':killer_healer:', ':killer_awoken:', ':killer_enhancemat:', ':killer_vendor:', ':killer_evomat:', ':misc_comboboost:',
        ':misc_guardbreak:', ':misc_extraattack:', ':teamboost_hp:', ':teamboost_rcv:', ':misc_voidshield:', ':misc_assist:', ':misc_super_extraattack:',
        ':misc_skillcharge:', ':res_bind_super:', ':misc_te_super:', ':res_cloud:', ':res_seal:', ':misc_sb_super:', ':attack_boost_high:',
        ':attack_boost_low:', ':l_shield:', ':l_attack:', ':misc_super_comboboost:',':orb_combo:', ':misc_voice:', ':misc_dungeonbonus:',':reduce_hp:',':reduce_atk:',
        ':reduce_rcv:', ':res_blind_super:', ':res_jammer_super:', ':res_poison_super:', ':misc_jammerboost:', ':misc_poisonboost:',
        ':rcombo:', ':bcombo:', ':gcombo:', ':lcombo:', ':dcombo:', ':cross_boost:',
    ),
    'type' => array('Dragon', 'Balanced', 'Physical', 'Healer', 'Attacker', 'God', 'Evo Mat', 'Enhance Mat', 'Devil', 'Special', 'Awoken Mat', 'Machine', 'Redeemable'),
);

$barr = array(
    'orb' => array(
        0 => '0',
        1 => 'r',
        2 => 'b',
        3 => 'g',
        4 => 'l',
        5 => 'd',
    )
);

$datt = $darr['orb'][$_POST['att']];
if ($_POST['subatt']) $datt .= '/'.$darr['orb'][$_POST['subatt']];
$batt = '[orb id='.$barr['orb'][$_POST['att']].']';
if ($_POST['subatt']) $batt .= '/'.'[orb id='.$barr['orb'][$_POST['subatt']].']';
$type = $darr['type'][$_POST['type1']-1];
if ($_POST['type2']) $type .= ' / '.$darr['type'][$_POST['type2']-1];
if ($_POST['type3']) $type .= ' / '.$darr['type'][$_POST['type3']-1];
$dawak = $dsa = $bawak = $bsa = '';
foreach($awaks as $k => $v){
    if ($v > 0){
        $dawak .= $darr['awak'][$v-1];
        $bawak .= "[awk id=$v]";
    }
}

if (sizeof($sas) && $sas[0] != ''){
    $dsa = 'Super Awakening: ';
    foreach($sas as $k => $v){
        if ($v > 0){
            $dsa .= $darr['awak'][$v-1];
            $bsa .= "[awk id=$v]";
        }
    }
}

$_POST['as'] = str_replace('\\', '', $_POST['as']);
$_POST['ls'] = str_replace('\\', '', $_POST['ls']);

$cdtext = "**({$_POST['cd']} max CD)**";

if (!$_POST['as']){
    $_POST['as'] = 'None';
    $cdtext = '';
}
if (!$_POST['ls']) $_POST['ls'] = 'None';

$discord = "
[{$_POST['id']}] {$datt} ** {$_POST['mon_name']} **
$type
$dawak
$dsa
__Active Skill__: {$_POST['as']} {$cd}

__Leader Skill__: {$_POST['ls']}
";

$name = str_replace('\\', '', $_POST['mon_name']);

$blog = "
<p>
[cardgrid card_id={$_POST['id']}]
[col1]pic[/col1]
[col2][{$_POST['id']}]$batt <b> {$_POST['mon_name']}</b><br/>
<span class='card-type'>
$type </span><br/>
$bawak<br/>
$bsa<br/><br/>
<u>Active Skill</u>: {$_POST['as']} <b>{$cd}</b><br/><br/>

<u>Leader Skill</u>: {$_POST['ls']}<br/>
[/col2]
[/cardgrid]
</p>
";

$pvblog = do_shortcode($blog);

$form = "
<style>
.eznewsbtn{
    border: 1px solid #005662;
    border-bottom: 3px solid #005662;
    background: #67c7d2;
    padding: 0.5rem 0.75rem;
    color: white;
}
.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 25px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: '';
    height: 17px;
    width: 17px;
    left: 4px;
    bottom: 3.5px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:focus + .slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
    -webkit-transform: translateX(35px);
    -ms-transform: translateX(35px);
    transform: translateX(35px);
}

.slider.round {
    border-radius: 25px;
}

.slider.round:before {
    border-radius: 50%;
}
</style>
<script>
function selectType(id){
    var type1 = document.getElementById('type1');
    var type2 = document.getElementById('type2');
    var type3 = document.getElementById('type3');
    var icon1 = document.getElementById('type1_icon');
    var icon2 = document.getElementById('type2_icon');
    var icon3 = document.getElementById('type3_icon');

    var iconimg = \"<img src='/wp-content/uploads/pad-types/\"+id+\".png'>\";

    // Check for repeated types
    if (![type1.value, type2.value, type3.value].includes(id)){
        if (type1.value == ''){
            type1.value = id;
            icon1.innerHTML = iconimg;
        } else if (type2.value == ''){
            type2.value = id;
            icon2.innerHTML = iconimg;
        } else if (type3.value == ''){
            type3.value = id;
            icon3.innerHTML = iconimg;
        }
    }
}

function clearType(){
    var type1 = document.getElementById('type1');
    var type2 = document.getElementById('type2');
    var type3 = document.getElementById('type3');
    var icon1 = document.getElementById('type1_icon');
    var icon2 = document.getElementById('type2_icon');
    var icon3 = document.getElementById('type3_icon');

    type1.value = type2.value = type3.value = icon1.innerHTML = icon2.innerHTML = icon3.innerHTML = '';
}

var awakcnt = $awakcnt;
var sacnt = $sacnt;

function selectAwak(id){
    var sa = document.getElementById('sa_toggle').checked;
    var type = sa ? 'sa' : 'awak';
    var val = document.getElementById(type);
    var container = document.getElementById(type+'_icon');

    if ((sa && sacnt < 9 ) || (!sa && awakcnt < 9)){
        if (val.value == ''){
            val.value = id;
            container.innerHTML = '<img src=\"/wp-content/uploads/pad-awks/'+id+'.png\">';
        } else {
            val.value = val.value+','+id;
            container.innerHTML += '<img src=\"/wp-content/uploads/pad-awks/'+id+'.png\">';
        }
    }

    if (sa) sacnt++;
    else awakcnt++;
}

function clearAwak(sa = false){
    if (sa){
        var val = document.getElementById('sa');
        var container = document.getElementById('sa_icon');

        val.value = container.innerHTML = '';
        sacnt = 0;
    } else {
        var val = document.getElementById('awak');
        var container = document.getElementById('awak_icon');

        val.value = container.innerHTML = '';
        awakcnt = 0;
    }
}

function selectAtt(id, subatt = false){
    var attawaklist = [
        [14,22,73],
        [15,23,74],
        [16,24,75],
        [17,25,76],
        [18,26,77],
    ];
    var graylist = [];
    var allattawak = [];
    attawaklist.forEach(function(v, i){
        allattawak = allattawak.concat(v);
    });

    var main = document.getElementById('att_'+id);
    var mainval = document.getElementById('att');
    var sub = document.getElementById('subatt_'+id);
    var subval = document.getElementById('subatt');

    if (subatt){
        if (subval.value == id){
            sub.style.border = '';
            subval.value = '';
        } else {
            for (var i = 1; i <= 5; i++){
                document.getElementById('subatt_'+i).style.border = '';
            }

            sub.style.border = '$attsel';
            subval.value = id;
        }
    } else {
        for (var i = 0; i <= 5; i++){
            document.getElementById('att_'+i).style.border = '';
        }

        main.style.border = '$attsel';
        mainval.value = id;
    }

    if (['1','2','3','4','5'].includes(mainval.value)) graylist = graylist.concat(attawaklist[mainval.value-1]);
    if (['1','2','3','4','5'].includes(subval.value)) graylist = graylist.concat(attawaklist[subval.value-1]);
    allattawak.forEach(function(v, i){
        grayscale(v, graylist.includes(v));
    });
}
function copyText(container){
    var text = document.getElementById(container);
    text.select();
    document.execCommand('copy');
}
function clearForm(){
    clearAwak();
    clearAwak(true);
    clearType();

    for (var i = 1; i <= 5; i++){
        document.getElementById('att_'+i).style.border = '';
        document.getElementById('subatt_'+i).style.border = '';
    }
    document.getElementById('att').value = 1;
    document.getElementById('subatt').value = '';

    document.getElementById('att_1').style.border = '$attsel';

    document.getElementById('id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('as').innerHTML = '';
    document.getElementById('ls').innerHTML = '';
    document.getElementById('cd').innerHTML = '';
}
function grayscale(awk, color=false){
    var img = document.getElementById('awak_img_'+awk);

    img.style.filter = color ? 'grayscale(0)' : 'grayscale(1)';
}
</script>
<form method='post' style='width: 50%; float: left; display: inline-block'>
    <h2>eznews - I don't know if it's actually easier</h2>
    <table style='width:100%'><tbody>
        <tr>
            <td style='width: 10%'>ID:</td>
            <td><input id='id' name='id' type='text' style='height:25px;' value='{$_POST['id']}'></td>
            <td style='width:100px;'></td>
        </tr>
        <tr style='height: 55px;'>
            <td style='width: 10%'>
                Att:
                <input id='att' name='att' style='display:none' value='{$_POST['att']}'>
                <input id='subatt' name='subatt' style='display:none' value='{$_POST['subatt']}'>
            </td>
            <td style='display:inline-block; border-right: 3px solid white !important;'>$attlist</td>
            <td style='display:inline-block;'>$subattlist</td>
        </tr>
        <tr>
            <td style='width: 10%'>Name:</td>
            <td><input id='name' name='mon_name' type='text' style='width:100%; height:25px;' value='{$_POST['mon_name']}'></td>
        </tr>
        <tr style='height:35px;'>
            <td style='width: 10%'>Type:</td>
            <td>
                <a id='type1_icon'>$type1</a><input id='type1' name='type1' style='display:none;' value='{$_POST['type1']}'>
                <a id='type2_icon'>$type2</a><input id='type2' name='type2' style='display:none;' value='{$_POST['type2']}'>
                <a id='type3_icon'>$type3</a><input id='type3' name='type3' style='display:none;' value='{$_POST['type3']}'>
            </td>
        </tr>
        <tr>
            <td colspan='2'>$typelist</td>
            <td><a class='eznewsbtn' onclick='clearType();'>Clear</a></td>
        </tr>
        <tr style='height: 45px;'>
            <td style='width: 10%'>Awak:<input id='awak' name='awak' style='display:none;' value='{$_POST['awak']}'></td>
            <td id='awak_icon'>$awak</td>
            <td><a class='eznewsbtn' onclick='clearAwak();'>Clear</a></td>
        </tr>
        <tr style='height: 45px;'>
            <td style='width: 10%'>SA:<input id='sa' name='sa' style='display:none;' value='{$_POST['sa']}'></td>
            <td id='sa_icon'>$sa</td>
            <td><a class='eznewsbtn' onclick='clearAwak(true);'>Clear</a></td>
        </tr>
        <tr>
            <td></td>
            <td colspan='2'>
                <label class='switch'>
                  <input id='sa_toggle' type='checkbox'>
                  <span class='slider round'></span>
                </label>
                <span>Super Awakening</span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td colspan='2'>$awaklist</td>
        </tr>
        <tr>
            <td style='width: 10%'>AS:</td>
            <td><textarea name='as' style='height: 80px; width: 100%;'>{$_POST['as']}</textarea></td>
            <td>CD:<br><input name='cd' type='number' style='max-width: 80px' value='{$_POST['cd']}'></td>
        </tr>
        <tr>
            <td style='width: 10%'>LS:</td>
            <td><textarea name='ls' style='height: 80px; width: 100%;'>{$_POST['ls']}</textarea></td>
        </tr>
    </tbody></table>

    <div style='width: 100%; margin-top: 15px;'><input type='submit'><a class='eznewsbtn' onclick='clearForm();'>Clear Form</a></div>
</form>
<div style='width: 50%; display: inline-block;'>
    <h2>One Liner</h2>
    [id] [att] [name] [type] [awak] [sa]
    <form method='post'>
        <input type='text' name='oneline' style='width: 100%;' value=\"{$_POST['oneline']}\">
        <input type='submit' style='float: right;'>
    </form>
</div>
<div style='width: 50%; display: inline-block;'>
    <h2>Output</h2>
    <h3 style='float: left;'>Discord Output</h3>
    <a class='eznewsbtn' style='float: right; margin-top:15px;' onclick='copyText(\"output_discord\")'>Copy</a>
    <textarea id='output_discord' style='width: 100%; height: 30vh;'>$discord</textarea>
    <h3 style='float: left;'>Blog Output</h3>
    <a class='eznewsbtn' style='float: right; margin-top:15px;' onclick='copyText(\"output_blog\")'>Copy</a>
    <textarea id='output_blog' style='width: 100%; height: 30vh;'>$blog</textarea>
</div>
<div style='width: 100%; height: 30vh; display: inline-block;'>$pvblog</div>
";

echo $form;
?>
