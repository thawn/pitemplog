<?php

namespace Pitemplog\Conf;

/**
 * Config class loads, manipulates and saves the configuration from/to a file.
 *
 * @author korten
 */
class ConfigClass {
	/**
	 *
	 * @var DBHandler
	 */
	public $database;
	/**
	 *
	 * @var LocalSensor[]
	 */
	public $local_sensors = [ ];
	/**
	 *
	 * @var RemoteSensor[]
	 */
	public $remote_sensors = [ ];
	/**
	 *
	 * @var RemoteSensor[] || LocalSensor[]
	 */
	public $all_sensors = [ ];
	/**
	 *
	 * @var PitemplogServer[]
	 */
	public $push_servers = [ ];
	/**
	 *
	 * @var float
	 */
	public $version = '2.0';
	/**
	 *
	 * @var string
	 */
	protected $sensordir = '/sys/bus/w1/devices/';
	/**
	 *
	 * @var string
	 */
	protected $config_file = 'config.json';
	/**
	 *
	 * @var ResponseClass
	 */
	protected $response;
	/**
	 *
	 * @var array
	 */
	protected $commands = [ ];
	protected $config_changed = FALSE;
	/**
	 *
	 * @param ResponseClass $response
	 * @param string $config_file
	 */
	public function __construct(ResponseClass $response, string $config_file = NULL) {
		$this->response = $response;
		$this->database = new DBHandler( $this->response );
		if ($config_file)
			$this->config_file = $config_file;
		$this->sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
		if (file_exists( $this->config_file )) {
			$conf = json_decode( file_get_contents( $this->config_file ), true );
			$this->response->logger( 'Raw configuration from the config file', $conf, 3 );
			if (! isset( $conf['version'] ) || version_compare( $conf['version'], $this->version, '<' )) {
				$this->upgrade_config( $conf );
			} else {
				$this->import_data( $conf );
			}
		}
		$this->populate_local_sensors();
		if ($this->config_changed) {
			$this->write_config();
		}
	}

	/**
	 * Get the array in which the configuration for a certain sensor is stored.
	 *
	 * @param string $sensor
	 * @return string
	 */
	public function get_sensor_type(string $sensor) {
		if (array_key_exists( $sensor, $this->local_sensors )) {
			return 'local_sensors';
		} elseif (array_key_exists( $sensor, $this->remote_sensors )) {
			return 'remote_sensors';
		} else {
			return '';
		}
	}

	/**
	 * Check if a table is used by any other sensor.
	 *
	 * @param string $table:
	 * @param string $target_sensor:
	 * @return boolean
	 */
	public function is_table_used(string $table, string $target_sensor) {
		$used = FALSE;
		if ($table) {
			$this->response->logger( sprintf( 'Checking whether table %s is already used by a sensor other than %s.', $table, $target_sensor ), $conf, 3 );
			foreach ( array_merge( $this->remote_sensors, $this->local_sensors ) as $sensor ) {
				if ($sensor->sensor !== $target_sensor && $table === $sensor->table) {
					$used = TRUE;
					$this->response->logger( sprintf( 'The table %s is already used by sensor: %s. We cannot use it for sensor: %s', $table, $sensor->sensor, $target_sensor ), FALSE, 0 );
				}
			}
		}
		return $used;
	}

	/**
	 * Add a sensor to the respective array config property.
	 *
	 * @param array $data
	 * @param string $type
	 */
	protected function add_sensor(array $data, string $type = 'local') {
		$sensor_type = $type . '_sensors';
		if (isset( $this->{$sensor_type}[$data['sensor']] )) {
			$this->response->logger( 'Updating sensor with data:', $data, 3 );
			$this->{$sensor_type}[$data['sensor']]->update_config( $data );
		} else {
			$this->response->logger( 'Loading sensor with data:', $data, 3 );
			$class_name = __NAMESPACE__ . '\\' . ucfirst( $type ) . 'Sensor';
			$this->{$sensor_type}[$data['sensor']] = new $class_name( $this->response, $this->database, $data );
		}
	}

	/**
	 * Update sensor configuration and save the configuration to file.
	 * Run commands processing the database if necessary.
	 *
	 * @param array $data
	 */
	public function save_sensor_config(array $data, $save_to_disk = TRUE) {
		if (isset( $data['exturl'] )) {
			$type = 'remote';
		} else {
			$type = 'local';
		}
		$prop_name = $type . '_sensors';
		if (! $this->is_table_used( $data['table'], $data['sensor'] )) {
			$this->add_sensor( $data, $type );
			$this->response->{$prop_name}[$data['sensor']] = $this->{$prop_name}[$data['sensor']];
			$this->response->logger( 'Checking sensor for errors:' . $this->{$prop_name}[$data['sensor']]->has_error(), $this->{$prop_name}[$data['sensor']], 3 );
			if (! $this->{$prop_name}[$data['sensor']]->has_error()) {
				$this->populate_all_sensors();
				$new_commands = $this->{$prop_name}[$data['sensor']]->get_commands();
				if ($new_commands)
					$this->commands = array_merge( $this->commands, $new_commands );
				if ($save_to_disk) {
					$this->write_config( TRUE );
					$this->run_commands();
					$this->response->logger( 'Saved sensor:' . $data['sensor'], $this->{$prop_name}[$data['sensor']] );
				}
			}
		} else {
			$this->{$prop_name}[$data['sensor']]->set_error( 'table', 'This table is already used by another sensor, please choose a different table name.' );
			$this->response->abort( 'Please change the table name of sensor: ' . $data['sensor'], $data );
		}
	}

	/**
	 * Merge local and remote sensors into one array.
	 */
	protected function populate_all_sensors() {
		$this->all_sensors = array_merge( $this->remote_sensors, $this->local_sensors );
	}

	/**
	 * upgrade configuration of an older version to the current version
	 *
	 * @param array $conf
	 */
	protected function upgrade_config(array $conf) {
		if (! isset( $conf['version'] )) { // import legacy configuration file
			foreach ( $conf as $config ) {
				if (! $sensor == 'database') {
					if (isset( $config['exturl'] )) {
						$this->add_sensor( $config, 'remote' );
					} else {
						$this->add_sensor( $config, 'local' );
					}
				}
			}
		}
	}

	/**
	 * import a configuration array
	 *
	 * @param array $data
	 */
	protected function import_data(array $data) {
		foreach ( $data['local_sensors'] as $sensor ) {
			$this->add_sensor( $sensor, 'local' );
		}
		foreach ( $data['remote_sensors'] as $sensor ) {
			$this->add_sensor( $sensor, 'remote' );
		}
		foreach ( $data['push_servers'] as $server ) {
			$this->push_servers[$server['url']] = new PushServer( $this->response, $server );
		}
	}

	/**
	 * Save changes to config file.
	 * Also checks if there are changes and pages need to be rebuilt.
	 */
	public function write_config(bool $create_pages = FALSE) {
		$this->response->logger( 'Attempting to write configuration to: ' . $this->config_file, $this, 3 );
		if (is_writable( $this->config_file )) {
			file_put_contents( $this->config_file, json_encode( $this, JSON_UNESCAPED_SLASHES ), LOCK_EX );
			$this->config_changed = FALSE;
			$local_conf = escapeshellarg( $this->config_file );
			$build_conf = escapeshellarg( '/usr/local/share/templog/_data/config.json' );
			$diff = escapeshellcmd( '/usr/bin/diff -q ' . $build_conf . ' ' . $local_conf );
			$diff_output = shell_exec( $diff );
			$this->response->logger( 'Difference between old and new configuration:', $diff_output, 3 );
			if (! empty( $diff_output )) {
				$this->response->logger( 'Configuration saved successfully.', FALSE, 1 );
				$this->response->config_changed = TRUE;
				if ($create_pages) {
					$this->create_pages();
				}
			}
		} else {
			$this->response->abort( 'ERROR: Permission denied. Cannot write to config file:', (getcwd()) . '/' . $this->config_file );
		}
	}

	/**
	 * Load/create configuration for all sensors.
	 *
	 * @param array $conf
	 * @return array
	 */
	protected function populate_local_sensors() {
		// look for available temperature sensors
		$sensors = [ ];
		if (file_exists( $this->sensordir )) {
			$dirs = scandir( $this->sensordir );
			$this->response->logger( 'Sensor directories in' . $this->sensordir . ':', $dirs, 3 );
			foreach ( $dirs as $sensor ) {
				if ($sensor != '.' && $sensor != '..' && $sensor != 'w1_bus_master1' && ! isset( $this->local_sensors[$sensor] )) {
					$this->local_sensors[$sensor] = new LocalSensor( $this->response, $this->database, [ 
							'sensor' => $sensor
					] );
					$this->config_changed = TRUE;
				}
			}
		}
	}

	/**
	 * En- or disable a specific sensor.
	 *
	 * @param string $sensor
	 * @param bool $enabled
	 */
	public function toggle_sensor(string $sensor, bool $enabled = TRUE) {
		$sensor_type = $this->get_sensor_type( $sensor );
		if ($sensor_type) {
			$state = $enabled ? 'true' : 'false';
			$this->{$sensor_type}[$sensor]->enabled = $state;
			$this->write_config( TRUE );
			$this->response->logger( ($enabled? 'Enabled' : 'Disabled') . ' sensor: ' . $sensor, $this->{$sensor_type}[$sensor]);
			$this->response->{$sensor_type}[$sensor] = $this->{$sensor_type}[$sensor];
		}
	}

	/**
	 * Delete a sensor.
	 *
	 * @param string $sensor
	 * @return boolean
	 */
	public function delete_sensor(string $sensor) {
		$deleted = FALSE;
		$this->response->logger( 'Attempting to delete remote sensor: ' . $sensor, $this->remote_sensors[$sensor] );
		if ($this->remote_sensors[$sensor]) {
			unset( $this->remote_sensors[$sensor] );
			$this->populate_all_sensors();
			$this->write_config( TRUE );
			if ($response->config_changed) {
				$this->response->logger( 'Deleted: ' . $sensor, FALSE );
			}
			$deleted = TRUE;
		}
		return $deleted;
	}

	/**
	 * Run commands that were stored in the command queue.
	 */
	public function run_commands() {
		foreach ( $this->commands as $command ) {
			$output = shell_exec( $command );
			$this->response->logger( 'Executed command: ' . $command, $output );
		}
	}

	/**
	 * execute commands that create the html pages and update the website.
	 */
	public function create_pages() {
		$this->response->logger( 'Attempting to create pages:', FALSE, 3 );
		$create_pages = escapeshellcmd( '/usr/local/share/templog/_data/create_pages.py' );
		$create_pages .= ' 2>&1';
		$create_output = shell_exec( $create_pages );
		$this->response->logger( 'Created pages:', $create_output, 1 );
		$jekyll = escapeshellcmd( 'jekyll build' );
		$cd = escapeshellcmd( 'cd /usr/local/share/templog/' );
		$jekyllcmd = $cd . '&&' . $jekyll . ' 2>&1';
		$jekyll_ouptut = shell_exec( $jekyllcmd );
		$this->response->logger( 'Updated Site:', $jekyll_ouptut, 1 );
	}

	/**
	 * Add/update a push server.
	 *
	 * @param array $data
	 */
	public function add_push_server(array $data) {
		$this->response->logger( 'Adding push server: ', $data, 3 );
		$this->push_servers[$data['url']] = new PushServer( $this->response, $data );
		$this->write_config();
	}

	/**
	 * Push the local sensor configuration to an external server.
	 *
	 * @param array $server_data
	 */
	public function push_config(array $server_data) {
		if (! isset( $server_data['url'] )) {
			$this->response->abort( 'Could not process sensor without an url: ', $server_data );
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$push_url = $server_data['url'] . '?action=receive_push_config';
		if ($this->response->get_debug()) {
			$push_url .= '&debug=' . $this->response->get_debug();
		}
		curl_setopt( $ch, CURLOPT_URL, $push_url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		$local_sensor_config = json_decode( json_encode( $this->local_sensors ), TRUE );
		$local_sensor_config = ConfigClass::local_2_remote( $local_sensor_config, $server_data );
		foreach ( $local_sensor_config as $sensor => $conf ) {
			$local_sensor_config[$sensor]['exturl'] = 'http://' . $_SERVER['HTTP_HOST'];
			$local_sensor_config[$sensor]['extname'] = 'Pushed data: ' . gethostname();
		}
		$this->response->logger( 'Merged sensor config:', $local_sensor_config, 3 );
		$this->response->logger( 'Urlencoded sensor config:', http_build_query( $local_sensor_config ), 3 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $local_sensor_config ) );
		$raw_result = curl_exec( $ch );
		$result = json_decode( $raw_result, true );
		if ($result) {
			$this->response->logger( sprintf( 'Pushed configuration to: %s ', $push_url ), FALSE, 3 );
			if (! empty( $result['log'] )) {
				$this->response->log .= sprintf( '<div class="panel panel-info"><div class="panel-heading">Push server (url: %s) responded with messages:</div><div class="panel-body">%s</div></div>', $push_url, $result['log'] );
				unset( $result['log'] );
			}
			$this->response->logger( sprintf( 'Received answer from: %s ', $push_url ), $result, 3 );
			if ($result['status'] === 'success') {
				$this->response->logger( 'Saving server data:', $server_data, 3 );
				$this->add_push_server( $server_data );
				$this->response->push_servers = $this->push_servers;
			} else {
				$this->response->abort( 'Could not push configuration to: ' . $server_data['url'], $result );
			}
		} else {
			$this->response->abort( 'Remote API error: ' . $push_url, $raw_result );
		}
	}
	/**
	 * Save sensor configuration received by a push client.
	 *
	 * @param array $data
	 */
	public function receive_push_config(array $data) {
		$this->response->logger( 'Received sensor config:', $data, 3 );
		$required_fields = [ 
				'sensor',
				'table',
				'name',
				'category',
				'exturl',
				'extname',
				'exttable'
		];
		$config_changed = FALSE;
		foreach ( $data as $conf ) {
			$fields_ok = TRUE;
			if (isset( $conf['sensor'] )) {
				foreach ( $required_fields as $field ) {
					if (! isset( $conf[$field] )) {
						$this->response->remote_sensor_errors[$conf['sensor']][$field] = sprintf( 'Missing configuration parametner: Set the field "%s" in your configuration data.', $field );
						$fields_ok = FALSE;
					}
				}
			} else {
				$this->response->abort( 'Cannot save sensor without a sensor id. This api expects a JSON encoded array of the form{"sensor_id": {"table": "new_table", "sensor": "new_sensor","name": "New Sensor", "exturl": "http://example.com", "extname": "New push"}}.', $conf );
			}
			if ($fields_ok) {
				if (! isset( $conf['table_old'] )) {
					$conf['table_old'] = '';
				}
				$counter = 1;
				$table_name = $conf['table'];
				while ( $this->is_table_used( $conf['table'], $conf['sensor'] ) ) {
					$conf['table'] = sprintf( '%s_%02d', $table_name, $counter ++ );
				}
				$conf['push'] = 'true';
				$this->save_sensor_config( $conf, FALSE );
				if (! $this->remote_sensors[$conf['sensor']]->has_error()) {
					$config_changed = TRUE;
				}
			}
		}
		if ($config_changed) {
			$this->write_config( TRUE );
			$this->run_commands();
			$this->response->logger( 'Saved push sensors:', $data );
		}
	}
	public function save_pushed_data(array $data) {
		$this->database->open_connection();
		$this->database->begin();
		$this->response->logger( 'Received push data:', $data );
		try {
			foreach ( $data['sensor'] as $key => $sensor ) {
				/**
				 * @todo: check api key
				 */
				if ($this->remote_sensors[strval( $sensor )]->push == 'true') {
					$sql = sprintf( 'INSERT INTO `%s`(time,temp) VALUES (?,?)', $this->remote_sensors[strval( $sensor )]->table );
					$sth = $this->database->prepare( $sql );
					$sth->execute( [ 
							$data['time'][$key],
							$data['temp'][$key]
					] );
					$sth = NULL;
				}
			}
			$this->database->commit();
		} catch ( \PDOException $e ) {
			$this->database->roll_back();
			$this->response->abort( 'Caught error trying to save pushed data: ' . $e->getMessage() );
		}
	}

	/**
	 * Convert local sensor configuration into remote sensor configuration by merging the sensor
	 * configuration with the remote server configuration.
	 *
	 * @param array $sensor_data
	 * @param array $server_data
	 * @return array
	 */
	public static function local_2_remote(array $sensors, array $server_data) {
		$ext_conf = array_combine( array_map( function ($k) {
			return 'ext' . $k;
		}, array_keys( $server_data ) ), $server_data );
		foreach ( $sensors as $sensor => $conf ) {
			$sensors[$sensor] = array_merge( ( array ) $conf, $ext_conf );
			$sensors[$sensor]['exttable'] = $sensors[$sensor]['table'];
		}
		return $sensors;
	}
}
?>