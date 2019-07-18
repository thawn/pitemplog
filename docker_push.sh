#!/bin/bash
latest_id=$( docker images |grep -e "pitemplog[ \t]*latest"|head -n1|awk '{print $3}' )
docker tag $latest_id thawn/pitemplog:latest
docker push thawn/pitemplog:latest
if ! [ -z ${1+x} ]; then
	docker tag $latest_id thawn/pitemplog:version-$1
	docker push thawn/pitemplog:version-$1
fi
test_id=$( docker images |grep -e "pitemplog[ \t]*test"|head -n1|awk '{print $3}' )
docker tag $test_id thawn/pitemplog:test
docker push thawn/pitemplog:test

