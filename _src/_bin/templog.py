#!/usr/bin/python
import time
import json
try:
    import urllib.request as urllib_request  # for python 3
    from urllib.parse import urlencode
except ImportError:
    import urllib2 as urllib_request  # for python 2
    from urllib import urlencode
from contextlib import closing
import xml.etree.ElementTree as ET
import argparse
from pprint import pformat

import pitemplog


def get_local_temperature(conf, database, table):
    return save_temperature(conf, database, table, pitemplog.get_sensor_temperature(conf["sensor"]))


def get_remote_temperature(conf, database, table):
    if conf["push"] == 'true':
        raise ValueError('')
    if "extparser" in conf and conf["extparser"] != "none":
        temperatures = get_xml_temperatures(conf["exturl"], conf["extuser"], conf["extpw"])
        # the sensors in the mibi box are called temp1, temp2 ... tempN so for
        # less than 10 sensors this should work...
        temperature = temperatures[int(conf["sensor"][-1]) - 1]
    else:
        if '?' in conf["exturl"]:
            url = conf["exturl"].split('?')[-2]
        else:
            url = conf["exturl"]
        url += '?gettemp=' + urllib_request.quote(conf["sensor"])
        with closing(urllib_request.urlopen(url)) as external_box:
            temperature_data = external_box.read()
            temperature = float(temperature_data)
    return save_temperature(conf, database, table, temperature)


def save_temperature(conf, database, table, temperature):
    temperature += float(conf["calibration"])
    timestamp = int(time.time())
    query = "INSERT INTO `%s` (time, temp) VALUES (%d, %f)" % (table, timestamp, temperature)
    with database as cur:
        cur.execute(query)
    return {"sensor": conf["sensor"], "time": timestamp, "temp": temperature}


def get_xml_temperatures(url, username, pw):
    passman = urllib_request.HTTPPasswordMgrWithDefaultRealm()
    passman.add_password(None, url, username, pw)
    urllib_request.install_opener(urllib_request.build_opener(urllib_request.HTTPBasicAuthHandler(passman)))
    req = urllib_request.Request(url)
    temperatures = []
    with closing(urllib_request.urlopen(req)) as xml_temperatures:
        data = xml_temperatures.read()
    root = ET.fromstring(data)
    for i in list(range(1, 10)):  # more than ten sensors are not supported at the moment (see also below)
        sensor = root.find('temp' + str(i))
        if sensor != None:
            temp = sensor.text
            temperatures.append(float(temp))
        else:
            break
    return temperatures


def push_temperature(conf, data):
    data["apikey"] = conf["apikey"]
    post_data = {"data": json.dumps(data)}
    url = "%s?action=receive_push_temperatures" % conf["url"]
    pitemplog.log.debug('contacting url: %s with data: \n' % url)
    pitemplog.log.debug(pformat(post_data))
    request = urllib_request.Request(url, urlencode(post_data).encode())
    result = urllib_request.urlopen(request).read().decode()
    try:
        json_result = json.loads(result)
        if json_result["status"] != 'success':
            pitemplog.log.warning('Api error: \n')
            pitemplog.log.warning(json_result.pop("log"))
            pitemplog.log.warning(pformat(json_result))
        else:
            pitemplog.log.debug('result: \n')
            pitemplog.log.debug(json_result.pop("log"))
            pitemplog.log.debug(pformat(json_result))
    except ValueError:
        pitemplog.log.warning('Api error: \n' + result)


def main():
    config = pitemplog.PiTempLogConf()
    lock = pitemplog.LockTable('templog')
    if not lock.is_locked():
        with lock:
            config.db_open()
            temperature_data = config.each_local_sensor_database(get_local_temperature)
            config.each_push_server(push_temperature, temperature_data)
            config.each_remote_sensor_database(get_remote_temperature)
            config.db_close()


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument(
        '-d', '--debug',
        help="Print lots of debugging statements",
        action="store_const", dest="log_level", const=10,
        default=30,
    )
    parser.add_argument(
        '-v', '--verbose',
        help="Be verbose",
        action="store_const", dest="log_level", const=20,
    )
    args = parser.parse_args() 
    pitemplog.log.setLevel(args.log_level)   
    main()
