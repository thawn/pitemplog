#!/usr/bin/env python
import datetime
import json
import filecmp
import os
import stat
import shutil
import yaml
#from pprint import pprint

basepath=os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir))

with open(os.path.join(basepath, '_config.yml'), 'r') as jekyllfile:
  jekyll_conf=yaml.load(jekyllfile)

wwwConfigPath = os.path.join(jekyll_conf['destination'], 'conf/', 'config.json')

if (os.path.exists(wwwConfigPath)):
  with open(wwwConfigPath) as config_file:
    config=json.load(config_file)
  if 'local_sensors' in config:
    if 'remote_sensors' in config and isinstance(config['remote_sensors'], dict):
      configs = config['local_sensors'].copy()
      configs.update(config['remote_sensors'])
    else:
      configs = config['local_sensors']
    for sensor, conf in configs.iteritems():
      categoryPath=os.path.join(jekyll_conf['destination'],conf["category"])
      if os.path.exists(categoryPath):
        print("Deleting: "+categoryPath)
        shutil.rmtree(categoryPath, True)
