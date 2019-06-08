#!/usr/bin/python
import os
import time
#from pprint import pprint

import pitemplog


def aggregate_table(unused, database, table, extension):
    table_name = table + extension
    lock = pitemplog.LockTable(table_name)
    if not lock.is_locked():
        with lock:
            pitemplog.log.info("Updating aggregate times for: " + table_name)
            config = pitemplog.PiTempLogConf()
            timespan = config.get_timespan(extension)
            last_sync_file = "/tmp/%s_last" % table_name
            if os.path.isfile(last_sync_file):
                tmp_file = open(last_sync_file)
                last_sync = str(int(tmp_file.readline()))
                tmp_file.close()
            else:
                query = "SELECT time FROM `%s` LIMIT 1" % table_name
                with database as cur:
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
            with database as cur:
                cur.execute(query)
                rows = cur.fetchall()
            if not rows:
                # first we try again with the slow method just in case there was a long gap
                query = "SELECT time FROM `%s` ORDER BY time DESC LIMIT 1,1" % table_name
                with database as cur:
                    cur.execute(query)
                    rows = cur.fetchall()
            if not rows:
                last_time = "0"
            else:
                last_time = str(rows[0][0])
            tmp_file = open(last_sync_file, 'w')
            tmp_file.write(last_time)
            tmp_file.close()
            os.chmod(last_sync_file, 0o666)
            # delete the last database entry because it is not guaranteed to be in sync with the rest of the values:
            query = "DELETE FROM `%s`" % table_name + where_clause + " ORDER BY time DESC LIMIT 1"
            with database as cur:
                cur.execute(query)
                database.commit()
            # perform the actual calculation in mysql:
            query = "INSERT INTO `%s`" % table_name + \
                " (time, temp) SELECT MAX(time), AVG(temp) FROM " + table + \
                " WHERE time>" + last_time + " GROUP BY CEIL((time)/" + str(timespan) + ")"
            with database as cur:
                cur.execute(query)


def main():
    config = pitemplog.PiTempLogConf()
    config.modify_tables(aggregate_table)


if __name__ == "__main__":
    pitemplog.log.setLevel(20)
    main()
