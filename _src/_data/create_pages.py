#!/usr/bin/env python3
import datetime
import filecmp
import os
import shutil
import yaml
#from pprint import pprint

import pitemplog

def ensure_dir(path):
    if not os.path.exists(path):
        pitemplog.log.info("making directory: " + path)
        os.makedirs(path)


def write_post(path, filename, title, table, category, content):
    full_path = os.path.abspath(os.path.join(path, filename))
    ensure_dir(path)
    pitemplog.log.info("Generating: " + full_path + '\n')
    with open(full_path, 'w') as post:
        post.write('---\nlayout: default\ntitle: "' + title + '"\ntable: ' +
                table + '\ncategory: ' + category + '\n---\n' + content)


def write_category(path, category):
    full_path = os.path.abspath(os.path.join(path, "index.html"))
    ensure_dir(path)
    content = '<script>var now=new Date();</script>\n{% include category.html %}\n'
    pitemplog.log.info("Generating: " + full_path + '\n')
    with open(full_path, 'w') as page:
        page.write('---\nlayout: default\ntitle: "' + category + '"\ncategory: ' + category + '\n---\n' + content)


def empty_folder(folder):
    try:
        for the_file in os.listdir(folder):
            file_path = os.path.join(folder, the_file)
            try:
                os.unlink(file_path)
            except OSError:
                pass
    except OSError:
        pass

def delete_posts(conf, basepath):
    current_page_path = os.path.join(basepath, conf["category"], '_posts/')
    empty_folder(current_page_path)


def create_sensor_pages(conf, basepath ):
    if conf["enabled"] == "true" and conf["table"]:
        current_week_content = '<script>var now=new Date();</script>\n{% include weekviewjs.html %}\n'
        category_path = os.path.join(basepath, conf["category"])
        current_page_path = os.path.join(category_path, '_posts/')
        filename = pitemplog.get_sensor_page_filename(conf["table"])
        write_post(current_page_path, filename, conf["name"], conf["table"], conf["category"], current_week_content)
        if not(os.path.exists(os.path.join(category_path, "index.html"))):
            write_category(category_path, conf["category"])
        os.system("chmod -R a+w '" + os.path.join(basepath, conf["category"]) + "'")


def main():
    basepath = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir))

    with open(os.path.join(basepath, '_config.yml'), 'r') as jekyllfile:
        jekyll_conf = yaml.safe_load(jekyllfile)

    www_config_path = os.path.join(jekyll_conf['destination'], 'conf/', 'config.json')
    config_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'config.json')

    # if the config on the website has changed
    if (os.path.exists(www_config_path)):
        if not(filecmp.cmp(config_path, www_config_path)):
            oldconfig = pitemplog.PiTempLogConf(config_path)
            oldconfig.each_sensor(pitemplog.delete_category_path, basepath)
            pitemplog.log.info("copying updated configuration " + www_config_path + " to local configuration: " + config_path)
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
    pitemplog.log.setLevel(20)
    main()
