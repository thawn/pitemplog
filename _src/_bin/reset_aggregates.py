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

database = MySQLdb.connect(host='localhost', user=config["database"]["user"], passwd=config["database"]["pw"], db=config["database"]["db"])

def resetDB (database,table,extension):
    print(str(datetime.datetime.now())+" Resetting: "+table+extension)
    if not extension:
        print("Extension is empty, refusing to reset main table! Nothing done.")
        return
    #create the lock file
    lockFile="/tmp/"+table+extension+"_lock"
    with open(lockFile,'a'):
        os.utime(lockFile,None)
    cur=database.cursor()
    query="SELECT time FROM "+table+" ORDER BY time ASC LIMIT 1"
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
    query="DROP TABLE "+table+extension
    cur.execute(query)
    database.commit()    
    query="CREATE TABLE "+table+extension+"(`time` int(11) DEFAULT NULL,`temp` float DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1 PARTITION BY RANGE (time) ("
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

if len(sys.argv)>2:
    for sensor, conf in config.iteritems():
        if sensor != "database":
            if conf["enabled"]=="true":
                if conf["table"]==sys.argv[2]:
                    resetDB(database,sys.argv[2],sys.argv[1])
else:
    for sensor, conf in config.iteritems():
        if sensor != "database":
            if conf["enabled"]=="true":
                if len(sys.argv)>1:
                    resetDB(database,conf["table"],sys.argv[1])
                else:
                    for extension in config["database"]["aggregateTables"]:
                        resetDB(database,conf["table"],extension)
database.close()
