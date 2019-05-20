<?php

namespace Pitemplog\Conf;

/**
 * Handles any interaction with the mysql database.
 *
 * @author korten
 */
class DBHandler extends AutoAssignProp {
	/**
	 * 
	 * @todo remove possibility to configure database via config file. Switch entirely to using environment variables (for safety reasons).
	 */
	public $host = 'localhost';
	public $db = 'temperatures';
	public $user = 'temp';
	public $pw = 'temp';
	public $aggregateTables = [ 
			'_5min',
			'_15min',
			'_60min'
	];
	public $dbtest = '';
	/**
	 *
	 * @var \PDO
	 */
	protected $dbh;
	/**
	 * creates a new DBHandler object
	 *
	 * @param ResponseClass $response
	 * @param array $data
	 */
	function __construct(ResponseClass $response, array $data = []) {
		parent::__construct( $response );
		$this->init_props( $data );
		$this->host = $_ENV['DB_HOST'] ?: 'localhost';
		$this->db = $_ENV['DB_DB'] ?: 'temperatures';
		$this->user = $_ENV['DB_USER'] ?: 'temp';
		$this->pw = $_ENV['DB_PW'] ?: 'temp';
		$this->open_connection();
	}
	function filter_default(string $val, string $field, string $regexp = "/^[a-zA-Z_]{4,20}$/", string $message = '') {
		$val = filter_var( $val, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! filter_var( $val, FILTER_VALIDATE_REGEXP, [ 
				'options' => [ 
						'regexp' => $regexp
				]
		] )) {
			$this->set_error( $field, 'Only letters and underscore allowed. The ' . $field . ' name must be 4-20 characters long.' );
		}
		return $val;
	}
	function set_host(string $val) {
		$this->host = filter_var( $val, FILTER_SANITIZE_URL );
		if (! filter_var( gethostbyname( $this->host . '.' ), FILTER_VALIDATE_IP )) {
			$this->set_error( 'host', 'The database host name must be a valid and reachable ip address or domain name.' );
		}
	}
	function set_db(string $val) {
		$this->db = $this->filter_default( $val, 'db' );
	}
	function set_user(string $val) {
		$this->user = $this->filter_default( $val, 'user' );
	}
	function set_pw(string $val) {
		$this->pw = $val;
	}
	function set_aggregateTables(string $val) {
		foreach ( $val as $suffix ) {
			$this->aggregateTables[] = $this->filter_default( $val, 'aggregateTables' );
		}
	}
	function set_dbtest(string $val) {
		$this->dbtest = $val === 'OK' ? 'OK' : '';
	}
	function set_error(string $prop, string $message) {
		$this->has_error = TRUE;
		$this->response->dbErrors[$prop] = $message;
	}
	/**
	 * Open a connection to the database.
	 */
	function open_connection() {
		try {
			$this->dbh = new \PDO( 'mysql:host=' . $this->host . ';dbname=' . $this->db, $this->user, $this->pw, array (
					\PDO::ATTR_PERSISTENT => true
			) );
			$this->dbtest = 'OK';
			$this->response->logger( 'Connected to database: ', $this, 3 );
		} catch ( \PDOException $e ) {
			switch ($e->getCode()) {
				case '2002' :
					$this->set_error( 'host', sprintf( 'Could not connect to database server %s.', $this->host ) );
					break;
				case '1044' :
					$this->set_error( 'db', sprintf( 'Database %s not found.', $this->db ) );
					break;
				case '1698' :
					$this->set_error( 'user', sprintf( 'User %s doe not have access to the database.', $this->user ) );
					break;
				case '1045' :
					$this->set_error( 'pw', 'Wrong password.' );
					break;
			}
			$this->response->abort( 'Could not connect to database. Got error: ' . $e->getMessage(), $this );
		}
	}
	/**
	 * Test whether the database connection exists.
	 */
	function test_connection() {
		return isset( $this->dbh );
	}
	/**
	 * Execute a database query.
	 * 
	 * @param string $query
	 * @return \PDOStatement
	 */
	function query(string $query) {
		return $this->dbh->query( $query );
	}
	/**
	 * make sure the database connection is closed properly
	 */
	function __destruct() {
		$this->dbh = null;
	}
}
?>