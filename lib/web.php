<?php
$WEB_HEADCHECK["WEB"]  = "web_localHeadCheck";
$MENU_LIST["Home"] = "index.php";
$MENU_LIST["Configuration"] = "?action=config";
$MENU_LIST["Users"] = "?action=users";
$PAGEBODY_FUNCTION = "";
$MESSAGE = null;
$MESSAGE_TYPE = 0;

global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION, $MESSAGE, $MESSAGE_TYPE;

function web_buildPage()
{
  session_start();

  if(!web_checkMgmtIP()) {
    header("Location: noaccess.html");
    return;
  }

  if(!isset($_SESSION["logged_in"])) {
    web_doLoginPage();
    return;
  }

  web_doHeadCheck();
  web_doHeaders();

  echo "<div id='container'>";
  web_doPageTop();
  web_doPageMessage();
  web_doPageMiddle();
  web_doPageBottom();
  echo "</div>";
}

function web_doLoginPage()
{
  if(web_doLoginHeadCheck()) {
      header("Location: index.php");
      exit(0);
  }
  web_doHeaders();

  echo "<html>";
  echo "<div id='loginframe' class='loginframe'>";
  echo "<div class='logininsideframe'>";
  echo "<form method='post'>";
  echo "<table><tr><th>Username</th><td><input type='text' name='username' class='logintext'></td></tr>";
  echo "<tr><th>Password</th><td><input type='password' name='password' class='logintext'></td></tr>";
  echo "<tr><td colspan='2'><input type='submit' name='login' value='Login'></td></tr>";
  echo "</table>";
  if(isset($_SESSION["falselogin"])) {
    echo "<div id='failedlogin'>Login Failed</div>";
  }
  echo "</form>";
  echo "</div>";
  echo "</div>";
  echo "</html>";
}

function web_doLoginHeadCheck()
{
  if(isset($_REQUEST["login"])) {
    if(isset($_REQUEST["username"])) {
      if(isset($_REQUEST["password"])) {
        if(web_doLoginValidateWeb()) {
          $_SESSION["logged_in"] = $_REQUEST["username"];
          return true;
        } else {
          $_SESSION["falselogin"] = true;
        }
      }
    }
  }
  return false;
}

function web_checkMgmtIP()
{
  $ips = explode(" ", trim(preg_replace('/\s\s+/', ' ', db_getConfig("admin.allowednetworks", "10.*\r\n192.168.*\r\n127.*\r\n::1\r\n"))));
  $from_addr = $_SERVER["REMOTE_ADDR"];

  //print_r($ips);

  foreach($ips as $val) {
    //$valnew = str_replace(":", '\\:', $val);
    $valnew = $val;
    //file_put_contents("/tmp/f.txt",$valnew);
    //error_log("checking $from_addr against $valnew");

    if(preg_match("/$val/", $from_addr)) {
      //error_log("Yes: $from_addr, $valnew");
      return true;
    } else {
      //error_log("no: $from_addr, $valnew");
    }
  }
  return false;
}

function web_encode($val)
{
  return htmlentities($val, ENT_QUOTES | ENT_COMPAT | ENT_HTML401, ini_get("default_charset"), false);
}

function web_decode($val)
{
  return html_entity_decode($val, ENT_QUOTES | ENT_COMPAT | ENT_HTML401, ini_get("default_charset"));
}

function web_doLoginValidateWeb()
{
  $pass = $_REQUEST["password"];
  $user = $_REQUEST["username"];
  // ... and then....
  return true;
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
      case "createcert":
        web_doCreateCert();
        exit(0);
      break;
      case "createdhkey":
        web_doCreateDH();
        exit(0);
      break;
      case "logout":
        unset($_SESSION["logged_in"]);
        header("Location: index.php");
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
  global $HOMEDIR;
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

  $cmd = "px5g selfsigned -days $valid -newkey rsa:2048 -keyout $HOMEDIR/data/server.key -out $HOMEDIR/data/server.crt -subj \"/C=$country/ST=$st/L=$loc/O=$org/OU=$dept/CN=$cn\" > /dev/null 2>&1";
  //echo("would run: $cmd");
  system("touch ../data/server.key");
  system("touch ../data/server.crt");
  system($cmd);

  $_SESSION["messages"]["cert"]["type"] = 1;
  $_SESSION["messages"]["cert"]["text"] = "New certificate created";

  header("Location: index.php?action=config");
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
  echo "<div id='myheader_outer'><div id='myheader_inner'>Real Simple OpenVPN Server</div></div>"; // Real Simple OpenVPN Service

  echo "<div class='userhelpbox' id='userhelpboxid'>";
  echo "</div>";

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

  echo "<hr>";
  echo "<a href='?action=logout'>Logout</a>";
}

function web_doPageBottom()
{

  echo "<div id='myfooter'><a href='http://www.gnu.org/licenses/gpl-2.0.html'>GPL</a> software by <a href='https://github.com/takigama'>Takigama</a> &copy 2015</div>";
  global $HOMEDIR, $DB_TYPE, $DB_LOCATION;
  echo "<div id='mydebug'>";
  echo "<pre>\n\nServer:\n";
  print_r($_SERVER);
  echo "\n\nRequest:\n";
  print_r($_REQUEST);
  echo "\n\nGlobal:\n";
  print_r($_GLOBAL);
  echo "\n\sSession:\n";
  print_r($_SESSION);
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
  global $HOMEDIR;

  if(isset($_REQUEST["pickupconf"])) {
    ctrl_writeClientFile();
    if(!file_exists("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn")) {
      error_log("fail...");
      echo "<html>Problem...</html>";
    } else {
      $info = file_get_contents("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn");
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . db_getConfig("site.ident").".ovpn");
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: ' . strlen($info));
      echo $info;
    }
    exit(0);
  }

  if(isset($_REQUEST["gettokenimage"])) {
    if(isset($_REQUEST["tkpuid"])) {
      $tid = $_REQUEST["tkpuid"];
      header("Content-Type: image/png");
      echo file_get_contents("../pickup/$tid.png");
      unlink("../pickup/$tid.png");
      db_clearTKIDForUser($tid);
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

  echo "<br>While you are here, you can also download the client configuration file, <a href='?pickupconf'>Here</a>";
  echo "</html>";

  unlink("../pickup/$tid.url");
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
