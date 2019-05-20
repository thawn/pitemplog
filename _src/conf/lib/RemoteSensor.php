<?php

namespace Pitemplog\Conf;

/**
 * Store information about remote sensors.
 *
 * @author korten
 */
class RemoteSensor extends LocalSensor {
	public $exturl = '';
	public $exttable = '';
	public $extname = '';
	public $extuser = '';
	public $extpw = '';
	public $extparser = 'none';
	protected $parsers = [ ];
	function __construct(ResponseClass $response, DBHandler $database, array $data = []) {
		parent::__construct( $response, $database, $data );
		$this->init_props( $data );
		$parserdir = dirname( getcwd() ) . '/assets/parser/';
		$parsers = [ ];
		if (file_exists( $parserdir )) {
			$files = scandir( $parserdir );
			foreach ( $files as $key => $file ) {
				if (strpos( $file, '.php' ) === false) {
					unset( $files[$key] );
				}
			}
			$this->parsers = array_values( $files );
		}
	}
	function set_exturl($val) {
		$this->exturl = filter_var( $val, FILTER_SANITIZE_URL );
		if (! filter_var( gethostbyname( parse_url( $this->exturl, PHP_URL_HOST ) . '.' ), FILTER_VALIDATE_IP )) {
			$this->response->remote_sensor_error[$this->sensor]['exturl'] = 'The external url ' . $this->exturl . ' must be a valid and reachable ip address or domain name.';
		}
	}
	function set_extname(string $val) {
		$this->extname = $this->filter_default( $val, 'extname' );
	}
	function set_exttable(string $val) {
		$this->exttable = $this->filter_default( $val, 'exttable', "/^[a-zA-Z][a-zA-Z0-9_]{3,19}$/", 'must start with a letter, be 4-20 characters long and contain only letters, numbers and underscore.' );
	}
	function set_extuser(string $val) {
		$this->extuser = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	function set_extpw(string $val) {
		$this->extpw = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	function set_extparser(string $val) {
		$this->extparser = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! empty( $this->extparser ) && $this->extparser != 'none' && array_search( $this->extparser, $this->parsers ) === false) {
			$this->response->abort( 'There is an error in your configuration: The file for the parser: ' . $parsed . ' does not exist. These are the parsers that I know:', $this->parsers );
		}
	}
	function set_error(string $prop, string $message) {
		$this->response->remote_sensor_error[$this->sensor][$prop] = $message;
	}
	function getExternalParsers() {
		return $this->parsers;
	}
}
?>