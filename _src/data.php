<?php

namespace Pitemplog;

header( 'content-type: application/json; charset=utf-8' );
header( "Access-Control-Allow-Origin: *" );
$sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
$starttime = 0;
$endtime = 0;
$host = $_ENV['DB_HOST'] ?: 'localhost';
$db = $_ENV['DB_DB'] ?: "temperatures";
$table = "";
$user = $_ENV['DB_USER'] ?: "temp";
$pw = $_ENV['DB_PW'] ?: "temp";
$aggregate = "_5min";
if ($_GET) {
	// print_r($_GET);
	if (isset( $_GET["end"] )) {
		$endtime = intval( $_GET["end"] );
	}
	if (isset( $_GET["start"] )) {
		$starttime = intval( $_GET["start"] );
	}
	if (isset( $_GET["table"] )) {
		$table = $_GET["table"];
	}
	if (isset( $_GET["aggregate"] )) {
		switch ($_GET["aggregate"]) {
			case "_5min" :
				$aggregate = "_5min";
				break;
			case "_15min" :
				$aggregate = "_15min";
				break;
			case "_60min" :
				$aggregate = "_60min";
				break;
			case "_1min" :
				$aggregate = "";
				break;
		}
	}
	if (isset( $_GET["config"] ) && $_GET["config"] == "get") {
		$config_file = "conf/config.json";
		if (file_exists( $config_file )) {
			$conf = json_decode( file_get_contents( $config_file ), true );
			echo json_encode( $conf['local_sensors'] );
			exit();
		}
	}
	if (isset( $_GET["gettemp"] )) {
		// record the current temperature
		$sensorpath = $sensordir . urldecode( $_GET["gettemp"] ) . "/w1_slave";
		if (file_exists( $sensorpath )) {
			$sensordata = (substr( trim( file_get_contents( $sensorpath ) ), - 5 )) / 1000;
			echo $sensordata;
			exit();
		} else { // generate an empty config
			echo ("Error: Sensor not found: " . $sensorpath);
			exit();
		}
	}
	if (isset( $_GET["action"] )) {
		spl_autoload_register( function ($class) {
			$prefix = 'Pitemplog\\';
			$len = strlen( $prefix );
			if (strncmp( $prefix, $class, $len ) !== 0) {
				return false;
			}
			$relative_class = substr( $class, $len );

			$file = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';
			if (file_exists( $file )) {
				require $file;
				return true;
			}
			$dir = dirname( $file );
			$file = strtolower( $dir ) . DIRECTORY_SEPARATOR . basename( $file );
			if (file_exists( $file )) {
				require $file;
				return true;
			}
			$libfile = strtolower( $dir ) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . basename( $file );
			if (file_exists( $libfile )) {
				require $libfile;
				return true;
			}
			return false;
		} );
		$response = new Conf\ResponseClass();
		$conf = new Conf\ConfigClass( $response, 'conf/config.json' );
		switch ($_GET['action']) {
			case 'receive_push_config' :
				$conf->receive_push_config( $_POST );
				break;
			case 'receive_push_temperatures' :
				$conf->save_pushed_data( json_decode( $_POST['data'], TRUE ) );
				break;
		}
		$response->finish();
	}
}
if ($endtime == 0) {
	$endtime = time();
}
if ($starttime == 0) {
	$starttime = $endtime - (1 * 24 * 60 * 60); // by default we get one days worth of data
}
$table = $table . $aggregate;

try {
	$dbh = new \PDO( 'mysql:host=' . $host . ';dbname=' . $db, $user, $pw, array (
			\PDO::ATTR_PERSISTENT => true
	) );

	if (isset( $_GET["table"] )) {
		$result = [ ];
		// Fetch the data
		if (isset( $_GET["latest"] )) {
			// get the latest temperature
			foreach ( $dbh->query( 'SELECT FROM_UNIXTIME(time),temp FROM ' . $table . ' ORDER BY time DESC LIMIT 1' ) as $row ) {
				$result[] = [ 
						'time' => $row['FROM_UNIXTIME(time)'],
						'temp' => $row['temp']
				];
			}
		} else {
			// get the temperatures between start and end time
			foreach ( $dbh->query( 'SELECT FROM_UNIXTIME(time),temp FROM ' . $table . ' WHERE time BETWEEN ' . $starttime . ' AND ' . $endtime . ' ORDER BY time ASC' ) as $row ) {
				$result[] = [ 
						'time' => $row['FROM_UNIXTIME(time)'],
						'temp' => $row['temp']
				];
			}
		}
		// print the result as JSON
		echo json_encode( $result );
	}
	// Close the connection
	$dbh = null;
} catch ( \PDOException $e ) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
}
?>