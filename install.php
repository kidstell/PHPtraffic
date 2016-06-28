<?php
die("this program is supposed to run massive database operations! comment the line throwing this to continue");
die("this script should only be run once to avoid unnecessary data manipulation or duplication in Database\n<br> Are you sure you want to continue? \n<br><code style="color:#cc3030;"><blink>HINT :</blink>always clear database before running this script a second time.</code>");
set_time_limit(0);
include 'php/config.php';
include 'php/DB.php';

$one_meter_vector=0.000009;
mt_srand((double)microtime()*1000000);

//create DB
$db=mysql_connect(DB_SERVER, DB_USER, DB_PASS);
$sql="CREATE DATABASE IF NOT EXISTS `".DB_NAME."`";
mysql_query($sql,$db) or die(mysql_error());;
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

$xsep=48.9;
$ysep=50.7;

$ith=1;

for ($i=0; $i<67; $i++) {
	for ($j=0; $j < 22; $j++) { 
		$name="";
		while ($name=="" || strstr($name_pool, $name)) {
			$name=substr(str_shuffle($str_pool), 0,4);
		}
		$name_pool.=$name.",";

		$pos="";
		while ($pos=="" || strstr($pos_pool, $pos)) {
			$x=LAGOS_START_LAT+($i*$one_meter_vector*mt_rand(96,112)*0.01*$xsep);
			$y=LAGOS_START_LNG+($j*$one_meter_vector*mt_rand(96,112)*0.01*$ysep);
			$pos="[{$x},{$y}]";
		}

		$values=array('point_name'=>$name,'lat'=>$x,'lng'=>$y);

		/*echo $ith."...";*/
		$ith++;
		/*if ($x<6.465422 || $x>6.7556530999546 ||$y<3.406448||$y>3.5029325000008) {
			echo '{{{{latrand: '.$x;
			echo 'lngrand: '.$y.'}}}}}';
		}else{
			//echo $x.'...'.$y;
		}
		echo */ DB::insert(SPOTS_TBL,$values);
		//echo "<br>=====================================<br>\n";
	}
}

echo "\n<br>{$ith} bus stops created successfully\n<br>";

echo "...Linking {$ith} bus stops to random destinations...\n<br>";

$lat_tolerance=460*$one_meter_vector;//(+/-)460 meters in latitude
$lng_tolerance=560*$one_meter_vector;//(+/-)560 meters in longtitude

$linkVolume=0;

for ($i=1; $i <= 1474; $i++) { 
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
		$minlat-=$hm;
		$maxlat+=$hm;
		$minlng-=$hm;
		$maxlng+=$hm;

		$sql="SELECT * FROM ".SPOTS_TBL." WHERE (lat>='{$minlat}' AND lat<='{$maxlat}') AND (lng>='{$minlng}' AND lng<='{$maxlng}') AND sn<>$i";
		$linkables=DB::q($sql);
	}
	
	shuffle($linkables);
	$linkables = array_slice($linkables, 0, min(6,count($linkables)));
	$rqFill=array(1,1,1,2,2,2,2,2,2,2,2,2,2,2,3,3,3,3,4,4,5,5);//$roadQualityFillers
	shuffle($rqFill);
	$smart_tol=0.4;
	
	$lc=0; //$lc: linkCounter
	foreach ($linkables as $key => $link) {
		if ($link['sn']==$i) continue;
		if ($lc>=4 && mt_rand(0,100)<=95){ continue;}
		elseif ($lc>=3 && mt_rand(0,100)<=70){ continue;}
		elseif ($lc>=2 && mt_rand(0,100)>70){ continue;}

		$rqr=$rqFill[mt_rand(0,count($rqFill)-1)];//$rqr: roadQualityRandomized; $dkm:distance(in Kilometere)
		$dkm=geoGetDistance($starter[0]['lat'],$starter[0]['lng'],$link['lat'],$link['lng'],'K');//straight line distance
		$dkm=$dkm*mt_rand(100,150)*0.01;//increamenting the straight line distance since a curved road is actually longer than a curved road, simply say accounting for curved road within two points
		if ($dkm>4) {
			$max_speed_allowed=160;//km/hr
		}elseif ($dkm>3) {//>3km <3.9km
			$max_speed_allowed=120;//km/hr
		}elseif ($dkm>1.5) {//>1.5km <2.9km
			$max_speed_allowed=80;//km/hr
		}else{//>0km <=1.49km
			$max_speed_allowed=60;//km/hr
		}

		$last_known_speed=($max_speed_allowed*mt_rand(80,100)*0.01)-($rqr*$rqr)*mt_rand(0,50)*0.01;
		
		$values=array('start_point_sn'=>$i, 'stop_point_sn'=>$link['sn'], 'link_name'=>'', 'alias_comb'=>$starter[0]['point_name'].'-'.$link['point_name'], 'road_quality'=>$rqr, 'distance_km'=>$dkm, 'max_speed_allowed'=>$max_speed_allowed, 'last_known_speed'=>$last_known_speed, 'ave_speed_today'=>$last_known_speed*mt_rand(90,120)*0.01, 'ave_speed_all_time'=>$last_known_speed*mt_rand(90,120)*0.01,'report_vol_all_time'=>mt_rand(100,500));
		
		DB::insert(LINKS_TBL,$values);
		$lc++;
	}
	$linkVolume+=$lc;
	//echo $i."....".$lc."<br>\n";
}

echo "{$ith} bus stops sucessfully linked to {$linkVolume} destinations...\n<br>";
echo "{$linkVolume} link roads created\n<br>\n<br>";
echo "you may remove the installation script";
