#!/bin/bash
/usr/sbin/service cron stop
/usr/local/bin/partition_database.py
/usr/sbin/service cron start
