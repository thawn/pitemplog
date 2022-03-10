#!/usr/bin/python3
import datetime
import sys
#from pprint import pprint

import pitemplog


def partition_table(unused, database, table, extension):
    if not table:
        pitemplog.log.warning("Table is empty! Nothing done.")
        return
    table_name = table + extension
    pitemplog.log.info(str(datetime.datetime.now()) + " Partitioning: %s" % table_name )
    lock = pitemplog.LockTable(table_name)
    with lock:
        now, one_year, two_weeks, cur_interval = pitemplog.calculate_partition_borders(database, table + extension)
        count = int(0)
        query = "ALTER TABLE `%s` PARTITION BY RANGE (time) (" % table_name
        while cur_interval < (now + one_year):
            query += " PARTITION p%d VALUES LESS THAN (%d)," %(count, cur_interval)
            cur_interval += two_weeks
            count += int(1)
        query += " PARTITION p%d VALUES LESS THAN MAXVALUE);" % count
        with database as cur:
            cur.execute(query)
        pitemplog.log.info(str(datetime.datetime.now()) + " Done.")


def main():
    config = pitemplog.PiTempLogConf()
    if len(sys.argv) < 2:
        config.each_sensor_database(partition_table, "")
        for ext in config.database["aggregateTables"]:
            config.each_sensor_database(partition_table, ext)
    else:
        config.modify_tables(partition_table)


if __name__ == "__main__":
    pitemplog.log.setLevel(20)
    main()
