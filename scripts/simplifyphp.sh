#!/bin/sh

for i in `find . -name '*.php'`
do
	cat $i | php -w $i.simpl
	mv $i.simpl $i
done
