<?php

if(!isset($argv[1])) {
  echo "usage error: stats.php proc name\n";
  return 1;
}

// now search thru /proc/[0-9]+/cmdline until we get what we want
$files = glob('/proc/[0-9]*/cmdline');

//print_r($files);
$n = 0;
$x = array();
foreach($files as $file) {
  $sname_t = explode("/", $file);
  $sname = $sname_t[2];
  $execname = file_get_contents($file);
  //echo "$file and $sname and $execname\n";
  if(stristr("$execname", $argv[1])) {
    echo "$sname: $execname\n";
    $x[$n]["name"] = $execname;
    $x[$n]["pid"] = $sname;
    $n++;
  } //else echo "nomatch\n";
}

if($n == 0) {
  echo "No process matches\n";
  return 0;
}

// now do our calcs
$st = file_get_contents("/proc/stat");
$stx = array();
for($i = 0; $i<$n; $i++) {
  $stx[$i] = file_get_contents("/proc/".$x[$i]["pid"]."/stat");
}

// sleep 5 perhaps
if(isset($argv[2])) {
  sleep($argv[2]);
} else {
  sleep(5);
}

$sti = file_get_contents("/proc/stat");
$stxi = array();
for($i = 0; $i<$n; $i++) {
  $stxi[$i] = file_get_contents("/proc/".$x[$i]["pid"]."/stat");
}

$sta_t = explode(" ", $st);
$sta = $sta_t[1] + $sta_t[2];
$stax_t = explode(" ", $sti);
$stax = $stax_t[1] + $sta_t[2];

$tdf = $stax - $sta;

echo "$tdf...\n";
print_r($sti);
print_r($st);

$us = array();
for($i=0; $i < $n; $i++) {
  if($tdf > 0) {
    $a = explode(" ", $stx[$i]);
    $b = explode(" ", $stxi[$i]);
    $d = $b[14] - $a[14];
    $e = $b[15] - $a[15];
    $us = (100*$d)/$tdf;
    echo $x[$i]["name"].": ".$us." (".$sy.")\n";
  } else {
    echo $x[$i]["name"].": 0 (0)\n";
  }
}
?>
