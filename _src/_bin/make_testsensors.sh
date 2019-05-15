#!/bin/bash
RANDOM=$RAND
declare -a directories=("$RANDOM" "$RANDOM" "$RANDOM" )
for sensor in "${directories[@]}"; do
	sensordir=/tmp/devices/"$sensor"
	mkdir -p "$sensordir"
	printf "temp: %05d" $RANDOM > "${sensordir}"/w1_slave
done
rm "${sensordir}"/w1_slave
chown -R www-data /tmp/devices/
echo "export SENSOR_DIR=$SENSOR_DIR" > /etc/profile.d/sensors.sh
echo "SENSOR_DIR=$SENSOR_DIR" > /tmp/crontab_env
cat "${INSTALL_DIR}_bin/crontab" >> /tmp/crontab_env
mv /tmp/crontab_env "${INSTALL_DIR}_bin/crontab"
