<?php

function  count_getCountryListAsSelect($default)
{
  $fh = fopen("../data/country_codes.csv", "r");

  while (($line = fgets($fh, 4096)) !== false) {
    $clv = preg_split("/,(?=[^,]*$)/", trim($line));
    $cn = str_replace('"', '', $clv[0]);
    $tl = $clv[1];
    if($cn != "Name") {
      $select = "";
      if($default == $tl) $select = " selected";
      echo "<option value='$tl'$select>$cn ($tl)</option>\n";
    }
  }
  fclose($fh);
}

?>
