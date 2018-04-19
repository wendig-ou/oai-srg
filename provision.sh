#!/bin/bash -e

function base {
  apt-get -y update
  apt-get -y upgrade

  export DEBIAN_FRONTEND=noninteractive
  apt-get install -y \
    curl git-core unzip net-tools \
    mariadb-server mariadb-client

  # for testing with selenium
  # apt-get install -y zip libgconf-2-4 chromium-browser
  # cd /opt
  # wget https://chromedriver.storage.googleapis.com/2.32/chromedriver_linux64.zip
  # unzip chromedriver_linux64.zip
  # ln -sfn /opt/chromedriver /usr/bin/chromedriver
  # rm chromedriver_linux64.zip

  # cp /vagrant/chromedriver.service /etc/systemd/system/chromedriver.service
  # systemctl enable chromedriver
  # systemctl start chromedriver
}

function install_php5 {
  apt-get install -y php5-cli php5-mysql

  sed -i -E "s/bind-address\s*=\s*127.0.0.1/#bind-address = 127.0.0.1/" /etc/mysql/my.cnf
  systemctl restart mysql
  SQL="${SQL} UPDATE mysql.user SET password=PASSWORD('root') WHERE user='root';"
  SQL="${SQL} FLUSH PRIVILEGES;"
  mysql -e "$SQL"

  cd /tmp
  curl -s http://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
}

function install_php7 {
  apt-get install -y php-cli php-mysql

  sed -i -E "s/bind-address\s*=\s*127.0.0.1/#bind-address = 127.0.0.1/" /etc/mysql/mariadb.conf.d/50-server.cnf
  systemctl restart mariadb
  SQL="UPDATE mysql.user SET plugin='', host='%' WHERE user='root';"
  SQL="${SQL} UPDATE mysql.user SET password=PASSWORD('root') WHERE user='root';"
  SQL="${SQL} FLUSH PRIVILEGES;"
  mysql -e "$SQL"

  cd /tmp
  curl -s http://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
}

$1