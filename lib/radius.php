<?php

require_once("radius.class.php");

function radius_doAuth($user, $pass)
{
  $radius = new Radius(db_getConfig('radius.server', '127.0.0.1'), db_getConfig('radius.secret', ''));
  //$radius->SetNasPort(0);
  $radius->SetNasIpAddress(db_getConfig('radius.nasip', '127.0.0.1')); // Needed for some devices, and not auto_detected if PHP not runned through a web server
  if ($radius->AccessRequest($user, $pass)) {
    log_log(1, "radius user auth succeeded for $user");
    return true;
  } else {
    log_log(2, "radius user auth failed for $user");
    return false;
  }
}
?>
