#!/usr/bin/python
import json
import MySQLdb
import sys
import os
import time
#import pprint

with open('/var/www/conf/config.json') as config_file:
    config=json.load(config_file)

database = MySQLdb.connect(host=config["database"]["host"], user=config["database"]["user"], passwd=config["database"]["pw"], db=config["database"]["db"])

def updateDB (database,table,extension):
    print("Updating aggregate times for: "+table+extension)
    if extension=="_5min":
        timespan="300"
    elif extension=="_15min":
        timespan="900"
    elif extension=="_60min":
        timespan="3600"
    else:
        raise LookupError("Unknown Extension: "+extension)
    #check if another process is running
    lockFile="/tmp/"+table+extension+"_lock"
    if os.path.isfile(lockFile):
        twoHoursAgo = time.time() - 7200
        if os.stat(lockFile).st_ctime>twoHoursAgo:
            print("another process is working on: "+table+extension+" aborting...")
            return
        else:
            os.remove(lockFile)
    #create the lock file
    with open(lockFile,'a'):
        os.utime(lockFile,None)
    cur=database.cursor()
    lastSyncFile="/tmp/"+table+extension+"_last"
    if os.path.isfile(lastSyncFile):
        tmpFile=open(lastSyncFile)
        lastSync=str(int(tmpFile.readline()))
        tmpFile.close()
    else:
        query="SELECT time FROM "+table+extension+" LIMIT 1"
        cur.execute(query)
        rows=cur.fetchall()
        if not rows:
            lastSync="0"
        else:
            lastSync=str(rows[0][0])
    now=str(int(time.time())+3600)
    whereClause=" WHERE time>"+str(int(lastSync)-2*int(timespan))+" AND time<"+now
    #fetch the second to last time entry. The average temperatures for all times after that one will be calculated.
    query="SELECT time FROM "+table+extension+whereClause+" ORDER BY time DESC LIMIT 1,1"
    cur.execute(query)
    rows=cur.fetchall()
    if not rows:
        #first we try again with the slow method just in case there was a long gap
        query="SELECT time FROM "+table+extension+" ORDER BY time DESC LIMIT 1,1"
        cur.execute(query)
        rows=cur.fetchall()
    if not rows:
        lastTime="0"
    else:
        lastTime=str(rows[0][0])
    tmpFile=open(lastSyncFile,'w')
    tmpFile.write(lastTime)
    tmpFile.close()
    #pprint.pprint(lastTime)
    #delete the last database entry because it is not guaranteed to be in sync with the rest of the values:
    query="DELETE FROM "+table+extension+whereClause+" ORDER BY time DESC LIMIT 1"
    cur.execute(query)
    database.commit()
    #perform the actual calculation in mysql:
    query="INSERT INTO "+table+extension+" (time, temp) SELECT MAX(time), AVG(temp) FROM "+table+" WHERE time>"+lastTime+" GROUP BY CEIL((time)/"+timespan+")"
    cur.execute(query)
    database.commit()
    cur.close()
    os.remove(lockFile)

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
                    updateDB(database,sys.argv[2],sys.argv[1])
else:
    for sensor, conf in configs.iteritems():
        if sensor != "database":
            if conf["enabled"]=="true":
                if len(sys.argv)>1:
                    updateDB(database,conf["table"],sys.argv[1])
                else:
                    for extension in config["database"]["aggregateTables"]:
                        updateDB(database,conf["table"],extension)
database.close()
