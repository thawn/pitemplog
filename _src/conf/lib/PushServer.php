<?php
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
	function __construct(ResponseClass $response, array $data = []) {
		parent::__construct( $response );
		$this->init_props($data);
	}
	function set_url(string $val) {
		$this->url = filter_var( $val, FILTER_SANITIZE_URL );
		if (! filter_var( gethostbyname( $this->host ), FILTER_VALIDATE_IP )) {
			$this->response->push_server_error['url'] = 'The url must be a valid and reachable ip address or domain name.';
		}
	}
	function set_name(string $val) {
		$this->name = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! filter_var( $this->name, FILTER_VALIDATE_REGEXP, [ 
				'regexp' => "/^[a-zA-Z0-9_\- ]*$/"
		] )) {
			$this->response->push_server_error['name'] = 'Only letters, numbers and space allowed';
		}
	}
	function set_user(string $val) {
		$this->user = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	function set_pw(string $val) {
		$this->wp = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
	}
	function set_error (string $prop, string $message) {
		$this->has_error = TRUE;
		$this->response->push_server_errors[$this->url][$prop] = $message;
	}
}
?>