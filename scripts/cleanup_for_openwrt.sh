#!/bin/sh

if [ ! -f bin/auth.sh ]
then
	echo "This script shoudl be run from the root of the RSOVPNS source"
	exit 0
fi

chmod a+x bin/*
rm -f README.md
rm -rf assets
rm -rf upstream-source
rm -rf scripts
