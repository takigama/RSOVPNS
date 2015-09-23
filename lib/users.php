<?php

$WEB_HEADCHECK["USERS"]  = "users_localHeadCheck";
$MENU_LIST["Users"] = "?action=users";

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
      case "deleteuser":
        users_deleteUser();
        exit(0);
      break;
      case "reinittoken":
        users_reinitToken();
        exit(0);
      break;
      case "edituservals":
        users_editUserVals();
        exit(0);
      break;
    }
  }
}

function users_editUserVals()
{
  error_log("input incoming");
  error_log(print_r($_REQUEST,true));
  $user = $_REQUEST["user"];
  $result = false;

  $message_text = "successfully updated $user";

  switch($_REQUEST["type"]) {
    case "enabled":
      $enab = $_REQUEST["user_enabled"];
      if($enab == "on") {
        db_changeEnablesForUser($user, 1);
        $result = true;
        $message_text = "$user is now enabled";
      } else {
        db_changeEnablesForUser($user, 0);
        $result = true;
        $message_text = "$user is now disabled";
      }
    break;
    case "email":
      $email = $_REQUEST["email"];
      db_updateEmailForUser($user, $email);
      $result = true;
      $message_text = "Updated email for $user";
    break;
    case "password":
      error_log("start password update");
      if(isset($_REQUEST["clear"])) {
        $pass1 = "";
        $pass2 = "";
      }
      $pass1 = $_REQUEST["pass1"];
      $pass2 = $_REQUEST["pass2"];
      if($pass1 != $pass2) {
        $json = '{ "result": "failure", "reason": "Passwords dont match" }';
      } else {
        $result = db_updateUserPassword($user, $pass1);
        $result = true;
        $message_text = "Updated password for $user";
      }

    break;
    case "token":
      $newtoken = $_REQUEST["tokentype"];
      $myga = new MyGA();

      if($newtoken != "none") {
        $ttype = $newtoken;
        $oldtkid = db_getTokenPickupKey($user);
        if(file_exists("../pickup/$oldtkid.url")) unlink("../pickup/$oldtkid.url");
        if(file_exists("../pickup/$oldtkid.png")) unlink("../pickup/$oldtkid.png");
        db_clearTokenForUser($user);
        $newtkid = $myga->ga_createTokenForUser($ttype, $user);
        db_setTKIDForUser($user, $newtkid);
        user_createPickupData($user);
      } else {
        $oldtkid = db_getTokenPickupKey($user);
        if(file_exists("../pickup/$oldtkid.url")) unlink("../pickup/$oldtkid.url");
        if(file_exists("../pickup/$oldtkid.png")) unlink("../pickup/$oldtkid.png");
        db_clearTokenForUser($user);
      }
      $message_text = "Created new token ($ttype) for $user";
      $result = true;
    break;
    case "radius":
      $radius = $_REQUEST["radius_enabled"];
      if($radius == "on") {
        $result = true;
        error_log("turning on radius for $user ($radius)");
        $message_text = "Radius is now enabled for $user";
        db_changeRadiusForUser($user, 1);
      } else {
        $result = true;
        error_log("turning off radius for $user ($radius)");
        $message_text = "Radius is now disabled for $user";
        db_changeRadiusForUser($user, 0);
      }

    break;
  }


  if($result) {
    $_SESSION["messages"]["updateuser"]["type"] = 1;
    $_SESSION["messages"]["updateuser"]["text"] = $message_text;
    $json = '{ "result": "success", "reason": "User Updated" }';
  }
  echo $json;

  exit(0);
}

function users_reinitToken()
{
  $username = $_REQUEST["user"];

  $myga = new MyGA();

  $ttype = $myga->getTokenType($username);
  $oldtkid = db_getTokenPickupKey($username);
  if(file_exists("../pickup/$oldtkid.url")) unlink("../pickup/$oldtkid.url");
  if(file_exists("../pickup/$oldtkid.png")) unlink("../pickup/$oldtkid.png");
  db_clearTokenForUser($username);
  $newtkid = $myga->ga_createTokenForUser($ttype, $username);
  db_setTKIDForUser($username, $newtkid);
  user_createPickupData($username);

  header("Location: ?action=users");
}

function users_deleteUser()
{
  $username = $_REQUEST["user"];

  $json = '{ "result": "failure", "reason": "Um...." }';

  $oldtkid = db_getTokenPickupKey($username);
  if($oldtkid != "") {
    if(file_exists("../pickup/$oldtkid.url")) unlink("../pickup/$oldtkid.url");
    if(file_exists("../pickup/$oldtkid.png")) unlink("../pickup/$oldtkid.png");
  }

  if(db_deleteUser($username)) {
    $json = '{ "result": "success", "reason": "User deleted" }';
  } else {
    $json = '{ "result": "failure", "reason": "User not deleted for some reason" }';
  }
  echo $json;
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

  $url = $myga->createURL($username, db_getConfig('site.ident', ''));


  file_put_contents("../pickup/$tkpid.url", $url);

  QRcode::png($url, "../pickup/$tkpid.png");

}

function users_doUsersBody()
{
  $myga = new MyGA();
  echo "<div class='mybodyheading'>Users</div><hr>";


  echo "<form method='post' id='createuserform'>";
  echo "<div id='createuserframe'>";

    echo "<div id='createusertitle' class='mybodysubheading'>Create New</div>";

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
    echo "<div id='userlisthead' class='mybodysubheading'>Current Users</div>";
    echo "<div id='searchbox'><form><input type='text' name='searchval' placeholder='Type to search' id='search_entry'></form></div>";
    echo "<table class='configtable' id='userlisttable'><tr id='headerrow'><th>Username</th><th>Email</th><th>Enabled</th><th>Token</th><th>Password</th><th>Radius</th></tr>";
    $users = db_getUsers();
    foreach($users as $val) {

      $uname = $val["Username"];

      $em = $val["EMail"];
      if($em == "") $em = "Not set";

      $ht = $myga->hasToken($uname);
      $ttype = $myga->getTokenType($uname);
      $token = "none";
      $pickedup = "";
      if($ht) {
        if($val["TokenPickupKey"] != "") {
          $tkid = $val["TokenPickupKey"];
          if(file_exists("../pickup/$tkid.url")) {
            $pickedup = "<div class='tokenpickupwarn'>Not picked up yet <a href='pickup.php?tkpuid=$tkid'>Pickup URL</a></div>";
          }
        }
        $reset = "<div class='reinittoken'><a href='?action=reinittoken&user=".rawurlencode($uname)."'>Re-initialise Token</a></div>";
        $token = "<div class='tokentypeinlist'>$ttype</div>$reset$pickedup";
      }

      $radius = "no";
      if($val["Radius"] == 0) {
        $radius = "no";
        $radius_en = "<img src='images/cross_simpl.png' width='16px' height='16px'>";
      } else {
        $radius = "Enabled";
        $radius_en = "<img src='images/tick_simpl.png' width='16px' height='16px'>";
      }

      $pass = "none";
      $pass_en = "<img src='images/cross_simpl.png' width='16px' height='16px'>";
      if($val["Password"] != "") {
        $pass = "Enabled and Set";
        $pass_en = "<img src='images/tick_simpl.png' width='16px' height='16px'>";
      }

      if($val["Enabled"] == 1) {
        $enab_en = "<img src='images/tick_simpl.png' width='16px' height='16px'>";
        $enab = "Yes";
      } else {
        $enab = "Disabled";
        $enab_en = "<img src='images/cross_simpl.png' width='16px' height='16px'>";
      }

      $del = "<a href='index.php?action=deleteuser&deleteuser=$uname' onclick='return confirmDeleteUser(\"$uname\")' id='delete_$uname'>Delete</a>";

      echo "<tr id='row_$uname' onmouseover='change_line_class_in(\"row_$uname\");'";
      echo "onmouseout='change_line_class_out(\"row_$uname\");'>";
      echo "<td>$uname</td><td onmouseover='bring_up_edit(\"email\", \"$uname\", \"$em\");' onmouseout='drop_edit();' onclick='edit_clicked();'>$em</td>";
      echo "<td onmouseover='bring_up_edit(\"enabled\", \"$uname\", \"$enab\");' onmouseout='drop_edit();' onclick='edit_clicked();'>$enab_en</td>";
      echo "<td onmouseover='bring_up_edit(\"token\", \"$uname\", \"\");' onmouseout='drop_edit();' onclick='edit_clicked();'>$token</td>";
      echo "<td onmouseover='bring_up_edit(\"password\", \"$uname\", \"\");' onmouseout='drop_edit();' onclick='edit_clicked();'>$pass_en</td>";
      echo "<td onmouseover='bring_up_edit(\"radius\", \"$uname\", \"$radius\");' onmouseout='drop_edit();' onclick='edit_clicked();'>$radius_en</td><td class='control_tr'>$del</td>";
      echo "</tr>";
    }
    echo "</table>";
  echo "</div>";
  echo "<div class='usereditbox' id='usereditboxid'></div>";

  // doing this js here is a little ugly, but ahh well.
  echo "<script type='text/javascript'>";
?>
var $rows = $('#userlisttable tr');
console.log("rows:");
console.log($rows);
$('#search_entry').keyup(function() {
  var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

  $rows.show().filter(function() {
      var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();

      // this next line stops it deleting the header row
      if($(this)[0].id == "headerrow") return false;
      return !~text.indexOf(val);
  }).hide();
});
<?php
  echo "</script>";

}
?>
