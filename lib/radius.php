<?php

require_once("radius.class.php");

function radius_doAuth($user, $pass)
{
  $radius = new Radius(db_getConfig('radius.server', '127.0.0.1'), db_getConfig('radius.secret', ''));
  $radius->SetNasPort(0);
  $radius->SetNasIpAddress(db_getConfig('radius.nasip', $thisnasip)); // Needed for some devices, and not auto_detected if PHP not runned through a web server
  if ($radius->AccessRequest($user, $pass)) {
    return true;
  } else {
    return false;
  }
}
?>
