<?php
include_once 'php/config.php';
include_once 'php/traceFn.php';



?>

<!DOCTYPE html>
<html ng-app="myapp">
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/angular.min.js"></script>
	<script type="text/javascript" src="js/angular-route.min.js"></script>
	<script type="text/javascript" src="js/angular-cookies.min.js"></script>
</head>
<body>

<div ng-view></div>

<script type="text/javascript">
	var app = angular.module("myapp", ['ngRoute','ngCookies']);
	
	app.config([
		'$routeProvider',
		function($routeProvider) {
			$routeProvider
				.when('/', 
					{
						templateUrl: 'home.php',
						controller: 'homeCtrl'
					})
		}
	]);
	
	app.controller(
		"homeCtrl",
		['$rootScope','$scope','$location','$cookies','$http',
			function($rootScope,$scope,$location,$cookies,$http) {
				$scope.zones={}

				$scope.zones.zonesn1=0;
				$scope.zones.zonesn2=0;

				$scope.zones.points=[];

				$scope.zones.point1={};
				$scope.zones.point2={};

				$scope.journeyTracks={};
				$scope.trackshow=[];
				$scope.journeyTracks.tracksfound=0;
				$scope.journeyTracks.reachable=0;
				$scope.journeyTracks.tracks=[];

				$scope.tracks=[];
				$scope.tracked=false;

				$scope.setZone=function(z){
					$scope.waiting=true;
					$scope.tracked=false;
					if (z==1){zsn=$scope.zones.zonesn1;$scope.zones.point1.name='';}else{zsn=$scope.zones.zonesn2;$scope.zones.point2.name='';}
					$http.get('php/ajax.php?get=points&zone='+zsn)
					.then(
						function(response){
							$scope.zones.points[z]=response.data;
							$scope.waiting=false;
						},
						function(response){
							alert('fail');
							$scope.waiting=false;
						}
					);
				}

				$scope.setPoint=function(i){
					$scope.tracked=false;
					if (i==1) {a=$scope.zones.point1.sn;}else{a=$scope.zones.point2.sn;}
					opob=angular.element('#point'+i+' option[value='+a+']');
					opob=opob.text();
					if (i==1) {$scope.zones.point1.name=opob;}else{$scope.zones.point2.name=opob;}
				}

				$scope.showTracks=function(tracks){
					//if(tracks.reachable==0){$scope.reachable=false;}else{$scope.reachable=true;}
					$scope.journeyTracks=tracks;
					$scope.tracked=true;
				}

				$scope.trace=function(){
					if($scope.zones.point1.sn==0 || $scope.zones.point2.sn==0 || $scope.zones.point1.sn==null || $scope.zones.point2.sn==null){
						alert('select 2 locations');
						return true;
					}
					$scope.waiting=true;
					$http.get('php/ajax.php?get=tracks&point1='+$scope.zones.point1.sn+'&point2='+$scope.zones.point2.sn)
					.then(
						function(response){
							//console.log(response);
							if (angular.isObject(response.data.tracks)) {
								//console.log(response.data);
								$scope.showTracks(response.data);
							}else{
								console.log(response);
								$scope.journeyTracks={};
								$scope.journeyTracks.tracksfound=0;
								$scope.journeyTracks.reachable=0;
								$scope.journeyTracks.tracks=[];
							}
							$scope.waiting=false;
						},
						function(response){
							console.log(response);
							alert('fail');
							$scope.waiting=false;
						}
					);
				}

				$scope.details=function(track){
					console.log(track.places);
					$scope.trackshow=track.places;//JSON.parse(track.places);
					$scope.showable=true;
				}

				$scope.closeDetails=function(){
					$scope.showable=true;
					$scope.trackshow=[];
				}
			}
		]
	);


</script>
</body>
</html>