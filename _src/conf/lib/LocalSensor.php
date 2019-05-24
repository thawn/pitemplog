<?php

namespace Pitemplog\Conf;

/**
 * Store information about locÏal sensors.
 *
 * @author kortenÏ
 */
class LocalSensor extends AutoAssignProp {
	public $sensor = '';
	public $calibration = '0';
	public $name = '';
	public $table = '';
	public $category = 'DIEZLAB';
	public $confirmed = '';
	public $enabled = 'true';
	public $comment = '';
	public $tabletest = '';
	/**
	 *
	 * @var DBHandler
	 */
	protected $database;
	/**
	 *
	 * @var array
	 */
	protected $commands = [ ];
	protected $table_old = '';
	protected $has_error = FALSE;
	/**
	 * creates a new DBHandler object
	 *
	 * @param ResponseClass $response
	 * @param array $data
	 */
	public function __construct(ResponseClass $response, DBHandler $database, array $data = []) {
		parent::__construct( $response );
		$this->database = $database;
		$this->update_config( $data );
	}
	public function set_sensor(string $val) {
		$this->sensor = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_calibration(string $val) {
		$this->calibration = filter_var( $val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC );
	}
	public function set_name(string $val) {
		$this->name = $this->filter_default( $val, 'name' );
	}
	public function set_table(string $val) {
		$this->table = $this->filter_default( $val, 'table', "/^[a-zA-Z][a-zA-Z0-9_]{3,19}$/", 'The table name must start with a letter, be 4-20 characters long and contain only letters, numbers and underscore.' );
	}
	public function set_category(string $val) {
		$this->category = $this->filter_default( $val, 'category' );
	}
	public function set_confirmed(string $val) {
		$this->confirmed = $val === 'true' ? 'true' : '';
	}
	public function set_enabled(string $val) {
		$this->enabled = $val === 'false' ? 'false' : 'true';
	}
	public function set_comment(string $val) {
		$this->comment = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_tabletest(string $val) {
		$this->tabletest = $val === 'OK' ? 'OK' : '';
	}
	public function set_table_old(string $val) {
		$this->response->logger( 'Caught attempt to write the property "table_old" to the configuration:', $this, 3 );
	}
	public function set_error(string $prop, string $message) {
		$this->has_error = TRUE;
		$this->response->local_sensor_error[$this->sensor][$prop] = $message;
		if ($prop === 'table') {
			$this->tabletest = '';
		}
	}
	public function get_commands() {
		return $this->commands;
	}
	public function has_error() {
		return $this->tabletest !== 'OK';
	}
	public function update_config(array $data) {
		$this->has_error = FALSE;
		if (isset( $data['table_old'] )) {
			/*
			 * the field table_old is not stored and thus if it exists, that means we got the data
			 * from the web interface and the table may have been updated
			 */
			$table_old = $data['table_old'];
			unset( $data['table_old'] );
		}
		$this->init_props( $data );
		if (! isset( $data['sensor'] )) {
			$this->response->abort( 'Could not process sensor without an id: ', $data );
		}
		if (isset( $table_old )) {
			$this->update_table( $table_old );
		} else {
			unset( $this->response->local_sensor_error[$this->sensor] );
		}
	}
	protected function update_table($table_old) {
		if ($this->table_exists()) {
			if ($this->table !== $table_old && $this->table_exists( $table_old )) {
				if ($this->confirmed) {
					$this->merge_db_tables( $table_old );
					$this->reset_aggregate_tables();
					$this->aggregate_data_now();
					$this->tabletest = 'OK';
					$this->confirmed = '';
				} else {
					if ($_GET['action'] === 'save_everything') {
						$this->set_error( 'table', 'Cannot rename multiple tables at once. Please save each sensor individually.' );
					} else {
						$this->response->confirm = [ 
								'message' => 'It appears you are trying to rename the table "' . $table_old . '" to an existing table "' . $this->table . '". Do you want to merge the contents of "' . $this->table . '" with "' . $table_old . '"? If you say no, the sensor will simply save its data to table "' . $this->table . '" from now on and leave "' . $table_old . '" in the database as it is.',
								'data' => json_decode( json_encode( $this ), true ),
								'action' => $_GET['action']
						];
						$this->response->confirm['data']['table_old'] = $table_old;
						$this->response->logger( 'Confirmation info: ', $this->response->confirm, 3 );
					}
				}
			} elseif ($this->table !== $table_old) {
				$this->response->add_table_alert( $this->table );
				$this->tabletest = 'OK';
			} else {
				$this->tabletest = 'OK';
			}
		} else {
			$this->make_db_table();
			if ($table_old && $this->table !== $table_old && $this->table_exists( $table_old )) {
				$this->merge_db_tables( $table_old );
				$this->aggregate_data_now();
			}
		}
		$this->response->logger( 'New sensor config is:', $this, 3 );
		$this->response->sensor_config[$this->sensor] = $this;
		if (! $this->has_error()) {
			$this->response->logger( 'Tabletest OK and has_error is: ' . $this->has_error, FALSE, 3 );
			$this->confirmed = '';
		}
	}

	/**
	 * Create the database tables for a sensor.
	 *
	 * @param string $sensor
	 */
	public function make_db_table() {
		$this->response->logger( 'Attempting to create table for sensor: ', $this->sensor, 3 );
		$result = $this->database->query( 'DROP TABLE IF EXISTS `' . $this->table . '`; CREATE TABLE `' . $this->table . '` (time INT, temp FLOAT)' );
		$this->response->logger( sprintf( 'Created table %s for sensor %s: ', $this->table, $this->sensor ), $result );
		if ($result) {
			$result = null;
			$this->tabletest = 'OK';
			$partition_database = escapeshellcmd( '/usr/local/bin/partition_database.py' );
			$this->commands[] = $partition_database . ' "" ' . $this->table;
			$this->reset_aggregate_tables();
		} else {
			$this->tabletest = '';
		}
	}

	/**
	 * Create the aggregate tables.
	 * Delete them if they are already present (and thus reset them).
	 */
	public function reset_aggregate_tables() {
		if ($this->table) {
			$partition_database = escapeshellcmd( '/usr/local/bin/partition_database.py' );
			foreach ( $this->database->aggregateTables as $extension ) {
				$table_name = $this->table . $extension;
				$result = $this->database->query( 'DROP TABLE IF EXISTS `' . $table_name . '`; CREATE TABLE `' . $table_name . '` (time INT, temp FLOAT)' );
				$this->response->logger( 'Created table: ' . $table_name, $result );
				$result = null;
				$this->commands[] = $partition_database . ' ' . $extension . ' ' . $this->table;
			}
		}
	}

	/**
	 * Aggregate temperature data.
	 */
	public function aggregate_data_now() {
		$aggregate_cmd = escapeshellcmd( '/usr/local/bin/tempaggregate.py' );
		$this->response->logger( 'Aggregating data for table: ' . $this->table, FALSE );
		foreach ( $this->database->aggregateTables as $extension ) {
			$this->commands[] = $aggregate_cmd . ' ' . $extension . ' ' . $this->table;
		}
	}

	/**
	 * Check if a table exists in the database
	 *
	 * @param string $table_in
	 * @return boolean
	 */
	public function table_exists($table_in = NULL) {
		$result = FALSE;
		$table = $table_in ?? $this->table;
		if (! empty( $table )) {
			try {
				$answer = $this->database->query( 'SELECT 1 from `' . $table . '`' );
				$result = $answer !== FALSE;
			} catch ( PDOException $e ) {
				$this->response->logger( 'Caught error when looking for table: ' . $table, $e, 3 );
				$result = FALSE;
			}
			if ($result) {
				$this->response->logger( 'Table: ' . $table . ' exists.', FALSE, 3 );
			} else {
				$this->response->logger( 'Table: ' . $table . ' does not exist.', FALSE, 3 );
			}
		}
		return $result;
	}

	/**
	 * Merge two database tables.
	 * Caution: the table $table_old will be deleted
	 *
	 * @param string $table_old:
	 *        	The table from which the data will be merged.
	 *        	This table will be deleted after the data was merged successfully!
	 */
	protected function merge_db_tables($table_old) {
		$this->response->logger( 'Merging table "' . $this->table . '" with "' . $table_old . '":', FALSE );
		$result = $this->database->query( 'INSERT INTO `' . $this->table . '` SELECT * FROM `' . $table_old . '`' );
		$this->response->logger( sprintf( 'Moved data from old table "%s" to "%s"', $table_old, $this->table ), $result );
		$result = null;
		$result = $this->database->query( 'DROP TABLE IF EXISTS `' . $table_old . '`' );
		$this->response->logger( 'Deleted old table: ' . $table_old, $result );
		$result = null;
		foreach ( $this->database->aggregateTables as $extension ) {
			$result = $this->database->query( 'DROP TABLE IF EXISTS `' . $table_old . $extension . '`' );
			$this->response->logger( 'Deleted old table: ' . $table_old . $extension, $result );
			$result = null;
		}
	}
}
?>