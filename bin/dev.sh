#!/bin/bash -e

if [ -d /vagrant ]; then
  export APP_ENV=development
  php -S 0.0.0.0:3000 -t public public/index.php
else
  vagrant ssh -c "cd /vagrant && bin/dev.sh"
fi
