import os
import sys
import json
import MySQLdb
import time
import datetime
import re
from pprint import pprint


def get_sensor_dir():
    return os.environ.get('SENSOR_DIR', '/sys/bus/w1/devices/')


def get_lock_file(table, extension):
    return "/tmp/" + table + extension + "_lock"


def modify_tables(function_handle):
    config = PiTempLogConf()
    config.db_open()
    if len(sys.argv) > 2:
        config.each_sensor_table(sys.argv[2], function_handle, sys.argv[2], sys.argv[1])
    elif len(sys.argv) > 1:
        config.each_sensor_database(function_handle, sys.argv[1])
    config.db_close()


def is_table_locked(table, extension):
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
    lock_file = get_lock_file(table, extension)
    with open(lock_file, 'a'):
        os.utime(lock_file, None)
    return lock_file


def calculate_partition_borders(database, table):
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


class PiTempLogConf:
    def __init__(self, config_file_path='/var/www/conf/config.json'):
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
            self.version = float(config['version'])
        except KeyError:
            self.version = 2.0
        self.log('processed config:', self)

    def db_open(self):
        self.dbh = MySQLdb.connect(
            host=self.database["host"], user=self.database["user"], passwd=self.database["pw"], db=self.database["db"])

    def db_commit(self):
        self.dbh.commit()

    def db_close(self):
        self.dbh.close()

    def get_timespan(self, extension):
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
            raise LookupError("could not interpret unit: %s. Valid units are %s" % (unit, ', '.join(unit_factors.keys())))

    def log(self, message, obj, level=1):
        if level <= self.debug:
            print(message)
            pprint(obj)

    def each_local_sensor(self, function_handle, *args):
        '''
        Execute a function for each local sensor.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.
        '''
        for conf in self.local_sensors.itervalues():
            try:
                function_handle(conf, *args)
            except Exception as e:
                print(e)

    def each_local_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each local sensor.
        Also passes along the MySQLdb database connection.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.
        '''
        self.each_local_sensor(self._with_database, function_handle, *args)

    def each_remote_sensor(self, function_handle, *args):
        '''
        Execute a function for each remote sensor.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.
        '''
        for conf in self.remote_sensors.itervalues():
            try:
                function_handle(conf, *args)
            except Exception as e:
                print(e)

    def each_remote_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each remote sensor.
        Also passes along the MySQLdb database connection.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.
        '''
        self.each_remote_sensor(self._with_database, function_handle, *args)

    def each_sensor(self, function_handle, *args):
        '''
        Execute a function for each sensor.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.
        '''
        self.each_local_sensor(function_handle, *args)
        self.each_remote_sensor(function_handle, *args)

    def each_sensor_database(self, function_handle, *args):
        '''
        Execute a function for each sensor.
        Also passes along the MySQLdb database connection.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            database (the MySQLdb database connection),
            table (the name of the table to be processed)
            *additional parameters are possible.
        '''
        self.each_local_sensor_database(function_handle, *args)
        self.each_remote_sensor_database(function_handle, *args)

    def each_sensor_table(self, table, function_handle, *args):
        '''
        Passthrough function used by the each_*_database functions.

        @param conf: dict. Containing the sensor configuration
        @param function_handle: function. A function that must accept the following parameters: 
            conf (the sensor configuration), 
            database (the MySQLdb database connection), 
             *additional parameters are possible.
        '''
        self.each_sensor(self._force_table, table, function_handle, *args)

    def each_push_server(self, function_handle, *args):
        '''
        Execute a function for each push sensor.

        @param function_handle: function handle. A function that mus accept the following parameters:
            conf (the sensor configuration),
            *additional parameters are possible.
        '''
        for conf in self.push_servers.itervalues():
            function_handle(conf, *args)

    def _with_database(self, conf, function_handle, *args):
        '''
        Passthrough function used by the each_*_database functions.

        @param conf: dict. Containing the sensor configuration
        @param function_handle: function. A function that must accept the following parameters: 
            conf (the sensor configuration), 
            database (the MySQLdb database connection), 
            table (the name of the table to be processed)
            *additional parameters are possible.
        '''
        if conf["enabled"] == "true":
            try:
                function_handle(conf, self.dbh, conf["table"], *args)
            except AttributeError:
                self.open_database()
                function_handle(conf, self.dbh, conf["table"], *args)

    def _force_table(self, conf, table, function_handle, *args):
        '''
        Passthrough function used by the each_*_table functions.

        @param conf: dict. Containing the sensor configuration
        @param table: string. Only this table will be procerssed
        @param function_handle: function handle. A function that must accept the following parameters: 
            conf (the sensor configuration), 
            database (the MySQLdb database connection), 
            *additional parameters are possible
        '''
        if conf["table"] == table and conf["enabled"] == "true":
            try:
                function_handle(conf, self.dbh, *args)
            except AttributeError:
                self.open_database()
                function_handle(conf, self.dbh, *args)

    def __repr__(self):
        from pprint import pformat
        return pformat(vars(self), indent=4, width=1)
