#!/bin/bash -e

COMMAND="APP_ENV=development php -S 0.0.0.0:3000 -t public public/index.php"

if [ -d /vagrant ]; then
  $COMMAND
else
  vagrant ssh -c "cd /vagrant && $COMMAND"
fi
