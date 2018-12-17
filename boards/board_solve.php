<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="boards.css">
</head>
<body>
<?php
include 'boards_common.php';
function getMatches($pattern, $size = 'm', $minimumMatched = 2){
	global $size_list;
	
	echo get_board($pattern) . '<br/>';
	
	$wh = $size_list[$size];
	$p_arr = str_split($pattern);
	$comboPositionList = array();
	$comboColor = '';
	$comboPosition = array();
	for($f = 0; $f < $wh[1]; $f++){
		$comboColor = '';
		$comboPosition = array();
		for($i = $f*$wh[0]; $i < $f*$wh[0]+$wh[0]; $i++){
			if ($p_arr[$i] != $comboColor){
				if (sizeof($comboPosition) > $minimumMatched){
					$comboPositionList = array_merge($comboPositionList, $comboPosition);
				}
				$comboColor = $p_arr[$i];
				$comboPosition = array();
			}
			$comboPosition[] = $i;
			if (sizeof($comboPosition) > $minimumMatched && $i == $f*$wh[0]+$wh[0]-1){
				$comboPositionList = array_merge($comboPositionList, $comboPosition);
			}
		}
	}
	for($f = 0; $f < $wh[0]; $f++){
		$comboColor = '';
		$comboPosition = [];
		for($i = 0+$f; $i < $wh[0]*$wh[1]; $i=$i+$wh[0]){
			if ($p_arr[$i] != $comboColor){
				if (sizeof($comboPosition) > $minimumMatched){
					$comboPositionList = array_merge($comboPositionList, $comboPosition);
				}
				$comboColor = $p_arr[$i];
				$comboPosition = array();
			}
			$comboPosition[] = $i;
			if (sizeof($comboPosition) > $minimumMatched && $i > $wh[0]*($wh[1]-1)-1){
				$comboPositionList = array_merge($comboPositionList, $comboPosition);
			}
		}
	}
	echo '<br/>';
	
	if (sizeof($comboPositionList) == 0){
		return false;
	}
	class FloodFill{
		public $p_arr = array();
		public $comboColor = '';
		public $wh = array();
		public $minimumMatched;
		public $comboPositionList = array();
		public $comboTracker = array();
		public $stack = array();
		public $solutions = array();
		public $track = array();
		function __construct($p_arr, $wh, $minimumMatched, $comboPositionList) {
			$this->p_arr = $p_arr;
			$this->minimumMatched = $minimumMatched;
			$this->wh = $wh;
			$this->comboPositionList = $comboPositionList;
			foreach($comboPositionList as $key => $value){
				$this->comboTracker[$value] = $key;
			}
		}
		function convertXY($p){
			return array($p%$this->wh[0], floor($p/$this->wh[0]));
		}
		function convertPosition($x, $y){
			return intval($y * $this->wh[0] + $x);
		}
		function alreadyFilled($x, $y){
			if ($x<0 || $y<0 || $x>$this->wh[0]-1 || $y>$this->wh[1]-1){
				return true;
			}
			if (!array_key_exists($this->convertPosition($x, $y), $this->comboTracker)){
				return true;
			}
			if ($this->p_arr[$this->convertPosition($x, $y)] != $this->comboColor){
				return true;
			}
			return false;
		}
		function fillPosition ($x, $y){
			if(!$this->alreadyFilled($x, $y)) {
				$p = $this->convertPosition($x, $y);
				unset($this->comboTracker[$p]);
				$track[] = $p;
				echo 'self ' . $p . '<br/>';
			}
			if(!$this->alreadyFilled($x, $y-1)){
				$this->stack[] = array($x, $y-1);
			}
			if(!$this->alreadyFilled($x+1, $y)){
				$this->stack[] = array($x+1, $y);
			}
			if(!$this->alreadyFilled($x, $y+1)){
				$this->stack[] = array($x, $y+1);
			}
			if(!$this->alreadyFilled($x-1, $y)){
				$this->stack[] = array($x-1, $y);
			}
			print_r($this->stack[]);
			echo 'out';
		}
		function floodFill($p){
			$this->comboColor = $this->p_arr[$p];
			if (!array_key_exists($p, $this->comboTracker)){
				return;
			}
			if($this->comboColor == '-'){
				return;
			}
			$this->track = array();
			$xy = $this->convertXY($p);
			$this->fillPosition($xy[0], $xy[1]);
			echo '<br/>';
			/*while(sizeof($this->stack)>0){
				$toFill = end($this->stack);
				$this->fillPosition($toFill[0], $toFill[1]);
			}*/
			if(sizeof($this->track) > $this->minimumMatched){
				$this->solutions[] = $this->track;
			}
		}
	}
	$ff = new FloodFill($p_arr, $wh, $minimumMatched, $comboPositionList);
	foreach($comboPositionList as $p => $color){
		$ff->floodFill($p);
	}
	print_r($ff);
}
$pattern = 'DDGGGDGDDGGDGDGDDGGGDGGDDDGGGD';
getMatches($pattern);
?>
</body>
</html>