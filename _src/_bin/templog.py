#!/usr/bin/python
import time
import peewee
from peewee import *
import json
import urllib2
from contextlib import closing
import xml.etree.ElementTree as ET
import os
import sys
#from pprint import pprint

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

database = MySQLDatabase(config["database"]["db"], **{'host': 'localhost', 'password': config["database"]["pw"], 'port': 3306, 'user': config["database"]["user"]})

class UnknownField(object):
    pass

class BaseModel(Model):
    class Meta:
        database = database

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
                class Temperatures(BaseModel):
                    temp = FloatField(null=True)
                    time = IntegerField(null=True)
                    
                    class Meta:
                        db_table = conf["table"]
                if "url" in conf:
                    if conf["urlparser"]!="none":
                        try:
                            temperatures
                        except NameError:
                            temperatures = getXMLTemperatures(conf["url"], conf["urlusername"], conf["urlpw"])
                        temperature = temperatures[int(sensor[-1])-1] #the sensors in the mibi box are called temp1, temp2 ... tempN so for less than 10 sensors this should work...
                    else:
                        url=conf["url"].split('?')[-2]+'?gettemp='+sensor[3:]
                        with closing(urllib2.urlopen(url)) as externalBox:
                            temperature_data = externalBox.read()
                            temperature = float(temperature_data)
                else:
                    with open("/sys/bus/w1/devices/"+sensor+"/w1_slave") as tfile:
                        text = tfile.read()
                        temperature_data = text.split()[-1]
                        temperature = float(temperature_data[2:])
                        temperature = temperature / 1000
                temperature+=float(conf["calibration"])
                timestamp=int(time.time())
                Temp = Temperatures(temp=temperature, time=timestamp)
                Temp.save()
            except Exception, e:
                print(e)
os.remove(lockFile)

