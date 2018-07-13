#!/bin/bash -e

if [ -d /vagrant ]; then
  export APP_ENV=development
  php vendor/bin/codecept run
else
  vagrant ssh -c "cd /vagrant && php vendor/bin/codecept run"
fi
