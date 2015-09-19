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
- you need basically all the php packages for openwrt
- add 'list interpreter ".php=/usr/bin/php-cgi"' to the "main" in /etc/config/uhttpd
- mkdir /vpn; cd /vpn; git clone https://github.com/takigama/RSOVPNS.git; ln -s /vpn /www/vpn
- reboot or restart uhttpd
- browse to http://ip_of_thing_you_installed_it_on/vpn/
- advisable to create the servers dh key on another box as on the router will take HOURS (goes in /vpn/data/server.dh)
- chmod a+x /vpn/bin/auth.sh

Then on the web gui...
- edit the configuration
- create a user (password isnt mandatory)
- if you gave it a token, pick it up on your device
- donwload the client config
- start the openvpn server (from the status menu item)
- client config should automatically import into the openvpn client (if it knows how)
- connect!
- magic!
