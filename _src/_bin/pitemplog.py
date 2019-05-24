import os
import sys
import json
import MySQLdb
import time
import datetime
import re
from pprint import pprint


def get_sensor_dir():
    '''
    Get the sensor dir from the environment or use the default value.
    '''
    return os.environ.get('SENSOR_DIR', '/sys/bus/w1/devices/')


def get_lock_file(table, extension):
    '''
    Get the lock file name for a specific table and extension.
    Args:
        table: string
        extension: string
    '''
    return "/tmp/" + table + extension + "_lock"


def is_table_locked(table, extension):
    '''
    Check if a table is locked. Remove stale lockfiles.

    Args:
        table: string
        extension: string
    '''
    lock_file = get_lock_file(table, extension)
    if os.path.isfile(lock_file):
        two_hours_ago = time.time() - 7200
        if os.stat(lock_file).st_ctime > two_hours_ago:
            print("another process is working on: " + table + extension + " aborting...")
            return True
        else:
            os.remove(lock_file)
    return False


def lock_table(table, extension):
    '''
    Create lock file for a table.
    Args:
        table: string
        extension: string
    '''
    lock_file = get_lock_file(table, extension)
    with open(lock_file, 'a'):
        os.utime(lock_file, None)
    return lock_file


def calculate_partition_borders(database, table):
    '''
    Calculate partition borders for partitioning the mysql database tables. Partition borders will correspond to timestamps saturday at 24:00 h.

    Args:
        database: string
        table: string
    Returns:
        cur: database cursor
        now: int unix timestamp for current time
        one_year: int number of seconds in one year
        two_weeks: int number of seconds in two weeks
        cur_interval: int unix timestamp corresponding to the first saturday in
    '''
    cur = database.cursor()
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
    return cur, now, one_year, two_weeks, cur_interval


def merge_local_remote(local, remote):
    '''
    Merge the results returned by PiTempLogConf.each_local() and PiTempLogConf.each_remote()

    Args:
        local: dict containing arrays of results
        remote: dict containing arrays of results
    Returns: dict containing arrays of results
    '''
    for key, array in remote:
        try:
            local[key] = local[key] + array
        except KeyError:
            local[key] = array
    return local


class PiTempLogConf:
    '''
    Read the pitemplog config file and perform operations on the database and sensors defined there.

    Instance variables:
        debug: int debugging level
        database: dict database configuration (taken both from environment variables and the config file)
        dbh: database handle object
        local_sensors: dict local sensor configuration
        remote_sensors: dict remote sensor configuration
        push_servers: dict push server configuration
        versio: string version number

    Public methods:
        db_open
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
        log: print debugging messages depending on the debug level
    '''

    def __init__(self, config_file_path='/var/www/conf/config.json'):
        '''
        Initialize a PiTempLogConf class from a json config file.

        Args:
            config_file_path: string path to the config file
        '''
        self.debug = int(float(os.environ.get('PITEMPLOG_DEBUG', '0')))
        self.database = {
            'host': os.environ.get('DB_HOST', 'localhost'),
            'db': os.environ.get('DB_DB', 'temperatures'),
            'user': os.environ.get('DB_USER', 'temp'),
            'pw': os.environ.get('DB_PW', 'temp'),
            'aggregateTables': ['_5min', "_15min", "_60min"]
        }
        with open(config_file_path) as config_file:
            config = json.load(config_file)
        self.log('raw config from file:', config, 3)
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
        self.log('processed config:', self)

    def db_open(self):
        self.dbh = MySQLdb.connect(
            host=self.database["host"], user=self.database["user"], passwd=self.database["pw"], db=self.database["db"])

    def db_commit(self):
        try:
            self.dbh.commit()
        except AttributeError:
            print('Error: Nothing to commit, open a database connection and do some queries first.')

    def db_close(self):
        try:
            self.dbh.close()
            del self.dbh
        except AttributeError:
            pass

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
        return local.update(remote)

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
        return local.update(remote)

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

    def log(self, message, obj, level=1):
        '''
        Log messages to stdout.

        Args:
            message: string. The log message
            obj: mixed. The content of this variable is pprinted below the log message
            level: int. Log level. Printing will happen only if the current debug level 
                is higher than the log level.
        '''
        if level <= self.debug:
            print(message)
            pprint(obj)

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
        for conf in getattr(self, attrname).itervalues():
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
                print(e)
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
            try:
                return function_handle(conf, self.dbh, conf["table"], *args)
            except AttributeError:
                self.db_open()
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
            try:
                return function_handle(conf, self.dbh, *args)
            except AttributeError:
                self.db_open()
                return function_handle(conf, self.dbh, *args)

    def __repr__(self):
        from pprint import pformat
        return pformat(vars(self), indent=4, width=1)
