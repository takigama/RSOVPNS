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
      case "sendtestemail";
        ctrl_sendtestemail();
        exit(0);
      break;
    }
  }
}

function ctrl_sendtestemail()
{
  $mail = new PHPMailer;

  $mail->isSMTP();
  $mail->Host = db_getConfig("smtp.server");
  $mail->Port = db_getConfig("smtp.port");
  $mail->setFrom = db_getConfig("smtp.fromemail");
  $mail->addAddress($_REQUEST["testemailto"]);
  $mail->Subject = "This is a test email from your Simple VPN software";
  $mail->Body = "Hi,\nThis is your simple vpn software sending you a test email because you\nhave asked it to (hopefully)\n";
  $mail->SMTPAuth = false;

  $smtp_user = db_getConfig("smtp.username");
  $smtp_pass = db_getConfig("smtp.password");
  if($smtp_user !="") {
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
  }

  $json = "";
  if(!$mail->send()) {
    log_log(3, "Sending test email failed with ".$mail->ErrorInfo);
    $json = '{ "result": "failure", "reason": "'.$mail->ErrorInfo.'"}';
  } else {
    log_log(1, "Sending test email succeeded");
    $json = '{ "result": "success", "reason": "E-Mail Sent Successfully" }';
  }
  echo $json;
  return;
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

  log_log(1, "Backup of system taken");
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

  log_log(2, "Restore of system from in-place backup completed");
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
  echo "<div class='mybodysubheading'>OpenVPN <a href='#' onmouseover='show_help(\"openvpn_status.html\")' onmouseout='hide_help()'>?</a></div>";
  echo "<table class='configtable'>";
  echo "<tr><th>Server Status</th><td>$server_status</td><td class='control_tr'>$control_options</td></tr>";
  echo "<tr><th>Number of Users</th><td>$n_users</td></tr>";
  echo "<form id='testemailform'><tr><th>Send Test Email</th><td><input type='text' name='testemailto'></td>";
  echo "<td><input type='submit' name='Send Test Email' value='Send Test Email' id='send_test_email'><div id='send_test_email_scroller'></div></td></tr></form>";
  echo "</table>";

  if(file_exists("../data/backup.bk")) {
    $backuptime = date ("F d Y H:i:s.", filemtime("../data/backup.bk"));
    $td = round((filemtime("../data/backup.bk")-time())/(24*3600));
    if($td > 1 ) $bk_time = "$td days ago ($backuptime)";
    if($td <= 0 ) $bk_time = " Today ($backuptime)";
    $download = "<a href='index.php?action=downloadbackup'>Download</a>";
  } else {
    $bk_time = "None Exists";
    $download = "None Exists";
  }

  echo "<hr><div class='mybodysubheading'>Backups <a href='#' onmouseover='show_help(\"backups.html\")' onmouseout='hide_help()'>?</a></div>";
  echo "<table class='configtable'>";
  echo "<tr><th>Last Backup</th><td>$bk_time</td><td class='control_tr'><a href='#' onclick='return send_do_backup()'>Create</a></td><td class='control_tr'><a href='#' onclick='return send_do_restore_inplace()'>Restore</a></td></tr>";
  echo "<tr><th>Download backup</th><td>$download</td></tr>";
  echo "<tr><th>Restore (Upload)</th><td>backup restore thingo</td></tr>";
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
  log_log(2, "OpenVPN service has been stopped");

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
  log_log(1, "OpenVPN service has been started");

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
  $config .= "verb 3\n";
  $routelist = preg_split('/\s\s+/', db_getConfig("openvpn.routes"));
  foreach($routelist as $route) {
    if($route != "") {
      $config .= "push \"route $route\"\n";
    }
  }
  $config .= "push \"dhcp-option DNS ".db_getConfig("openvpn.dns1")."\"\npush \"dhcp-option DNS ".db_getConfig("openvpn.dns2")."\"\n";
  $config .= "auth-user-pass-verify $HOMEDIR/bin/auth.sh via-file\n";


  file_put_contents("$HOMEDIR/data/openvpn.conf", $config);
  log_log(1, "OpenVPN server configuration file was wrriten");
}

function ctrl_writeClientFile()
{
  global $HOMEDIR;

  // TODO: fix remote!!!!
  $server_remote = $_SERVER['SERVER_NAME'];
  $config = "remote $server_remote\nport ".db_getConfig("openvpn.port")."\nkeepalive 5 60\nauth-user-pass\nclient\n";
  $config .= "dev tun\nverb 3\ncomp-lzo yes\npersist-key\npersist-tun\n\n<ca>\n".file_get_contents("$HOMEDIR/data/server.crt")."\n</ca>\n";

  file_put_contents("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn", $config);
  log_log(1, "OpenVPN client configuration file was wrriten");

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
