#!/usr/bin/env python
import datetime
import filecmp
import os
import shutil
import yaml
#from pprint import pprint

import pitemplog


def write_post(path, filename, title, table, category, content):
    full_path = os.path.abspath(os.path.join(path, filename))
    if not(os.path.exists(path)):
        print("making directory: " + path)
        os.makedirs(path)
    print("Generating: " + full_path + '\n')
    post = open(full_path, 'w')
    post.write('---\nlayout: default\ntitle: "' + title + '"\ntable: ' +
               table + '\ncategory: ' + category + '\n---\n' + content)
    post.close()


def empty_folder(folder):
    if os.path.exists(folder):
        for the_file in os.listdir(folder):
            file_path = os.path.join(folder, the_file)
            try:
                if os.path.isfile(file_path):
                    os.unlink(file_path)
            except Exception as e:
                print(e)


def delete_category_path(conf, basepath):
    category_path = os.path.join(basepath, conf["category"])
    if os.path.exists(category_path):
        print("Deleting: " + category_path)
        shutil.rmtree(category_path, True)


def delete_posts(conf, basepath):
    current_page_path = os.path.join(basepath, conf["category"], '_posts/')
    empty_folder(current_page_path)


def create_sensor_pages(conf, basepath, ):
    if conf["enabled"] == "true" and conf["table"]:
        weekly_content = '{% assign time = page.date %}\n<script>var now=new Date({{ time | date: "%s" }}*1000);</script>\n{% include weekviewjs.html %}\n'
        current_week_content = '{% assign time = site.time %}\n<script>var now=new Date();</script>\n{% include weekviewjs.html %}\n'
        current_page_path = os.path.join(basepath, conf["category"], '_posts/')
        filename = datetime.date.today().isoformat() + '-' + conf["table"] + '-temperatures.html'
        write_post(current_page_path, filename, conf["name"], conf["table"], conf["category"], current_week_content)
        os.system("chmod -R a+w '" + os.path.join(basepath, conf["category"]) + "'")


def main():
    basepath = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir))

    with open(os.path.join(basepath, '_config.yml'), 'r') as jekyllfile:
        jekyll_conf = yaml.safe_load(jekyllfile)

    www_config_path = os.path.join(jekyll_conf['destination'], 'conf/', 'config.json')
    config_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'config.json')

    archive = False
    # if the config on the website has changed
    if (os.path.exists(www_config_path)):
        if not(filecmp.cmp(config_path, www_config_path)):
            oldconfig = pitemplog.PiTempLogConf(config_path)
            oldconfig.each_sensor(delete_category_path, basepath)
            print("copying updated configuration " + www_config_path + " to local configuration: " + config_path)
            shutil.copyfile(www_config_path, config_path)
            local_config_path = os.path.abspath(os.path.join(
                os.path.dirname(config_path), os.pardir, 'conf/', 'config.json'))
            shutil.copyfile(www_config_path, local_config_path)

    config = pitemplog.PiTempLogConf(config_path)

    # first we need to remove old pages
    config.each_sensor(delete_posts, basepath)
    # now we create the new pages for each sensor
    config.each_sensor(create_sensor_pages, basepath)


if __name__ == "__main__":
    main()
