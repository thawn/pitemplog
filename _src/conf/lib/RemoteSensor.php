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
	public $apikey = '';
	public $push = '';
	protected $parsers = [ ];
	public function __construct(ResponseClass $response, DBHandler $database, array $data = []) {
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
	public function set_exturl($val) {
		$this->exturl = filter_var( $val, FILTER_SANITIZE_URL );
	}
	public function set_extname(string $val) {
		$this->extname = $this->filter_default( $val, 'extname', "/^[a-zA-Z0-9_:\- ]*$/" );
	}
	public function set_exttable(string $val) {
		$this->exttable = $this->filter_default( $val, 'exttable', "/^[a-zA-Z][a-zA-Z0-9_]{3,19}$/", 'must start with a letter, be 4-20 characters long and contain only letters, numbers and underscore.' );
	}
	public function set_extuser(string $val) {
		$this->extuser = filter_var( $val, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_extpw(string $val) {
		$this->extpw = filter_var( $val, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_extparser(string $val) {
		$this->extparser = filter_var( $val, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! empty( $this->extparser ) && $this->extparser != 'none' && array_search( $this->extparser, $this->parsers ) === false) {
			$this->response->abort( 'There is an error in your configuration: The file for the parser: ' . $parsed . ' does not exist. These are the parsers that I know:', $this->parsers );
		}
	}
	public function set_apikey(string $val) {
		$this->apikey = $this->filter_default( $val, 'apikey', "#^[a-zA-Z0-9/+= ]{8,76}$#", 'must be base64 encoded and 8-76 characters long. Copy and paste the api key from the configuration interface of the target server');
	}
	public function set_push(string $val) {
		$this->push = $val === 'true' ? 'true' : '';
	}
	public function set_error(string $prop, string $message) {
		$this->response->remote_sensor_error[$this->sensor][$prop] = $message;
	}
	public function getExternalParsers() {
		return $this->parsers;
	}
}
?>