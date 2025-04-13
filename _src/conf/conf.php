<?php

namespace Pitemplog\Conf;

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
function get_external($response, $config, $command) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	if (array_key_exists( 'parser', $config ) && ! empty( $config['parser'] ) && $config['parser'] != 'none') {
		$tempurl = 'http://' . $_SERVER['SERVER_NAME'] . '/assets/parser/' . $config['parser'];
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array (
				'url' => $config['url'],
				'user' => $config['username'],
				'pw' => $config['pw']
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
		$result = ConfigClass::local_2_remote( $result, $config );
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
	if (! isset( $data['url'] )) {
		$data['url'] = 'http://localhost/data.php';
	}
	$sensordata = get_external( $response, $data, 'temperature' );
	if ($sensordata) {
		$temperature = $sensordata . ' ˚C';
	} else {
		$response->logger( 'Error: reading temperature failed for sensor: ' . $data['sensor'], $data, 0 );
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
	if ($data['push_servers']) {
		foreach ( $data['push_servers'] as $server ) {
			if (isset( $conf->push_servers[$server["url"]] )) {
				$conf->save_push_server( $server );
			} else {
				$conf->push_config( $server );
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
	if (isset( $_GET['db_config'] ) || isset( $_GET['local_sensors'] ) || isset( $_GET['remote_sensors'] ) || isset( $_GET['push_servers'] )) {
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
	if (isset( $_GET['push_servers'] )) {
		$response->push_servers = $conf->push_servers;
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
			break;
		case 'create_pages' :
			$conf->create_pages( $response );
			break;
		case 'save_everything' :
			save_everything( $response, $conf, json_decode( $_POST['conf'], TRUE ) );
			break;
		case 'get_external' :
			$response->external_config = get_external( $response, $_POST, 'config' );
			break;
		case 'push_config' :
			$conf->push_config( $_POST );
			break;
		case 'save_push_server' :
			if (isset( $conf->push_servers[$_POST["url"]] )) {
				$conf->save_push_server( $_POST );
			} else {
				$conf->push_config( $_POST );
			}
			break;
		case 'delete_push_server' :
			if ($conf->delete_push_server( $_POST['url'] )) {
				$response->push_servers = [$_POST['url']];
			}
			break;
		case 'receive_push_config' :
			$conf->receive_push_config( $_POST );
			break;
		case 'add_new_push_sensor' :
			$conf->add_new_push_sensor( $_POST );
			break;
		default :
			$response->abort( 'Error: unknown action:', $_GET['action'] );
	/**
	 *
	 * @todo: disable and delete push servers
	 * @todo: directly configure table that receives push data (instead of receiving the pushed configuration)
	 */
	}
}
$response->finish();
?>
