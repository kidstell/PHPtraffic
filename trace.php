<?php
set_time_limit(0);
include 'config.php';
include 'DB.php';

$db=mysql_connect(DB_SERVER, DB_USER, DB_PASS);
mysql_select_db(DB_NAME) or die("<a href='install.php'>Click here to install</a>");

$start_sn=(isset($_REQUEST['startsn']))?intval($_REQUEST['startsn']):0;
if ($start_sn<1 || $start_sn>SAMPLE_SIZE) {
	$start_sn=rand(1,SAMPLE_SIZE);
}

$stop_sn=(isset($_REQUEST['stopsn']))?intval($_REQUEST['stopsn']):0;
if ($stop_sn<1 || $stop_sn>SAMPLE_SIZE) {
	$stop_sn=rand(1,SAMPLE_SIZE);
	if ($stop_sn==$start_sn) {
		$stop_sn=max($start_sn+10,$start_sn-10);
		if ($stop_sn>SAMPLE_SIZE) $stop_sn-=SAMPLE_SIZE;
	}
}
 
$sql="SELECT * FROM `".SPOTS_TBL."` WHERE sn ={$start_sn}";
$start_point=DB::q($sql); $start_point=$start_point[0];

$sql="SELECT * FROM `".SPOTS_TBL."` WHERE sn ={$stop_sn}";
$target=DB::q($sql); $target=$target[0];

$shortestdistance=geoGetDistance($start_point['lat'],$start_point['lng'],$target['lat'],$target['lng'],'K');


$sql="SELECT * FROM `".LINKS_TBL."` a, `".SPOTS_TBL."` b WHERE a.stop_point_sn = b.sn AND a.start_point_sn ={$start_sn}";
$tracks=DB::q($sql);

foreach ($tracks as $key => $value) {
	//print_r($tracks);
	$tracks[$key]['history']=array();
	$tracks[$key]['history'][]=$tracks[$key]['start_point_sn'];

	$shortestdistance1=geoGetDistance($tracks[$key]['lat'],$tracks[$key]['lng'],$target['lat'],$target['lng'],'K');

	$tracks[$key]['times']=array(0, ($tracks[$key]['distance_km']/$tracks[$key]['ave_speed_today']));
	$tracks[$key]['distaway']=array($shortestdistance,$shortestdistance1);
	$tracks[$key]['travelled']=array(0,$tracks[$key]['distance_km']);
	$tracks[$key]['probate']=0;
}

$tracksfound=0;
$rounds=0;
$found=array();

$maxNoTracks=5;
$leastprobed=0;
$minGeoDistAway=$shortestdistance;
$maxGeoDistAway=0;

while ($tracksfound<$maxNoTracks) {
	$mvmnt=$tracks; $passlist=array();
	foreach ($mvmnt as $mk => $mv) {
		$sql="SELECT * FROM `".LINKS_TBL."` a, `".SPOTS_TBL."` b WHERE a.stop_point_sn = b.sn AND a.start_point_sn ={$mv['stop_point_sn']}";
		$linkables=DB::q($sql);
		foreach ($linkables as $lk => $lv) {
			$lv['probate']=$mv['probate'];
			if (in_array($lv['stop_point_sn'], $mv['history'])) {//avoiding reverse movement
				continue;
			}
			if(count($tracks)>20 && $lv['probate']>0){
				continue;
			}
			
			$lv['history']=$mv['history'];
			$lv['history'][]=$lv['start_point_sn'];

			$da=geoGetDistance($lv['lat'],$lv['lng'],$target['lat'],$target['lng'],'K');
			$maxGeoDistAway=max($maxGeoDistAway,$da);
			$minGeoDistAway=min($minGeoDistAway,$da);

			$lv['times']=$mv['times'];
			$lv['times'][]=$mv['distance_km']/$mv['ave_speed_today'];
			$lv['distaway']=$mv['distaway'];
			$lv['distaway'][]=$da;
			$lv['travelled']=$mv['travelled'];
			$lv['travelled'][]=$lv['distance_km'];
			if (array_sum($lv['travelled'])>$shortestdistance) {
				$rda=array_sum($lv['distaway'])/(count($lv['distaway'])-1);
				if ($rda<$lv['distaway'][count($lv['distaway'])-1]) {
					$lv['probate']++;
					//$leastprobed=min($leastprobed,$lv['probate']);
					$leastprobed=($leastprobed>0)?min($leastprobed,$lv['probate']):$lv['probate'];
				}
			}
			if ($lv['stop_point_sn']==$stop_sn) {
				$lv['history'][]=$lv['stop_point_sn'];
				$lv['total_time']=(array_sum($lv['times'])*60).' minutes';
				$lv['total_dist']=array_sum($lv['travelled']).' km';
				$found[]=$lv;
				$tracksfound++;
				continue;
			}else{
				$passlist[]=$lv;
			}
		}
	}
	if (count($passlist)==0||$rounds>60) {
		echo "unreachable after ({$rounds}) Rounds<br>\n";
		print_r($tracks);
		echo "\nTarget===================================\n";
		print_r($target);
		die();
	}
	$tracks=$passlist;
	$rounds++;
}

echo "\n===================================\nFOUNDS ({$tracksfound})\n";
print_r($found);
print_r($target);
//[856]-[173]-[409]-[416]-[49]-[497]-[134]-[494]-[686]
//[221]-[46]-[133]-[451]-[427]-[252]-[91]-[1261]-[1435]-[121]-[1093]

//(221, 46, 133, 451, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 46, 133, 451, 1175, 892, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 46, 133, 1555, 797, 451, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 1267, 98, 1076, 451, 427, 252, 91, 1261, 1435, 121, 1093)



//(130, 1260, 773, 1581, 990, 1039, 337, 716, 1584, 936)
//(130, 1260, 923, 1039, 337, 716, 1584, 936)
//add 661-1020