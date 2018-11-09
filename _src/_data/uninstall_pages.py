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
  for sensor, conf in config.iteritems():
    if sensor != "database":
      categoryPath=os.path.join(jekyll_conf['destination'],conf["category"])
      if os.path.exists(categoryPath):
        print("Deleting: "+categoryPath)
        shutil.rmtree(categoryPath, True)
