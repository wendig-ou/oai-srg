#!/bin/bash -e

if [ -d /vagrant ] || [ -d /c ] ; then
  export APP_ENV=development
  php -S 0.0.0.0:3002 -t tests/_data
else
  vagrant ssh -c "cd /vagrant && bin/serve-test-data.sh"
fi
