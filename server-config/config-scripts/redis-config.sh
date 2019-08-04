#!/bin/sh

###########################################
# this file is to be run as the root user #
###########################################

# ensure we have make and gcc installed for redis building
apt-get update
apt-get install build-essential

# download the redis source files to be built
wget http://download.redis.io/redis-stable.tar.gz
tar xvzf redis-stable.tar.gz
cd redis-stable
make

# install it to the proper locations
make install

# put configuration, log and pid files where they need to go
mkdir /etc/redis
mkdir /var/redis
cp utils/redis_init_script /etc/init.d/redis_6379
cp redis.conf /etc/redis/6379.conf
mkdir /var/redis/6379

# add the redis init script to all the default run levels
update-rc.d redis_6379 defaults

# to start the redis server instance, run:
# /etc/init.d/redis_6379 start

# likewise to stop/restart it, run:
# /etc/init.d/redis_6379 stop/restart