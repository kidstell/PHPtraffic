<?php
//die("this program is supposed to run massive database operations! comment the line throwing this to continue");
set_time_limit(0);
include 'config.php';
include 'DB.php';

$one_meter_vector=0.000009;


/**********************************/
//create DB
$db=mysql_connect(DB_SERVER, DB_USER, DB_PASS);
$sql="CREATE DATABASE IF NOT EXISTS `".DB_NAME."`";
mysql_query($sql,$db);
mysql_select_db(DB_NAME) or die(mysql_error());


//create bus_stops table
$sql="CREATE TABLE IF NOT EXISTS `".SPOTS_TBL."` (
  `sn` int(11) NOT NULL AUTO_INCREMENT,
  `point_name` varchar(150) NOT NULL,
  `lat` float default NULL,
  `lng` float default NULL,
  `remark` varchar(250) NOT NULL,
  PRIMARY KEY (`sn`)
)";
DB::q($sql,'c');

//create link_roads table
$sql="CREATE TABLE IF NOT EXISTS `".LINKS_TBL."` (
  `sn` int(11) NOT NULL AUTO_INCREMENT,
  `start_point_sn` int(11) NOT NULL,
  `stop_point_sn` int(11) NOT NULL,
  `link_name` varchar(150) NOT NULL,
  `alias_comb` varchar(110) NOT NULL,
  `road_quality` int(1) NOT NULL,
  `distance_km` float default NULL,
  `max_speed_allowed` float default NULL,
  `last_known_speed` float default NULL,
  `ave_speed_today` float default NULL,
  `ave_speed_all_time` float default NULL,
  `report_vol_all_time` int(11) NOT NULL,
  PRIMARY KEY (`sn`)
)";
DB::q($sql,'c');

$min_lat=LAGOS_START_LAT;//6.465422;
$min_lng=LAGOS_START_LNG;//3.406448;

$max_lat=LAGOS_END_LAT;//6.7556530999546
$max_lng=LAGOS_END_LNG;//3.5029325000008


$nums="0123456789";
$caps="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$acaps="abcdefghijklmnopqrstuvwxyz";

$str_pool=$caps.$nums;//$caps.$acaps.$nums;
$name_pool="";
$pos_pool="";

for ($i=1; $i <= SAMPLE_SIZE; $i++) { 
	$name="";
	while ($name=="" || strstr($name_pool, $name)) {
		$name=substr(str_shuffle($str_pool), 0,4);
	}
	$name_pool.=$name.",";

	$pos="";
	while ($pos=="" || strstr($pos_pool, $pos)) {
		$r=rndPos();
		$pos="[{$r['lat']},{$r['lng']}]";
	}
	
	$pos_pool.=$pos.",";

	$values=array('point_name'=>$name,'lat'=>$r['lat'],'lng'=>$r['lng']);

	echo $i."...";//
	if ($r['lat']<6.465422 || $r['lat']>6.7556530999546 ||$r['lng']<3.406448||$r['lng']>3.5029325000008) {
		echo '{{{{latrand: '.$r['lat'];
		echo 'lngrand: '.$r['lng'].'}}}}}';
	}else{
		echo $r['lat'].'...'.$r['lng'];
	}
	echo DB::insert(SPOTS_TBL,$values);
	echo "<br>=====================================<br>";
}


$lat_tolerance=820*$one_meter_vector;//(+/-)460*2 meters in latitude
$lng_tolerance=1120*$one_meter_vector;//(+/-)560*2 meters in longtitude


for ($i=1; $i <= SAMPLE_SIZE; $i++) { 
	$starter=DB::getDataByField('sn',$i,SPOTS_TBL);
	$lat=$starter[0]['lat'];
	$lng=$starter[0]['lng'];

	$minlat=$lat-$lat_tolerance;
	$maxlat=$lat+$lat_tolerance;

	$minlng=$lng-$lng_tolerance;
	$maxlng=$lng+$lng_tolerance;

	$sql="SELECT * FROM ".SPOTS_TBL." WHERE (lat>='{$minlat}' AND lat<='{$maxlat}') AND (lng>='{$minlng}' AND lng<='{$maxlng}') AND sn<>$i";
	$linkables=DB::q($sql);
	
	while (count($linkables)<8) {//==0) {
		$hm=100*$one_meter_vector;
		//if (lcg_value()<0.65) {
			$minlat-=$hm;
			$maxlat+=$hm;
		//}else{
			$minlng-=$hm;
			$maxlng+=$hm;
		//}

		$sql="SELECT * FROM ".SPOTS_TBL." WHERE (lat>='{$minlat}' AND lat<='{$maxlat}') AND (lng>='{$minlng}' AND lng<='{$maxlng}') AND sn<>$i";
		$linkables=DB::q($sql);
	}
	
	shuffle($linkables);
	$linkables = array_slice($linkables, 0, min(6,count($linkables)));
	$rp=rand(0,count($linkables)-1);
	$rqFill=array(1,1,1,2,2,2,2,2,2,2,2,2,2,2,3,3,3,3,4,4,5,5);
	shuffle($rqFill);
	$smart_tol=0.4;
	
	$lv=1;
	foreach ($linkables as $key => $link) {
		if ($link['sn']==$i) continue;
		if ($key==$rp || ($smart_tol>lcg_value() && $smart_tol>lcg_value())) {//==$rp || $lv<6){
			// if ($lv>2 && lcg_value()>8) {
			// 	continue;
			// }
			$rqr=$rqFill[rand(0,count($rqFill)-1)];
			$dkm=geoGetDistance($starter[0]['lat'],$starter[0]['lng'],$link['lat'],$link['lng'],'K');
			if ($dkm>4) {
				$max_speed_allowed=160;//km/hr
			}elseif ($dkm>3) {//>3km <3.9km
				$max_speed_allowed=120;//km/hr
			}elseif ($dkm>1.5) {//>1.5km <2.9km
				$max_speed_allowed=80;//km/hr
			}else{//>0km <=1.49km
				$max_speed_allowed=60;//km/hr
			}

			$last_known_speed=($max_speed_allowed*rand(80,100)*0.01)-($rqr*$rqr)*rand(0,50)*0.01;
			$values=array('start_point_sn'=>$i, 'stop_point_sn'=>$link['sn'], 'link_name'=>'', 'alias_comb'=>$link['point_name'].'-'.$starter[0]['point_name'], 'road_quality'=>$rqr, 'distance_km'=>$dkm, 'max_speed_allowed'=>$max_speed_allowed, 'last_known_speed'=>$last_known_speed, 'ave_speed_today'=>$last_known_speed*rand(90,120)*0.01, 'ave_speed_all_time'=>$last_known_speed*rand(90,120)*0.01,'report_vol_all_time'=>rand(100,500));
			DB::insert(LINKS_TBL,$values);
			$lv++;
		}
	}
	echo $i."....".$lv."<br>\n";
}