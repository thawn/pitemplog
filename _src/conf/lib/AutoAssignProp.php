<?php

namespace Pitemplog\Conf;

/**
 * Base Class that enables the semi-automatic assignment of properties from an associative array.
 *
 * @author korten
 */
class AutoAssignProp {
	/**
	 *
	 * @var ResponseClass
	 */
	protected $response;
	protected $has_error = FALSE;
	function __construct(ResponseClass $response) {
		$this->response = $response;
	}
	function init_props(array $data) {
		foreach ( get_object_vars( $this ) as $prop => $value ) {
			if (isset( $data[$prop] )) {
				$this->{'set_' . $prop}( $this->prepare_input( $data[$prop] ) );
				unset( $data[$prop] );
			}
		}
		if ($data) {
			$this->response->logger( 'Warning: unknown properties for: ' . get_class( $this ), $data, 2 );
		}
	}
	function prepare_input($input) {
		if (is_string( $input )) {
			return trim( $input );
		} else {
			return ($input);
		}
	}
	function filter_default(string $val, string $field, string $regexp = "/^[a-zA-Z0-9_\- ]*$/", string $message = '') {
		$val = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! filter_var( $val, FILTER_VALIDATE_REGEXP, [ 
				'options' => [ 
						'regexp' => $regexp
				]
		] )) {
			$this->set_error( $field, $message ?: 'Only letters, numbers and space allowed' );
		}
		return $val;
	}
	function set_error(string $prop, string $message) {
		$this->has_error = TRUE;
	}
}
?>