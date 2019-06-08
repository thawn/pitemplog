#!/usr/bin/python
import os
import time
#from pprint import pprint

import pitemplog


def aggregate_table(unused, database, table, extension):
    if not table:
        pitemplog.log.warning("Table is empty! Nothing done.")
        return
    table_name = table + extension
    lock = pitemplog.LockTable(table_name)
    if not lock.is_locked():
        with lock:
            pitemplog.log.info("Updating aggregate times for: " + table_name)
            config = pitemplog.PiTempLogConf()
            timespan = config.get_timespan(extension)
            last_sync_file = "/tmp/%s_last" % table_name
            try:
                with open(last_sync_file) as tmp_file:
                    last_sync = str(int(tmp_file.readline()))
            except IOError:
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
            pitemplog.log.debug("Query lasttime: " + query)
            with database as cur:
                cur.execute(query)
                rows = cur.fetchall()
            if not rows:
                # first we try again with the slow method just in case there was a long gap
                query = "SELECT time FROM `%s` ORDER BY time DESC LIMIT 1,1" % table_name
                pitemplog.log.debug("Query lasttime all: " + query)
                with database as cur:
                    cur.execute(query)
                    rows = cur.fetchall()
            if not rows:
                last_time = "0"
            else:
                last_time = str(rows[0][0])
            pitemplog.log.debug("Updated lasttime: " + last_time)
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
            with open(last_sync_file, 'w') as tmp_file:
                tmp_file.write(last_time)
            try:
                os.chmod(last_sync_file, 0o666)
            except OSError:
                pass


def main():
    config = pitemplog.PiTempLogConf()
    config.modify_tables(aggregate_table)


if __name__ == "__main__":
    pitemplog.log.setLevel(int(os.getenv('PITEMPLOG_DEBUG', '20')))
    main()
