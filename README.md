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

At this point, you will want a reasonable amount of disk space to install it as there is things in the git repo that arent really needed by openwrt. There are some scripts under the
scripts directory that will reduce the size of the php code and remove uneeded stuff. If your using a pivot root or overlay FS you probably dont care that much, if you using the root
flash space thats on the router, you probably do. In which case, download it to a seperate machine, run the scripts under the scripts directory and copy it up to the router manually

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
3. implement logins
4. so much error checking... so much...

## DONE:
1. Fixed database code so that SQL injection shouldn't be possible
2. backups code working
3. management address limitations now work
4. cleanup code for pickup ids and token pickups in various cases (token re-init, delete user, etc)
5. radius code
6. email code
7. user editing

## Not in the plan:
1. certificate auth - There are many reasons for this, but ultimately i wanted something simple

## Thanks and Attributions
1. OpenVPN coders http://openvpn.net
2. OpenWRT coders http://openwrt.org
3. phpqrencode project http://phpqrcode.sourceforge.net/
4. Myself for GA4PHP https://github.com/takigama/ga4php (which i have not updated in a while)
5. PHPMailer for PHPMailer https://github.com/PHPMailer/PHPMailer
