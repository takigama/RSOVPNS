<?php

$MENU_LIST["Management"] = "?action=status";
$WEB_HEADCHECK["Control"]  = "ctrl_localHeadCheck";

function ctrl_localHeadCheck()
{
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "status":
        $PAGEBODY_FUNCTION = "ctrl_statusPageBody";
      break;
      case "stopopenvpn":
        ctrl_stopOpenVpn();
        header("Location: index.php?action=status");
        exit(0);
      break;
      case "startopenvpn":
        ctrl_startOpenVpn();
        header("Location: index.php?action=status");
        exit(0);
      break;
      case "restartopenvpn":
        ctrl_restartOpenVpn();
        header("Location: index.php?action=status");
        exit(0);
      break;
      case "createbackup":
        ctrl_backupData();
        exit(0);
      break;
      case "downloadbackup":
        ctrl_downloadBackup();
        exit(0);
      break;
      case "restorefrominplace":
        ctrl_restoreFromInPlace();
        exit(0);
      break;
    }
  }
}

function ctrl_downloadBackup()
{
  global $HOMEDIR;

  $info = file_get_contents("$HOMEDIR/data/backup.bk");
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="backup.bk"');
  header('Content-Transfer-Encoding: binary');
  header('Content-Length: ' . strlen($info));
  echo $info;

}

function ctrl_backupData()
{
  /*
  tar cfz - data | php_encrypt | [ email | download]

  */

  $cmd = "cd ../; tar cfz - data | openssl enc -aes-256-cbc -k '".db_getConfig('backup.key', '12345678')."' > /tmp/.backuptest.tar.gz; mv /tmp/.backuptest.tar.gz data/backup.bk";

  system("$cmd > /tmp/backup.log 2>&1 &");

  $json = '{ "result": "success", "reason": "Backup Started" }';

  echo $json;

}

function ctrl_restoreFromInPlace()
{
  /*
  tar cfz - data | php_encrypt | [ email | download]

  */

  $key = db_getConfig('backup.key', '12345678');
  $cmd = "cd ../; cat data/backup.bk | openssl aes-256-cbc -d -k $key | tar xfz -";

  system("$cmd > /tmp/restore.log 2>&1 &");

  $json = '{ "result": "success", "reason": "Restore Started" }';

  echo $json;

}

function ctrl_statusPageBody()
{

  $ss = ctrl_serverStatus();
  if($ss) {
    $server_status = "<div id='server_running'>Running</div>";
    $control_options = "<a href='?action=stopopenvpn' onclick='return confirm_stop_server()'>Stop</a></td><td><a href='?action=restartopenvpn' onclick='return confirm_restart_server()'>Restart</a>";
    $n_users = 10; // TODO: fix this
  } else {
    $server_status = "<div id='server_stopped'>Stopped</div>";
    $control_options = "<a href='?action=startopenvpn'>Start</a>";
    $n_users = 0;
  }
  echo "<div class='mybodyheading'>Status</div><hr>";
  echo "<div class='mybodysubheading'>OpenVPN</div>";
  echo "<table class='configtable'>";
  echo "<tr><th>Server Status</th><td>$server_status</td><td class='control_tr'>$control_options</td></tr>";
  echo "<tr><th>Number of Users</th><td>$n_users</td></tr>";
  echo "</table>";

  if(file_exists("../data/backup.bk")) {
    $backuptime = date ("F d Y H:i:s.", filemtime("../data/backup.bk"));
    $td = round((filemtime("../data/backup.bk")-time())/(24*3600));
    if($td > 1 ) $bk_time = "$td days ago ($backuptime)";
    if($td <= 0 ) $bk_time = " Today ($backuptime)";
    $download = "<a href='index.php?action=downloadbackup'>Download</a>";
  } else {
    $bk_time = "None Exists";
    $download = "";
  }

  echo "<hr><div class='mybodysubheading'>Backups</div>";
  echo "<table class='configtable'>";
  echo "<tr><th>Last Backup</th><td>$bk_time</td><td class='control_tr'><a href='#' onclick='return send_do_backup()'>Create</a></td></tr>";
  echo "<tr><th>Download backup</th><td>$download</td></tr>";
  echo "<tr><th>Restore (Upload)</th><td>backup restore thingo</td></tr>";
  echo "<tr><th>Restore (from current)</th><td  class='control_tr'><a href='#' onclick='return send_do_restore_inplace()'>Begin</a></td></tr>";
  echo "</table>";


}

function ctrl_stopOpenVpn()
{
  global $HOMEDIR;

  $pid = trim(file_get_contents("$HOMEDIR/data/openvpn.pid"));
  $cmd = "/bin/kill $pid";

  system("$cmd");

  $_SESSION["messages"]["ctrl"]["type"] = 1;
  $_SESSION["messages"]["ctrl"]["text"] = "OpenVPN Server Has Been Stopped";

}

function ctrl_startOpenVpn()
{
  global $HOMEDIR;

  ctrl_writeConfigFile();
  ctrl_writeClientFile();

  if(ctrl_serverStatus()) {
    $_SESSION["messages"]["ctrl"]["type"] = 2;
    $_SESSION["messages"]["ctrl"]["text"] = "OpenVPN Server Appears to be running - not starting";
    return;
  }

  //   672 root      2744 S    /usr/sbin/openvpn --syslog openvpn(usercon) --cd /var/etc --config openvpn-usercon.conf
  // command line ... openvpn --writepid $HOMEDIR/data/openvpn.pid --config $HOMEDIR/data/openvpn.conf --syslog


  $cmd = "/usr/sbin/openvpn --writepid $HOMEDIR/data/openvpn.pid --config $HOMEDIR/data/openvpn.conf --syslog";

  system("$cmd > /tmp/vpn.log 2> /tmp/vpn.log.2 &");

  $_SESSION["messages"]["ctrl"]["type"] = 1;
  $_SESSION["messages"]["ctrl"]["text"] = "OpenVPN Server Has Been Started";

}

function ctrl_restartOpenVpn()
{
  ctrl_stopOpenVpn();
  ctrl_startOpenVpn();

  $_SESSION["messages"]["ctrl"]["type"] = 1;
  $_SESSION["messages"]["ctrl"]["text"] = "OpenVPN Server Has Been Re-Started";
}

function ctrl_writeConfigFile()
{
  global $HOMEDIR;

  /*
  ca /etc/easy-rsa/keys/ca.crt
  cert /etc/easy-rsa/keys/server.crt
  comp-lzo yes
  dev tun_usercon
  dh /etc/easy-rsa/keys/dh2048.pem
  keepalive 5 60
  key /etc/easy-rsa/keys/server.key
  port 1194
  proto udp
  script-security 2
  server 10.10.7.0 255.255.255.0
  verb 3
  push "route 10.10.0.0 255.255.0.0"
  push "dhcp-option DNS 10.10.0.59"
  push "dhcp-option DNS 10.10.0.20"
  push "dhcp-option SEARCH pjr.cc"
  push "dhcp-option DOMAIN pjr.cc"
  */
  $config = "client-cert-not-required\nca /vpn/data/server.crt\ncert /vpn/data/server.crt\ndh /vpn/data/server.dh\nkey /vpn/data/server.key\n";
  $config .= "dev tun_rsopenvpn_1\n";
  $config .= "comp-lzo yes\n";
  $config .= "keepalive 5 60\nport ".db_getConfig("openvpn.port", "1194")."\n";
  $config .= "proto ".db_getConfig("openvpn.proto", "udp")."\nscript-security 2\nserver ".db_getConfig('openvpn.clientnetwork')."\n";
  $config .= "verb 3\npush \"route ".trim(preg_replace('/\s\s+/', ' ', db_getConfig("openvpn.routes")))."\"\n";
  $config .= "push \"dhcp-option DNS ".db_getConfig("openvpn.dns1")."\"\npush \"dhcp-option DNS ".db_getConfig("openvpn.dns2")."\"\n";
  $config .= "auth-user-pass-verify $HOMEDIR/bin/auth.sh via-file\n";


  file_put_contents("$HOMEDIR/data/openvpn.conf", $config);
}

function ctrl_writeClientFile()
{
  global $HOMEDIR;

  // TODO: fix remote!!!!
  $server_remote = $_SERVER['SERVER_NAME'];
  $config = "remote $server_remote\nport ".db_getConfig("openvpn.port")."\nkeepalive 5 60\nauth-user-pass\nclient\n";
  $config .= "dev tun\nverb 3\ncomp-lzo yes\npersist-key\npersist-tun\n\n<ca>\n".file_get_contents("$HOMEDIR/data/server.crt")."\n</ca>\n";

  file_put_contents("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn", $config);
}


function ctrl_serverStatus()
{
  global $HOMEDIR;

  if(file_exists("$HOMEDIR/data/openvpn.pid")) {
    $pid = trim(file_get_contents("$HOMEDIR/data/openvpn.pid"));
    if(file_exists("/proc/$pid")) {
      return true;
    }
  }

  return false;
}

?>
