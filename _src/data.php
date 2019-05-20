<?php
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
 * @todo get database configuration from config file
 */
$aggregate = "_5min";
$write_data = FALSE;
$add_table = FALSE;
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
		$sensorpath = $sensordir . urldecode($_GET["gettemp"]) . "/w1_slave";
		if (file_exists( $sensorpath )) {
			$sensordata = (substr( trim( file_get_contents( $sensorpath ) ), - 5 )) / 1000;
			echo $sensordata;
			exit();
		} else { // generate an empty config
			echo ("Error: Sensor not found: " . $sensorpath);
			exit();
		}
	}
}
if ($_POST) {
	// print_r($_POST);
	if (isset( $_POST["temp"] )) {
		$temperatures = explode( ',', $_POST["temp"] );
		$write_data = TRUE;
	}
	if (isset( $_POST["time"] )) {
		$times = explode( ',', $_POST["time"] );
	} else {
		$times = array (
				time()
		);
	}
	if (isset( $_POST["db"] )) {
		$db = $_POST["db"];
	}
	if (isset( $_POST["table"] )) {
		$table = $_POST["table"];
	}
	if (isset( $_POST["user"] )) {
		$user = $_POST["user"];
	}
	if (isset( $_POST["pw"] )) {
		$pw = $_POST["pw"];
	}
}
if ($endtime == 0) {
	$endtime = time();
}
if ($starttime == 0) {
	$starttime = $endtime - (1 * 24 * 60 * 60); // by default we get one weeks worth of data
}
$table = $table . $aggregate;

try {
	$dbh = new PDO( 'mysql:host=' . $host . ';dbname=' . $db, $user, $pw, array (
			PDO::ATTR_PERSISTENT => true
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
	if ($write_data) {
		if (count( $temperatures ) == count( $times )) {
			for($n = 0; $n < count( $temperatures ); $n ++) {
				$values .= '(' . $times[$n] . ',' . $temperatures[$n] . '),';
			}
			$values = rtrim( $values, ", " );
		} else {
			print "Error, time and temperature should be comma separated lists with the same number of elements.<br/>";
			$dbh = null;
			die();
		}
		$dbh->exec( 'INSERT INTO ' . $table . ' VALUES ' . $values );
	}
	// Close the connection
	$dbh = null;
} catch ( PDOException $e ) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
}
?>