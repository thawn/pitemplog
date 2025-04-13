<?php

namespace Pitemplog\Conf;

/**
 * Stores the response and print it as json string which will be interpreted by the javascript frontend.
 *
 * @author korten
 */
class ResponseClass {
	public $db_config = [ ];
	public $dbErrors = [ ];
	public $sensor = '';
	public $local_sensors = [ ];
	public $local_sensor_error = [ ];
	public $remote_sensors = [ ];
	public $remote_sensor_error = [ ];
	public $external_config = [ ];
	public $push_servers = [];
	public $push_server_errors = [];
	public $config_changed = FALSE;
	public $temperature = '';
	public $confirm = FALSE;
	public $alert = '';
	public $log = '';
	public $status = 'error';
	/**
	 * the debugging level determines which kind of messages end up in the message window.
	 * 0 = errors only; 1 = errors and messages; 2 = errors, messages and verbose messages;
	 * 3 = everything (including debugging info)
	 *
	 * @var integer $debug
	 */
	protected $debug = 0;
	protected $table_alerts = [ ];
	public function __construct() {
		if (isset( $_GET['debug'] )) {
			$this->debug = intval( $_GET['debug'] );
		} elseif (isset( $_POST['debug'] )) {
			$this->debug = intval( $_POST['debug'] );
		}
	}
	public function get_debug(){
		return $this->debug;
	}
	/**
	 * store debug messages in log property
	 *
	 * @param string $heading
	 * @param mixed $content
	 * @param number $level
	 */
	public function logger($heading, $content, $level = 1) {
		if ($this->debug >= $level) {
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
			$this->log .= '<h4 class="' . $class . '">' . $heading . '</h4>';
			if ($content) {
				$content_str = $this->log .= '<pre>';
				$this->log .= is_scalar( $content ) ? print_r( $content, TRUE ) : stripslashes( json_encode( $content, JSON_PRETTY_PRINT ) );
				$this->log .= '</pre>';
			}
		}
	}
	
	/**
	 * Add a table about which we want to inform the user that it's table already exists in the database.
	 *
	 * @param string $table
	 */
	public function add_table_alert(string $table) {
		$this->table_alerts[] = $table;
	}
	/**
	 * Format alerts that tell the user which tables are already existing in the database.
	 */
	public function format_table_alerts() {
		if ($this->table_alerts) {
			if (count( $this->table_alerts ) > 1) {
				$s = 's';
				$nos = '';
				$its = 'their';
				$a = '';
			} else {
				$s = '';
				$nos = 's';
				$its = 'its';
				$a = ' a';
			}
			$format = 'The table%1$s "%3$s" already exist%2$s and may contain data. ';
			$format .= 'The sensor%1$s will encorporate that data into %4$s history. ';
			$format .= 'If you don\'t want that, choose%5$s different table name%1$s.';
			$this->alert .= sprintf( $format, $s, $nos, implode( '", "', $this->table_alerts ), $its, $a );
		}
	}
	/**
	 * Abort execution and return an error.
	 *
	 * @param string $message
	 * @param mixed $content
	 */
	public function abort($message, $object) {
		$this->logger( 'ABORTED: ' . $message, $object, 0 );
		echo json_encode( $this,  JSON_FORCE_OBJECT );
		die();
	}
	/**
	 * finish successfully and creat the json output.
	 */
	public function finish() {
		$this->format_table_alerts();
		$this->status = 'success';
		echo json_encode( $this,  JSON_FORCE_OBJECT );
		exit();
	}
}
?>