# Unit tests for pitemplog

The python unit tests in `src/pitemplog_tests.py` use a special docker environment to test all python files as well as most of the php API.

## Quick start

The easiest way to run the unit tests is to run
```bash
grunt test
```

## Manual testing

To run tests manually, first start the testing environment:
```bash
grunt docker_compose_test
```

The testing docker container mounts the directory `testing/src` as a volume to `/usr/local/share/templog_tests` inside the container. This means that changes to `pitemplog_tests.py` take effect within the container immediately.
the directory `build` is mounted to `/usr/local/share/templog`. This means that changes to all other source files take effect after compiling the web frontend with `grunt default`.

Docker-compose sets up 3 test environments and one mysql database. The test environments are accessible via ports [8080](http://localhost:8080) (hostname within the docker environment: pitemplog), [8081](http://localhost:8081) (hostname within the docker environment: pitemplogext), [8082](http://localhost:8082) (hostname within the docker environment: pitemplogfoo). For each of these webservers, the [xdebug php extension](https://xdebug.org/) is active. Therefore, you can set breakpoints and peek into the php code at runtime.

You can run the unittests via `docker exec`:
```bash
docker exec testing-pitemplog-1 python3 -m unittest pitemplog_tests.py
```

To run only tests from TestClass, use 
```bash
docker exec testing-pitemplog-1 python3 -m unittest pitemplog_tests.TestClass
```

To run only one specific test, use 
```bash
docker exec testing-pitemplog-1 python3 -m unittest pitemplog_tests.TestClass.test_specific
```

You can also get a commandline within the container like so:
```bash
docker exec -it testing-pitemplog-1 /bin/bash
```

In order to explore the mysql database, run:
```bash
docker exec -it testing-pitemplog-1 mysql_connect.sh
```
