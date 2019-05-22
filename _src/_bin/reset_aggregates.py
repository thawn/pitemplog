#!/usr/bin/python
import os
import datetime
#from pprint import pprint

import pitemplog


def reset_table(conf, database, table, extension):
    table_name = table + extension
    print(str(datetime.datetime.now()) + " Resetting: " + table_name)
    if not extension:
        print("Extension is empty, refusing to reset main table! Nothing done.")
        return
    # create the lock file
    lock_file = pitemplog.lock_table(table, extension)
    cur, now, one_year, two_weeks, cur_interval = pitemplog.calculate_partition_borders(database, table)
    count = int(0)
    query = "DROP TABLE IF EXISTS `%s`" % table_name
    cur.execute(query)
    database.commit()
    query = "CREATE TABLE `%s`" % table_name + \
        "(`time` int(11) DEFAULT NULL,`temp` float DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1 PARTITION BY RANGE (time) ("
    while cur_interval < (now + one_year):
        query += " PARTITION p" + str(count) + " VALUES LESS THAN (" + str(cur_interval) + "),"
        cur_interval += two_weeks
        count += int(1)
    query += " PARTITION p" + str(count) + " VALUES LESS THAN MAXVALUE);"
    # pprint(query)
    cur.execute(query)
    database.commit()
    cur.close()
    os.remove(lock_file)
    print(str(datetime.datetime.now()) + " Done.")


def main():
    pitemplog.modify_tables(reset_table)


if __name__ == "__main__":
    main()
