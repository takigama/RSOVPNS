<?php

$prepares = array();
global $HOMEDIR, $DB_TYPE, $DB_LOCATION, $MESSAGE, $MESSAGE_TYPE, $prepares;

try {
  //echo "attempting database\n";
  if(!file_exists($DB_LOCATION)) {
    $ourdb = new SQLite3($DB_LOCATION);
    db_createDB();
  }
  $ourdb = new SQLite3($DB_LOCATION);
} catch (Exception $e) {
  //echo "exception\n".print_r($e)."\n";
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
  //error_log("rows from select is $n");

  return $retval;
}

function db_getUser($username)
{
  global $ourdb, $prepares;

  if(!isset($prepares["getusers"])) {
    $prepares["getusers"] = $ourdb->prepare("select * from users where Username=:user");
  }
  $prepares["getusers"]->bindValue(':user', $username, SQLITE3_TEXT);
  $rows = $prepares["getusers"]->execute();

  $retval = array();
  $n = 0;

  while($row = $rows->fetchArray()) {
    $retval = $row;
    $n++;
  }
  //error_log("rows from select is $n");

  return $retval;
}


function db_deleteUser($username)
{
  global $ourdb, $prepares;

  if(!isset($prepares["deluser"])) {
    $prepares["deluser"] = $ourdb->prepare("delete from users where Username=:user");
  }
  $prepares["deluser"]->bindValue(':user', $username, SQLITE3_TEXT);
  $prepares["deluser"]->execute();

  return true;
}

function db_getConfig($key, $default = -1)
{
  global $ourdb;

  $users = $ourdb->query("select Value from config where Name='$key'");
  if(!isset($prepares["getconfig"])) {
    $prepares["getconfig"] = $ourdb->prepare("select Value from config where Name=:name");
  }
  $prepares["getconfig"]->bindValue(':name', $key, SQLITE3_TEXT);
  $rows = $prepares["getconfig"]->execute();


  $retval = array();
  $n = 0;

  while($row = $rows->fetchArray()) {
    $retval = $row[0];
    $n++;
  }

  if($n == 0) {
    //error_log("cant find value for $key, sending default, $default");
    return $default;
  } else {
    //error_log("value for $key was $retval, sending");
    return $retval;
  }
}

function db_setConfig($key, $value)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  $cval = db_getConfig($key);

  if($cval == -1) {
    if(!isset($prepares["deleteconfig"])) {
      $prepares["deleteconfig"] = $ourdb->prepare("delete from config where Name=:name");
    }
    $prepares["deleteconfig"]->bindValue(':name', $key, SQLITE3_TEXT);
    $prepares["deleteconfig"]->execute();

    if(!isset($prepares["insertconfig"])) {
      $prepares["insertconfig"] = $ourdb->prepare("insert into config (Name, Value) values (:key, :value)");
    }
    $prepares["insertconfig"]->bindValue(':key', $key, SQLITE3_TEXT);
    $prepares["insertconfig"]->bindValue(':value', $value, SQLITE3_TEXT);
    $prepares["insertconfig"]->execute();

  } else {
    if(!isset($prepares["updateconfig"])) {
      $prepares["updateconfig"] = $ourdb->prepare("update config set Value=:value where Name=:key");
    }
    $prepares["updateconfig"]->bindValue(':key', $key, SQLITE3_TEXT);
    $prepares["updateconfig"]->bindValue(':value', $value, SQLITE3_TEXT);
    $prepares["updateconfig"]->execute();
  }

  return true;
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

  if(!isset($prepares["updateusers"])) {
    $prepares["updateusers"] = $ourdb->prepare("update users set GAData=:gadata where Username=:user");
  }
  $prepares["updateusers"]->bindValue(':gadata', $tokendata, SQLITE3_TEXT);
  $prepares["updateusers"]->bindValue(':user', $user, SQLITE3_TEXT);
  $prepares["updateusers"]->execute();
}

function db_getTokenData($user)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["getgadata"])) {
    $prepares["getgadata"] = $ourdb->prepare("select GAData from users where Username=:user");
  }
  $prepares["getgadata"]->bindValue(':user', $user, SQLITE3_TEXT);
  $userdata = $prepares["getgadata"]->execute();

  while($row = $userdata->fetchArray()) {
    $retval = $row[0];
  }

  return $retval;
}

function db_getTokenPickupKey($user)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["gettkid"])) {
    $prepares["gettkid"] = $ourdb->prepare("select TokenPickupKey from users where Username=:user");
  }
  $prepares["gettkid"]->bindValue(':user', $user, SQLITE3_TEXT);
  $userdata = $prepares["gettkid"]->execute();

  while($row = $userdata->fetchArray()) {
    $retval = $row[0];
  }

  return $retval;
}

function db_userExists($lo_username)
{
  // TODO implement
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["userexists"])) {
    $prepares["userexists"] = $ourdb->prepare("select count(*) from users where Username=:user");
  }
  $prepares["userexists"]->bindValue(':user', $lo_username, SQLITE3_TEXT);
  $nu = $prepares["userexists"]->execute();


  //error_log("sql: ".$sql);
  //error_log("doh: ".print_r($nu, true));

  $num = $nu->fetchArray()[0];

  if($num != 0) {
    //error_log("user exists, return true");
    return true;
  }
  //error_log("user not exists, return false");

  return false;
}

function db_setTKIDForUser($username, $tkid)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["settokentkid"])) {
    $prepares["settokentkid"] = $ourdb->prepare("update users set TokenPickupKey=:tkid where Username=:user");
  }
  $prepares["settokentkid"]->bindValue(':user', $username, SQLITE3_TEXT);
  $prepares["settokentkid"]->bindValue(':tkid', $tkid, SQLITE3_TEXT);
  $nu = $prepares["settokentkid"]->execute();
}

function db_clearTKIDForUser($tkid)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["deletetkid"])) {
    $prepares["deletetkid"] = $ourdb->prepare("update users set TokenPickupKey='' where TokenPickupKey=:tkid");
  }
  $prepares["deletetkid"]->bindValue(':tkid', $tkid, SQLITE3_TEXT);
  $prepares["deletetkid"]->execute();

  return true;
}


function db_clearTokenForUser($username)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;

  if(!isset($prepares["deleteusertoken"])) {
    $prepares["deleteusertoken"] = $ourdb->prepare("update users set TokenPickupKey='',GAData='' where Username=:username");
  }
  $prepares["deleteusertoken"]->bindValue(':username', $username, SQLITE3_TEXT);
  $prepares["deleteusertoken"]->execute();

  return true;
}

function db_createUser($username, $email, $pass, $radius, $token, $enabled, $token_type)
{
  global $ourdb, $MESSAGE, $MESSAGE_TYPE;



  if($pass != "" ) $pass_hash = hash(sha256, $pass);
  else $pass_hash = "";
  $thisTokenData = "";
  $thisTokenPickupKey = "";


  if(!isset($prepares["createuser"])) {
    $prepares["createuser"] = $ourdb->prepare("insert into users (Username, EMail, Enabled, Password, Radius) values (:username, :email, :enabled, :passhash, :radius)");
  }
  $prepares["createuser"]->bindValue(':username', $username, SQLITE3_TEXT);
  $prepares["createuser"]->bindValue(':email', $email, SQLITE3_TEXT);
  $prepares["createuser"]->bindValue(':enabled', $enabled, SQLITE3_INTEGER);
  $prepares["createuser"]->bindValue(':passhash', $pass, SQLITE3_TEXT);
  $prepares["createuser"]->bindValue(':radius', $radius, SQLITE3_INTEGER);
  $nu = $prepares["createuser"]->execute();

  if($token == 1) {
    // create token and pickup url here

    $myga = new MyGA();
    $thisTokenPickupKey = $myga->ga_createTokenForUser($token_type, $username);
    if(!isset($prepares["settokentkid"])) {
      $prepares["settokentkid"] = $ourdb->prepare("update users set TokenPickupKey=:tkid where Username=:user");
    }
    $prepares["settokentkid"]->bindValue(':user', $username, SQLITE3_TEXT);
    $prepares["settokentkid"]->bindValue(':tkid', $thisTokenPickupKey, SQLITE3_TEXT);
    $nu = $prepares["settokentkid"]->execute();
  }
}

?>
