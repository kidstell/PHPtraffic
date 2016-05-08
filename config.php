<?php
define('DB_SERVER', 'localhost');
define('DB_NAME', 'traffic_app');
define('DB_USER', 'root');
define('DB_PASS', '');

define('LAGOS_START_LAT', 6.465422);
define('LAGOS_END_LAT',   6.755653);//6.7556530999546
define('LAGOS_START_LNG', 3.406448);
define('LAGOS_END_LNG',   3.502932);//3.5029325000008
define('SAMPLE_SIZE', 1600);//1300);

define('SPOTS_TBL', 'bus_stops');
define('LINKS_TBL', 'link_roads');


function geoGetDistance($lat1,$lon1,$lat2,$lon2,$unit='M') {
    
  // calculate miles
  $M =  69.09 * rad2deg(acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon1 - $lon2)))); 

  switch(strtoupper($unit))
  {
    case 'K':
      // kilometers
      return $M * 1.609344;
      break;
    case 'N':
      // nautical miles
      return $M * 0.868976242;
      break;
    case 'F':
      // feet
      return $M * 5280;
      break;            
    case 'I':
      // inches
      return $M * 63360;
      break;            
    case 'M':
    default:
      // miles
      return $M;
      break;
  }
}

$xlagos=geoGetDistance(LAGOS_START_LAT,LAGOS_START_LNG,LAGOS_END_LAT,LAGOS_START_LNG,$unit='K');
$ylagos=geoGetDistance(LAGOS_START_LAT,LAGOS_START_LNG,LAGOS_START_LAT,LAGOS_END_LNG,$unit='K');

define('LAGOS_LENGTH', $xlagos);//km
define('LAGOS_BREADTH', $ylagos);//km


function rndLat(){
  $latstart=str_replace('.', '', LAGOS_START_LAT);
  $latstop=str_replace('.', '', LAGOS_END_LAT);

  $z='0000000';

  $latpick=rand($latstart,$latstop);
  $ld=substr($latpick,-1).$z;
  $ld=rand($ld,30999546);
  $dl=8-strlen($ld);
  $ld=substr($z,0,$dl).$ld;

  $latpick=substr($latpick, 0,-1);
  $latpick.=$ld;
  $latpick=$latpick/pow(10, strlen($latpick)-1);
  return $latpick;
}

function rndLng(){
  $lngstart=str_replace('.', '', LAGOS_START_LNG);
  $lngstop=str_replace('.', '', LAGOS_END_LNG);

  $z='0000000';

  $lngpick=rand($lngstart,$lngstop);
  $ld=rand(0,5000008);
  $dl=7-strlen($ld);
  $ld=substr($z,0,$dl).$ld;
  $lngpick.=$ld;

  $lngpick=$lngpick/pow(10, strlen($lngpick)-1);
  return $lngpick;
}

function rndPos(){
  return array('lat'=>rndLat(), 'lng'=>rndLng());
}


/*
0.000009 on lat or lng approx 1 meter distance

Area: 344.00021698482 Km(sq)
Tries: 143228
lat1: 6.465422
lon1: 3.406448

lat2: 6.7556530999546
lon2: 3.5029325000008

lat distance=32.270673224598Km or 32270.673224598m or 3227067.3224598cm
lng distance=10.659840115223Km or 10659.840115223m or 1065984.0115223cm

lat difference=0.2902310999546
lon difference=0.0964845000008
*/
