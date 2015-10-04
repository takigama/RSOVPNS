<?php

require_once("ga4php.php");

class MyGA extends GoogleAuthenticator {
  function getData($username) {
    return db_getTokenData($username);
  }

  function putData($username, $data) {
    //error_log("in put for ga as ".print_r(unserialize(base64_decode($data)),true));
    db_putTokenData($username, $data);
  }

  function getUsers() {

  }

  function ga_createTokenForUser($tokentype, $user)
  {
    $thisTokenData = "";
    $tkdata = "";
    for($i=0; $i<368; $i++) {
      $tkdata .= chr(rand(34,125));
    }
    //error_log("tkdata: $tkdata");
    $thisTokenPickupKey = hash(sha256, $tkdata);

    error_log("createing token for ".$user." and ".$tokentype);
    log_log(1, "creating token for $user of $tokentype");
    $this->setUser($user, $tokentype);

    return $thisTokenPickupKey;
  }

}




?>
