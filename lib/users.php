<?php

$WEB_HEADCHECK["USERS"]  = "users_localHeadCheck";
$MENU_LIST["Users"] = "?action=users";
$MENU_LIST["testtoken"] = "?action=testtoken";

function users_localHeadCheck() {
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "users":
        $PAGEBODY_FUNCTION = "users_doUsersBody";
      break;
      case "createuser":
        users_doCreateUser();
        exit(0);
      break;
      case "testtoken":
      $PAGEBODY_FUNCTION = "users_testtoken";
      break;
    }
  }
}


function users_testtoken()
{
  $myga = new MyGA();

  echo "<pre>";
  //$myga->setUser("asdf");
  //print_r($myga->internalGetData("asdf"));
  echo "</pre>";
}

function users_doCreateUser()
{
  $username = $_REQUEST["cr_username"];
  $pass1 = $_REQUEST["cr_pass1"];
  $pass2 = $_REQUEST["cr_pass2"];
  $email = $_REQUEST["cr_email"];
  if(isset($_REQUEST["cr_rad_on"])) $radius = 1;
  else $radius = 0;
  $token = 0;
  $token_type = null;
  switch($_REQUEST["cr_tok_on"]) {
      case "totp":
        $token = 1;
        $token_type = "totp";
      break;
      case "hotp":
        $token = 1;
        $token_type = "hotp";
      break;
  }
  if(isset($_REQUEST["cr_enabled"])) $enabled = 1;
  else $enabled = 0;

  error_log("usrname is $username");

  $result = false;
  error_log("Starting user create $username");
  if($pass1 != $pass2) {
    $json = '{ "result": "failure", "reason": "Passwords dont match" }';
    return;
  } else {
    if(db_userExists($username)) {
      $json = '{ "result": "failure", "reason": "User already exists" }';
      echo $json;
      return;
    } else {
      $result = db_createUser("$username", "$email", "$pass1", $radius, $token, $enabled, $token_type);
    }
  }

  user_createPickupData($username);


  $json = '{ "result": "success", "reason": "User created" }';
  echo $json;

  error_log("happy camper");
}

function user_createPickupData($username)
{
  require_once("../lib/phpqrcode.php");

  $myga = new MyGA();

  $tkpid = db_getTokenPickupKey($username);

  $url = $myga->createURL($username);

  file_put_contents("../pickup/$tkpid.url", $url);

  QRcode::png($url, "../pickup/$tkpid.png");

}

function users_doUsersBody()
{
  echo "<div id='mybodyheading'>Users</div><hr>";


  echo "<form method='post' id='createuserform'>";
  echo "<div id='createuserframe'>";

    echo "<div id='createusertitle'>Create New</div>";

    echo "<div id='createusertable'>";
      echo "<table class='configtable'>";
      echo "<tr><th>Username</th><th>EMail</th><th>Password</th><th>Password Confirm</th><th>Radius?</th><th>Token?</th><th>Enabled?</th><th></th></tr>";
      echo "<tr>";
      echo "<td><input type='text' name='cr_username' id='cr_username'></td><td><input type='text' name='cr_email' id='cr_email'></td><td><input type='text' name='cr_pass1' id='cr_pass1'></td><td><input type='text' name='cr_pass2' id='cr_pass2'></td>";
      echo "<td><input type='checkbox' name='cr_rad_on' id='cr_rad_on'></td><td>";
      echo "<select id='cr_tok_on' name='cr_tok_on'><option value='none'>None</option><option value='hotp'>HOTP</option><option value='totp'>TOTP</option></select>";
      echo "</td><td><input type='checkbox' name='cr_enabled' id='cr_enabled'></td>";
      echo "<td><input type='submit' name='go' value='Create' id='submit_create_user_form_button'></td>";
      echo "</tr>";
      echo "</table>";
    echo "</div>";

  echo "</div><hr></form>";


  echo "<div id='userlistframe'>";
    echo "<div id='userlisthead'>Current Users</div>";
    echo "<pre>";
    print_r(db_getUsers());
    echo "</pre>";
  echo "</div>";




}
?>
