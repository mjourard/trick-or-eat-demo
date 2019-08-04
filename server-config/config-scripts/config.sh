
#intalling nginx 
apt-get update
apt-get install nginx

#installing php7
cat deb http://packages.dotdeb.org jessie-php7.0 all >> /etc/apt/sources.list
cat deb-src http://packages.dotdeb.org jessie-php7.0 all >> /etc/apt/sources.list
wget https://www.dotdeb.org/dotdeb.gpg
apt-key add dotdeb.gpg
apt-get update
apt-get install php7.0-fpm php7.0-mysql php7.0-dom