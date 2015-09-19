#!/bin/sh

if [ -f /usr/bin/php-cli ]
  then
  PHPCLI="/usr/bin/php-cli"
fi

if [ -f /usr/bin/php ]
  then
  PHPCLI="/usr/bin/php"
fi

FULLPATH=`dirname $0`

# echo "fullpath: $FULLPATH"

cd $FULLPATH

$PHPCLI $FULLPATH/auth.php $*

exit $?
