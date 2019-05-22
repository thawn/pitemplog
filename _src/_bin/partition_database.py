#!/usr/bin/python
import os
import datetime
#from pprint import pprint

import pitemplog


def partition_table(conf, database, table, extension):
    print(str(datetime.datetime.now()) + " Partitioning: " + table + extension)
    # create the lock file
    lock_file = pitemplog.lock_table(table, extension)
    cur, now, one_year, two_weeks, cur_interval = pitemplog.calculate_partition_borders(database, table + extension)
    count = int(0)
    query = "ALTER TABLE `" + table + extension + "` PARTITION BY RANGE (time) ("
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
    pitemplog.modify_tables(partition_table)


if __name__ == "__main__":
    main()
