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
	 * @var PitemplogServer[]
	 */
	public $push_servers = [ ];
	/**
	 *
	 * @var float
	 */
	public $version = 2.0;
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
	function __construct(ResponseClass $response, string $config_file = NULL) {
		$this->response = $response;
		if ($config_file)
			$this->config_file = $config_file;
		$this->sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
		if (file_exists( $this->config_file )) {
			$conf = json_decode( file_get_contents( $this->config_file ), true );
			$this->response->logger( 'Raw configuration from the config file', $conf, 3 );
			if (! isset( $conf['version'] ) || $conf['version'] < $this->version) {
				$this->upgrade_config( $conf );
			} else {
				$this->import_data( $conf );
			}
		}
		if (! $this->database) {
			$this->database = new DBHandler( $this->response );
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
	function get_sensor_type(string $sensor) {
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
	function is_table_used(string $table, string $target_sensor) {
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
	function add_sensor(array $data, string $type = 'local') {
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
	function save_sensor_config(array $data, $save_to_disk = TRUE) {
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
	 * Add a database handler.
	 *
	 * @param array $data
	 */
	function add_database(array $data) {
		$this->database = new DBHandler( $this->response );
	}

	/**
	 * upgrade configuration of an older version to the current version
	 *
	 * @param array $conf
	 */
	function upgrade_config(array $conf) {
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
	function import_data(array $data) {
		if (isset( $data['database'] )) {
			$this->add_database( $data['database'] );
		}
		foreach ( $data['local_sensors'] as $sensor ) {
			$this->add_sensor( $sensor, 'local' );
		}
		foreach ( $data['remote_sensors'] as $sensor ) {
			$this->add_sensor( $sensor, 'remote' );
		}
		foreach ( $data['push_servers'] as $sensor ) {
			$this->push_servers[$sensor['sensor']] = new PushServer( $this->response, $sensor );
		}
	}

	/**
	 * Update database configuration and save the configuration to file.
	 *
	 * @param array $data
	 */
	function save_database_config(array $data, $save_to_disk = TRUE) {
		$this->add_database( $data );
		$this->response->db_config = $this->database;
		if ($save_to_disk)
			$this->write_config();
	}

	/**
	 * Save changes to config file.
	 * Also checks if there are changes and pages need to be rebuilt.
	 */
	function write_config(bool $create_pages = FALSE) {
		$this->response->logger( 'Attempting to write configuration to: ' . $this->config_file, $this, 3 );
		if (is_writable( $this->config_file )) {
			file_put_contents( $this->config_file, json_encode( $this, JSON_UNESCAPED_SLASHES ), LOCK_EX );
			$this->config_changed = FALSE;
			$local_conf = escapeshellarg( $this->config_file );
			$build_conf = escapeshellarg( '/usr/local/share/templog/_data/config.json' );
			$diff = escapeshellcmd( '/usr/bin/diff -q ' . $build_conf . ' ' . $local_conf );
			$diff_output = shell_exec( $diff );
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
	function populate_local_sensors() {
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
	function toggle_sensor(string $sensor, bool $enabled = TRUE) {
		$sensor_type = $this->get_sensor_type( $sensor );
		if ($sensor_type) {
			$state = $enabled ? 'true' : 'false';
			$this->{$sensor_type}[$sensor]->enabled = $state;
			$this->write_config( TRUE );
			if ($response->config_changed) {
				$this->create_pages();
			}
			$this->response->{$sensor_type}[$sensor] = $this->{$sensor_type}[$sensor];
		}
	}

	/**
	 * Delete a sensor.
	 *
	 * @param string $sensor
	 * @return boolean
	 */
	function delete_sensor(string $sensor) {
		$sensor_type = $this->get_sensor_type( $sensor );
		$deleted = FALSE;
		if ($sensor_type) {
			$this->response->logger( 'Attempting to delete ' . $sensor_type . ': ' . $sensor, $this->{$sensor_type}[$sensor] );
			unset( $this->{$sensor_type}[$sensor] );
			$this->write_config( TRUE );
			if ($response->config_changed) {
				$this->create_pages();
				$this->response->logger( 'Deleted: ' . $sensor, FALSE );
			}
			$deleted = TRUE;
		}
		return $deleted;
	}

	/**
	 * Run commands that were stored in the command queue.
	 */
	function run_commands() {
		foreach ( $this->commands as $command ) {
			$output = shell_exec( $command );
			$this->response->logger( 'Executed command: ' . $command, $output );
		}
	}

	/**
	 * execute commands that create the html pages and update the website.
	 */
	function create_pages() {
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
 *
 * @todo configure push to external server
 */
}
?>