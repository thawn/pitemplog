<?php
class DBHandler {
	public $host = $_ENV['DB_HOST'] ?: 'localhost';
	public $db = $_ENV['DB_DB'] ?: 'temperatures';
	public $user = $_ENV['DB_USER'] ?: 'temp';
	public $pw = $_ENV['DB_PW'] ?: 'temp';
	public $aggregateTables = [ 
			'_5min',
			'_15min',
			'_60min'
	];
	public $dbtest = '';
}
class LocalSensor {
	public $sensor = '';
	public $name = '';
	public $table = '';
	public $category = 'DIEZLAB';
	public $enabled = 'true';
	public $comment = '';
	public $tabletest = '';
	public $calibration = '0';
}
class PitemplogServer {
	public $url = '';
	public $name = '';
	public $user = '';
	public $pw = '';
	public $parser = 'none';
}
class ExternalSensor extends LocalSensor {
	public $exttable = '';
	public $server = new PitemplogServer();
}
class PushSensor extends LocalSensor {}
class ConfigClass {
	public $database = new DBHandler();
	public $local_sensors = [];
	public $remote_sensors = [];
	public $push_server = array (
			'url' => '',
			'name' => '',
			'enabled' => FALSE
	);
	protected $sensordir = $_ENV['SENSOR_DIR'] ?: '/sys/bus/w1/devices/';
	function __construct() {
	}
}
?>