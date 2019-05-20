#!/usr/bin/env python
import datetime
import json
import filecmp
import os
import stat
import shutil
import yaml
from pprint import pprint

def writePost(path,filename, title, table, category, content):
  fullPath=os.path.abspath(os.path.join(path,filename))
  if not(os.path.exists(path)):
    print("making directory: "+path)
    os.makedirs(path)
  print("Generating: "+fullPath+'\n')
  post = open(fullPath,'w')
  post.write('---\nlayout: default\ntitle: "'+title+'"\ntable: '+table+'\ncategory: '+category+'\n---\n'+content)
  post.close()

def emptyFolder(folder):
  if os.path.exists(folder):
    for the_file in os.listdir(folder):
      file_path = os.path.join(folder, the_file)
      try:
          if os.path.isfile(file_path):
              os.unlink(file_path)
      except Exception as e:
          print(e)


basepath=os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir))

with open(os.path.join(basepath, '_config.yml'), 'r') as jekyllfile:
  jekyll_conf=yaml.safe_load(jekyllfile)

wwwConfigPath = os.path.join(jekyll_conf['destination'], 'conf/', 'config.json')
configPath = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'config.json')

archive=False
#if the config on the website has changed
if (os.path.exists(wwwConfigPath)):
  if not(filecmp.cmp(configPath, wwwConfigPath)):
    with open(configPath) as config_file:
        oldconfig=json.load(config_file)
    if 'local_sensors' in oldconfig:
        if 'remote_sensors' in oldconfig and isinstance(oldconfig['remote_sensors'], dict):
            configs = oldconfig['local_sensors'].copy()
            configs.update(oldconfig['remote_sensors'])
        else:
            configs = oldconfig['local_sensors']
        for sensor, conf in configs.iteritems():
            categoryPath=os.path.join(basepath,conf["category"])
            if os.path.exists(categoryPath):
                print("Deleting: "+categoryPath)
                shutil.rmtree(categoryPath, True)
    print("copying updated configuration "+wwwConfigPath+" to local configuration: "+configPath)
    shutil.copyfile(wwwConfigPath, configPath)
    localConfigPath = os.path.abspath(os.path.join(os.path.dirname(configPath), os.pardir, 'conf/', 'config.json'))
    shutil.copyfile(wwwConfigPath, localConfigPath)

with open(configPath) as config_file:
  config=json.load(config_file)

weeklyContent='{% assign time = page.date %}\n<script>var now=new Date({{ time | date: "%s" }}*1000);</script>\n{% include weekviewjs.html %}\n'
currentWeekContent='{% assign time = site.time %}\n<script>var now=new Date();</script>\n{% include weekviewjs.html %}\n'

today = datetime.date.today()
#first we need to remove old pages
if 'local_sensors' in config:
  if 'remote_sensors' in config and isinstance(config['remote_sensors'], dict):
    configs = config['local_sensors'].copy()
    configs.update(config['remote_sensors'])
  else:
        configs = config['local_sensors']
  for sensor, conf in configs.iteritems():
    currentPagePath=os.path.join(basepath,conf["category"],'_posts/')
    emptyFolder(currentPagePath)
  #now we create the new pages for each sensor
  for sensor, conf in configs.iteritems():
    if conf["enabled"] == "true" and conf["table"]:
      currentPagePath = os.path.join(basepath, conf["category"], '_posts/')
      filename = today.isoformat() + '-' + conf["table"] + '-temperatures.html'
      writePost(currentPagePath, filename, conf["name"], conf["table"], conf["category"], currentWeekContent)
      if archive:
        lastSunday = datetime.date.today()
        while lastSunday.weekday() != 6:
          lastSunday -= datetime.timedelta(1)
        #print(lastSunday.isoformat())
        date = datetime.date.fromtimestamp(int(conf["firsttime"]))
        if date.weekday() == 6:
          date += datetime.timedelta(1)  # if the first day is a sunday, we would create an empty week so lets skip that
        #print(date.isoformat())
        while date < lastSunday:
          while date.weekday() != 6:
            date += datetime.timedelta(1)
          filename = date.isoformat() + '-' + conf["table"] + '-week-' + str(date.isocalendar()[1]) + '.html'
          path = os.path.join(basepath, conf["category"], 'archive/year-' + str(date.year), '_posts/')
          writePost(path, filename, conf["name"] + ': week ' + str(date.isocalendar()[1]),
                    conf["table"], conf["category"], weeklyContent)
          date += datetime.timedelta(1)
      os.system("chmod -R a+w '" + os.path.join(basepath, conf["category"]) + "'")
