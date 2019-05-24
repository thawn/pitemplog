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
import os
from pprint import pprint

import pitemplog


def get_local_temperature(conf, database, table):
    sensordir = pitemplog.get_sensor_dir()
    with open(sensordir + conf["sensor"] + "/w1_slave") as tfile:
        text = tfile.read()
        temperature_data = text.split()[-1]
        temperature = float(temperature_data[2:])
        temperature = temperature / 1000
    return save_temperature(conf, database, table, temperature)


def get_remote_temperature(conf, database, table):
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
    cur = database.cursor()
    query = "INSERT INTO `%s` (time, temp) VALUES (%d, %f)" % (table, timestamp, temperature)
    cur.execute(query)
    cur.close
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
    post_data = {'data': json.dumps(data)}
    request = urllib_request.Request("%s?action=receive_push_temperatures" % conf['url'], urlencode(post_data).encode())
    result = urllib_request.urlopen(request).read().decode()
    try:
        json_result = json.loads(result)
        if json_result["status"] != 'success':
            print('Api error: \n')
            pprint(json_result)
    except ValueError:
        print('Api error: \n' + result)


def main():
    config = pitemplog.PiTempLogConf()
    config.db_open()

    if pitemplog.is_table_locked('templog', ''):
        return
    lock_file = pitemplog.lock_table('templog', '')

    temperature_data = config.each_local_sensor_database(get_local_temperature)
    config.each_push_server(push_temperature, temperature_data)
    config.each_remote_sensor_database(get_remote_temperature)
    config.db_commit()
    config.db_close()
    os.remove(lock_file)


if __name__ == "__main__":
    main()
