import os
import sys
import json
import MySQLdb
import time
import datetime
import re
import logging
import shutil
from pprint import pformat


class LockTable:
    def __init__(self, table_name):
        self.table_name = table_name
        self.lock_file = "/tmp/" + table_name + "_lock"

    def __enter__(self, *unused):
        with open(self.lock_file, 'a'):
            os.utime(self.lock_file, None)
        os.chmod(self.lock_file, 0o666)
        return self

    def __exit__(self, *unused):
        os.remove(self.lock_file)

    def is_locked(self):
        if os.path.isfile(self.lock_file):
            two_hours_ago = time.time() - 7200
            if os.stat(self.lock_file).st_ctime > two_hours_ago:
                log.warning("another process is working on: " + self.table_name + " aborting...")
                return True
            else:
                os.remove(self.lock_file)
        return False


class DBHandler:
    '''
    Wrapper for MySQLdb to enable syntax like:
    with DBHandler as cursor:
        <your code>
    '''
    def __init__(self, database):
        self.dbh = MySQLdb.connect(
            host=database["host"], user=database["user"], passwd=database["pw"], db=database["db"])

    def __enter__(self, *unused):
        self.cursor = self.dbh.cursor()
        return self.cursor

    def __exit__(self, *unused):
        self.cursor.close()
        self.commit()

    def __del__(self):
        self.dbh.close()

    def commit(self):
        self.dbh.commit()


class PiTempLogConf:
    '''
    Read the pitemplog config file and perform operations on the database and sensors defined there.

    Public properties:
        debug: int debugging level
        database: dict database configuration (taken both from environment variables and the config file)
        dbh: database handle object
        local_sensors: dict local sensor configuration
        remote_sensors: dict remote sensor configuration
        push_servers: dict push server configuration
        versio: string version number

    Public methods:
        db_commit
        db_close
        modify_tables apply a function to a specific table or all tables with an extension
        each_sensor apply a function to all sensors
        each_sensor_database apply a function to all sensors, pass the database handle object to the function
        each_sensor_table like each_sensor_database but execute the function ony for a sensor stored in a specific table
        each_local_sensor apply a functio to each local sensor
        each_remote_sensor apply a functio to each remote sensor
        each_local_sensor_database apply a functio to each local sensor, provides the database handler
        each_remote_sensor_database apply a functio to each remote sensor, provides the database handler
        each_push_server apply a functio to each push server
        get_timespan get the timespan in seconds associated to a certain table extension
    '''

    def __init__(self, config_file_path='/var/www/html/conf/config.json'):
        '''
        Initialize a PiTempLogConf class from a json config file.

        Args:
            config_file_path: string path to the config file
        '''
        self.database = {
            'host': os.environ.get('DB_HOST', 'localhost'),
            'db': os.environ.get('DB_DB', 'temperatures'),
            'user': os.environ.get('DB_USER', 'temp'),
            'pw': os.environ.get('DB_PW', 'temp'),
            'aggregateTables': ['_5min', "_15min", "_60min"]
        }
        try:
            with open(config_file_path) as config_file:
                config = json.load(config_file)
        except IOError:
            config = json.loads(config_file_path)
        log.debug("raw config from file:\n" + pformat(config))
        try:
            self.database['aggregateTables'] = config['database']['aggregateTables']
        except KeyError:
            pass
        try:
            self.local_sensors = dict(config['local_sensors'])
        except KeyError:
            self.local_sensors = {}
        try:
            self.remote_sensors = dict(config['remote_sensors'])
        except KeyError:
            self.remote_sensors = {}
        try:
            self.push_servers = dict(config['push_servers'])
        except KeyError:
            self.push_servers = {}
        try:
            self.version = config['version']
        except KeyError:
            self.version = '2.0'
        self._dbh = None
        log.debug("processed config:\n" + pformat(self))

    @property
    def dbh(self):
        if self._dbh is None:
            self._dbh = DBHandler(self.database)
        return self._dbh

    def db_close(self):
        try:
            del self._dbh
        except AttributeError:
            pass
        self._dbh = None

    def modify_tables(self, function_handle, *args):
        '''
        Apply a function to a specific table (sys.argv[2] or all tables with certain table extensions (sys.argv[1]) 
        specified by command line arguments.

        Args:
            function_handle:
        '''
        if len(sys.argv) > 2:
            self.each_sensor_table(sys.argv[2], function_handle, sys.argv[2], sys.argv[1], *args)
        elif len(sys.argv) > 1:
            self.each_sensor_database(function_handle, sys.argv[1], *args)
        self.db_close()

    def each_sensor(self, function_handle, *args):
        '''
        Execute a function for each sensor.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        local = self.each_local_sensor(function_handle, *args)
        remote = self.each_remote_sensor(function_handle, *args)
        return merge_local_remote(local, remote)

    def each_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each sensor.
        Also passes along the MySQLdb database connection.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        local = self.each_local_sensor_database(function_handle, *args)
        remote = self.each_remote_sensor_database(function_handle, *args)
        return merge_local_remote(local, remote)

    def each_sensor_table(self, table, function_handle, *args):
        '''
        Like each_sensor_database but execute the function ony for a sensor stored in a specific table.

        Args:
            conf: dict. Containing the sensor configuration
            function_handle: function. A function that must accept the following parameters: 
            conf (the sensor configuration), 
            database (the MySQLdb database connection), 
             *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self.each_sensor(self._force_table, table, function_handle, *args)

    def each_local_sensor(self, function_handle, *args):
        '''
        Execute a function for each local sensor.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self._loop_config('local_sensors', function_handle, *args)

    def each_local_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each local sensor.
        Also passes along the MySQLdb database connection.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self.each_local_sensor(self._with_database, function_handle, *args)

    def each_remote_sensor(self, function_handle, *args):
        '''
        Execute a function for each remote sensor.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
                conf (the sensor configuration),
                *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self._loop_config('remote_sensors', function_handle, *args)

    def each_remote_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each remote sensor.
        Also passes along the MySQLdb database connection.

        Args:
            function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self.each_remote_sensor(self._with_database, function_handle, *args)

    def each_push_server(self, function_handle, *args):
        '''
        Execute a function for each push sensor.

        Args:
            function_handle: A function that must accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.

        Returns:
            dict or None
        '''
        return self._loop_config('push_servers', function_handle, *args)

    def get_timespan(self, extension):
        '''
        Calculate the timespan in seconds from a database table extension string.

        Args:
            extension: string
        Returns:
            int
        '''
        if extension not in self.database["aggregateTables"]:
            raise LookupError("Unknown Extension: " + extension)
        unit_factors = {'s': 1, 'sec': 1, 'm': 60, 'min': 60, 'h': 3600, 'hour': 3600, 'd': 86400, 'day': 86400}
        number = int(float(re.findall(r'\d+', extension)[0]))
        try:
            unit = re.search(r'\d+(.*)$', extension).group(1)
        except AttributeError:
            raise LookupError("Could not identify unit of extension: " + extension)
        try:
            return number * unit_factors[unit]
        except AttributeError:
            raise LookupError("could not interpret unit: %s. Valid units are %s" %
                              (unit, ', '.join(unit_factors.keys())))

    def _loop_config(self, attrname, function_handle, *args):
        '''
        Execute a function for each element in an attribute.

        Args:
            attrname: string name of the PiTempLogConf attribute
            function_handle: A function that must accept the following parameters:
                conf (the sensor configuration),
                *additional parameters are possible.

        Returns:
            dict or None
        '''
        result = {}
        for conf in getattr(self, attrname).values():
            try:
                res = function_handle(conf, *args)
                try:
                    for key, value in res.items():
                        try:
                            result[key].append(value)
                        except KeyError:
                            result[key] = [value]
                except AttributeError:
                    pass
            except Exception as e:
                log.debug("{function} caused an error: ".format(function=function_handle.__name__), exc_info=True)
                log.info("{function} caused an error: {error}".format(function=function_handle.__name__, error=e))
        return result

    def _with_database(self, conf, function_handle, *args):
        '''
        Passthrough function used by the each_*_database functions.

        Args:
            conf: dict. Containing the sensor configuration
            function_handle: function. A function that must accept the following parameters: 
            conf (the sensor configuration), 
                database (the MySQLdb database connection), 
                table (the name of the table to be processed)
                *additional parameters are possible.

        Returns:
            dict or None
        '''
        if conf["enabled"] == "true":
            return function_handle(conf, self.dbh, conf["table"], *args)

    def _force_table(self, conf, table, function_handle, *args):
        '''
        Passthrough function used by the each_*_table functions.

        Args:
            conf: dict. Containing the sensor configuration
            table: string. Only this table will be procerssed
            function_handle: function handle. A function that must accept the following parameters: 
                conf (the sensor configuration), 
                database (the MySQLdb database connection), 
                *additional parameters are possible

        Returns:
            dict or None
        '''
        if conf["table"] == table and conf["enabled"] == "true":
            return function_handle(conf, self.dbh, *args)

    def __repr__(self):
        return pformat(vars(self), indent=4, width=1)

    def __eq__(self, other):
        if not isinstance(other, PiTempLogConf):
            # don't attempt to compare against unrelated types
            return NotImplemented
        return self.database == other.database and self.local_sensors == other.local_sensors and self.remote_sensors == other.remote_sensors and self.push_servers == other.push_servers


def merge_local_remote(local, remote):
    '''
    Merge the results returned by PiTempLogConf.each_local() and PiTempLogConf.each_remote()

    Args:
        local: dict containing arrays of results
        remote: dict containing arrays of results
    Returns: dict containing arrays of results
    '''
    result = local.copy()
    for key, array in remote.items():
        try:
            result[key] = local[key] + array
        except KeyError:
            result[key] = array
    return result


def calculate_partition_borders(database, table):
    '''
    Calculate partition borders for partitioning the mysql database tables. Partition borders will correspond to timestamps saturday at 24:00 h.

    Args:
        database: MySQLdb database cursor
        table: string
    Returns:
        now: int unix timestamp for current time
        one_year: int number of seconds in one year
        two_weeks: int number of seconds in two weeks
        cur_interval: int unix timestamp corresponding to the first saturday in
    '''
    with database as cur:
        query = "SELECT time FROM " + table + " ORDER BY time ASC LIMIT 1"
        cur.execute(query)
        rows = cur.fetchall()
    now = int(time.time())
    if not rows:
        first_time = now
    else:
        first_time = int(rows[0][0])
    one_year = 366 * 24 * 3600
    if (now - first_time) > one_year:  # if first logged time is older than one year
        first_interval = now - one_year
    else:
        first_interval = first_time
    first_date_time = datetime.date.fromtimestamp(first_interval)
    first_saturday_date = first_date_time + datetime.timedelta((5 - first_date_time.weekday()) % 7)
    first_saturday = (first_saturday_date - datetime.date(1970, 1, 1)).total_seconds()
    two_weeks = int(14 * 24 * 3600)
    cur_interval = int(first_saturday)
    return now, one_year, two_weeks, cur_interval


def get_sensor_dir():
    '''
    Get the sensor dir from the environment or use the default value.
    '''
    return os.environ.get('SENSOR_DIR', '/sys/bus/w1/devices/')


def get_sensor_temperature(sensor):
    try:
        with open(get_sensor_dir() + sensor + "/w1_slave") as tfile:
            text = tfile.read()
            temperature_data = text.split('=')[-1]
            return float(temperature_data) / 1000
    except FileNotFoundError:
        return 'Error'


def get_sensor_page_filename(table):
    return "{date}-{table}-temperatures.html".format(date=datetime.date.today().isoformat(), table=table)


def delete_category_path(conf, basepath):
    category_path = os.path.join(basepath, conf["category"])
    if os.path.abspath(category_path) == os.path.abspath(basepath):
        log.error("Refusing to delete basepath: " + category_path)
        return
    if not category_path.startswith(basepath):
        log.error("Refusing to delete outside basepath: " + category_path)
        return
    if os.path.exists(category_path):
        log.info("Deleting: " + category_path)
        shutil.rmtree(category_path, True)


log = logging.getLogger(__name__)
c_handler = logging.StreamHandler()
debug = int(float(os.environ.get('PITEMPLOG_DEBUG', '0')))
if debug > 0:
    c_handler.setLevel(logging.INFO)
    if debug > 1:
        c_handler.setLevel(logging.DEBUG)
log.addHandler(c_handler)
