<?php

namespace Pitemplog\Conf;

class Autoloader {
	public static function register() {
		spl_autoload_register( function ($class) {
			$prefix = 'Pitemplog\\Conf\\';
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
			$libfile = $dir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . basename( $file );
			if (file_exists( $libfile )) {
				require $libfile;
				return true;
			}
			return false;
		} );
	}
}
Autoloader::register();
header( 'content-type: application/json; charset=utf-8' );
/*
 * variable definitions and helper functions
 */
/**
 *
 * @var ResponseClass $response: global variable that
 */
$response = new ResponseClass();
/**
 *
 * @var array $commands: global variable that stores the command queue of commands to be executed. We need this, because external programs that connect to the mysql server will interrupt our database connection that we maintain within php.
 */
$commands = [ ];

/*
 * get functions that fetch information for GET requests
 */

/**
 * pull data from external server
 *
 * @param unknown $config
 * @param unknown $command
 * @return mixed
 */
function getExternal($response, $config, $command) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	if (array_key_exists( 'parser', $config ) && ! empty( $config['parser'] ) && $config['parser'] != 'none') {
		$tempurl = 'http://' . $_SERVER['SERVER_NAME'] . '/assets/parser/' . $config['parser'] . '?url=' . (urlencode( $config['url'] )) . '&user=' . (urlencode( $config['username'] )) . '&pw=' . (urlencode( $config['pw'] ));
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array (
				'postvar1' => 'value1'
		) ) );
	} else {
		$tempurl = explode( '?', $config['url'] );
		$tempurl = $tempurl[0];
	}
	if ($command === 'config') {
		$tempurl .= '?config=get';
	} else {
		$tempurl .= '?gettemp=' . urlencode( $config['sensor'] );
	}
	curl_setopt( $ch, CURLOPT_URL, $tempurl );
	$result = json_decode( curl_exec( $ch ), true );
	$response->logger( sprintf( 'Fetched %s from: %s', $command, $tempurl ), $result, 3 );
	curl_close( $ch );
	if (is_array( $result )) {
		$response->logger( 'Got external config from: ' . $tempurl, $result, 3 );
		$ext_conf = array_combine( array_map( function ($k) {
			return 'ext' . $k;
		}, array_keys( $config ) ), $config );
		$response->logger( 'Processed url config:', $ext_conf, 3 );
		foreach ( $result as $sensor => $conf ) {
			$result[$sensor] = array_merge( ( array ) $conf, $ext_conf );
			$result[$sensor]['exttable'] = $result[$sensor]['table'];
		}
		$response->logger( 'Merged external sensor config:', $result, 3 );
	} else {
		$response->logger( sprintf( 'Got temperature %f from %s', $result, $tempurl ), FALSE );
	}
	return $result;
}

/**
 * get the temperature for the sensor with id $sensorID
 *
 * @param string $sensorID
 * @return string
 */
function get_sensor_temperature($response, $data) {
	$temperature = 'Error';
	$data['sensor'] = $data['temperature'];
	if (! $data['url']) {
		$data['url'] = 'http://localhost/data.php';
	}
	$sensordata = getExternal( $response, $data, 'temperature' );
	if ($sensordata) {
		$temperature = $sensordata . ' ˚C';
	} else {
		$response->logger( 'Error: reading external temperature failed for sensor: ' . $data['sensor'], $data, 0 );
	}
	return $temperature;
}

/**
 * saves all configuration.
 *
 * @param array $conf
 * @param object $data
 */
function save_everything($response, $conf, $data) {
	$response->db_config = $conf->database;
	foreach ( $data as $prop => $config ) {
		if (substr_compare( $prop, '_sensors', - 8 ) === 0) {
			foreach ( $config as $sensor ) {
				$conf->save_sensor_config( $sensor, FALSE );
			}
		}
	}
	$conf->write_config( TRUE );
	$conf->run_commands();
	$response->logger( 'Saved entire configuration:', $conf );
}

/*
 * Handle GET requests
 */
if ($_GET) {
	if (isset( $_GET['db_config'] ) || isset( $_GET['local_sensors'] ) || isset( $_GET['remote_sensors'] )) {
		$conf = new ConfigClass( $response );
	}
	if (isset( $_GET['db_config'] )) {
		$response->db_config = $conf->database;
	}
	if (isset( $_GET['local_sensors'] )) {
		$response->local_sensors = $conf->local_sensors;
	}
	if (isset( $_GET['remote_sensors'] )) {
		$response->remote_sensors = $conf->remote_sensors;
	}
	if (isset( $_GET['temperature'] )) {
		$response->temperature = get_sensor_temperature( $response, $_GET );
	}
}

/*
 * Handle input from POST requests
 */
if (isset( $_GET['action'] )) {
	$conf = new ConfigClass( $response );
	switch ($_GET['action']) {
		case 'save_sensor' :
			$conf->save_sensor_config( $_POST );
			break;
		case 'disable_sensor' :
			$conf->toggle_sensor( $_POST['sensor'], FALSE );
			break;
		case 'enable_sensor' :
			$conf->toggle_sensor( $_POST['sensor'], TRUE );
			break;
		case 'delete_sensor' :
			if ($conf->delete_sensor( $_POST['sensor'] )) {
				$response->sensor = $_POST['sensor'];
			}
		case 'create_pages' :
			$conf->create_pages( $response );
			break;
		case 'save_everything' :
			save_everything( $response, $conf, json_decode( $_POST['conf'], TRUE ) );
			break;
		case 'get_external' :
			$response->external_config = getExternal( $response, $_POST, 'config' );
			break;
		default :
			abortWithError( 'Error: unknown action:', $_GET['action'] );
	}
}
$response->finish();
?>
