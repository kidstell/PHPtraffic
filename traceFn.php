<?php
set_time_limit(0);
include 'config.php';
include 'DB.php';

function getSpot($spotid){
	$sql="SELECT * FROM `".SPOTS_TBL."` WHERE sn ={$spotid}";
	$spot=DB::q($sql);
	return $spot[0];
}

function getLinks($spotid){
	$sql="SELECT * FROM `".LINKS_TBL."` a, `".SPOTS_TBL."` b WHERE a.stop_point_sn = b.sn AND a.start_point_sn ={$spotid}";
	$links=DB::q($sql);
	return $links;
}


function trackInit($start_point,$target,$shortestdistance){

	$tracks=getLinks($start_point['sn']);

	foreach ($tracks as $key => $value) {
		$tracks[$key]['history']=array();
		$tracks[$key]['history'][]=$tracks[$key]['start_point_sn'];

		$shortestdistance1=geoGetDistance($tracks[$key]['lat'],$tracks[$key]['lng'],$target['lat'],$target['lng'],'K');

		$tracks[$key]['times']=array(0, ($tracks[$key]['distance_km']/$tracks[$key]['ave_speed_today']));
		$tracks[$key]['distaway']=array($shortestdistance,$shortestdistance1);
		$tracks[$key]['travelled']=array(0,$tracks[$key]['distance_km']);
		$tracks[$key]['probate']=0;
	}
	return $tracks;
}


function journeyApp($start_sn,$stop_sn,$maxNoTracks=5,$maxRounds=40){
	$start_point=getSpot($start_sn);
	$target=getSpot($stop_sn);

	$tracksfound=$rounds=$leastprobed=0;
	$found=array();

	$minGeoDistAway=$shortestdistance=geoGetDistance($start_point['lat'],$start_point['lng'],$target['lat'],$target['lng'],'K');
	$maxGeoDistAway=0;

	$tracks=trackInit($start_point,$target,$minGeoDistAway);

	while ($tracksfound<$maxNoTracks) {
		$mvmnt=$tracks; $passlist=array();
		foreach ($mvmnt as $mk => $mv) {
			$linkables=getLinks($mv['stop_point_sn']);
			foreach ($linkables as $lk => $lv) {
				$lv['probate']=$mv['probate'];
				if (in_array($lv['stop_point_sn'], $mv['history'])) {//avoiding reverse movement
					continue;
				}

				$da=geoGetDistance($lv['lat'],$lv['lng'],$target['lat'],$target['lng'],'K');
				if(count($tracks)>20 && ($lv['probate']>0 || $maxGeoDistAway<$da)){
					continue;
				}

				$maxGeoDistAway=max($maxGeoDistAway,$da);
				$minGeoDistAway=min($minGeoDistAway,$da);
				
				$lv['history']=$mv['history'];				$lv['history'][]=$lv['start_point_sn'];
				$lv['times']=$mv['times'];						$lv['times'][]=$mv['distance_km']/$mv['ave_speed_today'];
				$lv['distaway']=$mv['distaway'];			$lv['distaway'][]=$da;
				$lv['travelled']=$mv['travelled'];		$lv['travelled'][]=$lv['distance_km'];
				
				if (array_sum($lv['travelled'])>$shortestdistance) {
					$rda=array_sum($lv['distaway'])/(count($lv['distaway'])-1);
					if ($rda<$lv['distaway'][count($lv['distaway'])-1]) {
						$lv['probate']++;
						$leastprobed=($leastprobed>0)?min($leastprobed,$lv['probate']):$lv['probate'];
					}
				}
				if ($lv['stop_point_sn']==$stop_sn) {
					$remove=array('link_name','alias_comb','road_quality','distance_km','max_speed_allow','last_known_speed','ave_speed_today','ave_speed_a','report_vol_all_time','point_name','lat','lng','remark');
					$f=array();
					$f['probate']=$lv['probate'];
					$f['times']=$lv['times'];
					$f['history']=$lv['history'];
					$f['distaway']=$lv['distaway'];
					$f['travelled']=$lv['travelled'];

					$f['history'][]=$lv['stop_point_sn']; unset($lv);
					$f['total_time']=(array_sum($f['times'])*60).' minutes';
					$f['total_dist']=array_sum($f['travelled']).' km';

					$found[]=$f;
					$tracksfound++;
					continue;
				}else{
					$passlist[]=$lv;
				}
			}
		}
		if (count($passlist)==0||$rounds>$maxRounds) {
			// if ($tracksfound>0) {break;}
			// $tracks['reachable']=0;
			// $tracks['tracksfound']=0;//count($tracks);
			// echo 'stopped';
			//return $tracks;
		}
		$tracks=$passlist;
		$rounds++;
	}
	$found['reachable']=1;
	$found['tracksfound']=$tracksfound;
	return $found;
}





print_r(journeyApp(686,1386,5,60));
//[856]-[173]-[409]-[416]-[49]-[497]-[134]-[494]-[686]
//[221]-[46]-[133]-[451]-[427]-[252]-[91]-[1261]-[1435]-[121]-[1093]

//(221, 46, 133, 451, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 46, 133, 451, 1175, 892, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 46, 133, 1555, 797, 451, 427, 252, 91, 1261, 1435, 121, 1093)

//(221, 1267, 98, 1076, 451, 427, 252, 91, 1261, 1435, 121, 1093)



//(130, 1260, 773, 1581, 990, 1039, 337, 716, 1584, 936)
//(130, 1260, 923, 1039, 337, 716, 1584, 936)
//add 661-1020