#!/usr/bin/python3
import datetime
#from pprint import pprint

import pitemplog


def reset_table(unused, database, table, extension):
    table_name = table + extension
    pitemplog.log.info(str(datetime.datetime.now()) + " Resetting: " + table_name)
    if not extension:
        pitemplog.log.warning("Extension is empty, refusing to reset main table! Nothing done.")
        return
    if not table:
        pitemplog.log.warning("Table is empty! Nothing done.")
        return
    # create the lock file
    lock = pitemplog.LockTable(table_name)
    with lock:
        now, one_year, two_weeks, cur_interval = pitemplog.calculate_partition_borders(database, table)
        count = int(0)
        query = "DROP TABLE IF EXISTS `%s`" % table_name
        with database as cur:
            cur.execute(query)
        query = "CREATE TABLE `%s`" % table_name + \
            "(`time` int(11) DEFAULT NULL,`temp` float DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1 PARTITION BY RANGE (time) ("
        while cur_interval < (now + one_year):
            query += " PARTITION p" + str(count) + " VALUES LESS THAN (" + str(cur_interval) + "),"
            cur_interval += two_weeks
            count += int(1)
        query += " PARTITION p" + str(count) + " VALUES LESS THAN MAXVALUE);"
        # pprint(query)
        with database as cur:
            cur.execute(query)
        pitemplog.log.info(str(datetime.datetime.now()) + " Done.")


def main():
    config = pitemplog.PiTempLogConf()
    config.modify_tables(reset_table)


if __name__ == "__main__":
    pitemplog.log.setLevel(20)
    main()
