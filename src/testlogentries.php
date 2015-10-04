<?php

require_once("../config/config.php");
require_once("../lib/lib.php");

$ctim = time();
for($i=0; $i < 1000; $i++) {
  $ltim = $ctim - ($i*20);
  log_log(1, "hi there this is a log entry", $ltim);
  log_log(2, "hi this is a warning - not quite in danger yet", $ltim-4);
  log_log(3, "hi this is an error test - we're in trouble", $ltim-8);
}

?>
