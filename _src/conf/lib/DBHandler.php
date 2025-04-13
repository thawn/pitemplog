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
	protected $host = 'localhost';
	protected $db = 'temperatures';
	protected $user = 'temp';
	protected $pw = 'temp';
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
	public function __construct(ResponseClass $response, array $data = []) {
		parent::__construct( $response );
		$this->init_props( $data );
		$this->host = $_ENV['DB_HOST'] ?: 'localhost';
		$this->db = $_ENV['DB_DB'] ?: 'temperatures';
		$this->user = $_ENV['DB_USER'] ?: 'temp';
		$this->pw = $_ENV['DB_PW'] ?: 'temp';
		$this->open_connection();
	}
	public function set_aggregateTables(string $val) {
		$this->aggregateTables = [];
		foreach ( $val as $suffix ) {
			$this->aggregateTables[] = $this->filter_default( $val, 'aggregateTables' );
		}
	}
	public function set_dbtest(string $val) {
		$this->dbtest = $val === 'OK' ? 'OK' : '';
	}
	public function set_error(string $prop, string $message) {
		$this->dbtest = '';
		$this->has_error = TRUE;
		$this->response->dbErrors[] = $message;
	}
	/**
	 * Open a connection to the database.
	 */
	public function open_connection() {
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
	public function test_connection() {
		return isset( $this->dbh );
	}
	/**
	 * Execute a database query.
	 * 
	 * @param string $query
	 * @return \PDOStatement
	 */
	public function query(string $query) {
		return $this->dbh->query( $query );
	}
	public function prepare($sql) {
		return $this->dbh->prepare($sql);
	}
	public function begin() {
		return $this->dbh->beginTransaction();
	}
	public function commit() {
		return $this->dbh->commit();
	}
	public function roll_back() {
		return $this->dbh->rollBack();
	}
	protected function filter_default(string $val, string $field, string $regexp = "/^[a-zA-Z_]{4,20}$/", string $message = '') {
		$val = filter_var( $val, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK );
		if (! filter_var( $val, FILTER_VALIDATE_REGEXP, [
				'options' => [
						'regexp' => $regexp
				]
		] )) {
			$this->set_error( $field, 'Only letters and underscore allowed. The ' . $field . ' name must be 4-20 characters long.' );
		}
		return $val;
	}
	/**
	 * make sure the database connection is closed properly
	 */
	public function __destruct() {
		$this->dbh = null;
	}
}
?>