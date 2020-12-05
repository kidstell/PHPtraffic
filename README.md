# PHPtraffic
# certain functions requires a script to run for up to 7 or 10 minutes
# using up massive system resources alongside.  Please be warned. 

##########

# contents within this repository can not be used for commercial purpose without prior permission from its original author (kidstell@gmail.com).

##########to my honourable TEAM MATES... Details below #####
Install creates 1600 bus stops in the database
The database preferences can be modified in the config.php file

The config file contains constants used by the install.php and also the DB.php.
Config file also contains php functions like getGeoDistance, rndLat, rndLng, rndPos.

DB.php is a mini database wrapper to utilize specific database operations

trace.php uses two variables which can be supplied as any of POST or GET parameters. 
When any of the 2 variables are missing trace.php will automatically generate a valid random value for the missing parameter(s).

gpath.php run a specific amount of steps from a given start point. The start point is picked randomly each time the script runs or page is refreshed. The value for number of steps or start point can be alteted in the script by hard coding into the script.

traceFn.php rewrites ttrace.php utilizing functions in order to increase usability.

Thanks for your time. Written with all the love in the world.
