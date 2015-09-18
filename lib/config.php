<?php
/*

  TODO: I've moved some of the config components here from the web.php page,
  but i need to move the headercheck functions and things like that

*/

$WEB_HEADCHECK["CONFIG"]  = "conf_localHeadCheck";

function conf_localHeadCheck()
{
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "config":
        $PAGEBODY_FUNCTION = "conf_doConfigurationBody";
      break;
      case "updateconfig":
        conf_doUpdateConfig();
        exit(0);
      break;
      case "updateconfig":
        conf_doUpdateConfig();
        exit(0);
      break;
    }
  }
}

function conf_doUpdateConfig()
{
  $ignore_array = array("Save", "PHPSESSID");
  /*web_doHeaders();
  echo "<html><pre>";
  echo "GETS:\n";
  print_r($_GET);
  echo "\n\nPOST\n";
  print_r($_POST);
  echo "\n\nREQUEST\n";
  print_r($_REQUEST);*/

  foreach($_REQUEST as $key => $val) {
    error_log("doing $key, $val");
    if(in_array($key, $ignore_array)) {
      error_log("---- ignore");
    } else {
      error_log("++++ push");
      // chrome, et al replace . in form var names with _, which is quite a bit annoying
      db_setConfig(str_replace("_", ".", $key), $val);
    }
  }
  //echo "</html></pre>";

  $json = '{ "result": "success", "reason": "Configuration Updated" }';

  echo $json;

  return;
}

function conf_doConfigurationBody()
{
  if(file_exists("../data/server.crt")) {
    exec('openssl x509 -enddate -noout -in ../data/server.crt | cut -f 2 -d\=', $output);
    $certleft = $output[0];
    if($certleft == "") $certleft = "Problem with cert, likely needs to be re-created";
    else {
      error_log("date is from $certdate -  ".print_r(date_parse($certleft), true));
      $td = round((strtotime($certleft)-time())/(24*3600));
      if($td > 0 ) $certleft .= " which is $td days left";
      if($td <= 0 ) $certleft .= " which is $td days AGO!!! This should be re-created";
    }
  } else {
    $certleft = "Not yet created - required for OpenVPN to function";
  }

  if(file_exists("../data/server.dh")) {
    $dhkeyage = date ("F d Y H:i:s.", filemtime("../data/server.dh"));
  } else {
    $dhkeyage = "no key created yet - required for OpenVPN to function";
  }

  echo "<div id='mybodyheading'>Configraution</div><hr>";
  echo "<form method='post' action='?action=updateconfig' id='configform'>";
  echo "<table class='configtable'>";
  echo "<tr><th>Configraution Name</th><th>Value</th><th>Description</th></tr>";
  echo "<tr><td>OpenVPN Port</td><td><input type='text' name='openvpn.port' value='".db_getConfig('openvpn.port', '1194')."'></td><td>The port OpenVPN users connect to (Required)</td></tr>";
  echo "<tr><td>OpenVPN Max Clients</td><td><input type='text' name='openvpn.maxclients' value='".db_getConfig('openvpn.maxclients', '50')."'></td><td>The Maxmimum numnber of clients that can connect to this OpenVPN Instance</td></tr>";
  echo "<tr><td>OpenVPN Multiple Login</td><td><input type='text' name='openvpn.multilogin' value='".db_getConfig('openvpn.multilogin', '1194')."'></td><td>The port OpenVPN users connect to (Required)</td></tr>";
  $proto = db_getConfig('openvpn.protocol', 'udp');
  $udpchecked = "";
  $tcpchecked = "";
  if($proto == 'udp') {
    $udpchecked = " selected";
  }
  if($proto == 'tcp') {
    $tcpchecked = " selected";
  }
  echo "<tr><td>OpenVPN Protocol</td><td><select name='openvpn.protocol'><option value='tcp'$tcpchecked>TCP</option><option value='udp'$udpchecked>UDP</option></select></td>";
    echo "<td>Protocol Used by OpenVPN, UDP Is Prefered</td></tr>";
  echo "<tr><td>Network to use for clients</td><td><input type='text' name='openvpn.clientnetwork' value='".db_getConfig('openvpn.clientnetwork', '10.250.250.0/24')."'></td><td>Network to put clients into (x.x.x.x/y notation)</td></tr>";
  if(db_getConfig('radius.primary') == "on") $rad_prim = 'checked';
  else $rad_prim = "";
  echo "<tr><td>Radius Only Permitted</td><td><input type='hidden' name='radius.primary' value='off'><input type='checkbox' name='radius.primary' $rad_prim></td><td>If a user isn't defined in the local database, do all auth through radius</td></tr>";
  echo "<tr><td>Radius Server</td><td><input type='text' name='radius.server' value='".db_getConfig('radius.server', 'none')."'></td><td>The Server Used for Radius Auth (Optional)</td></tr>";
  echo "<tr><td>Radius Port</td><td><input type='text' name='radius.port' value='".db_getConfig('radius.port', '1819')."'></td><td>The Port Used for Radius Auth (Optional)</td></tr>";
  echo "<tr><td>Radius Secret</td><td><input type='text' name='radius.secret' value='".db_getConfig('radius.secret', '')."'></td><td>The Secret Used for Radius Auth (Optional)</td></tr>";
  echo "<tr><td>Routes to push</td><td><textarea name='openvpn.routes' rows='4'>".db_getConfig('openvpn.routes', "10.0.0.0/8\n192.168.0.0/16\n")."</textarea></td><td>Internal network routes to push to client, one per line</td></tr>";
  echo "<tr><td>Primary DNS</td><td><input type='text' name='openvpn.dns1' value='".db_getConfig('openvpn.dns1', '8.8.8.8')."'></td><td>Primary DNS Server to push to client</td></tr>";
  echo "<tr><td>Secondary DNS</td><td><input type='text' name='openvpn.dns2' value='".db_getConfig('openvpn.dns2', '8.8.4.4')."'></td><td>Secondary DNS Server to push to client</td></tr>";
  echo "<tr><td colspan='3'><input type='submit' name='Save' value='Save' onclick='validateConfigForm()' id='main_configuration_form'></td></tr>";
  echo "</table>";
  echo "</form>";
  echo "<hr>";
  echo "<div id='mybodyheading'>Certificates</div><br>";
  echo "<div id='mybodysubheading'>SSL</div><br>";
  echo "Expiration of current cert is: $certleft<br>";
  echo "<form method='post' action='?action=createcert'>";
  echo "<table class='configtable'>";
  echo "<tr><td>Country</td><td><input type='text' name='cert_country' value='".db_getConfig('cert.country', 'GB')."'></td><td>Two letter country code (e.g. US, AU, CN, etc)</td></tr>";
  // px5g selfsigned -days 2048 -newkey rsa:2048 -keyout f.key -out f.pem -subj "/C=GB/ST=London/L=London/O=Global Security/OU=IT Department/CN=example.com"
  echo "<tr><td>State/Region</td><td><input type='text' name='cert_state' value='".db_getConfig('cert.state', 'London')."''></td><td>State or region of the certificate, (eg NSW, Washington, etc)</td></tr>";
  echo "<tr><td>Locality</td><td><input type='text' name='cert_locality' value='".db_getConfig('cert.locality', 'London')."''></td><td>City or town (e.g. Sydney, New York, Tokyo)</td></tr>";
  echo "<tr><td>Organisation</td><td><input type='text' name='cert_org' value='".db_getConfig('cert.organisation', 'Internet Stuff Org')."''></td><td>Name of the organisation who owns the certificate</td></tr>";
  echo "<tr><td>Department</td><td><input type='text' name='cert_dept' value='".db_getConfig('cert.department', 'IT Admin')."''></td><td>Department who owns the certificate (e.g. Admin)</td></tr>";
  echo "<tr><td>Common Name</td><td><input type='text' name='cert_cn' value='".db_getConfig('cert.commonname', 'net.internal')."''></td><td>Common Name - URL of the cert (e.g. www.mydomain.com)</td></tr>";
  echo "<tr><td>Validity (days)</td><td><input type='text' name='cert_valid' value='".db_getConfig('cert.validity', '3650')."''></td><td>Number of days before the cert expires (e.g. 3650 - which is 10 years)</td></tr>";
  echo "<tr><td colspan='3'><input type='submit' name='Save' value='Create Self-Signed Certificate'> - Note that creating a new cert requires all client configuration to be updated</td></tr>";
  echo "</table>";
  echo "</form>";
  echo "<div id='mybodysubheading'>DH Key</div>";
  echo "Creation date of current key: $dhkeyage<br>";
  echo "Click <a href='?action=createdhkey'>here</a> to begin the process of creating a new key - Note: this can take HOURS!!<br>";


}


?>
