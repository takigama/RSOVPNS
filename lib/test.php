<?php

// page for testing tokens, by default, disabled
$MENU_LIST["testtoken"] = "?action=testtoken";

$WEB_HEADCHECK["TEST"]  = "test_localHeadCheck";

function test_localHeadCheck() {
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "testtoken":
      $PAGEBODY_FUNCTION = "test_testtoken";
      break;
      case "testtokencheck":
        testtokencheck();
        exit(0);
      break;
    }
  }
}

function test_testtoken()
{
  $myga = new MyGA();

  $users = db_getUsers();

  echo "<div id='mybodyheading'>Test a Token</div><hr><form method='post' id='tokentestform'>";
  echo "User: <select name='usertotest'>";
  foreach($users as $val) {
    $us = $val["Username"];
    echo "<option name='$us'>$us</option>";
  }
  echo "</select> <input type='text' name='tokenval'> <input type='submit' value='Test' name='Test' id='test_token_submit_button'>";
  echo "</form><hr>";
}

function testtokencheck()
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
