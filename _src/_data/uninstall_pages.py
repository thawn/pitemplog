#!/usr/bin/env python
import os
import shutil
import yaml
#from pprint import pprint

import pitemplog


def delete_category_path(conf, basepath):
    category_path = os.path.join(basepath, conf["category"])
    if os.path.exists(category_path):
        print("Deleting: " + category_path)
        shutil.rmtree(category_path, True)


def main():
    basepath = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir))

    with open(os.path.join(basepath, '_config.yml'), 'r') as jekyllfile:
        jekyll_conf = yaml.safe_load(jekyllfile)

    www_config_path = os.path.join(jekyll_conf['destination'], 'conf/', 'config.json')

    if (os.path.exists(www_config_path)):
        config = pitemplog.PiTempLogConf(www_config_path)
        config.each_sensor(delete_category_path, basepath)


if __name__ == "__main__":
    main()
