<?php
require_once("../config/config.php");
require_once("../lib/lib.php");

// we get the user/pass via file passed on cmd line
// TODO: add some error checking here
$fh = fopen($argv[1], "r");
$user = trim(fgets($fh, 4096));
$pass = trim(fgets($fh, 4096));

$rad_only = db_getConfig("radius.primary", 0);

if($rad_only=="on" && !db_userExists($user)) {
  error_log("User auth failed - no radius only and user not in database");
  failAuth();;
}

if($rad_only==1 && !db_userExists($user)) {
  $result = radius_doAuth($user, $pass);
  if(!$result) failAuth();
  else suceessAuth();
}

$userDetails = db_getUser($user);
//print_r($userDetails);

if($userDetails["Enabled"] == 0) {
  error_log("User not enabled");
  failAuth();
}

if($userDetails["GAData"] != "") {
  // do a token auth on first 6 chars
  $token = substr($pass, 0, 6);
  error_log("token auth on $token");
  $myga = new MyGA();
  if(!$myga->authenticateUser($user, $token)) {
    error_log("user token failure");
    failAuth();
  }
  $newpass = substr($pass, 6);

  //error_log("newpass is $newpass");
  $pass = $newpass;
}

if($userDetails["Radius"] == 1) {
  $result = radius_doAuth($user, $pass);
  if(!$result) {
    error_log("user failed radius auth");
    failAuth();
  }
}

if($userDetails["Password"] != "") {
  if(hash(sha256, $pass) != $userDetails["Password"]) {
    error_log("user failed password auth");
    failAuth();
  }
}


// if we make it here, auth is sueccessful
successAuth();

function failAuth()
{
  error_log("auth fail");
  exit(1);
}

function successAuth()
{
  error_log("auth success");
  exit(0);
}
?>
