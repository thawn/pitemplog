<?php
header( 'content-type: application/json; charset=utf-8' );
/*
 * variable definitions and helper functions
 */
/**
 *
 * @var \stdClass $response: global variable that stores the response which will be returned as json string
 */
$response = new \stdClass();
$response->status = 'error';
/**
 *
 * @var string $sensordir: global variable storing the path to the sensors.
 */
$sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
// $sensordir = '/tmp/devices/';
/**
 *
 * @var array $commands: global variable that stores the command queue of commands to be executed. We need this, because external programs that connect to the mysql server will interrupt our database connection that we maintain within php.
 */
$commands = [ ];

/**
 * the debugging level determines which kind of messages end up in the debugging window.
 * 0 = errors only; 1 = errors and messages; 2 = errors, messages and verbose messages;
 * 3 = everything (including debugging info)
 *
 * @var integer $debug
 */
$debug = 0;
$response->log = '';
if (isset( $_GET['debug'] ) || isset( $_POST['debug'] )) {
	if (isset( $_GET['debug'] )) {
		$debug = intval( $_GET['debug'] );
	} else {
		$debug = intval( $_POST['debug'] );
	}
}

/**
 * stores debug messages in the response objects
 *
 * @param string $heading
 * @param mixed $content
 * @param number $level
 */
function logger($heading, $content, $level = 1) {
	global $debug, $response;
	if ($debug >= $level) {
		$class = '';
		switch ($level) {
			case 0 :
				$class = 'text-danger';
				break;
			case 1 :
				$class = 'text-success';
				break;
			case 2 :
				$class = 'text-info';
				break;
			case 3 :
				$class = 'text-warning';
				break;
		}
		$response->log .= '<h4 class="' . $class . '">' . $heading . '</h4>';
		if ($content) {
			$response->log .= '<pre>';
			$response->log .= print_r( $content, TRUE );
			$response->log .= '</pre>';
		}
	}
}

/**
 * Abort execution and return an error.
 *
 * @param string $message
 * @param mixed $content
 */
function abortWithError($message, $content) {
	global $response;
	logger( 'ABORTED: ' . $message, $content, 0 );
	echo json_encode( $response );
	die();
}

/**
 * Save changes to config file.
 *
 * @param array $conf
 * @param string $config_file
 */
function writeConfig($conf, $config_file = 'config.json') {
	global $response;
	logger( 'Attempting to write configuration to: ' . $config_file, $conf, 3 );
	if (is_writable( $config_file )) {
		file_put_contents( $config_file, json_encode( $conf, JSON_UNESCAPED_SLASHES ), LOCK_EX );
		$local_conf = escapeshellarg( $config_file );
		$build_conf = escapeshellarg( '/usr/local/share/templog/_data/config.json' );
		// $build_conf = escapeshellarg('/Users/korten/Apps/temperatures/build/_data/config.json');
		$diff = escapeshellcmd( '/usr/bin/diff -q ' . $build_conf . ' ' . $local_conf );
		$diff_output = shell_exec( $diff );
		if (! empty( $diff_output )) {
			logger( 'Configuration saved successfully.', false, 1 );
			$response->config_changed = TRUE;
		}
	} else {
		abortWithError( 'ERROR: Permission denied. Cannot write to config file:', (getcwd()) . '/' . $config_file );
	}
}

/**
 * execute commands that create the html pages and update the website.
 */
function createPages() {
	logger( 'Attempting to create pages:', FALSE, 3 );
	$create_pages = escapeshellcmd( '/usr/local/share/templog/_data/create_pages.py' );
	$create_pages .= ' 2>&1';
	$create_output = shell_exec( $create_pages );
	logger( 'Created pages:', $create_output, 1 );
	$jekyll = escapeshellcmd( 'jekyll build' );
	$cd = escapeshellcmd( 'cd /usr/local/share/templog/' );
	$jekyllcmd = $cd . '&&' . $jekyll . ' 2>&1';
	$jekyll_ouptut = shell_exec( $jekyllcmd );
	logger( 'Updated Site:', $jekyll_ouptut, 1 );
}

/**
 * Load configuration from file.
 *
 * @param string $config_file
 * @return array
 */
function loadConfig($config_file = 'config.json') {
	$dbConfig = array (
			'host' => $_ENV['DB_HOST'] ?: 'localhost',
			'db' => $_ENV['DB_DB'] ?: 'temperatures',
			'user' => $_ENV['DB_USER'] ?: 'temp',
			'pw' => $_ENV['DB_PW'] ?: 'temp',
			'aggregateTables' => [ 
					'_5min',
					'_15min',
					'_60min'
			],
			'dbtest' => ''
	);
	$conf = [ ];
	if (file_exists( $config_file )) {
		$conf = json_decode( file_get_contents( $config_file ), true );
		logger( 'Raw configuration from the config file', $conf, 3 );
	}
	if (isset( $conf['database'] )) {
		if (! isset( $conf['database']['dbtest'] ) || empty( $conf['database']['dbtest'] )) {
			$conf = testDatabaseConnection( $conf );
		}
	} else {
		if (isset( $_ENV['DB_HOST'] ) && isset( $_ENV['DB_DB'] ) && isset( $_ENV['DB_USER'] ) && isset( $_ENV['DB_PW'] )) {
			logger( '<h4>Got database configuration from $_ENV:', $dbConfig, 3 );
		}
		$conf['database'] = $dbConfig;
		$conf = testDatabaseConnection( $conf );
		writeConfig( $conf );
	}
	return $conf;
}

/**
 * Test whether the database connection works.
 *
 * @param array $conf
 * @return array
 */
function testDatabaseConnection($conf) {
	$dbh = openDatabaseConnection( $conf );
	$conf['database']['dbtest'] = 'OK';
	$dbh = null;
	return $conf;
}

/**
 * Open a connection to the database.
 *
 * @param array $conf
 * @return PDO
 */
function openDatabaseConnection($conf) {
	global $response;
	try {
		$dbh = new PDO( 'mysql:host=' . $conf['database']['host'] . ';dbname=' . $conf['database']['db'], $conf['database']['user'], $conf['database']['pw'], array (
				PDO::ATTR_PERSISTENT => true
		) );
		return $dbh;
	} catch ( PDOException $e ) {
		switch ($e->getCode()) {
			case '2002' :
				$response->dbErrors['host'] = sprintf( 'Could not connect to database server %s.', $conf['database']['host'] );
				break;
			case '1044' :
				$response->dbErrors['db'] = sprintf( 'Database %s not found.', $conf['database']['db'] );
				break;
			case '1698' :
				$response->dbErrors['user'] = sprintf( 'User %s doe not have access to the database.', $conf['database']['user'] );
				break;
			case '1045' :
				$response->dbErrors['pw'] = 'Wrong password.';
				break;
		}
		abortWithError( 'Could not connect to database. Got error: ' . $e->getMessage(), $conf['database'] );
	}
}

/*
 * get functions that fetch information for GET requests
 */

/**
 * Load/create configuration for all sensors.
 *
 * @param array $conf
 * @return array
 */
function getSensors($conf) {
	global $sensordir;
	$model = array (
			'sensor' => '',
			'name' => '',
			'table' => '',
			'category' => 'DIEZLAB',
			'enabled' => 'true',
			'comment' => '',
			'tabletest' => '',
			'calibration' => '0'
	);
	$sensor_config = $conf;
	unset( $sensor_config['database'] );
	// look for available temperature sensors
	$sensors = [ ];
	if (file_exists( $sensordir )) {
		$dirs = scandir( $sensordir );
		logger( 'Sensor directories in' . $sensordir . ':', $dirs, 3 );
		foreach ( $dirs as $sensor ) {
			if ($sensor != '.' && $sensor != '..' && $sensor != 'w1_bus_master1' && ! isset( $sensor_config[$sensor] )) {
				$sensor_config[$sensor] = $model;
				$sensor_config[$sensor]['sensor'] = $sensor;
			}
		}
	}
	logger( 'Sensor configuration:', $sensors, 3 );
	return $sensor_config;
}

/**
 * Search the plugin directory for parsers for external sources.
 * Return a list of parsers.
 *
 * @return array
 */
function getExternalParsers() {
	$parserdir = dirname( getcwd() ) . '/assets/parser/';
	$parsers = [ ];
	if (file_exists( $parserdir )) {
		$files = scandir( $parserdir );
		foreach ( $files as $key => $file ) {
			if (strpos( $file, '.php' ) === false) {
				unset( $files[$key] );
			}
		}
		$parsers = array_values( $files );
	}
	return $parsers;
}

/**
 * get the temperature for the sensor with id $sensorID
 *
 * @param string $sensorID
 * @return string
 */
function getSensorTemperature($data) {
	global $sensordir;
	$temperature = 'Error';
	$data = array_map( "urldecode", $data );
	$data['sensor'] = $data['temperature'];
	if ($data['url']) {
		$sensordata = getExternal( $data, 'temperature' );
		if ($sensordata) {
			$temperature = $sensordata . ' ˚C';
		} else {
			logger( 'Error: reading external temperature failed for sensor: ' . $data['sensor'], $data, 0 );
		}
	} else {
		$sensorpath = $sensordir . $data['sensor'] . '/w1_slave';
		if (file_exists( $sensorpath )) {
			$sensordata = (substr( trim( file_get_contents( $sensorpath ) ), - 5 )) / 1000;
			// $sensordata += $config['calibration'];
			$temperature = $sensordata . '˚C';
		} else { // clear that sensor from the configuration
			logger( 'Error: reading temperature failed for sensor: ' . $data['sensor'], $data, 0 );
		}
	}
	return $temperature;
}

/*
 * Handle GET requests
 */
if ($_GET) {
	if (isset( $_GET['db_config'] ) || isset( $_GET['sensor_config'] )) {
		$conf = loadConfig();
	}
	if (isset( $_GET['db_config'] )) {
		$response->db_config = $conf['database'];
	}
	if (isset( $_GET['sensor_config'] )) {
		$response->sensor_config = getSensors( $conf );
	}
	if (isset( $_GET['temperature'] )) {
		$response->temperature = getSensorTemperature( $_GET );
	}
}

/*
 * Functions that handle input from POST requests
 */

/**
 * Prepare form input.
 *
 * @param string $data
 * @return string
 */
function test_input($data) {
	$data = trim( $data );
	$data = stripslashes( $data );
	$data = htmlspecialchars( $data );
	return $data;
}

/**
 * Parse the user input for the database configuration.
 *
 * @param string $field
 * @param string $value
 * @return string[]|unknown[]|NULL[]
 */
function parseDatabaseInput($field, $value) {
	global $response;
	/**
	 *
	 * @todo switch to filter_var_array
	 */
	$parsed = '';
	switch ($field) {
		case 'host' :
			if (empty( $value )) {
				$parsed = 'localhost';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! filter_var( gethostbyname( $parsed ), FILTER_VALIDATE_IP )) {
					$response->dbErrors[$field] = 'The database host name must be a valid and reachable ip address or domain name.';
				}
			}
			break;
		case 'db' :
			if (empty( $value )) {
				logger( 'Database name is required. Input was invalid: ', $value, 0 );
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! preg_match( "/^[a-zA-Z_]{4,20}$/", $parsed )) {
					$response->dbErrors[$field] = 'Only letters and underscore allowed. The database name must be 4-20 characters long.';
				}
			}
			break;
		case 'user' :
			if (empty( $value )) {
				logger( 'Username for database is required. Input was invalid: ', $value, 0 );
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! preg_match( "/^[a-zA-Z]{4,20}$/", $parsed )) {
					$response->dbErrors[$field] = 'Only letters allowed. The user name must be 4-20 characters long.';
				}
			}
			break;
		case 'pw' :
			if (empty( $value )) {
				$parsed = '';
			} else {
				$parsed = test_input( $value );
			}
			break;
		case 'aggregateTables' :
			$parsed = [ ];
			if (! is_array( $value )) {
				$value = explode( '/', $value );
			}
			foreach ( $value as $suffix ) {
				$tested = test_input( $suffix );
				if (! preg_match( "/^[a-zA-Z0-9_]{1,10}$/", $tested )) {
					logger( 'Only letters, numbers and underscore allowed. The database aggregateTable suffix must be 1-10 characters long. Input was invalid: ', $suffix, 0 );
				} else {
					$parsed[] = $tested;
				}
			}
			break;
		case 'dbtest' :
			$parsed = $value;
			break;
		case 'dberrormsg' :
			$parsed = $value;
			break;
		default :
			abortWithError( 'Error: unknown input field for the database:', $field );
	}
	return $parsed;
}

/**
 * Parse the user input for the sensor configuration.
 *
 * @param string $sensor
 * @param string $field
 * @param string $value
 * @return string[]|unknown[]|NULL[]
 */
function parseSensorInput($sensor, $field, $value) {
	global $response;
	$parsed = [ ];
	switch ($field) {
		case 'sensor' :
			if ($value != $sensor && $value != substr( $sensor, 3 )) {
				abortWithError( 'Error: sensor prefix and value do not match. Something is wrong:', $sensor . '/' . $value );
			} else {
				$parsed = $value;
			}
			break;
		case 'calibration' :
			if (empty( $value )) {
				$parsed = '0';
			} else {
				$parsed = test_input( $value );
			}
			if (! is_numeric( $parsed )) {
				$response->sensorError[$sensor][$field] = 'The calibration must be numeric. The value you entered is not valid: ' . $value;
			}
			break;
		case 'name' :
			if (empty( $value )) {
				$response->sensorError[$sensor][$field] = 'Name is required';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters and whitespace
				if (! preg_match( "/^[a-zA-Z0-9_\- ]*$/", $parsed )) {
					$response->sensorError[$sensor][$field] = 'Only letters, numbers and space allowed';
				}
			}
			break;
		case 'table' :
			if (empty( $value )) {
				$response->sensorError[$sensor][$field] = 'Database table name is required.';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! preg_match( "/^[a-zA-Z][a-zA-Z0-9_]{3,19}$/", $parsed )) {
					$$response->sensorError[$sensor][$field] = 'Only letters, numbers and underscore allowed. The database table name must be 4-20 characters long.';
				} elseif (preg_match( "/^\d.*/", $parsed )) {
					$response->sensorError[$sensor][$field] = 'The table name may not start with a number.';
				}
			}
			break;
		case 'comment' :
			if (empty( $value )) {
				$parsed = '';
			} else {
				$parsed = test_input( $value );
			}
			break;
		case 'category' :
			if (empty( $value )) {
				$response->sensorError[$sensor][$field] = 'Category is required.';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters and whitespace
				if (! preg_match( "/^[a-zA-Z0-9_\- ]*$/", $parsed )) {
					$response->sensorError[$sensor][$field] = 'Only letters, numbers and space allowed';
				}
			}
			break;
		case 'enabled' :
			$testedVal = test_input( $value );
			if ($testedVal == 'false') {
				$parsed = 'false';
			} else {
				$parsed = 'true';
			}
			break;
		case 'exttable' :
			$parsed = test_input( $value );
			// check if name only contains letters
			if (empty( $parsed )) {
				unset( $parsed );
			} elseif (! preg_match( "/^[a-zA-Z0-9_]{4,20}$/", $parsed )) {
				$response->sensorError[$sensor][$field] = 'The external table name should only contain letters and numbers: ' . $parsed;
			} elseif (preg_match( "/^\d.*/", $parsed )) {
				$response->sensorError[$sensor][$field] = 'The external table name may not start with a number:' . $parsed;
			}
			break;
		case 'exturl' :
			if (empty( $value )) {
				$response->sensorError[$sensor][$field] = 'URL is required.';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! filter_var( $parsed, FILTER_VALIDATE_URL )) {
					$response->sensorError[$sensor][$field] = 'This is not a valid url: ' . $parsed;
				}
			}
			break;
		case 'extname' :
			if (empty( $value )) {
				$response->sensorError[$sensor][$field] = 'URL Name is required.';
			} else {
				$parsed = test_input( $value );
				// check if name only contains letters
				if (! preg_match( "/^[a-zA-Z0-9_\- ]*$/", $parsed )) {
					$response->sensorError[$sensor][$field] = 'Error: The URL Name may onl contain letters, numbers and spaces: ' . $parsed;
				}
			}
			break;
		case 'extuser' :
			$parsed = test_input( $value );
			break;
		case 'extpw' :
			$parsed = test_input( $value );
			break;
		case 'extparser' :
			$parsed = test_input( $value );
			$parsers = getExternalParsers();
			if (! empty( $parsed ) && $parsed != 'none' && array_search( $parsed, $parsers ) === false) {
				abortWithError( 'There is an error in your configuration: The file for the parser: ' . $parsed . ' does not exist. These are the parsers that I know:', $parsers );
			}
			break;
		case 'tabletest' :
			$parsed = $value;
			break;
		case 'confirmed' :
			$parsed = $value === 'true';
			break;
		case 'firsttime' :
			if (empty( $value )) {
				$parsed = '0';
			} else {
				$parsed = test_input( $value );
			}
			if (! is_numeric( $parsed )) {
				$response->sensorError[$sensor][$field] = 'There is an error in your config file: Firsttime must be numeric! Offending value for sensor: "' . $sensor . '" was: "' . $value . '".';
			}
			break;
		default :
			abortWithError( 'Error: unknown input field: "' . $field . '" for sensor: "' . $sensor . '!', '' );
	}
	return $parsed;
}

/**
 * saves the database configuration unless there are errors
 *
 * @param array $conf
 * @return array
 */
function saveDatabaseConfig($conf, $data) {
	global $response;
	logger( 'Attempting to save database configuration from POST data:', $data, 3 );
	$keys = array_keys( $data );
	foreach ( preg_grep( '/^database.*/', $keys ) as $name ) {
		$field = explode( '/', $name )[1];
		$conf['database'][$field] = parseDatabaseInput( $field, $data[$name] );
	}
	$conf['database']['dbtest'] = '';
	$conf = testDatabaseConnection( $conf );
	$response->db_config = $conf['database'];
	if (! $response->dbErrors && $conf['dbtest'] == 'OK') {
		writeConfig( $conf );
	}
	return $conf;
}

/**
 * Check if a table exists in the database
 *
 * @param PDO $dbh
 * @param string $table
 * @return boolean
 */
function tableExists($dbh, $table) {
	$result = FALSE;
	if (! empty( $table )) {
		try {
			$answer = $dbh->query( 'SELECT 1 from ' . $table );
			$result = $answer !== FALSE;
		} catch ( PDOException $e ) {
			logger( 'Caught error when looking for table: ' . $table, $e, 3 );
			$result = FALSE;
		}
		if ($result) {
			logger( 'Table: ' . $table . ' exists.', FALSE, 3 );
		} else {
			logger( 'Table: ' . $table . ' does not exist.', FALSE, 3 );
		}
	}
	return $result;
}

/**
 * Create the database tables for a sensor.
 *
 * @param string $sensor
 * @param PDO $dbh
 * @return string[]
 */
function makeDbTable($dbh, $conf, $sensor) {
	global $commands;
	logger( 'Attempting to create table for sensor: ', $conf[$sensor], 3 );
	$result = $dbh->query( 'DROP TABLE IF EXISTS `' . $conf[$sensor]['table'] . '`; CREATE TABLE `' . $conf[$sensor]['table'] . '` (time INT, temp FLOAT)' );
	logger( 'Created table for sensor: ' . $conf[$sensor]['table'], $result );
	if ($result) {
		$result = null;
		$conf[$sensor]['tabletest'] = 'OK';
		$partition_database = escapeshellcmd( '/usr/local/bin/partition_database.py' );
		$commands[] = $partition_database . ' "" ' . $conf[$sensor]['table'];
		resetAggregateTables( $dbh, $conf[$sensor]['table'], $conf['database']['aggregateTables'] );
	} else {
		$conf[$sensor]['tabletest'] = '';
	}
	return $conf;
}

/**
 * Create the aggregate tables.
 * Delete them if they are already present (and thus reset them).
 *
 * @param PDO $dbh
 * @param string $table
 * @param array $aggregate_extensions
 */
function resetAggregateTables($dbh, $table, $aggregate_extensions) {
	global $commands;
	$partition_database = escapeshellcmd( '/usr/local/bin/partition_database.py' );
	foreach ( $aggregate_extensions as $extension ) {
		$table_name = $table . $extension;
		$result = $dbh->query( 'DROP TABLE IF EXISTS `' . $table_name . '`; CREATE TABLE `' . $table_name . '` (time INT, temp FLOAT)' );
		logger( 'Created table: ' . $table_name, $result );
		$result = null;
		$commands[] = $partition_database . ' ' . $extension . ' ' . $table;
	}
}

/**
 * Aggregate temperature data.
 *
 * @param PDO $dbh
 * @param string $table
 * @param array $aggregate_extensions
 */
function aggregateDataNow($dbh, $table, $aggregate_extensions) {
	global $commands;
	$aggregate_cmd = escapeshellcmd( '/usr/local/bin/tempaggregate.py' );
	logger( 'Aggregating data for table: ' . $table, FALSE );
	foreach ( $aggregate_extensions as $extension ) {
		$commands[] = $aggregate_cmd . ' ' . $extension . ' ' . $table;
	}
}

/**
 * Merge two database tables.
 * Caution: the table $table_old will be deleted
 *
 * @param PDO $dbh
 * @param string $table:
 *        	The table into which the data will be merged
 * @param string $table_old:
 *        	The table from which the data will be merged.
 *        	This table will be deleted after the data was merged successfully!
 * @param array $aggregate_extensions:
 *        	The extensions of the aggregation tables, usually [ _5min, _15min, _60min ]
 */
function mergeDbTables($dbh, $table, $table_old, $aggregate_extensions) {
	logger( 'Merging table "' . $table . '" with "' . $table_old . '":', FALSE, 3 );
	$result = $dbh->query( 'INSERT INTO ' . $table . ' SELECT * FROM ' . $table_old );
	logger( sprintf( 'Moved data from old table "%s" to "%s"', $table_old, $table ), $result, 3 );
	$result = null;
	$result = $dbh->query( 'DROP TABLE IF EXISTS ' . $table_old );
	logger( 'Deleted old table: ' . $table_old, $result, 3 );
	$result = null;
	foreach ( $aggregate_extensions as $extension ) {
		$result = $dbh->query( 'DROP TABLE IF EXISTS ' . $table_old . $extension );
		logger( 'Deleted old table: ' . $table_old . $extension, $result, 3 );
		$result = null;
	}
}
/**
 * Check if a table is used by another sensor.
 *
 * @param string $table:
 *        	The name of the table to be checked.
 * @param string $target_sensor:
 *        	The name of the sensor that should use this table.
 * @param array $conf:
 *        	The configuration array.
 * @return boolean
 */
function isTableUsed($table, $target_sensor, $conf) {
	$used = FALSE;
	logger( sprintf( 'Checking whether table %s is already used by a sensor other than %s.', $table, $target_sensor ), $conf, 3 );
	foreach ( $conf as $sensor => $config ) {
		if ($sensor !== 'database' && strval( $sensor ) !== $target_sensor && $table === $config['table']) {
			$used = TRUE;
			logger( sprintf( 'The table %s is already used by sensor: %s. We cannot use it for sensor: %s', $table, $sensor, $target_sensor ), FALSE, 0 );
		}
	}
	return $used;
}
/**
 * Run commands that were stored in the command queue.
 */
function runCommands() {
	global $commands;
	foreach ( $commands as $command ) {
		$output = shell_exec( $command );
		logger( 'Executed command: ' . $command, $output );
	}
}

/**
 * saves the sensor configuration unless there are errors.
 *
 * @param array $conf
 * @param array $data
 * @return array
 */
function saveSensorConfig($conf, $data, $toggle_enabled = FALSE) {
	global $response, $commands;
	logger( 'Saving sensor config data:', $data, 3 );
	$sensor = $data['sensor'];
	if (! $sensor) {
		abortWithError( 'Could not identify the sensor to save:', $data );
	}
	$keys = array_keys( $data );
	foreach ( preg_grep( '/^database.*$/', $keys, PREG_GREP_INVERT ) as $field ) {
		if ($field != 'table_old') {
			$conf[$sensor][$field] = parseSensorInput( $sensor, $field, $data[$field] );
		}
	}
	if (isTableUsed( $conf[$sensor]['table'], $sensor, $conf )) {
		$response->sensorError[$sensor]['table'] = 'This table is already used by another sensor, please choose a different table name.';
		$response->sensor_config[$sensor] = $conf[$sensor];
		return;
	}
	$dbh = openDatabaseConnection( $conf );
	if (tableExists( $dbh, $conf[$sensor]['table'] )) {
		if ($conf[$sensor]['tabletest'] === 'OK' && $conf[$sensor]['table'] !== $data['table_old'] && tableExists( $dbh, $data['table_old'] )) {
			if ($conf[$sensor]['confirmed']) {
				mergeDbTables( $dbh, $conf[$sensor]['table'], $data['table_old'], $conf['database']['aggregateTables'] );
				resetAggregateTables( $dbh, $conf[$sensor]['table'], $conf['database']['aggregateTables'] );
				aggregateDataNow( $dbh, $conf[$sensor]['table'], $conf['database']['aggregateTables'] );
				$conf[$sensor]['tabletest'] = 'OK';
				unset( $conf[$sensor]['confirmed'] );
			} else {
				$response->confirm = [ 
						'message' => 'It appears you are trying to rename the table "' . $data['table_old'] . '" to an existing table "' . $conf[$sensor]['table'] . '". Do you want to merge the contents of "' . $conf[$sensor]['table'] . '" with "' . $data['table_old'] . '"? If you say no, the sensor will simply save its data to table "' . $conf[$sensor]['table'] . '" from now on and leave "' . $data['table_old'] . '" in the database as it is.',
						'data' => $conf[$sensor],
						'action' => $_GET['action']
				];
				$response->confirm['data']['table_old'] = $data['table_old'];
			}
		} elseif ($conf[$sensor]['table'] !== $data['table_old'] && ! $toggle_enabled) {
			$response->alert = [ 
					'message' => 'The table "' . $conf[$sensor]['table'] . '" already exists and may contain data. The sensor will encorporate that data into its history. If you don\'t want that, choose a different table name.'
			];
			$conf[$sensor]['tabletest'] = 'OK';
		} else {
			$conf[$sensor]['tabletest'] = 'OK';
		}
	} else {
		$conf = makeDbTable( $dbh, $conf, $sensor );
		if ($data['table_old'] && tableExists( $dbh, $data['table_old'] )) {
			mergeDbTables( $dbh, $conf[$sensor]['table'], $data['table_old'], $conf['database']['aggregateTables'] );
			aggregateDataNow( $dbh, $conf[$sensor]['table'], $conf['database']['aggregateTables'] );
		}
	}
	$dbh = null;
	logger( 'New sensor config is:', $conf[$sensor], 3 );
	$response->sensor_config[$sensor] = $conf[$sensor];
	if (! $response->sensorError[$sensor] && $conf[$sensor]['tabletest'] == 'OK') {
		unset( $conf[$sensor]['confirmed'] );
		writeConfig( $conf );
		runCommands();
	}
	return $conf;
}

/**
 * saves all configuration.
 *
 * @param array $conf
 * @param object $data
 */
function saveEverything($conf, $data) {
	foreach ( $data as $sensor => $config ) {
		if ($sensor === 'database') {
			$conf = saveDatabaseConfig( $conf, $config );
		} else {
			$conf = saveSensorConfig( $conf, $config );
		}
	}
}

/*
 * pull data from external server
 */
function getExternal($config, $command) {
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
	logger( sprintf( 'Fetched %s from: %s', $command, $tempurl ), $result, 3 );
	curl_close( $ch );
	if (is_array( $result )) {
		logger( 'Got external config from: ' . $tempurl, $result, 3 );
		$ext_conf = array_combine( array_map( function ($k) {
			return 'ext' . $k;
		}, array_keys( $config ) ), $config );
		logger( 'Processed url config:', $ext_conf, 3 );
		if ($result['database']) {
			unset( $result['database'] );
		}
		foreach ( $result as $sensor => $conf ) {
			$result[$sensor] = array_merge( $conf, $ext_conf );
			$result[$sensor]['exttable'] = $result[$sensor]['table'];
		}
		logger( 'Merged external sensor config:', $result, 3 );
	} else {
		logger( sprintf( 'Got external temperature %f from %s', $result, $tempurl ), FALSE );
	}
	return $result;
}

/**
 *
 * @todo configure push to external server
 */

if (isset( $_GET['action'] )) {
	$conf = loadConfig();
	switch ($_GET['action']) {
		case 'save_db' :
			saveDatabaseConfig( $conf, $_POST );
			break;
		case 'save_sensor' :
			saveSensorConfig( $conf, $_POST );
			if ($response->config_changed) {
				createPages();
			}
			break;
		case 'disable_sensor' :
			if (isset( $conf[$_POST['sensor']] )) {
				$conf[$_POST['sensor']]['enabled'] = 'false';
				saveSensorConfig( $conf, $conf[$_POST['sensor']], TRUE );
			}
			break;
		case 'enable_sensor' :
			if (isset( $conf[$_POST['sensor']] )) {
				$conf[$_POST['sensor']]['enabled'] = 'true';
				saveSensorConfig( $conf, $conf[$_POST['sensor']], TRUE );
			}
			break;
		case 'delete_sensor' :
			if (isset( $conf[$_POST['sensor']] )) {
				unset( $conf[$_POST['sensor']] );
				writeConfig( $conf );
				$response->sensor = $_POST['sensor'];
			}
		case 'create_pages' :
			createPages();
			break;
		case 'save_everything' :
			saveEverything( $conf, json_decode( $_POST['conf'], TRUE ) );
			if ($response->config_changed) {
				createPages();
			}
			break;
		case 'get_external' :
			$response->external_config = getExternal( $_POST, 'config' );
			break;
		default :
			abortWithError( 'Error: unknown action:', $_GET['action'] );
	}
}

// Close the database connection
$dbh = null;
// if we got till here without errors, the response is a success.
$response->status = 'success';
echo json_encode( $response );
?>
