#!/usr/bin/python
import time
import json
import urllib2
from contextlib import closing
import xml.etree.ElementTree as ET
import os
# from pprint import pprint

import pitemplog


def save_temperature(conf, database, temperature):
    temperature += float(conf["calibration"])
    timestamp = int(time.time())
    cur = database.cursor()
    query = "INSERT INTO `%s` (time, temp) VALUES (%d, %f)" % (conf["table"], timestamp, temperature)
    cur.execute(query)
    cur.close


def get_xml_temperatures(url, username, pw):
    passman = urllib2.HTTPPasswordMgrWithDefaultRealm()
    passman.add_password(None, url, username, pw)
    urllib2.install_opener(urllib2.build_opener(urllib2.HTTPBasicAuthHandler(passman)))
    req = urllib2.Request(url)
    temperatures = []
    with closing(urllib2.urlopen(req)) as xml_temperatures:
        data = xml_temperatures.read()
    root = ET.fromstring(data)
    for i in xrange(1, 10):  # more than ten sensors are not supported at the moment (see also below)
        sensor = root.find('temp' + str(i))
        if sensor != None:
            temp = sensor.text
            temperatures.append(float(temp))
        else:
            break
    return temperatures


def get_remote_temperature(conf, database, table):
    if "extparser" in conf and conf["extparser"] != "none":
        try:
            temperatures
        except NameError:
            temperatures = get_xml_temperatures(conf["exturl"], conf["extuser"], conf["extpw"])
        # the sensors in the mibi box are called temp1, temp2 ... tempN so for
        # less than 10 sensors this should work...
        temperature = temperatures[int(conf["sensor"][-1]) - 1]
    else:
        if '?' in conf["exturl"]:
            url = conf["exturl"].split('?')[-2]
        else:
            url = conf["exturl"]
        url += '?gettemp=' + urllib2.quote(conf["sensor"])
        with closing(urllib2.urlopen(url)) as external_box:
            temperature_data = external_box.read()
            temperature = float(temperature_data)
    save_temperature(conf, database, temperature)


def get_local_temperature(conf, database, table):
    sensordir = pitemplog.get_sensor_dir()
    with open(sensordir + conf["sensor"] + "/w1_slave") as tfile:
        text = tfile.read()
        temperature_data = text.split()[-1]
        temperature = float(temperature_data[2:])
        temperature = temperature / 1000
    save_temperature(conf, database, temperature)


def main():
    config = pitemplog.PiTempLogConf()
    config.db_open()

    if pitemplog.is_table_locked('templog', ''):
        return
    lock_file = pitemplog.lock_table('templog', '')

    config.each_local_sensor_database(get_local_temperature)
    config.each_remote_sensor_database(get_remote_temperature)
    config.db_commit()
    config.db_close()
    os.remove(lock_file)


if __name__ == "__main__":
    main()
