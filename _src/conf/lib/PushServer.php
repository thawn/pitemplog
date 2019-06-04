<?php

namespace Pitemplog\Conf;

/**
 * Store information about remote pitemplog servers.
 *
 * @author korten
 */
class PushServer extends AutoAssignProp {
	public $url = '';
	public $name = '';
	public $user = '';
	public $pw = '';
	public function __construct(ResponseClass $response, array $data = []) {
		parent::__construct( $response );
		$this->init_props( $data );
	}
	public function set_url(string $val) {
		$this->url = $this->filter_url($val, 'url');
	}
	public function set_name(string $val) {
		$this->name =  $this->filter_default( $val, 'name' );
	}
	public function set_user(string $val) {
		$this->user = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_pw(string $val) {
		$this->pw = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	public function set_error(string $prop, string $message) {
		$this->has_error = TRUE;
		$this->response->push_server_errors[$this->url][$prop] = $message;
	}
}
?>