#!/bin/bash -e

source .env.development

if which mysqldump > /dev/null; then
  OPTS="-d -h $SRG_DB_HOST -u $SRG_DB_USERNAME -p$SRG_DB_PASSWORD $SRG_DB_DBNAME"
  mysqldump $OPTS > tests/_data/dump.sql
fi
