#!/bin/bash -e

function base {
  apt-get -y update
  apt-get -y upgrade

  apt-get install -y \
    mariadb-server mariadb-client php-cli php-mysql php-mbstring curl git-core \
    unzip net-tools

  sed -i -E "s/bind-address\s*=\s*127.0.0.1/#bind-address = 127.0.0.1/" /etc/mysql/mariadb.conf.d/50-server.cnf
  systemctl restart mariadb
  SQL="UPDATE mysql.user SET plugin='', host='%' WHERE user='root';"
  SQL="${SQL} UPDATE mysql.user SET password=PASSWORD('root') WHERE user='root';"
  SQL="${SQL} FLUSH PRIVILEGES;"

  mysql -e "$SQL"

  cd /tmp
  curl -s http://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer

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

$1