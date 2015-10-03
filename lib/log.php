<?php

function log_log($type, $entry, $time = 0)
{
  if($time == 0) $time = time();

  db_createLog($type, $entry, $time);
}

function log_viewLogs()
{
  $logs = db_getLogs();

  echo "<div id='searchbox'><form><input type='text' name='searchval' placeholder='Type to search' id='search_entry'></form></div>";
  echo "<table class='logstable_frame'><tr><td>";
  echo "<div id='logstable_div'>";
  echo "<table class='logstable' id='logstable'>";
  echo "<tr id='headerrow'><th>Time</th><th>Event</th></tr>";
  if($logs != null) {
    //print_r($logs);
    foreach($logs as $entry) {
      switch($entry["type"]) {
        case 1:
          $class = "log_info";
        break;
        case 2:
          $class = "log_warning";
        break;
        case 3:
          $class = "log_error";
        break;
      }
      $tm = strftime("%c", $entry["time"]);
      $ev = $entry["entry"];
      echo "<tr class='$class'><td class='$class'>$tm</td><td class='$class'>$ev</td></tr>";
    }
  } else {
    echo "<tr><td colspan='2'>No logs yet....</td></tr>";
  }
  echo "</table>";
  echo "</div>"
  echo "</tr></td></table>";
  echo "<script type='text/javascript'>";
?>
var timer;
//console.log("rows:");
//console.log($rows);
$('#search_entry').keyup(function() {
  console.log("keyup");
  if (timer){
    clearTimeout(timer);
  }

  timer = setTimeout(function(){
    var $rows = $('#logstable tr');
    console.log($('#search_entry').val());
    var val = $.trim($('#search_entry').val()).replace(/ +/g, ' ').toLowerCase();
    $rows.show().filter(function() {
      var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();

      // this next line stops it deleting the header row
      if($(this)[0].id == "headerrow") return false;
      return !~text.indexOf(val);
    }).hide();
  }, 400);
});

<?php
  echo "</script>";

}

?>
