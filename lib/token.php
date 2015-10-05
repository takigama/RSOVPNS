<?php

// page for testing tokens, by default, disabled
$MENU_LIST["Tokens"] = "?action=tokenpage";

$WEB_HEADCHECK["TOKEN"]  = "token_localHeadCheck";

function token_localHeadCheck() {
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "tokenpage":
      $PAGEBODY_FUNCTION = "token_testtoken";
      break;
      case "testtokencheck":
        token_testtokencheck();
        exit(0);
      break;
      case "resynctoken":
        token_resynctoken();
        exit(0);
      break;
    }
  }
}

function token_testtoken()
{
  $myga = new MyGA();

  $users = db_getUsers();

  echo "<div class='mybodyheading'>Tokens</div><hr>";
  echo "<div class='mybodysubheading'>Test Token <a href='#' onmouseover='show_help(\"test_token.html\")' onmouseout='hide_help()'>?</a></div>";
  echo "<form method='post' id='tokentestform'>User: <select name='usertotest'>";
  foreach($users as $val) {
    $us = $val["Username"];
    echo "<option name='$us'>$us</option>";
  }
  echo "</select> <input type='text' name='tokenval'> <input type='submit' value='Test' name='Test' id='test_token_submit_button'>";
  echo "</form><hr>";

  echo "<div class='mybodysubheading'>Re-Sync Token <a href='#' onmouseover='show_help(\"resync_token.html\")' onmouseout='hide_help()'>?</a></div>";
  echo "<form method='post' id='tokenresyncform' action='?action=resynctoken'>User: <select name='usertotest'>";
  foreach($users as $val) {
    $us = $val["Username"];
    echo "<option name='$us'>$us</option>";
  }
  echo "</select>";
  echo " Token Value 1: <input type='text' name='tval1'> Token Value 2: <input type='text' name='tval2'>";
  echo "<input type='submit' name='Re-Sync' value='Re-Sync' id='resync_token_button'>";
  echo "</form>";
}

function token_resynctoken()
{
  $myga = new MyGA();

  $user = $_REQUEST["usertotest"];
  $tval1 = $_REQUEST["tval1"];
  $tval2 = $_REQUEST["tval2"];

  $json = '{ "result": "faiure", "reason": "um...?" }';
  error_log($myga->getTokenType($user));
  if($myga->getTokenType($user) == "TOTP") {
    $json = '{ "result": "failure", "reason": "time based tokens (totp) cannot be resynced" }';
  } else {
    if($myga->resyncCode($user, $tval1, $tval2)) {
      $json = '{ "result": "success", "reason": "User token resync succeeds" }';
    } else {
      $json = '{ "result": "failure", "reason": "User token resync failed (none-consequtive tokens)" }';
    }
  }
  echo $json;
}

function token_testtokencheck()
{
  $myga = new MyGA();

  $user = $_REQUEST["usertotest"];
  $ttest = $_REQUEST["tokenval"];

  $json = '{ "result": "faiure", "reason": "um...?" }';
  if($myga->authenticateUser($user, $ttest)) {
    $json = '{ "result": "success", "reason": "User auth succeeds" }';
  } else {
    $json = '{ "result": "failure", "reason": "User auth failed" }';
  }
  echo $json;
}


?>
