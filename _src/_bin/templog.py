#!/usr/bin/python
import time
import json
import urllib2
from contextlib import closing
import xml.etree.ElementTree as ET
import os
import sys
import MySQLdb
# from pprint import pprint

def getXMLTemperatures(url,username,pw):
    passman = urllib2.HTTPPasswordMgrWithDefaultRealm()
    passman.add_password(None, url, username, pw)
    urllib2.install_opener(urllib2.build_opener(urllib2.HTTPBasicAuthHandler(passman)))
    req = urllib2.Request(url)
    temperatures=[]
    with closing(urllib2.urlopen(req)) as xmlTemperatures:
        data = xmlTemperatures.read()
    root=ET.fromstring(data)
    for i in xrange(1, 10): #more than ten sensors are not supported at the moment (see also below)
        sensor=root.find('temp'+str(i))
        if sensor!=None:
            temp=sensor.text
            temperatures.append(float(temp))
        else:
            break
    return temperatures

with open('/var/www/conf/config.json') as config_file:
    config=json.load(config_file)
    
sensordir = os.environ.get('SENSOR_DIR', '/sys/bus/w1/devices/')

database = MySQLdb.connect(host=config["database"]["host"], user=config["database"]["user"], passwd=config["database"]["pw"], db=config["database"]["db"])

lockFile="/tmp/templog_lock"
if os.path.isfile(lockFile):
    twoHoursAgo = time.time() - 7200
    if os.stat(lockFile).st_ctime>twoHoursAgo:
        print("another process is logging temperatures aborting...")
        sys.exit()
    else:
        os.remove(lockFile)
with open(lockFile,'a'):
    os.utime(lockFile,None)
for sensor, conf in config.iteritems():
    if sensor != "database":
        if conf["enabled"]=="true":
            try:
                if "exturl" in conf:
                    #@todo: switch to using the local parsers 
                    if "extparser" in conf and conf["extparser"]!="none":
                        try:
                            temperatures
                        except NameError:
                            temperatures = getXMLTemperatures(conf["exturl"], conf["extuser"], conf["extpw"])
                        temperature = temperatures[int(sensor[-1])-1] #the sensors in the mibi box are called temp1, temp2 ... tempN so for less than 10 sensors this should work...
                    else:
                        if '?' in conf["exturl"]:
                            url = conf["exturl"].split('?')[-2]
                        else:
                            url = conf["exturl"]
                        url += '?gettemp=' + urllib2.quote(sensor)
                        with closing(urllib2.urlopen(url)) as externalBox:
                            temperature_data = externalBox.read()
                            temperature = float(temperature_data)
                else:
                    with open(sensordir+sensor+"/w1_slave") as tfile:
                        text = tfile.read()
                        temperature_data = text.split()[-1]
                        temperature = float(temperature_data[2:])
                        temperature = temperature / 1000
                temperature+=float(conf["calibration"])
                timestamp=int(time.time())
                cur = database.cursor()
                query = "INSERT INTO %s (time, temp) VALUES (%d, %f)" % (conf["table"], timestamp, temperature)
                cur.execute(query)
                cur.close
            except Exception as e:
                print(e)
database.commit()
database.close()
os.remove(lockFile)

