# RSOVPNS
Real Simple OpenVPN Server is a simple chunk of software (primarily at this time aimed at openwrt)
that provides a simple gui to an OpenVPN service. 

What it has is:
- simple gui for config
- multi-factor authentication (radius, local password and google authenticator)
- inbuild Google Authicator server (on-box two-factor auth)
- simple controls for starting/stopping/managing OpenVPN

Its NOT PRODUCTION WORTHY by any stretch - in fact, running it would be very detrimental to your
routers health.

My test beds are an AR9331 (GL.inet) router and a TPLink TL-WDR4300

If you really want to play, heres a rough outline of how you make it work on openwrt:
- you need basically all the php packages for openwrt (but dont add php5-pecl-apc, it causes a crash with any code, not just mine)
- add 'list interpreter ".php=/usr/bin/php-cgi"' to the "main" in /etc/config/uhttpd
- mkdir /vpn; cd /vpn; git clone https://github.com/takigama/RSOVPNS.git; ln -s /vpn/www/ /www/vpn
- reboot or restart uhttpd
- browse to http://ip_of_thing_you_installed_it_on/vpn/
- advisable to create the servers dh key on another box as on the router will take HOURS (goes in /vpn/data/server.dh) with the command "openssl gendh 2048 > server.dh"
- chmod a+x /vpn/bin/auth.sh

Then on the web gui...
- there is no login at the moment, just click the login button
- edit the configuration
- create a user (password isnt mandatory)
- if you gave it a token, pick it up on your device
- donwload the client config
- start the openvpn server (from the status menu item)
- client config should automatically import into the openvpn client (if it knows how)
- connect!
- magic!

If you do use two factors (radius + google authenticator or password + google authenticator) note that openvpn
clients only allow a single entry for password, to have both, you concat them together. So if your password
was "password" and your google authenticator read "123456" you would type "123456password" into the password
dialog.

## Why PHP?
Yes, its aimed at openwrt so LUA would have been a better option perhaps, but I also want it to be
portable, and so i've made it in php where it can be easily ported to run on linux/BSD

## TODO:
1. form validation
2. radius code
3. email code

## DONE:
1. Fixed database code so that it isnt possible to create an sql injection

## Not in the plan:
1. certificate auth - There are many reasons for this, but ultimately i wanted something simple

## Thanks and Attributions
1. OpenVPN coders http://openvpn.net
2. OpenWRT coders http://openwrt.org
3. phpqrencode project http://phpqrcode.sourceforge.net/
4. Myself for GA4PHP https://github.com/takigama/ga4php (which i have not updated in a while)
