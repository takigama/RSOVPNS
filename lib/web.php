<?php
$WEB_HEADCHECK["WEB"]  = "web_localHeadCheck";
$MENU_LIST["Home"] = "index.php";
$MENU_LIST["Configuration"] = "?action=config";
$MENU_LIST["Users"] = "?action=users";
$MENU_LIST["Status"] = "?action=status";
$PAGEBODY_FUNCTION = "";
$MESSAGE = null;
$MESSAGE_TYPE = 0;

global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION, $MESSAGE, $MESSAGE_TYPE;

function web_buildPage()
{
  session_start();

  web_doHeadCheck();
  web_doHeaders();

  echo "<div id='container'>";
  web_doPageTop();
  web_doPageMessage();
  web_doPageMiddle();
  web_doPageBottom();
  echo "</div>";
}

function web_doHeadCheck()
{
  global $WEB_HEADCHECK;

  foreach($WEB_HEADCHECK as $val) {
    if(function_exists($val)) {
      $val();
    }
  }
}

function web_doPageMessage()
{
  global $MESSAGE, $MESSAGE_TYPE;

  if($MESSAGE != null && $MESSAGE != "") {
    switch($MESSAGE_TYPE) {
      case 1: // info
        echo "<div id='message_outer'><div id='message_info'>Info: $MESSAGE</div></div>";
      break;
      case 2: // warning
        echo "<div id='message_outer'><div id='message_warning'>Warning: $MESSAGE</div></div>";
      break;
      case 3: // error
        echo "<div id='message_outer'><div id='message_error'>Error: $MESSAGE</div></div>";
      break;
    }
  }

  if(isset($_SESSION["messages"])) {
    foreach($_SESSION["messages"] as $messages) {
      $ses_mess = $messages["text"];
      $ses_type = $messages["type"];
      switch($ses_type) {
        case 1: // info
          echo "<div id='message_outer'><div id='message_info'>Info: $ses_mess</div></div>";
        break;
        case 2: // warning
          echo "<div id='message_outer'><div id='message_warning'>Warning: $ses_mess</div></div>";
        break;
        case 3: // error
          echo "<div id='message_outer'><div id='message_error'>Error: $ses_mess</div></div>";
        break;
      }
    }

    unset($_SESSION["messages"]);
  }
}

function web_localHeadCheck()
{
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "config":
        $PAGEBODY_FUNCTION = "web_doConfigurationBody";
      break;
      case "status":
        $PAGEBODY_FUNCTION = "web_normalPageBody";
      break;
      case "createcert":
        web_doCreateCert();
        exit(0);
      break;
      case "createdhkey":
        web_doCreateDH();
        exit(0);
      break;
      case "updateconfig":
        web_doUpdateConfig();
        exit(0);
      break;
    }
  }
}

function web_doCreateDH()
{
  $cmd = "openssl gendh 2048 2> /tmp/dh.log > ../data/server.dh &";

  system($cmd);

  $_SESSION["messages"]["dh"]["type"] = 1;
  $_SESSION["messages"]["dh"]["text"] = "DH Key Createion Stated";

  header("Location: index.php?action=config");

}

function web_doCreateCert()
{
  // TODO: lots of validity checking
  $country = $_REQUEST["cert_country"];
  $st = $_REQUEST["cert_state"];
  $loc = $_REQUEST["cert_locality"];
  $org = $_REQUEST["cert_org"];
  $dept = $_REQUEST["cert_dept"];
  $cn = $_REQUEST["cert_cn"];
  $valid = $_REQUEST["cert_valid"];

  db_setConfig("cert.country", $country);
  db_setConfig("cert.state", $st);
  db_setConfig("cert.locality", $loc);
  db_setConfig("cert.organisation", $org);
  db_setConfig("cert.department", $dept);
  db_setConfig("cert.commonname", $cn);
  db_setConfig("cert.validity", $valid);

  $cmd = "px5g selfsigned -days $valid -newkey rsa:2048 -keyout ../data/server.key -out ../data/server.crt -subj \"/C=$country/ST=$st/L=$loc/O=$org/OU=$dept/CN=$cn\"";
  error_log("would run: $cmd");
  system("touch ../data/server.key");
  system("touch ../data/server.crt");

  $_SESSION["messages"]["cert"]["type"] = 1;
  $_SESSION["messages"]["cert"]["text"] = "New certificate created";

  header("Location: index.php?action=config");
}

function web_doUpdateConfig()
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

  header("Location: index.php?action=config");
}

function web_doConfigurationBody()
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
  echo "<tr><td>OpenVPN Protocol</td><td><select name='openvpn.protocol'><option value='tcp'>TCP</option><option value='udp'>UDP</option></select></td>";
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
  echo "<tr><td colspan='3'><input type='submit' name='Save' value='Save' onclick='validateConfigForm()'></td></tr>";
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


function web_doHeaders()
{
  $dir = "../www/js/";
  if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
          while (($file = readdir($dh)) !== false) {
              //echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
              if(preg_match("/.*\.js/", $file)) echo "<script type='text/javascript' src='js/$file'></script>";
          }
          closedir($dh);
      }
  }

  $dir = "../www/css/";
  if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
          while (($file = readdir($dh)) !== false) {
              //echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
              if(preg_match("/.*\.css/", $file)) echo "<link rel='stylesheet' href='css/$file' type='text/css' />";
          }
          closedir($dh);
      }
  }
}

function web_doPageTop()
{
  echo "<div id='myheader_outer'><div id='myheader_inner'>LMNOP</div></div>"; // Real Simple OpenVPN Service
}

function web_doPageMiddle()
{


  echo "<div id='mymenu'>";
  web_doPageMenu();
  echo "</div>";

  echo "<div id='mybody'>";
  web_doPageBody();
  echo "</div>";

}

function web_doPageBody()
{
  global $PAGEBODY_FUNCTION;

  if($PAGEBODY_FUNCTION == "") {
    // normal page boxy goes here
    web_normalPageBody();
  } else {
    if(function_exists($PAGEBODY_FUNCTION)) {
      $PAGEBODY_FUNCTION();
    } else {
      echo ".... body function error ....";
      web_normalPageBody();
    }
  }
}

function web_normalPageBody()
{
  echo "<div id='mybodyheading'>Status</div>";
  echo "normal page body...";
}

function web_doPageMenu()
{
  echo "<div id='menu_name'>Menu</div><hr>";
  global $WEB_HEADCHECK, $MENU_LIST;
  foreach($MENU_LIST as $name => $mlist) {
    echo "<li><a class='menuitem' id='mi_$name' href='$mlist'>$name</a><br>";
  }
}

function web_doPageBottom()
{

  echo "<div id='myfooter'>Copywrite PJR 2015</div>";
  global $HOMEDIR, $DB_TYPE, $DB_LOCATION;
  echo "<div id='mydebug'>";
  echo "<pre>\n\nServer:\n";
  print_r($_SERVER);
  echo "\n\nRequest:\n";
  print_r($_REQUEST);
  echo "\n\nGlobal:\n";
  print_r($_GLOBAL);
  echo "\n\nconfigs\nHOMEDIR: $HOMEDIR\nDBTYPE: $DB_TYPE\nDB_LOC: $DB_LOCATION\n";
  echo "\n\nend\n</pre>";
  echo "</div>";
}

/* **********************

Token pickup functions start here

*/


function web_buildPagePickup()
{
  web_pickupCheckPost();

  web_doHeaders();

  if(isset($_REQUEST["tkpuid"])) {
    $tid = $_REQUEST["tkpuid"];
    if(file_exists("../pickup/$tid.url")) {
      web_pickupBeginInstruction();
    } else {
      web_pickupNoToken();
    }
  } else {
    web_pickupNoID();
  }

  web_doPageBottom();
}

function web_pickupCheckPost()
{
  if(isset($_REQUEST["gettokenimage"])) {
    if(isset($_REQUEST["tkpuid"])) {
      $tid = $_REQUEST["tkpuid"];
      header("Content-Type: image/png");
      echo file_get_contents("../pickup/$tid.png");
      exit(0);
    }
  }

  if(isset($_REQUEST["sure"])) {
    if(isset($_REQUEST["tkpuid"])) {
      $tid = $_REQUEST["tkpuid"];
      web_pickupDoPickupPage();
    }
  }
}

function web_pickupDoPickupPage()
{

  $tid = $_REQUEST["tkpuid"];

  $url = file_get_contents("../pickup/$tid.url");

  web_doHeaders();

  echo "<html>";
  echo "<h1>Heres your token</h1>";
  echo "<b>Once you have a key in Google Authenticator, be sure to close this page!</b><br><br>";
  echo "If your browsing from the mobile device with google authenticator installed, click the <a href='$url'>Here</a> to import your key.<br><br>";
  echo "If you are browsing this page from your desktop, scan the following QRcode using the google authenticator software<br><img src='pickup.php?tkpuid=$tid&gettokenimage'>";


  echo "</html>";

  exit(0);
}

function web_pickupBeginInstruction()
{
  echo file_get_contents("../templates/pickup/page1.html");
}

function web_pickupNoToken()
{
  echo file_get_contents("../templates/pickup/notoken.html");
}

function web_pickupNoID()
{
  echo file_get_contents("../templates/pickup/noid.html");
}



?>
