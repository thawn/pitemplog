<?php

namespace Pitemplog;

header( 'content-type: application/json; charset=utf-8' );
header( "Access-Control-Allow-Origin: *" );
$sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
$starttime = 0;
$endtime = 0;
$host = $_ENV['DB_HOST'] ?: 'localhost';
$db = $_ENV['DB_DB'] ?: "temperatures";
$table = "temperatures";
$user = $_ENV['DB_USER'] ?: "temp";
$pw = $_ENV['DB_PW'] ?: "temp";
/**
 *
 * @todo get database configuration from config file
 */
$aggregate = "_5min";
if ($_GET) {
	// print_r($_GET);
	if (isset( $_GET["end"] )) {
		$endtime = intval( $_GET["end"] );
	}
	if (isset( $_GET["start"] )) {
		$starttime = intval( $_GET["start"] );
	}
	if (isset( $_GET["db"] )) {
		$db = $_GET["db"];
	}
	if (isset( $_GET["table"] )) {
		$table = $_GET["table"];
	}
	if (isset( $_GET["user"] )) {
		$user = $_GET["user"];
	}
	if (isset( $_GET["pw"] )) {
		$pw = $_GET["pw"];
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
		/**
		 *
		 * @todo return config only for internal sensors
		 */
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
		class Autoloader {
			public static function register() {
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
			}
		}
		Autoloader::register();
		$response = new Conf\ResponseClass();
		$conf = new Conf\ConfigClass( $response, 'conf/config.json' );
		switch ($_GET['action']) {
			case 'receive_push_config' :
				$conf->receive_push_config( $_POST );
				break;
			case 'receive_push_temperatures' :
				$conf->save_pushed_data( json_decode( $_POST['data'], TRUE) );
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

	if ($_GET) {
		$prefix = '';
		echo "[\n";
		// Fetch the data and print it out as JSON
		foreach ( $dbh->query( 'SELECT FROM_UNIXTIME(time),temp FROM ' . $table . ' WHERE time BETWEEN ' . $starttime . ' AND ' . $endtime . ' ORDER BY time ASC' ) as $row ) {
			echo $prefix . " {\n";
			// print_r($row);
			echo '  "time": "' . $row['FROM_UNIXTIME(time)'] . '",' . "\n";
			echo '  "temp": ' . $row['temp'] . ',' . "\n";
			echo " }";
			$prefix = ",\n";
		}
		echo "\n]";
	}
	// Close the connection
	$dbh = null;
} catch ( \PDOException $e ) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
}
?>