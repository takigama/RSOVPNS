##
# Dockerfile to create a docker image for the RSOVPNS app
#
# @author  takigama
# @date    12 April 2017
# @version $Id$
#

# Our base image
#
FROM php:7.0-apache

ENV TIMEZONE            UTC

RUN mkdir -p /vpn/

RUN docker-php-source extract \
&& apt-get update \
&& apt-get install libmcrypt-dev libldap2-dev nano libsqlite3-dev sqlite3 libpolarssl-dev openvpn -y \
&& rm -rf /var/lib/apt/lists/* \
&& docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
&& docker-php-ext-install ldap pdo pdo_mysql pdo_sqlite \
&& a2enmod rewrite \
&& a2enmod ssl \
&& docker-php-source delete
# pdo_sqlite \

# RUN apt-get update && apt-get install -y sqlite3 && docker-php-ext-install pdo_sqlite

COPY www /vpn/www
COPY scripts /vpn/scripts/
COPY assets /vpn/assets/
COPY config /vpn/config/
COPY data /vpn/data/
COPY lib /vpn/lib/
COPY px5g/px5g.c /


RUN ln -s /vpn/www /var/www/html/vpn
RUN chown www-data /vpn/data
RUN chown www-data /
RUN cc -o /usr/bin/px5g /px5g.c -lpolarssl && rm /px5g.c
