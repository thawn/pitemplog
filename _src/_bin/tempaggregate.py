#!/usr/bin/python
import os
import time
#import pprint

import pitemplog


def aggregate_table(conf, database, table, extension):
    table_name = table + extension
    print("Updating aggregate times for: " + table_name)
    config = pitemplog.PiTempLogConf()
    timespan = config.get_timespan(extension)

    if pitemplog.is_table_locked(table, extension):
        return
    lock_file = pitemplog.lock_table(table, extension)

    cur = database.cursor()
    last_sync_file = "/tmp/%s_last" % table_name
    if os.path.isfile(last_sync_file):
        tmp_file = open(last_sync_file)
        last_sync = str(int(tmp_file.readline()))
        tmp_file.close()
    else:
        query = "SELECT time FROM `%s` LIMIT 1" % table_name
        cur.execute(query)
        rows = cur.fetchall()
        if not rows:
            last_sync = "0"
        else:
            last_sync = str(rows[0][0])
    now = str(int(time.time()) + 3600)
    where_clause = " WHERE time>" + str(int(last_sync) - 2 * timespan) + " AND time<" + now
    # fetch the second to last time entry. The average temperatures for all times after that one will be calculated.
    query = "SELECT time FROM `%s`" % table_name + where_clause + " ORDER BY time DESC LIMIT 1,1"
    cur.execute(query)
    rows = cur.fetchall()
    if not rows:
        # first we try again with the slow method just in case there was a long gap
        query = "SELECT time FROM `%s` ORDER BY time DESC LIMIT 1,1" % table_name
        cur.execute(query)
        rows = cur.fetchall()
    if not rows:
        last_time = "0"
    else:
        last_time = str(rows[0][0])
    tmp_file = open(last_sync_file, 'w')
    tmp_file.write(last_time)
    tmp_file.close()
    # pprint.pprint(last_time)
    # delete the last database entry because it is not guaranteed to be in sync with the rest of the values:
    query = "DELETE FROM `%s`" % table_name + where_clause + " ORDER BY time DESC LIMIT 1"
    cur.execute(query)
    database.commit()
    # perform the actual calculation in mysql:
    query = "INSERT INTO `%s`" % table_name + \
        " (time, temp) SELECT MAX(time), AVG(temp) FROM " + table + \
        " WHERE time>" + last_time + " GROUP BY CEIL((time)/" + str(timespan) + ")"
    cur.execute(query)
    database.commit()
    cur.close()
    os.remove(lock_file)


def main():
    pitemplog.modify_tables(aggregate_table)


if __name__ == "__main__":
    main()
