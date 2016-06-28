<?php
set_time_limit(0);
include_once 'php/config.php';
include_once 'php/DB.php';
?>
<form>
	<div class="container" style="position: relative;">


		<div class="row">

			<div class="col-xs-12 col-sm-9 col-md-5 col-lg-4 well">

				<div ng-show="waiting" class="col-xs-12 bg-danger" style="color:black; box-shadow:10px 10px 10px 5px #999">Loading...</div>

				<div class="col-xs-6">
					<select id="zone1" ng-model="zones.zonesn1" ng-change="setZone(1)" class="form-control input-sm">
						<?php
							for ($i=1; $i < 17; $i++) { 
								echo "<option value='{$i}'> Zone {$i}</option>";
							}
						?>
					</select>
				</div>

				<div class="col-xs-6">
					<select id="zone2" ng-model="zones.zonesn2" ng-change="setZone(2)" class="form-control input-sm">
						<?php
							for ($i=1; $i < 17; $i++) { 
								echo "<option value='{$i}'> Zone {$i}</option>";
							}
						?>
					</select>
				</div>

				<div class="clearfix"></div>

				<div class="col-xs-6">
					<select id="point1" class="form-control input-sm" ng-model="zones.point1.sn" ng-change="setPoint(1)">
						<option value="0"> Select a bus stop</option>
						<option ng-repeat="point in zones.points[1]" value="{{point.sn}}">{{point.point_name}}</option>
					</select>
				</div>

				<div class="col-xs-6">
					<select id="point2" class="form-control input-sm" ng-model="zones.point2.sn" ng-change="setPoint(2)">
						<option value="0"> Select a bus stop</option>
						<option ng-repeat="point in zones.points[2]" value="{{point.sn}}">{{point.point_name}}</option>
					</select>
				</div>

				<div class="clearfix"></div>

				<div id="journey-details" class="col-xs-12">
					<div>Journey from <h2>{{zones.point1.name}} <small>to</small> {{zones.point2.name}}</h2><span ng-show="tracked"> is <b ng-class="{'text-danger': journeyTracks.reachable==0, 'text-primary': journeyTracks.reachable!=0}"><span ng-hide="journeyTracks.reachable">UN-</span>Reachable</b></span></div>
					<div ng-show="tracked">{{journeyTracks.tracksfound + 0 | number}} Tracks found</div>
					<div id='tracklist' ng-show="tracked">
						<div class="well" ng-repeat="track in journeyTracks.tracks | orderBy:['total_time','total_dist','probate']">
							<h4>{{track.total_dist | number:3}} km in {{track.total_time | number:3}} minutes</h4>
							<a href='#/' ng-click="details(track)">view details</a>
						</div>
					</div>
				</div>

				<div class="clearfix"></div>

				<div id="journey-details" class="col-xs-12">
					<button class="btn btn-block btn-danger" ng-click="trace()">RUN TRACK</button>
				</div>

				<div class="clearfix"></div>

			</div>

		</div>
	</div>
</form>


			<div ng-show='showable' class="well" style="position: absolute; top:30%; left:30%; z-index:1; background:#775678; background-attachment:fixed; color: #dd5678">
				<h4>Journey Details </h4><button ng-click="closeDetails()" type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<div id="trckDet">
					<div ng-repeat="p in trackshow">{{p}}</div>
				</div>
			</div>