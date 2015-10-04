<?php

$WEB_HEADCHECK["LOGS"]  = "logs_localHeadCheck";
//$MENU_LIST["Logs"] = "?action=logs";

function logs_localHeadCheck() {
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "getlogs":
        logs_getlogs();
        exit(0);
      break;
    }
  }
}

function logs_getlogs()
{
  $firstline = true;

  if(isset($_REQUEST["query"])) {
    $logs = db_getLogs($_REQUEST["query"],$_REQUEST["num"],$_REQUEST["start"]);
    $total = db_getLogsTotal($_REQUEST["query"]);
  } else {
    $logs = db_getLogs("", $_REQUEST["num"],$_REQUEST["start"]);
    $total = db_getLogsTotal("");
  }
  error_log("rows: ".$_REQUEST["num"]);
  if($logs != null) {
    echo "{ \"maxitems\": \"$total\", \"items\": [";
    foreach($logs as $entry) {
      $type = $entry["type"];
      $time = strftime("%c", $entry["time"]);
      $text = $entry["entry"];
      if($firstline) $firstline = false;
      else echo ",";
      echo "{ \"type\":\"$type\", \"time\":\"$time\", \"entry\":\"$text\"}";
    }
    echo "]}";
  } else {
    echo "{ \"maxitems\": \"none\" }";
  }
  return;
}


function log_log($type, $entry, $time = 0)
{
  if($time == 0) $time = time();

  db_createLog($type, $entry, $time);
}

function log_viewLogs()
{
  //$logs = db_getLogs();

  $pagemax = 1;

  echo "<div id='searchbox'><form><input type='text' name='searchval' placeholder='Type to search' id='search_entry'>";
  echo " Rows: <select id='num_rows' name='num_rows'><option value='100'>100</option><option value='20' selected>20</option></select>";
  echo " <div class='page_chooser'>Pages: <a href='#' id='page_minus'><img src='images/button_left.png' class='pager_image'></a>";
  echo "<input type='text' name='pagenum' id='pagenum_id' class='pagenum_cl' value='1'>/<div id='pagemax_id'>$pagemax</div>";
  echo "<a href='#' id='page_plus'><img src='images/button_right.png' class='pager_image'></a></div>";
  echo "</form></div>";
  echo "<table class='logstable_frame'><tr><td>";
  echo "<div id='logstable_div'>";
  /*
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
  */
  echo "</div>";
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
    build_log_table($('#search_entry').val(), $('#num_rows').val(), $('#pagenum_id').val());
  }, 400);
});

$('#num_rows').change(function() {
  build_log_table($('#search_entry').val(), $('#num_rows').val(), $('#pagenum_id').val());
})

$( document ).ready(function() {
  build_log_table("", $('#num_rows').val(), $('#pagenum_id').val());
})

$('#page_minus').click(function() {
  console.log("click");
  var pgid = parseInt($('#pagenum_id').val());
  console.log("page minus from "+pgid);
  if(pgid == 1) return;
  pgid -= 1;
  $('#pagenum_id').val(pgid);
  build_log_table($('#search_entry').val(), $('#num_rows').val(), $('#pagenum_id').val());
})

$('#page_plus').click(function() {
  console.log("click");
  var pgid = parseInt($('#pagenum_id').val());
  var pgmax = document.getElementById('pagemax_id').innerHTML;
  if(pgid >= pgmax) {
    window.alert("Already at last page");
  } else {
    pgid+=1;
    console.log("page plus from "+pgid);
    $('#pagenum_id').val(pgid);
    build_log_table($('#search_entry').val(), $('#num_rows').val(), $('#pagenum_id').val());
  }
})

$('#pagenum_id').keyup(function() {
  console.log("keyup - pager");
  if (timer){
    clearTimeout(timer);
  }

  timer = setTimeout(function(){
    build_log_table($('#search_entry').val(), $('#num_rows').val(), $('#pagenum_id').val());
  }, 400);
});

function build_log_table(query, num, page) {
  var startval = parseInt(num)*(parseInt(page)-1);
  var fullquery = "num="+num+"&start="+startval;
  if(query != "") {
    fullquery += "&query="+query;
  }

  $.ajax({
    url: "index.php?action=getlogs&"+fullquery,
    type: "POST",
    success: function (data) {
      var html = "<table class='logstable' id='logstable'>";
      html += "<tr id='headerrow'><th>Time</th><th>Event</th></tr>";
      //console.log("got data");
      console.log(data);
      result = JSON.parse(data);
      console.log("parsed json");
      console.log(result)
      if(result.maxitems == "none") {
        console.log("called into maxitems is none");
        if(query != "") {
          html += "<tr><td colspan='2'>No logs match search criteria....</td></tr>";
        } else {
          html += "<tr><td colspan='2'>No logs....</td></tr>";
        }
        document.getElementById('pagemax_id').innerHTML = 0;
      } else {
        for(var i=0, len = result.items.length; i < len; i++) {
          //console.log("i: "+i);
          console.log(result.items[i]);
          var thisclass = "log_info";
          switch(result.items[i].type) {
            case "1":
              thisclass = "log_info";
            break;
            case "2":
              thisclass = "log_warning";
            break;
            case "3":
              thisclass = "log_error";
            break;
          }
          var thistime = result.items[i].time;
          var thisevent = result.items[i].entry;
          html += "<tr class='"+thisclass+"'><td class='"+thisclass+"'>"+thistime+"</td><td class='"+thisclass+"'>"+thisevent+"</td></tr>";
          var pagemax = parseInt(result.maxitems)/parseInt(num);
          document.getElementById('pagemax_id').innerHTML = pagemax;
        }
      }
      html += "</table>";
      document.getElementById('logstable_div').innerHTML = html;


    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })

}

<?php
  echo "</script>";

}

?>
