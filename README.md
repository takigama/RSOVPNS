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

My test beds are an AR9331 (GL.inet) router and a TPLink TL-WDR4300 - however, they all use a pivot overlay root on an external flash. The repo size is about 4M, which is the size
of the flash on most routers (i.e. you want to cleanup before you copy the code up to the router)

At this point, you will want a reasonable amount of disk space to install it as there is things in the git repo that arent really needed by openwrt. There are some scripts under the
scripts directory that will reduce the size of the php code and remove uneeded stuff. If your using a pivot root or overlay FS you probably dont care that much, if you using the root
flash space thats on the router, you probably do. In which case, download it to a seperate machine, run the scripts under the scripts directory and copy it up to the router manually.

If you really want to play (and at this point it would be good if people did start looking to give me feedback), running it in an x86 VM is a good idea, but heres a rough outline of how you make it work on openwrt:
- opkg update; opkg install openssl openvpn-openssl php5 php5-cgi php5-cli php5-mod-hash php5-mod-mcrypt php5-mod-sqlite3 px5g libopenssl php5-mod-session zoneinfo-core php5-mod-gd openssl-util git git-http
- add 'list interpreter ".php=/usr/bin/php-cgi"' to the "main" in /etc/config/uhttpd
- cd /; git clone https://github.com/takigama/RSOVPNS.git vpn; ln -s /vpn/www/ /www/vpn
- reboot or restart uhttpd
- browse to http://ip_of_thing_you_installed_it_on/vpn/
- advisable to create the servers dh key on another box as on the router will take HOURS on your stereotypical router CPU (goes in /vpn/data/server.dh) with the command "openssl gendh 2048 > server.dh"
- chmod a+x /vpn/bin/auth.sh

Then on the web gui...
- there is no login at the moment, just click the login button
- edit the configuration
- create a user (only one form of auth is mandatory and a username, everything else is optional)
- if you gave it a token, pick it up on your device (you'll see this in the user creation page)
- when you pickup the token, you would pickup the client configuration at the same time
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
2. code to backup data on schedule
3. implement logins on admin pages
4. so much error checking... so much...
5. logging needs to be added to all components
6. stats collcetion
7. a way of running a cron (for purging logs, collecting stats, etc)
8. add a field to the user for their real name?
9. move bulk user creation to a background process
10. add more help
11. pretty stat graphs on the login page via jschart
12. move the current users dialog to an ajax'y table with paging and stuff
13. add token re-sync to the test token page

## DONE:
1. Fixed database code so that SQL injection shouldn't be possible
2. backups code working
3. management address limitations now work
4. cleanup code for pickup ids and token pickups in various cases (token re-init, delete user, etc)
5. radius code
6. email code
7. user editing
8. Added a logging page with ajax and various pieces

## Not in the plan:
1. certificate auth - There are many reasons for this, but ultimately i wanted something simple

## Thanks and Attributions
1. OpenVPN coders http://openvpn.net
2. OpenWRT coders http://openwrt.org
3. phpqrencode project http://phpqrcode.sourceforge.net/
4. Myself and others for GA4PHP https://github.com/takigama/ga4php (which i have not updated in a while)
5. PHPMailer for PHPMailer https://github.com/PHPMailer/PHPMailer
6. Loading gif i cant find an attribution for (https://themarketingoak.files.wordpress.com/2015/07/circle-loading-animation.gif)

## License
1. This code is licensed under the GPL (v2): http://www.gnu.org/licenses/gpl-2.0.html
2. Any code from other sources is licensed under their respective licenses
3. Images not listed in the attributions (or contained in the assets directory) are created by me a licensed under the create commons, attributions share-alive 4.0 http://creativecommons.org/licenses/by-sa/4.0/
