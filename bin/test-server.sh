#!/bin/bash -e

if [ -d /vagrant ]; then
  export APP_ENV=test
  php -S 0.0.0.0:3001 -t public public/index.php
else
  vagrant ssh -c "cd /vagrant && bin/test-server.sh"
fi
