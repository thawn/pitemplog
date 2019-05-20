#!/usr/bin/python
import time
import datetime
import json
import MySQLdb
import sys
import os
from pprint import pprint

with open('/var/www/conf/config.json') as config_file:
    config=json.load(config_file)

database = MySQLdb.connect(host=config["database"]["host"], user=config["database"]["user"], passwd=config["database"]["pw"], db=config["database"]["db"])

def partitionDB (database,table,extension):
    print(str(datetime.datetime.now())+" Partitioning: "+table+extension)
    #create the lock file
    lockFile="/tmp/"+table+extension+"_lock"
    with open(lockFile,'a'):
        os.utime(lockFile,None)
    cur=database.cursor()
    query="SELECT time FROM "+table+extension+" ORDER BY time ASC LIMIT 1"
    cur.execute(query)
    rows=cur.fetchall()
    now=int(time.time())
    if not rows:
        firstTime=now
    else:
        firstTime=int(rows[0][0])
    oneYear=366*24*3600;
    if (now-firstTime)>oneYear: #if first logged time is older than one year
        firstInterval=now-oneYear
    else:
        firstInterval=firstTime
    firstDateTime=datetime.date.fromtimestamp(firstInterval)
    firstSaturdayDate=firstDateTime + datetime.timedelta( (5-firstDateTime.weekday()) % 7 )
    firstSaturday=(firstSaturdayDate-datetime.date(1970,1,1)).total_seconds()
    twoWeeks=int(14*24*3600)
    curInterval=int(firstSaturday)
    count=int(0)
    query="ALTER TABLE "+table+extension+" PARTITION BY RANGE (time) ("
    while curInterval<(now+oneYear):
        query+=" PARTITION p"+str(count)+" VALUES LESS THAN ("+str(curInterval)+"),"
        curInterval+=twoWeeks
        count+=int(1)
    query+=" PARTITION p"+str(count)+" VALUES LESS THAN MAXVALUE);"
    #pprint(query)
    cur.execute(query)
    database.commit()
    cur.close()
    os.remove(lockFile)
    print(str(datetime.datetime.now())+" Done.")

if 'local_sensors' in config:
  if 'remote_sensors' in config and isinstance(config['remote_sensors'], dict):
    configs = config['local_sensors'].copy()
    configs.update(config['remote_sensors'])
  else:
        configs = config['local_sensors']
if len(sys.argv)>2:
    for sensor, conf in configs.iteritems():
        if sensor != "database":
            if conf["enabled"]=="true":
                if conf["table"]==sys.argv[2]:
                    partitionDB(database,sys.argv[2],sys.argv[1])
else:
    for sensor, conf in configs.iteritems():
        if sensor != "database":
            if conf["enabled"]=="true":
                if len(sys.argv)>1:
                    partitionDB(database,conf["table"],sys.argv[1])
                else:
                    partitionDB(database,conf["table"],'')
                    for extension in config["database"]["aggregateTables"]:
                        partitionDB(database,conf["table"],extension)
database.close()
