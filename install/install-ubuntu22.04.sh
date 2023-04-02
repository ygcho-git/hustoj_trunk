#!/bin/bash

#detect and refuse to run under WSL
if [ -d /mnt/c ]; then
    echo "WSL is NOT supported."
    exit 1
fi

sed -i 's/tencentyun/aliyun/g' /etc/apt/sources.list
sed -i 's/cn.archive.ubuntu/mirrors.aliyun/g' /etc/apt/sources.list
sed -i "s|#\$nrconf{restart} = 'i'|\$nrconf{restart} = 'a'|g" /etc/needrestart/needrestart.conf

apt-get update && apt-get -y upgrade

apt-get install -y software-properties-common
add-apt-repository -y universe
add-apt-repository -y multiverse
add-apt-repository -y restricted

apt-get update && apt-get -y upgrade

apt-get install -y subversion
/usr/sbin/useradd -m -u 1536 judge

cd /home/judge/ || exit

#using tgz src files
wget -O hustoj.tar.gz http://dl.hustoj.com/hustoj.tar.gz
tar xzf hustoj.tar.gz
svn up src
#svn co https://github.com/zhblue/hustoj/trunk/trunk/  src

#手工解决阿里云软件源的包依赖问题 apt install libssl1.1=1.1.1f-1ubuntu2.8 -y --allow-downgrades

apt-get install -y libmysqlclient-dev
apt-get install -y libmysql++-dev 
PHP_VER=`apt-cache search php-fpm|grep -e '[[:digit:]]\.[[:digit:]]' -o`
if [ "$PHP_VER" = "" ] ; then PHP_VER="8.1"; fi
for pkg in net-tools make g++ php$PHP_VER-fpm nginx mysql-server php$PHP_VER-mysql php$PHP_VER-common php$PHP_VER-gd php$PHP_VER-zip php$PHP_VER-mbstring php$PHP_VER-xml php$PHP_VER-curl php$PHP_VER-intl php$PHP_VER-xmlrpc php$PHP_VER-soap php-yaml tzdata
do
	while ! apt-get install -y "$pkg" 
	do
		echo "Network fail, retry... you might want to change another apt source for install"
	done
done
service php$PHP_VER-fpm start
service mysql start
service nginx start

chgrp www-data  /home/judge

USER=$(grep user /etc/mysql/debian.cnf|head -1|awk  '{print $3}')
PASSWORD=$(grep password /etc/mysql/debian.cnf|head -1|awk  '{print $3}')
CPU=$(grep "cpu cores" /proc/cpuinfo |head -1|awk '{print $4}')
MEM=`free -m|grep Mem|awk '{print $2}'`

if [ "$MEM" -lt "1000" ] ; then
        echo "Memory size less than 1GB."
        if grep 'performance_schema=OFF' /etc/mysql/mysql.conf.d/mysqld.cnf ; then
                echo "already turn off"
        else
                echo "try turn off performance_schema"
                sed -i 's/^\[mysqld\]/\[mysqld\]\nperformance_schema=OFF/' /etc/mysql/mysql.conf.d/mysqld.cnf
                /etc/init.d/mysql restart
        fi
else
        echo "Memory size : $MEM MB"
fi

mkdir etc data log backup

cp src/install/java0.policy  /home/judge/etc
cp src/install/judge.conf  /home/judge/etc
chmod +x src/install/ans2out

# create enough runX dirs for each CPU core
if grep "OJ_SHM_RUN=0" etc/judge.conf ; then
	for N in `seq 0 $(($CPU-1))`
	do
	   mkdir run$N
	   chown judge run$N
	done
fi

sed -i "s/OJ_USER_NAME=root/OJ_USER_NAME=$USER/g" etc/judge.conf
sed -i "s/OJ_PASSWORD=root/OJ_PASSWORD=$PASSWORD/g" etc/judge.conf
sed -i "s/OJ_COMPILE_CHROOT=1/OJ_COMPILE_CHROOT=0/g" etc/judge.conf
sed -i "s/OJ_RUNNING=1/OJ_RUNNING=$CPU/g" etc/judge.conf

chmod 700 backup
chmod 700 etc/judge.conf
chown -R root:root etc

sed -i "s/DB_USER[[:space:]]*=[[:space:]]*\"root\"/DB_USER=\"$USER\"/g" src/web/include/db_info.inc.php
sed -i "s/DB_PASS[[:space:]]*=[[:space:]]*\"root\"/DB_PASS=\"$PASSWORD\"/g" src/web/include/db_info.inc.php
chmod 700 src/web/include/db_info.inc.php
chown -R www-data:www-data src/web/

chown -R root:root src/web/.svn
chmod 750 -R src/web/.svn

chown www-data:www-data src/web/upload
chown www-data:www-data data
chmod 750 -R data
if grep "client_max_body_size" /etc/nginx/nginx.conf ; then 
	echo "client_max_body_size already added" ;
else
	sed -i "s:include /etc/nginx/mime.types;:client_max_body_size    280m;\n\tinclude /etc/nginx/mime.types;:g" /etc/nginx/nginx.conf
fi

mysql -h localhost -u"$USER" -p"$PASSWORD" < src/install/db.sql
echo "insert into jol.privilege values('admin','administrator','true','N');"|mysql -h localhost -u"$USER" -p"$PASSWORD" 
echo "insert into jol.privilege values('admin','source_browser','true','N');"|mysql -h localhost -u"$USER" -p"$PASSWORD" 

if grep "added by hustoj" /etc/nginx/sites-enabled/default ; then
	echo "default site modified!"
else
	echo "modify the default site"
	sed -i "s#root /var/www/html;#root /home/judge/src/web;#g" /etc/nginx/sites-enabled/default
	sed -i "s:index index.html:index index.php:g" /etc/nginx/sites-enabled/default
	sed -i "s:#location ~ \\\.php\\$:location ~ \\\.php\\$:g" /etc/nginx/sites-enabled/default
	sed -i "s:#\tinclude snippets:\tinclude snippets:g" /etc/nginx/sites-enabled/default
	sed -i "s|#\tfastcgi_pass unix|\tfastcgi_pass unix|g" /etc/nginx/sites-enabled/default
	sed -i "s:}#added by hustoj::g" /etc/nginx/sites-enabled/default
	sed -i "s:php7.4:php$PHP_VER:g" /etc/nginx/sites-enabled/default
	sed -i "s|# deny access to .htaccess files|}#added by hustoj\n\n\n\t# deny access to .htaccess files|g" /etc/nginx/sites-enabled/default
	sed -i "s|fastcgi_pass 127.0.0.1:9000;|fastcgi_pass 127.0.0.1:9000;\n\t\tfastcgi_buffer_size 256k;\n\t\tfastcgi_buffers 32 64k;|g" /etc/nginx/sites-enabled/default
fi
/etc/init.d/nginx restart
sed -i "s/post_max_size = 8M/post_max_size = 180M/g" /etc/php/$PHP_VER/fpm/php.ini
sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 180M/g" /etc/php/$PHP_VER/fpm/php.ini
WWW_CONF=$(find /etc/php -name www.conf)
sed -i 's/;request_terminate_timeout = 0/request_terminate_timeout = 128/g' "$WWW_CONF"
sed -i 's/pm.max_children = 5/pm.max_children = 200/g' "$WWW_CONF"
 
COMPENSATION=$(grep 'mips' /proc/cpuinfo|head -1|awk -F: '{printf("%.2f",$2/3000)}')
sed -i "s/OJ_CPU_COMPENSATION=1.0/OJ_CPU_COMPENSATION=$COMPENSATION/g" etc/judge.conf

PHP_FPM=$(find /etc/init.d/ -name "php*-fpm")
$PHP_FPM restart
PHP_FPM=$(service --status-all|grep php|awk '{print $4}')
if [ "$PHP_FPM" != ""  ]; then service "$PHP_FPM" restart ;else echo "NO PHP FPM";fi;

cd src/core || exit 
chmod +x ./make.sh
./make.sh
if grep "/usr/bin/judged" /etc/rc.local ; then
	echo "auto start judged added!"
else
	sed -i "s/exit 0//g" /etc/rc.local
	echo "/usr/bin/judged" >> /etc/rc.local
	echo "exit 0" >> /etc/rc.local
fi
if grep "bak.sh" /var/spool/cron/crontabs/root ; then
	echo "auto backup added!"
else
	crontab -l > conf && echo "1 0 * * * /home/judge/src/install/bak.sh" >> conf && crontab conf && rm -f conf
	/etc/init.d/cron reload
fi
ln -s /usr/bin/mcs /usr/bin/gmcs

/usr/bin/judged
cp /home/judge/src/install/hustoj /etc/init.d/hustoj
update-rc.d hustoj defaults
systemctl enable hustoj
systemctl enable nginx
systemctl enable mysql
systemctl enable php$PHP_VER-fpm
#systemctl enable judged

sed -i "s#interactive_timeout=120#interactive_timeout=20#g" /etc/mysql/mysql.conf.d/mysqld.cnf
sed -i "s#wait_timeout=120#wait_timeout=20#g" /etc/mysql/mysql.conf.d/mysqld.cnf

/etc/init.d/mysql start


mkdir /var/log/hustoj/
chown www-data -R /var/log/hustoj/
cd /home/judge/src/install
if test -f  /.dockerenv ;then
	echo "Already in docker, skip docker installation, install some compilers ... "
	apt-get intall -y flex fp-compiler openjdk-14-jdk mono-devel
else
        sed -i 's/ubuntu:20/ubuntu:22/g' Dockerfile 
	sed -i 's|/usr/include/c++/9|/usr/include/c++/11|g' Dockerfile 
	bash docker.sh
	 sed -i "s/OJ_USE_DOCKER=0/OJ_USE_DOCKER=1/g" /home/judge/etc/judge.conf
	 sed -i "s/OJ_PYTHON_FREE=0/OJ_PYTHON_FREE=1/g" /home/judge/etc/judge.conf
fi
clear
reset

echo "Remember your database account for HUST Online Judge:"
echo "username:$USER"
echo "password:$PASSWORD"
echo "DO NOT POST THESE INFORMATION ON ANY PUBLIC CHANNEL!"
echo "Register a user as 'admin' on http://127.0.0.1/ "
echo "打开http://127.0.0.1/ 注册用户admin，获得管理员权限。"
echo "不要在QQ群或其他地方公开发送以上信息，否则可能导致系统安全受到威胁。"
echo "█████████████████████████████████████████"
echo "████ ▄▄▄▄▄ ██▄▄ ▀  █▀█▄▄██ ███ ▄▄▄▄▄ ████"
echo "████ █   █ █▀▄  █▀██ ██▄▄  █▄█ █   █ ████"
echo "████ █▄▄▄█ █▄▀ █▄█▀█  ▄▄█▀▀▄██ █▄▄▄█ ████"
echo "████▄▄▄▄▄▄▄█▄▀▄█ █ █▄█▄▀ █ ▀▄█▄▄▄▄▄▄▄████"
echo "████ ▄▀▀█▄▄ █▄ █▄▄▄█▄█▀███▄  ██▀ ▄▀▀█████"
echo "████▀█▀▀▀▀▄▀▀▄▀ ▄▄█▄ █▀▀ ▄▀▀▄  █▄▄▀▄█████"
echo "████▄█ ▀▄▀▄▄ ▄ █▀█▀█ ▄▀▄ █▀▀▄█  ███  ████"
echo "████▄ █▄ █▄▀▀▄██▀▄ ▄ ▄▄█▄█▀█▀   ▄█▀▄▀████"
echo "████▄▄█   ▄▄██ █▄▄▀  ▄▀█▀▀▀ ▄█▀▄▄▀█ ▀████"
echo "█████▄   ▀▄▄█ ▄▀▄▄▀▄▄▄▀▄▀█▀  ▀▀█▄█▀█▄████"
echo "████ ▀ █▄▀▄▄█▀▀▄▀▀▄▄▄ ▀▀█▀ ▀▄▄█▀ ▀█ █████"
echo "████ █▀   ▄ ▄ ▀█▀▄█ █▄▄███▀██▀▀██ ▀▄█████"
echo "████▄▄▄██▄▄█ ▀█▄▄▄▀█ █▀▀█▀ █ ▄▄▄ █▀▄▀████"
echo "████ ▄▄▄▄▄ █ ▄  ▄▄▀  ▄ ▀▄▄▄▄ █▄█   ▄█████"
echo "████ █   █ ██ ▄▄▀▀█ ▀▀▀▀▀ ▄▀  ▄  ▀███████"
echo "████ █▄▄▄█ █▀▄▄▄▀▀█ ▀▄ ▄▀██▄█ ██ █ █▄████"
echo "████▄▄▄▄▄▄▄█▄███▄█▄▄▄████▄▄▄▄▄▄█▄██▄█████"
echo "█████████████████████████████████████████"
echo "            QQ扫码加官方群"

