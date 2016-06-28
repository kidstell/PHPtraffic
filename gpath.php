<?php
//die("this program is supposed to run massive database operations! comment the line throwing this to continue");
/*
GeneratePATH:gpath
you might need to open the database wrapper in DB.php in order to set database name on line 10
you may also need to comment line 37 in this script and uncomment/modify line 36

this script shows how many possible paths can be generated in 10 steps as indicated by $stepTest
$allowReverseDir==false ensures that the script is not tempted to oscillate brtween to points (redundance)
if set to true unique path may be generated but it is noticed that when $stepTest (i.e 10 steps) can not be achieved the paths continue to oscillate to and fro

$stepTest indicate number of steps to trail from starting point $rspid

$rspid (random stop point id) though set as constant for now can be made random to enhance observation
$rspid is an integer between 1 and 1000 to ensure the value is a valid primary key on hack_stop_points

$tp is temproary collection of successive paths
$ntp is a draft of $tp which is usually swapped to update $tp

the first set of linkable stop points is obtained manually and stored in $children[1]
where 1 indicates number of steps taken
the paths are initialized

every child in the current step is traversed one step ahead to know all possible location the child can lead to

all reachable stop points from the current collection of children is added children in the next step

the children in the next step get iterated and produce more children for the next step

if reverse Direction is allowed as in $allowReverseDir == true
	a child will not be added to the next step if it links to a known (already used stop_point) in its own path

the $tp is updated per step to ensure that redundant paths are removed

*/
set_time_limit(0);
include 'php/config.php';
include 'php/DB.php';

$db=mysql_connect(DB_SERVER, DB_USER, DB_PASS);
mysql_select_db(DB_NAME) or die("<a href='install.php'>Click here to install</a>");

$allowReverseDir=false;

$stepTest=10;
$currentStep=1;

$rspid=rand(1,1000);

$tp=$ntp=$children=array();
$tp[$rspid]='['.$rspid.']';

$sql="SELECT * FROM `".LINKS_TBL."` a, `".SPOTS_TBL."` b WHERE a.start_point_sn ={$rspid} AND a.stop_point_sn = b.sn";
$children[1]=DB::q($sql);
//print_r($tp);

//print_r($children);
while ($currentStep<=$stepTest && isset($children[$currentStep])) {
	$usedkeys=$ntp=array();
	foreach ($children[$currentStep] as $index => $point) {
		$reverseDetected=false;
		if ($allowReverseDir==false) {
			foreach ($tp as $key => $value) {
				if (strstr($value, '['.$point['stop_point_sn'].']')) $reverseDetected=true;
				//echo $value.', '.$point['stop_point_sn']."\n";
			}
		}
		if ($reverseDetected==true) {$reverseDetected=false; continue; }

		$sql="SELECT * FROM `".LINKS_TBL."` a, `".SPOTS_TBL."` b WHERE a.start_point_sn ={$point['stop_point_sn']} AND a.stop_point_sn = b.sn";
		$res=DB::q($sql);
		if (!isset($children[$currentStep+1])) {
			$children[$currentStep+1]=array();
		}
		$children[$currentStep+1]=array_merge($children[$currentStep+1],$res);
		if (isset($tp[$point['start_point_sn']])) {
			$usedkeys[]=$point['start_point_sn'];
			$ntp[$point['stop_point_sn']]=$tp[$point['start_point_sn']].'-['.$point['stop_point_sn'].']';
		}

	}
	//$tp=$ntp;
	foreach ($ntp as $k => $v) {
		$tp[$k]=$v;
	}
	foreach ($usedkeys as $k => $v) {
		if(!isset($tp[$v])) continue;
		unset($tp[$v]);
	}
	$currentStep++;
}

print_r($tp);
//echo "==============================================================================<br>\n";


echo "number of longest path = ".count($children[$currentStep-1])."<br>\n";
$paths=array();
foreach ($children[$currentStep-1] as $key => $value) {
	$paths[]=$value['start_point_sn'];
}
//print_r($paths);
//print_r($children[$currentStep-1]);


$dist=$time=array();
foreach ($paths as $key => $value) {
	if (!isset($tp[$value])) {
		continue;
	}
	$journey=$tp[$value];
	$dist[$value]=0;
	$time[$value]=0;
	$j=explode('-', $journey);
	foreach ($j as $k => $v) {
		$v=str_replace('[', '', $v);
		$v=str_replace(']', '', $v);
		$link=DB::getDataByField('sn',$v,LINKS_TBL);
		//print_r(expression);
		$dist[$value]+=$link[0]['distance_km'];
		$time[$value]+=(($link[0]['distance_km']*1000)/$link[0]['last_known_speed']);
	}
}
echo "DIST =============<br>\n";
print_r($dist);
echo "TIME =============<br>\n";
print_r($time);
?>