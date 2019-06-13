try:
    import unittest2 as unittest
except ImportError:
    import unittest
try:
    from unittest.mock import patch
except ImportError:
    from mock import patch
import sys
import pitemplog
import MySQLdb
import time
import os
import stat
import glob
import json
try:
    import urllib.request as urllib_request  # for python 3
    from urllib.parse import quote
#     from urllib.parse import urlencode
except ImportError:
    import urllib2 as urllib_request  # for python 2
#     from urllib import urlencode
    from urllib import quote
from contextlib import closing
from pprint import pprint


class TestTableLocking(unittest.TestCase):
    def setUp(self):
        cleanup_tmp()

    def tearDown(self):
        cleanup_tmp()

    def test_lock_file_path(self):
        lock = pitemplog.LockTable('table_extension')
        self.assertEqual(lock.lock_file, '/tmp/table_extension_lock', 'incorrect lock executable path')

    def test_table_locking(self):
        lock = pitemplog.LockTable('table_extension')
        with lock:
            self.assertTrue(lock.is_locked(), 'could not create or find lock executable')
            st = os.stat(lock.lock_file)
            self.assertTrue(bool(st.st_mode & stat.S_IWOTH), 'lock file is not world writable')
        self.assertFalse(lock.is_locked(), 'could not remove lock executable')


class TestPiTempLogConfEach(unittest.TestCase):
    @classmethod
    def setUpClass(self):
        self.config_files = glob.glob('lib/config_*.json')
        self.test_attributes = ['local_sensors', 'remote_sensors', 'push_servers']

    def test_class_init(self):
        for file in self.config_files:
            pi = pitemplog.PiTempLogConf(file)
            with open(file) as config_file:
                config = json.load(config_file)
            for attr in self.test_attributes:
                self.assertEqual(len(getattr(pi, attr)), len(config[attr]))

    def test_each_local_sensor(self):
        def test_fun(conf): return {'sensor': conf['sensor']}
        pi = pitemplog.PiTempLogConf('lib/config_local_sensors.json')
        result = pi.each_local_sensor(test_fun)
        self.assertEqual(len(result['sensor']), 3, 'number of returned sensor ids is wrong')

    def test_each_remote_sensor(self):
        def test_fun(conf): return {'exturl': conf['exturl']}
        pi = pitemplog.PiTempLogConf('lib/config_local_pull_sensors.json')
        result = pi.each_remote_sensor(test_fun)
        self.assertEqual(len(result['exturl']), 3, 'number of returned sensor ids is wrong')

    def test_each_sensor(self):
        def test_fun(conf): return {'sensor': conf['sensor']}
        pi = pitemplog.PiTempLogConf('lib/config_local_pull_push_sensors.json')
        result = pi.each_sensor(test_fun)
        self.assertEqual(len(result['sensor']), 9, 'number of returned sensor ids is wrong')

    def test_each_local_sensor_database(self):
        def test_fun(unused, dbh, unused1): return {'database': isinstance(dbh, MySQLdb.connections.Connection)}
        pi = pitemplog.PiTempLogConf('lib/config_local_sensors.json')
        result = pi.each_local_sensor_database(test_fun)
        self.assertEqual(len(result['database']), 3, 'number of returned sensor ids is wrong')
        for cnctn in result['database']:
            self.assertTrue(cnctn, 'not a database connection')

    def test_each_remote_sensor_database(self):
        def test_fun(unused, dbh, unused1): return {'database': isinstance(dbh, MySQLdb.connections.Connection)}
        pi = pitemplog.PiTempLogConf('lib/config_local_pull_sensors.json')
        result = pi.each_remote_sensor_database(test_fun)
        self.assertEqual(len(result['database']), 3, 'number of returned sensor ids is wrong')
        for cnctn in result['database']:
            self.assertTrue(cnctn, 'not a database connection')

    def test_each_sensor_database(self):
        def test_fun(unused, dbh, unused1): return {'database': isinstance(dbh, MySQLdb.connections.Connection)}
        pi = pitemplog.PiTempLogConf('lib/config_local_pull_sensors.json')
        result = pi.each_sensor_database(test_fun)
        self.assertEqual(len(result['database']), 6, 'number of returned sensor ids is wrong')
        for cnctn in result['database']:
            self.assertTrue(cnctn, 'not a database connection')

    def test_each_sensor_table(self):
        def test_fun(unused, dbh): return {'database': isinstance(dbh, MySQLdb.connections.Connection)}
        pi = pitemplog.PiTempLogConf('lib/config_local_pull_sensors.json')
        result = pi.each_sensor_table('temp3', test_fun)
        self.assertEqual(len(result['database']), 1, 'number of returned sensor ids is wrong')
        for cnctn in result['database']:
            self.assertTrue(cnctn, 'not a database connection')

    def test_each_push_server(self):
        def test_fun(conf): return {'url': conf['url']}
        pi = pitemplog.PiTempLogConf('lib/config_push_servers.json')
        result = pi.each_push_server(test_fun)
        self.assertEqual(len(result['url']), 1, 'number of returned urls is wrong')


class TestPiTempLogConfDb(unittest.TestCase):
    def setUp(self):
        self.pi = pitemplog.PiTempLogConf('lib/config_no_sensors.json')

    def tearDown(self):
        self.pi.db_close()

    def test_db_open(self):
        self.pi.db_open()
        self.assertTrue(isinstance(self.pi.dbh, MySQLdb.connections.Connection), 'could not connect to database')

    def test_db_close(self):
        self.pi.db_open()
        self.pi.db_close()
        self.assertFalse(hasattr(self.pi, 'dbh'), 'could not close database connection')

    def test_get_timespan(self):
        self.assertEqual(self.pi.get_timespan('_5min'), 300, 'wrong timespan for _5min')
        self.assertEqual(self.pi.get_timespan('_15min'), 900, 'wrong timespan for _15min')
        self.assertEqual(self.pi.get_timespan('_60min'), 3600, 'wrong timespan for _60min')


class TestTemplog(unittest.TestCase):
    @classmethod
    def setUpClass(self):
        self.local_tables = ['temp1', 'temp3']
        self.local_error_tables = ['error']
        self.remote_tables = ['temp4', 'temp6']
        self.remote_error_tables = ['temp5']

    def setUp(self):
        cleanup_tmp()
        reset_conf_database(config_file='lib/config_local_pull_sensors.json',
                            sql_file='lib/db_local_pull_sensors.sql')
        self.pi = pitemplog.PiTempLogConf()
        self.pi.db_open()
        self.now = int(time.time())
        execute_source_file('/usr/local/bin/templog.py')

    def tearDown(self):
        db_tear_down(self)

    def test_templog_local(self):
        for table in self.local_tables:
            with self.subTest(local_table=table):
                self.assertTrue(int(latest_timestamp(table, self.pi.dbh)[0]) >= self.now,
                                'templog.py failed to add data to database')

    def test_templog_local_error(self):
        for table in self.local_error_tables:
            with self.subTest(local_error_table=table):
                self.assertTrue(latest_timestamp(table, self.pi.dbh) == None,
                                'templog.py got a result for the error table')

    def test_templog_remote(self):
        for table in self.remote_tables:
            with self.subTest(remote_table=table):
                self.assertTrue(int(latest_timestamp(table, self.pi.dbh)[0]) >= self.now,
                                'templog.py failed to add data to database')

    def test_templog_remote_error(self):
        for table in self.remote_error_tables:
            with self.subTest(remote_error_table=table):
                self.assertTrue(latest_timestamp(table, self.pi.dbh) == None,
                                'templog.py got a result for the error table')


# TODO: test templog push to remote server (requires setting up a remote
# server that is already configured for pushing with a predefined api
# key).


class TestResetAggregate(unittest.TestCase):
    @classmethod
    def setUpClass(self):
        self.executable = '/usr/local/bin/reset_aggregates.py'
        self.local_tables = ['temp1', 'temp3']
        self.local_error_tables = ['error']
        self.config_file = 'lib/config_local_sensors.json'
        self.sql_file = 'lib/db_local_sensors_no_partitions.sql'
        self.table = 'temp1'
        self.ext = '_5min'
        self.unaffected_table = 'temp3' + self.ext

    def setUp(self):
        cleanup_tmp()
        reset_conf_database(config_file=self.config_file, sql_file=self.sql_file)
        db_set_up(self)

    def tearDown(self):
        db_tear_down(self)

    def is_success(self, table_name, database):
        return latest_timestamp(table_name, database) == None

    def test_table_extension(self):
        execute_with_args(self.executable, [self.ext, self.table])
        self.assertTrue(self.is_success(self.table + self.ext, self.pi.dbh),
                        '%s did not affect %s' % (self.executable, self.table + self.ext))
        self.assertFalse(self.is_success(self.unaffected_table, self.pi.dbh),
                         '%s was not table specific: %s' % (self.executable, self.table + self.ext))

    def test_extension(self):
        for ext in self.pi.database['aggregateTables']:
            execute_with_args(self.executable, [ext])
            for table in self.local_tables:
                self.pi.dbh.commit()
                with self.subTest(local_table=table, ext=ext):
                    self.assertTrue(self.is_success(table + ext, self.pi.dbh),
                                    "%s did not work on %s" % (self.executable, table + ext))


class TestTempAggregate(TestResetAggregate):
    @classmethod
    def setUpClass(self):
        super(TestTempAggregate, self).setUpClass()
        self.executable = '/usr/local/bin/tempaggregate.py'
        self.sql_file = 'lib/db_local_sensors_empty_aggregates.sql'

    def is_success(self, table_name, database):
        st = os.stat('/tmp/%s_last' % (self.table + self.ext))
        return (latest_timestamp(table_name, database) != None) and bool(st.st_mode & stat.S_IWOTH)


class TestPartitionDatabase(TestResetAggregate):
    @classmethod
    def setUpClass(self):
        super(TestPartitionDatabase, self).setUpClass()
        self.executable = '/usr/local/bin/partition_database.py'
        self.sql_file = 'lib/db_local_sensors_no_partitions.sql'
        self.unaffected_table = 'temp1'
        self.another_table = 'temp3'

    def is_success(self, table_name, *unused):
        query = "SELECT `PARTITION_ORDINAL_POSITION` FROM information_schema.partitions "
        query += "WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='%s' AND PARTITION_NAME IS NOT NULL"
        query = query % (self.pi.database['db'], table_name)
        with self.pi.dbh as cur:
            cur.execute(query)
            return len(cur.fetchall()) > 20

    def test_table(self):
        execute_with_args(self.executable, ["", self.table])
        self.assertTrue(self.is_success(self.table),
                        'could not partition: %s' % self.table)
        self.assertFalse(self.is_success(self.table + self.ext),
                         'partitioning was not table specific: %s' % self.table + self.ext)

    def test_all_tables(self):
        execute_with_args(self.executable, [])
        self.assertTrue(self.is_success(self.table),
                        'could not partition: %s' % self.table,)
        self.assertTrue(self.is_success(self.another_table),
                        'could not partition: %s' % self.another_table)
        self.assertTrue(self.is_success(self.table + self.ext),
                        'could not partition: %s' % (self.table + self.ext))
        self.assertTrue(self.is_success(self.another_table + self.ext),
                        'could not partition: %s' % (self.another_table + self.ext))


class APIBaseClass(unittest.TestCase):
    def setUp(self):
        cleanup_tmp()
        reset_conf_database(config_file='lib/config_local_sensors.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        execute_source_file('/usr/local/share/templog/_data/uninstall_pages.py')

    def tearDown(self):
        db_tear_down(self)
        execute_source_file('/usr/local/share/templog/_data/uninstall_pages.py')

    def _get_api(self, get_vars):
        url = self.url + '?' + get_vars
        with closing(urllib_request.urlopen(url)) as data_api:
            return data_api.read().decode()

    def _post_api(self, action, post_data):
        url = "%s?debug=3&action=%s" % (self.url, action)
        request = urllib_request.Request(url, recursive_urlencode(post_data))
        result = urllib_request.urlopen(request).read().decode()
        try:
            return json.loads(result)
        except ValueError:
            print(result)
            return None

    def _assert_success(self, result, field=None):
        self.assertEqual(result["status"], "success", "query was not successful")
        if field:
            self.assertEqual(result[field], getattr(self.pi, field),
                             'result {field} was not equal to local configuration')


class TestConfAPI(APIBaseClass):
    @classmethod
    def setUpClass(self):
        self.url = 'http://pitemplog/conf/conf.php'
        self.error_sensor = "11481"
        self.working_sensor = "15089"
        self.other_working_sensor = "16807"
        self.remote_working_sensor = "1692"
        self.push_sensor = "1692"
        self.push_sensor2 = "13159"
        self.local_table = 'temp1'
        self.merge_table = "temp4"
        self.editable_sensor_fields = ['name', 'table', 'category', 'comment']
        self.local_server = {u"url": u"http://pitemplog/data.php", u"name": u"Pitemplog"}
        self.external_server = {u"url": u"http://pitemplogext/data.php", u"name": u"Pitemplogext"}
        self.external_server_apikey = {u"url": u"http://pitemplogext/data.php",
                                       "apikey": "qG6vwBT5MQKQ2yRXqjMdJ6fuu/PsxXD9Sw52EfAQq1Q=", u"name": u"Pitemplogext"}
        self.maxDiff = None

    def test_get_db_config(self):
        result = json.loads(self._get_api('db_config'))
        self._assert_success(result)
        self.assertEqual(result["db_config"]["dbtest"], "OK", 'dbtest was not OK')
        self.assertEqual(result["db_config"]["aggregateTables"].values().sort(),
                         self.pi.database["aggregateTables"].sort(), 'aggregate table configuration was not equal')

    def test_get_local_sensors(self):
        result = json.loads(self._get_api('local_sensors'))
        self._assert_success(result, "local_sensors")

    def test_get_remote_sensors(self):
        reset_conf_database(config_file='lib/config_local_pull_sensors.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        result = json.loads(self._get_api('remote_sensors'))
        self._assert_success(result, "remote_sensors")

    def test_get_push_servers(self):
        reset_conf_database(config_file='lib/config_push_servers.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        result = json.loads(self._get_api('push_servers'))
        self._assert_success(result, "push_servers")

    def test_get_temperature(self):
        result = json.loads(self._get_api('temperature=' + self.working_sensor))
        self._assert_success(result)
        self.assertEqual(float(result["temperature"][:5]), pitemplog.get_sensor_temperature(
            self.working_sensor), 'got different temperatures')

    def test_edit_sensor(self):
        sensor_data_old, sensor_data_new = self._set_up_sensor_edit()
        for field in self.editable_sensor_fields:
            with self.subTest(field=field):
                sensor_data_new["table_old"] = sensor_data_new["table"]
                sensor_data_new[field] = u"edited"
                result = self._post_api('save_sensor', sensor_data_new)
                updated_config = pitemplog.PiTempLogConf()
                self._assert_success(result)
                self.assertEqual(result["local_sensors"][self.working_sensor]
                                 [field], "edited", 'response was not updated')
                sensor_data_new.pop("table_old")
                self.assertEqual(
                    sensor_data_new, updated_config.local_sensors[self.working_sensor], 'sensor was not updated in config file')
        self._assert_table_renamed(sensor_data_old["table"], sensor_data_new["table"])
        self._assert_table_renamed(sensor_data_old["table"] + "_5min", sensor_data_new["table"] + "_5min")
        self._assert_page_created(sensor_data_new)

    def test_table_used(self):
        sensor_data_old, sensor_data_new = self._set_up_sensor_edit()
        sensor_data_new["table"] = self.pi.local_sensors[self.other_working_sensor]["table"]
        result = self._post_api('save_sensor', sensor_data_new)
        updated_config = pitemplog.PiTempLogConf()
        self.assertEqual(result["status"], "error", "query did not return an error")
        self.assertEqual(self.pi, updated_config, 'config has changed')
        self._assert_mysql_tables_unchanged(sensor_data_old["table"], sensor_data_new["table"])

    def test_table_switch(self):
        sensor_data_old, sensor_data_new = self._set_up_sensor_edit('lib/db_local_pull_sensors.sql')
        sensor_data_new["table"] = self.merge_table
        result = self._post_api('save_sensor', sensor_data_new)
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertTrue(result["confirm"] != False, 'result did not aske for confirmation')
        self.assertEqual(updated_config.local_sensors[self.working_sensor]
                         ["table"], self.merge_table, 'config table has not changed')
        self._assert_mysql_tables_unchanged(sensor_data_old["table"], sensor_data_new["table"])
        self._assert_page_created(sensor_data_new)

    def test_table_merge(self):
        sensor_data_old, sensor_data_new = self._set_up_sensor_edit('lib/db_local_pull_sensors.sql')
        sensor_data_new["table"] = self.merge_table
        sensor_data_new["confirmed"] = 'true'
        result = self._post_api('save_sensor', sensor_data_new)
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertFalse(result["confirm"], 'result asked for confirmation that was already given')
        self.assertEqual(updated_config.local_sensors[self.working_sensor]
                         ["table"], self.merge_table, 'config table has not changed')
        self._assert_table_renamed(sensor_data_old["table"], sensor_data_new["table"])
        self._assert_table_renamed(sensor_data_old["table"] + "_5min", sensor_data_new["table"] + "_5min")

    def test_disable_sensor(self):
        result = self._post_api('disable_sensor', self.pi.local_sensors[self.working_sensor])
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertEqual(updated_config.local_sensors[self.working_sensor]
                         ["enabled"], "false", 'sensor was not disabled')

    def test_enable_sensor(self):
        reset_conf_database(config_file='lib/config_local_sensors_disabled.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        result = self._post_api('enable_sensor', self.pi.local_sensors[self.working_sensor])
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertEqual(updated_config.local_sensors[self.working_sensor]["enabled"], "true", 'sensor was not enabled')

    def test_delete_local_sensor(self):
        result = self._post_api('delete_sensor', self.pi.local_sensors[self.working_sensor])
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertIn(self.working_sensor, updated_config.local_sensors, 'local sensor should not have been deleted')

    def test_delete_remote_sensor(self):
        reset_conf_database(config_file='lib/config_local_pull_sensors.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        result = self._post_api('delete_sensor', self.pi.remote_sensors[self.remote_working_sensor])
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertNotIn(self.remote_working_sensor, updated_config.remote_sensors, 'remote sensor was not deleted')

    def test_create_pages(self):
        result = json.loads(self._get_api('action=create_pages'))
        self._assert_success(result)
        for sensor, conf in self.pi.local_sensors.items():
            with self.subTest(sensor=sensor, conf=json.dumps(conf)):
                self._assert_page_created(conf)

    def test_save_everything(self):
        temp_config = pitemplog.PiTempLogConf('lib/config_local_pull_push_sensors.json')
        all_sensors = pitemplog.merge_local_remote(temp_config.local_sensors, temp_config.remote_sensors)
        config = {"conf": json.dumps({"all_sensors": all_sensors,
                                      "push_servers": temp_config.push_servers})}
        reset_conf_database(config_file='lib/config_local_sensors.json', sql_file='lib/db_local_sensors.sql')
        result = self._post_api('save_everything', config)
        updated_config = pitemplog.PiTempLogConf()
        self._assert_success(result)
        self.assertEqual(temp_config, updated_config, 'not all data saved')
        for sensor, conf in all_sensors.items():
            with self.subTest(sensor=sensor, conf=json.dumps(conf)):
                self._assert_page_created(conf)

    def test_get_external(self):
        reset_conf_database(config_file='lib/config_local_pull_sensors.json', sql_file=None)
        self.pi = pitemplog.PiTempLogConf()
        result = self._post_api('get_external', self.local_server)
        self._assert_success(result)
        for sensor, config in result["external_config"].items():
            with self.subTest(sensor=sensor, config=json.dumps(config)):
                self.assertEqual(config.pop("exturl"),
                                 self.local_server["url"], 'wrong "exturl" for sensor "{sensor}"'.format(sensor=sensor))
                self.assertEqual(config.pop("extname"),
                                 self.local_server["name"], 'wrong "exturl" for sensor "{sensor}"'.format(sensor=sensor))
                self.assertEqual(config.pop(
                    "exttable"), self.pi.local_sensors[sensor]["table"], 'wrong "extable" for sensor "{sensor}"'.format(sensor=sensor))
        self.assertEqual(result["external_config"], self.pi.local_sensors,
                         'external_config did not match local sensors')

    def test_push_config(self):
        reset_conf_database(config_file=None, sql_file='lib/create_database.sql')
        result = self._post_api('push_config', self.external_server)
        self._assert_config_pushed(result)

    def test_save_new_push_server(self):
        reset_conf_database(config_file=None, sql_file='lib/create_database.sql')
        result = self._post_api('save_push_server', self.external_server_apikey)
        self._assert_config_pushed(result)

    def test_save_push_server(self):
        reset_conf_database(config_file='lib/config_push_servers_no_apikey.json', sql_file=None)
        result = self._post_api('save_push_server', self.external_server_apikey)
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        self.assertEqual(updated_config.push_servers[self.external_server["url"]]
                         ["apikey"], self.external_server_apikey["apikey"], 'apikey was not saved')

    def test_delete_push_server(self):
        reset_conf_database(config_file='lib/config_push_servers_no_apikey.json', sql_file=None)
        result = self._post_api('delete_push_server', self.external_server_apikey)
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        self.assertNotIn(self.external_server_apikey["url"],
                         updated_config.push_servers, 'failed to delete push server')

    def test_receive_push_config(self):
        source_config, config = self._set_up_receive_push()
        result = self._post_api('receive_push_config', config)
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        updated_config.db_open()
        remote_sensors = updated_config.remote_sensors.copy()
        for sensor, config in remote_sensors.items():
            with self.subTest(sensor=sensor, config=json.dumps(config)):
                self._assert_page_created(config)
                self.assertTrue(table_exists(config["table"], updated_config.dbh),
                                'table for push sensor was not created')
                self.assertEqual(config.pop("push"), "true",
                                 'push not set to "true" for sensor "{sensor}"'.format(sensor=sensor))
                config[u"push"] = u""  # tweaking value for easier comparison later
                self.assertNotEqual(config.pop("apikey"), '', 'no api key was generated')
                config[u"apikey"] = u""
        self.assertEqual(remote_sensors, source_config.remote_sensors,
                         'remote_sensors did not match pushed configuration')

    def test_receive_push_config_missing_parameter(self):
        result = self._post_api('receive_push_config', self.pi.local_sensors)
        self._assert_success(result)
        self.assertIn(self.working_sensor, result["remote_sensor_errors"], 'no error returned')

    def test_receive_push_config_table_collision(self):
        unused, config = self._set_up_receive_push()
        collision_table = self.pi.local_sensors[self.working_sensor]["table"]
        config[self.push_sensor]["table"] = collision_table
        config[self.push_sensor2]["table"] = collision_table
        result = self._post_api('receive_push_config', config)
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        self.assertEqual(updated_config.remote_sensors[self.push_sensor]["table"],
                         collision_table + '_01', 'first table collision was not handled correctly')
        self.assertEqual(updated_config.remote_sensors[self.push_sensor2]["table"],
                         collision_table + '_02', 'second table collision was not handled correctly')

    def test_add_new_push_sensor(self):
        reset_conf_database(config_file=None, sql_file='lib/create_database.sql')
        new_push_sensor_conf = pitemplog.PiTempLogConf('lib/config_local_new_push_sensors.json')
        new_push_sensor_conf.remote_sensors["newsensor"]["apikey"] = ""
        result = self._post_api('add_new_push_sensor', new_push_sensor_conf.remote_sensors["newsensor"])
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        self.assertIn("newsensor", updated_config.remote_sensors, 'push sensor was not added')
        self.assertTrue(updated_config.remote_sensors["newsensor"]["apikey"] != "", 'api key was not created')
        updated_config.remote_sensors["newsensor"]["apikey"] = ""
        self.assertEqual(updated_config.remote_sensors["newsensor"],
                         new_push_sensor_conf.remote_sensors["newsensor"], 'pushed config and saved config differ')
        self.pi.db_open()
        self.assertTrue(table_exists(
            new_push_sensor_conf.remote_sensors["newsensor"]["table"], self.pi.dbh), 'table was not created')

    def test_add_existing_push_sensor(self):
        reset_conf_database(config_file='lib/config_local_new_push_sensors.json', sql_file='lib/create_database.sql')
        self.pi = pitemplog.PiTempLogConf()
        result = self._post_api('add_new_push_sensor', self.pi.remote_sensors["newsensor"])
        self._assert_success(result)
        updated_config = pitemplog.PiTempLogConf()
        self.assertEqual(self.pi.remote_sensors, updated_config.remote_sensors, 'push sensor was changed')
        self.pi.db_open()
        self.assertFalse(table_exists(
            self.pi.remote_sensors["newsensor"]["table"], self.pi.dbh), 'table should not habe been created')

    def test_unknown_action(self):
        result = self._post_api('unknown_action', {})
        self.assertEqual(result["status"], "error", 'no error reported for unknown action')

    def _set_up_receive_push(self):
        reset_conf_database(config_file=None, sql_file='lib/db_local_sensors_no_partitions.sql')
        source_config = pitemplog.PiTempLogConf('lib/config_local_pull_sensors.json')
        config = {}
        for sensor, conf in source_config.remote_sensors.items():
            config[sensor] = conf.copy()
            for key, value in self.external_server.items():
                config[sensor]["ext" + key] = value
                config[sensor]["exttable"] = config[sensor]["table"]
        return source_config, config

    def _set_up_sensor_edit(self, sql_file='lib/db_local_sensors_no_partitions.sql'):
        reset_conf_database(config_file=None, sql_file=sql_file)
        self.pi.db_open()
        sensor_data_old = self.pi.local_sensors[self.working_sensor].copy()
        sensor_data_new = self.pi.local_sensors[self.working_sensor].copy()
        sensor_data_new["table_old"] = sensor_data_new["table"]
        return sensor_data_old, sensor_data_new

    def _assert_table_renamed(self, old_table, new_table):
        self.assertFalse(table_exists(old_table, self.pi.dbh), 'original table still exists')
        self.assertTrue(table_exists(new_table, self.pi.dbh), 'renamed table does not exist')
        self.assertGreater(latest_timestamp(new_table, self.pi.dbh), 0, 'renamed table does not contain data')

    def _assert_mysql_tables_unchanged(self, table_old, table_new):
        self.assertTrue(table_exists(table_old, self.pi.dbh), 'original table does not exist')
        self.assertTrue(table_exists(table_new, self.pi.dbh), 'renamed table does not exist')
        self.assertGreater(latest_timestamp(table_old, self.pi.dbh), 0, 'original table data lost')
        self.assertGreater(latest_timestamp(table_new, self.pi.dbh), 0, 'target table data lost')

    def _assert_page_created(self, sensor_data):
        import datetime
        self.assertTrue(os.path.isfile('/usr/local/share/templog/{category}/_posts/{filename}'.format(
            category=sensor_data["category"], filename=pitemplog.get_sensor_page_filename(sensor_data["table"]))), 'source html page not found')
        self.assertTrue(os.path.isfile('/var/www/html/{category}/{date}/{table}-temperatures.html'.format(
            category=sensor_data["category"], date=datetime.date.today().strftime('%Y/%m/%d'), table=sensor_data["table"]).lower()), 'final html page not found')

    def _assert_config_pushed(self, result):
        self._assert_success(result)
        self.assertTrue(len(result["push_servers"]) == 1,
                        'api returned more than one sensor after pushing just one sensor')
        updated_config = pitemplog.PiTempLogConf()
        self.assertEqual(updated_config.push_servers[self.external_server["url"]]
                         ["url"], self.external_server["url"], 'stored and pushed urls do not match')
        self.assertEqual(updated_config.push_servers[self.external_server["url"]]["name"],
                         self.external_server["name"], 'stored and pushed names do not match')
        updated_config.db_open()
        self.assertTrue(table_exists(self.local_table, updated_config.dbh),
                        'remote server failed to create table %s' % self.local_table)


class TestDataAPI(APIBaseClass):
    @classmethod
    def setUpClass(self):
        self.url = 'http://pitemplog/data.php'
        self.error_sensor = "11481"
        self.working_sensor = "15089"
        self.test_config = {"newsensor": {"table": "newtable",
                                          "sensor": "newsensor",
                                          "name": "New Sensor",
                                          "category": "NewCat",
                                          "exturl": "http://example.com",
                                          "extname": "New push"}
                            }
        self.now = int(time.time())
        self.test_data = {"sensor": ["newsensor"], "temp": [23.15], 'time': [
            int(time.time())], "apikey": "qG6vwBT5MQKQ2yRXqjMdJ6fuu/PsxXD9Sw52EfAQq1Q="}

    def test_gettemp(self):
        self.assertEqual(self._gettemp(self.working_sensor), pitemplog.get_sensor_temperature(
            self.working_sensor), 'temperature from local sensor and api do not match')

    def test_gettemp_error(self):
        self.assertRaises(ValueError, self._gettemp, self.error_sensor)

    def test_get_config(self):
        api_conf = pitemplog.PiTempLogConf('{"local_sensors":%s}' % self._get_api('config=get'))
        self.assertEqual(api_conf.local_sensors, self.pi.local_sensors, 'failed to get config from api')

    def test_push_config(self):
        reset_conf_database(config_file=None, sql_file='lib/db_local_sensors.sql')
        result = self._post_api("receive_push_config", self.test_config)
        self._assert_success(result)
        self.assertEqual(result["remote_sensors"]["newsensor"]["sensor"], self.test_config["newsensor"]
                         ["sensor"], 'data.php did not answer with the proper remote_sensor data')
        new_conf = pitemplog.PiTempLogConf()
        self.assertIn("newsensor", new_conf.remote_sensors, 'newsensor did not appear in updated configuration')
        new_conf.db_open()
        self.assertTrue(table_exists(self.test_config["newsensor"]["table"], new_conf.dbh),
                        'table "%s" not found in database' % self.test_config["newsensor"]["table"])

    def test_push_config_incomplete(self):
        reset_conf_database(config_file=None, sql_file='lib/db_local_sensors.sql')
        for field in self.test_config["newsensor"].keys():
            with self.subTest(field=field):
                incomplete_config = {"newsensor": self.test_config["newsensor"].copy()}
                incomplete_config["newsensor"].pop(field)
                result = self._post_api("receive_push_config", incomplete_config)
                if result["status"] == "success":
                    self.assertNotIn("newsensor", result["remote_sensors"],
                                     'data.php had "newsensor" in its remote_sensor response even though incomplete data was submitted')
                    self.assertIn(field, result["remote_sensor_errors"]["newsensor"],
                                  'data.php had "newsensor" in its remote_sensor response even though incomplete data was submitted')
                    new_conf = pitemplog.PiTempLogConf()
                    self.assertNotIn("newsensor", new_conf.remote_sensors,
                                     'newsensor should not appear in updated configuration')
                    new_conf.db_open()
                    self.assertFalse(table_exists(self.test_config["newsensor"]["table"], new_conf.dbh),
                                     'table "%s" was found in database' % self.test_config["newsensor"]["table"])
                else:
                    self.assertRegex(result["log"], "ABORTED: Cannot save sensor without a sensor id",
                                     'abort reason was something other than "Cannot save sensor without a sensor id"')

    def test_receive_push_temperatures(self):
        reset_conf_database(config_file='lib/config_local_new_push_sensors.json',
                            sql_file='lib/db_local_new_push_sensors.sql')
        self.pi = pitemplog.PiTempLogConf()
        self.pi.db_open()
        result = self._post_api("receive_push_temperatures", {'data': json.dumps(self.test_data)})
        self._assert_success(result)
        self.assertTrue(int(latest_timestamp('newtable', self.pi.dbh)[0]) >= self.now,
                        'api failed to write pushed data to database')

    def test_receive_push_temperatures_local_sensor(self):
        reset_conf_database(config_file='lib/config_local_new_push_sensors.json',
                            sql_file='lib/db_local_new_push_sensors.sql')
        self.pi = pitemplog.PiTempLogConf()
        self.pi.db_open()
        local_data = self.test_data.copy()
        local_data["sensor"] = ["15089"]
        result = self._post_api("receive_push_temperatures", {'data': json.dumps(local_data)})
        self._assert_success(result)
        self.assertFalse(int(latest_timestamp('temp1', self.pi.dbh)[0]) >= self.now,
                         'api wrote pushed data to database of local sensor')

    def test_receive_push_temperatures_wrong_api_key(self):
        reset_conf_database(config_file='lib/config_local_new_push_sensors.json',
                            sql_file='lib/db_local_new_push_sensors.sql')
        self.pi = pitemplog.PiTempLogConf()
        self.pi.db_open()
        data_wrong_apikey = self.test_data.copy()
        data_wrong_apikey["apikey"] = "this/is/wrong="
        result = self._post_api("receive_push_temperatures", {'data': json.dumps(data_wrong_apikey)})
        self._assert_success(result)
        self.assertRegex(result["remote_sensor_error"][data_wrong_apikey["sensor"][0]]["apikey"], "Wrong apikey",
                         'api did not respond with the correct error message')
        self.assertEqual(latest_timestamp('newtable', self.pi.dbh), None, 'api wrote pushed data despite wrong api key')

    def _gettemp(self, sensor):
        return float(self._get_api('gettemp=' + sensor))


# TODO: add selenium tests for front-end pages

# TODO: add selenium tests for configuration interface


def apply_sql_file(sql_file):
    pi = pitemplog.PiTempLogConf('lib/config_no_sensors.json')
    from subprocess import Popen, PIPE
    process = Popen(['mysql', pi.database['db'], '-h',  pi.database['host'], '-u' + pi.database['user'],
                     '-p' + pi.database['pw']], stdout=PIPE, stdin=PIPE)
    output = process.communicate('source ' + sql_file)[0]
    print(output)


def table_exists(table, dbh):
    with dbh as cursor:
        query = "SHOW TABLES LIKE '%s'" % table
        cursor.execute(query)
        return cursor.fetchone() != None


def latest_timestamp(table, dbh):
    with dbh as cursor:
        query = "SELECT time FROM `%s` ORDER BY time DESC LIMIT 1" % table
        cursor.execute(query)
        return cursor.fetchone()


def reset_conf_database(config_file='lib/config.json', sql_file='lib/create_database.sql'):
    if config_file:
        from shutil import copyfile
        copyfile(config_file, '/var/www/html/conf/config.json')
        copyfile(config_file, '/usr/local/share/templog/_data/config.json')
    if sql_file:
        apply_sql_file(sql_file)


def db_set_up(self):
    self.pi = pitemplog.PiTempLogConf()
    self.pi.db_open()


def db_tear_down(self):
    self.pi.db_close()
    reset_conf_database()
    cleanup_tmp()


def execute_source_file(filename):
    try:
        from imp import load_source
    except ImportError:
        from importlib.util import spec_from_file_location as load_source
    templog_src = load_source('templog_src', filename)
    templog_src.main()


def execute_with_args(file, args):
    with patch.object(sys, 'argv', [file] + args):
        execute_source_file(file)


def cleanup_tmp():
    for file in glob.glob("/tmp/*_lock"):
        os.remove(file)
    for file in glob.glob("/tmp/*_last"):
        os.remove(file)


def recursive_urlencode(d):
    """URL-encode a multidimensional dictionary.

    >>> data = {'a': 'b&c', 'd': {'e': {'f&g': 'h*i'}}, 'j': 'k'}
    >>> recursive_urlencode(data)
    u'a=b%26c&j=k&d[e][f%26g]=h%2Ai'
    """
    def recursion(d, base=[]):
        pairs = []

        for key, value in d.items():
            new_base = base + [key]
            if hasattr(value, 'values'):
                pairs += recursion(value, new_base)
            else:
                new_pair = None
                if len(new_base) > 1:
                    first = quote(new_base.pop(0))
                    rest = map(lambda x: quote(x), new_base)
                    new_pair = "%s[%s]=%s" % (first, ']['.join(rest), quote(value))
                else:
                    new_pair = "%s=%s" % (quote(key), quote(value))
                pairs.append(new_pair)
        return pairs

    return '&'.join(recursion(d))
