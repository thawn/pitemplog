#!/bin/bash

# shellcheck source=env
[ -f /etc/profile.d/pitemplog.sh ] && . /etc/profile.d/pitemplog.sh

if [ -z "$PITEMPLOG_DIR" ] ;  then
  echo "PITEMPLOG_DIR is not set, please set it in /etc/profile.d/pitemplog.sh" >&2
  exit 1
fi

read -rp "This will overwrite all data on /dev/sda1 during the next boot! Press Enter to continue; press ctrl+c to cancel"
cat <<EOF > /etc/init.d/setup_usb &&
#!/bin/sh
### BEGIN INIT INFO
# Provides:          setup_usb
# Required-Start:
# Required-Stop:
# Default-Start: 3
# Default-Stop:
# Short-Description: Format first usb disk (/dev/sda1) and move mysql database, logfiles and swap file there.
# Description:
### END INIT INFO

. /lib/lsb/init-functions

# shellcheck source=env
[ -f /etc/profile.d/pitemplog.sh ] && . /etc/profile.d/pitemplog.sh

if [ -z "\$PITEMPLOG_DIR" ] ;  then
  echo "PITEMPLOG_DIR is not set, please set it in /etc/profile.d/pitemplog.sh" >&2
  exit 1
fi

case "\$1" in
  start)
    log_daemon_msg "Starting setup_usb" &&
    if [ -e /dev/sda1 ]; then
      hdparm -S 60 -B 254 /dev/sda1
      mkfs.ext4 -F /dev/sda1
      mkdir -p /mnt/usb1
      mount /dev/sda1 /mnt/usb1
      mkdir -p /mnt/usb1/var/ /mnt/usb1/tmp
      chmod a+rwxt /mnt/usb1/tmp
      service rsyslog stop
      service mysqld stop
      service cron stop
      mv /var/lib/mysql /mnt/usb1/
      chown -R mysql:mysql /mnt/usb1/mysql
      chmod 0700 /mnt/usb1/mysql
      mv "${PITEMPLOG_DIR%%/}" /mnt/usb1/templog
      chown -R ${PT_USER}:${PT_USER} /mnt/usb1/templog
      sed -i "s|^PITEMPLOG_DIR=.*|PITEMPLOG_DIR=/mnt/usb1/templog/|" /etc/profile.d/pitemplog.sh
      sed -i "s|^PITEMPLOG_DIR=.*|PITEMPLOG_DIR=/mnt/usb1/templog/|" /etc/systemd/system/partition_db.env
      #remove the old symlinks
      rm -f "$(python3 -m site | grep usr/local/lib | cut -d',' -f 1 | xargs)/pitemplog.py"
      rm -f /usr/local/bin/partition_database.py
      rm -f /usr/local/bin/reset_aggregates.py
      rm -f /usr/local/bin/tempaggregate.py
      rm -f /usr/local/bin/templog.py
      rm -f /usr/local/bin/pitemplog_backup.sh
      rm -f /usr/local/bin/pitemplog_restore.sh
      rm -f /etc/pitemplog.conf
      rm -f /usr/local/sbin/pitemplog_partition_database.sh
      #create new symlinks
      ln -s /mnt/usb1/templog/_bin/pitemplog.py "$(python3 -m site | grep usr/local/lib | cut -d',' -f 1 | xargs)"
      ln -s /mnt/usb1/templog/_bin/*.py /usr/local/bin/
      ln -s /mnt/usb1/templog/_bin/pitemplog_backup.sh /usr/local/bin/
      ln -s /mnt/usb1/templog/_bin/pitemplog_restore.sh /usr/local/bin/
      ln -s /mnt/usb1/templog/_sbin/pitemplog_partition_database.sh /usr/local/sbin/
      ln -s /mnt/usb1/templog/_sbin/pitemplog.conf /etc/
      mv /var/log /mnt/usb1/var/
      ln -s /mnt/usb1/var/log /var/log
      cp /mnt/usb1/templog/_sbin/fstab /etc/
      chown root:root /etc/fstab
      chmod 0644 /etc/fstab
      cp /mnt/usb1/templog/_sbin/datadir.cnf /etc/mysql/mariadb.conf.d/
      chown root:root /etc/mysql/mariadb.conf.d/datadir.cnf
      chmod 0644 /etc/mysql/mariadb.conf.d/datadir.cnf
      dphys-swapfile uninstall
      cp /mnt/usb1/templog/_sbin/dphys-swapfile /etc/
      chown root:root /etc/dphys-swapfile
      chmod 0644 /etc/dphys-swapfile
      dphys-swapfile install
      dphys-swapfile swapon
      mount -t tmpfs tmpfs /tmp -o defaults,noatime
      service rsyslog start
      service mysqld start
      service cron start
      update-rc.d setup_usb remove
      rm -f /etc/init.d/setup_usb
    else
      log_daemon_msg "no usb drive found. Please attach a usb mass storage device and make sure it has at leas one partition (use parted to partition the drive). Then reboot."
    fi
    log_end_msg \$?
    ;;
  *)
    echo "Usage: \$0 start" >&2
    exit 3
    ;;
esac
EOF
chmod +x /etc/init.d/setup_usb &&
update-rc.d setup_usb defaults
echo "Reboot the system so that the changes can take effect"

