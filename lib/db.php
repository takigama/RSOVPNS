<?php

global $HOMEDIR, $DB_TYPE, $DB_LOCATION, $MESSAGE, $MESSAGE_TYPE;

try {
  if(!file_exists($DB_LOCATION)) {
    $ourdb = new SQLite3($DB_LOCATION);
    db_createDB();
  }
  $ourdb = new SQLite3($DB_LOCATION);
} catch (Exception $e) {

  $ourdb = null;
  $MESSAGE = "Failed to open database...";
  $MESSAGE_TYPE = 1;
}

global $ourdb;

function db_getUsers()
{
  global $ourdb;

  $users = $ourdb->query("select * from users");

  $retval = array();
  $n = 0;

  while($row = $users->fetchArray()) {
    $retval[$n] = $row;
    $n++;
  }

  return $retval;
}

function db_createDB()
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  // create the config table
  $ourdb->exec("CREATE TABLE `config` (
  	`CID`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  	`Name`	TEXT NOT NULL,
  	`Value`	INTEGER NOT NULL
  )");

  // create the user table
  $ourdb->exec("CREATE TABLE `users` (
  	`UID`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  	`Username`	TEXT NOT NULL,
  	`EMail`	TEXT,
  	`Domain`	TEXT DEFAULT 'main',
  	`Enabled`	INTEGER NOT NULL DEFAULT '-1',
  	`GAData`	TEXT,
  	`TokenPickupKey`	TEXT,
  	`Password`	TEXT,
  	`Radius`	INTEGER NOT NULL
  )");

  $MESSAGE_TYPE=1;
  $MESSAGE = "Created database";

}

function db_putTokenData($user, $tokendata)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  $sql = "update users set GAData='$tokendata' where Username='$user'";

  $ourdb->exec($sql);
}

function db_getTokenData($user)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  $sql = "select GAData from users where Username='$user'";

  $userdata = $ourdb->query($sql);

  while($row = $userdata->fetchArray()) {
    $retval = $row[0];
  }

  return $retval;
}

function db_getTokenPickupKey($user)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  $sql = "select TokenPickupKey from users where Username='$user'";

  $userdata = $ourdb->query($sql);

  while($row = $userdata->fetchArray()) {
    $retval = $row[0];
  }

  return $retval;
}

function db_userExists($lo_username)
{
  // TODO implement
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  $sql = "select count(*) from users where Username = '$lo_username'";

  $nu = $ourdb->query($sql);

  error_log("sql: ".$sql);
  error_log("doh: ".print_r($nu, true));

  $num = $nu->fetchArray()[0];

  if($num != 0) {
    error_log("user exists, return true");
    return true;
  }
  error_log("user not exists, return false");

  return false;
}

function db_createUser($username, $email, $pass, $radius, $token, $enabled, $token_type)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;



  if($pass != "" ) $pass_hash = hash(sha256, $pass);
  else $pass_hash = "";
  $thisTokenData = "";
  $thisTokenPickupKey = "";

  $sql = "insert into users (Username, EMail, Enabled, Password, Radius) values ('$username', '$email', '$enabled', '$pass_hash', '$radius')";

  $ourdb->exec($sql);

  if($token == 1) {
    // create token and pickup url here
    $myga = new MyGA();
    $thisTokenPickupKey = $myga->ga_createTokenForUser($token_type, $username);
  }
  $sql = "update users set TokenPickupKey='$thisTokenPickupKey' where Username='$username'";

  $ourdb->exec($sql);

}

?>
