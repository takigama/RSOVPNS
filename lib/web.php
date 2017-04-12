<?php
$WEB_HEADCHECK["WEB"]  = "web_localHeadCheck";
$MENU_LIST["Home"] = "index.php";
$MENU_LIST["Configuration"] = "?action=config";
$MENU_LIST["Users"] = "?action=users";
$PAGEBODY_FUNCTION = "";
$MESSAGE = null;
$MESSAGE_TYPE = 0;

global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION, $MESSAGE, $MESSAGE_TYPE;

function web_buildPage()
{
  session_start();

  if(!web_checkMgmtIP()) {
    header("Location: noaccess.html");
    return;
  }

  if(!isset($_SESSION["logged_in"])) {
    web_doLoginPage();
    return;
  }

  web_doHeadCheck();
  web_doHeaders();

  echo "<div id='container'>";
  web_doPageTop();
  web_doPageMessage();
  web_doPageMiddle();
  web_doPageBottom();
  echo "</div>";
}

function web_doLoginPage()
{
  if(web_doLoginHeadCheck()) {
      header("Location: index.php");
      exit(0);
  }
  web_doHeaders();

  echo "<html>";
  echo "<div id='loginframe' class='loginframe'>";
  echo "<div class='logininsideframe'>";
  echo "<form method='post'>";
  echo "<table><tr><th>Username</th><td><input type='text' name='username' class='logintext'></td></tr>";
  echo "<tr><th>Password</th><td><input type='password' name='password' class='logintext'></td></tr>";
  echo "<tr><td colspan='2'><input type='submit' name='login' value='Login'></td></tr>";
  echo "</table>";
  if(isset($_SESSION["falselogin"])) {
    echo "<div id='failedlogin'>Login Failed</div>";
  }
  echo "</form>";
  echo "</div>";
  echo "</div>";
  echo "</html>";
}

function web_doLoginHeadCheck()
{
  if(isset($_REQUEST["login"])) {
    if(isset($_REQUEST["username"])) {
      if(isset($_REQUEST["password"])) {
        if(web_doLoginValidateWeb()) {
          $_SESSION["logged_in"] = $_REQUEST["username"];
          return true;
        } else {
          $_SESSION["falselogin"] = true;
        }
      }
    }
  }
  return false;
}

function web_checkMgmtIP()
{
  $ips = explode(" ", trim(preg_replace('/\s\s+/', ' ', db_getConfig("admin.allowednetworks", "10.*\r\n192.168.*\r\n127.*\r\n::1\r\n172.*\r\n"))));
  $from_addr = $_SERVER["REMOTE_ADDR"];

  //print_r($ips);

  foreach($ips as $val) {
    //$valnew = str_replace(":", '\\:', $val);
    $valnew = $val;
    //file_put_contents("/tmp/f.txt",$valnew);
    error_log("checking $from_addr against $valnew");

    if(preg_match("/$val/", $from_addr)) {
      //error_log("Yes: $from_addr, $valnew");
      return true;
    } else {
      //error_log("no: $from_addr, $valnew");
    }
  }
  return false;
}

function web_encode($val)
{
  return htmlentities($val, ENT_QUOTES | ENT_COMPAT | ENT_HTML401, ini_get("default_charset"), false);
}

function web_decode($val)
{
  return html_entity_decode($val, ENT_QUOTES | ENT_COMPAT | ENT_HTML401, ini_get("default_charset"));
}

function web_doLoginValidateWeb()
{
  $pass = $_REQUEST["password"];
  $user = $_REQUEST["username"];
  // ... and then....
  return true;
}

function web_doHeadCheck()
{
  global $WEB_HEADCHECK;

  foreach($WEB_HEADCHECK as $val) {
    if(function_exists($val)) {
      $val();
    }
  }
}

function web_doPageMessage()
{
  global $MESSAGE, $MESSAGE_TYPE;

  if($MESSAGE != null && $MESSAGE != "") {
    switch($MESSAGE_TYPE) {
      case 1: // info
        echo "<div id='message_outer'><div id='message_info'>Info: $MESSAGE</div></div>";
      break;
      case 2: // warning
        echo "<div id='message_outer'><div id='message_warning'>Warning: $MESSAGE</div></div>";
      break;
      case 3: // error
        echo "<div id='message_outer'><div id='message_error'>Error: $MESSAGE</div></div>";
      break;
    }
  }

  if(isset($_SESSION["messages"])) {
    foreach($_SESSION["messages"] as $messages) {
      $ses_mess = $messages["text"];
      $ses_type = $messages["type"];
      switch($ses_type) {
        case 1: // info
          echo "<div id='message_outer'><div id='message_info'>Info: $ses_mess</div></div>";
        break;
        case 2: // warning
          echo "<div id='message_outer'><div id='message_warning'>Warning: $ses_mess</div></div>";
        break;
        case 3: // error
          echo "<div id='message_outer'><div id='message_error'>Error: $ses_mess</div></div>";
        break;
      }
    }

    unset($_SESSION["messages"]);
  }
}

function web_localHeadCheck()
{
  global $WEB_HEADCHECK, $MENU_LIST, $PAGEBODY_FUNCTION;

  if(isset($_REQUEST["action"])) {
    switch($_REQUEST["action"]) {
      case "config":
        $PAGEBODY_FUNCTION = "web_doConfigurationBody";
      break;
      case "createcert":
        web_doCreateCert();
        exit(0);
      break;
      case "createdhkey":
        web_doCreateDH();
        exit(0);
      break;
      case "logout":
        unset($_SESSION["logged_in"]);
        header("Location: index.php");
        exit(0);
      break;
    }
  }
}

function web_doCreateDH()
{
  $cmd = "openssl gendh 2048 2> /tmp/dh.log > ../data/server.dh &";

  system($cmd);

  $_SESSION["messages"]["dh"]["type"] = 1;
  $_SESSION["messages"]["dh"]["text"] = "DH Key Createion Stated";

  header("Location: index.php?action=config");

}

function web_doCreateCert()
{
  // TODO: lots of validity checking
  global $HOMEDIR;
  $country = $_REQUEST["cert_country"];
  $st = $_REQUEST["cert_state"];
  $loc = $_REQUEST["cert_locality"];
  $org = $_REQUEST["cert_org"];
  $dept = $_REQUEST["cert_dept"];
  $cn = $_REQUEST["cert_cn"];
  $valid = $_REQUEST["cert_valid"];

  db_setConfig("cert.country", $country);
  db_setConfig("cert.state", $st);
  db_setConfig("cert.locality", $loc);
  db_setConfig("cert.organisation", $org);
  db_setConfig("cert.department", $dept);
  db_setConfig("cert.commonname", $cn);
  db_setConfig("cert.validity", $valid);

  $cmd = "px5g selfsigned -days $valid -newkey rsa:2048 -keyout $HOMEDIR/data/server.key -out $HOMEDIR/data/server.crt -subj \"/C=$country/ST=$st/L=$loc/O=$org/OU=$dept/CN=$cn\" > /tmp/px5g.log 2>&1";
  error_log("would run: $cmd");
  system("touch ../data/server.key");
  system("touch ../data/server.crt");
  system($cmd);

  $_SESSION["messages"]["cert"]["type"] = 1;
  $_SESSION["messages"]["cert"]["text"] = "New certificate created";

  header("Location: index.php?action=config");
}


function web_doHeaders()
{
  $dir = "../www/js/";
  if (is_dir($dir)) {
    $files = scandir($dir);
    error_log("files is: ".print_r($files, true));
    foreach($files as $file) {
      //error_log("file is $file");
      if(preg_match("/.*\.js/", $file)) echo "<script type='text/javascript' src='js/$file'></script>";
    }
  }

  $dir = "../www/css/";
  if (is_dir($dir)) {
    $files = scandir($dir);
    foreach($files as $file) {
      //error_log("css file is $file");
      if(preg_match("/.*\.css/", $file)) echo "<link rel='stylesheet' href='css/$file' type='text/css' />";
    }
  }
}

function web_doPageTop()
{
  echo "<div id='myheader_outer'><div id='myheader_inner'>Real Simple OpenVPN Server</div></div>"; // Real Simple OpenVPN Service

  echo "<div class='userhelpbox' id='userhelpboxid'>";
  echo "</div>";

}

function web_doPageMiddle()
{


  echo "<div id='mymenu'>";
  web_doPageMenu();
  echo "</div>";

  echo "<div id='mybody'>";
  web_doPageBody();
  echo "</div>";

}

function web_doPageBody()
{
  global $PAGEBODY_FUNCTION;

  if($PAGEBODY_FUNCTION == "") {
    // normal page boxy goes here
    web_normalPageBody();
  } else {
    if(function_exists($PAGEBODY_FUNCTION)) {
      $PAGEBODY_FUNCTION();
    } else {
      echo ".... body function error ....";
      web_normalPageBody();
    }
  }
}

function web_normalPageBody()
{
  echo "<div class='mybodyheading'>Status</div><hr>";
  echo "<div class='mybodysubheading'>Performance <a href='#' onmouseover='show_help(\"performance.html\")' onmouseout='hide_help()'>?</a></div>";
  echo "<table class='graphstable'>";
  echo "<tr><th>Connected Users</th><th>Data Usage</th><th>CPU Usage</th></tr>";
  echo "<tr><td><div id='connected_users_chart'></div></td><td><div id='data_usage_graph'></div></td><td><div id='cpu_usage_graph'></div></td></tr>";
  echo "</table>";

  echo "<script type='text/javascript'>";
?>
var cdate = new Date();
var starttime = cdate.getTime();

//var d = [[-373597200000, 315.71], [-370918800000, 317.45], [-368326800000, 317.50], [-363056400000, 315.86], [-360378000000, 314.93], [-357699600000, 313.19], [-352429200000, 313.34], [-349837200000, 314.67], [-347158800000, 315.58], [-344480400000, 316.47], [-342061200000, 316.65], [-339382800000, 317.71], [-336790800000, 318.29], [-334112400000, 318.16], [-331520400000, 316.55], [-328842000000, 314.80], [-326163600000, 313.84], [-323571600000, 313.34], [-320893200000, 314.81], [-318301200000, 315.59], [-315622800000, 316.43], [-312944400000, 316.97], [-310438800000, 317.58], [-307760400000, 319.03], [-305168400000, 320.03], [-302490000000, 319.59], [-299898000000, 318.18], [-297219600000, 315.91], [-294541200000, 314.16], [-291949200000, 313.83], [-289270800000, 315.00], [-286678800000, 316.19], [-284000400000, 316.89], [-281322000000, 317.70], [-278902800000, 318.54], [-276224400000, 319.48], [-273632400000, 320.58], [-270954000000, 319.78], [-268362000000, 318.58], [-265683600000, 316.79], [-263005200000, 314.99], [-260413200000, 315.31], [-257734800000, 316.10], [-255142800000, 317.01], [-252464400000, 317.94], [-249786000000, 318.56], [-247366800000, 319.69], [-244688400000, 320.58], [-242096400000, 321.01], [-239418000000, 320.61], [-236826000000, 319.61], [-234147600000, 317.40], [-231469200000, 316.26], [-228877200000, 315.42], [-226198800000, 316.69], [-223606800000, 317.69], [-220928400000, 318.74], [-218250000000, 319.08], [-215830800000, 319.86], [-213152400000, 321.39], [-210560400000, 322.24], [-207882000000, 321.47], [-205290000000, 319.74], [-202611600000, 317.77], [-199933200000, 316.21], [-197341200000, 315.99], [-194662800000, 317.07], [-192070800000, 318.36], [-189392400000, 319.57], [-178938000000, 322.23], [-176259600000, 321.89], [-173667600000, 320.44], [-170989200000, 318.70], [-168310800000, 316.70], [-165718800000, 316.87], [-163040400000, 317.68], [-160448400000, 318.71], [-157770000000, 319.44], [-155091600000, 320.44], [-152672400000, 320.89], [-149994000000, 322.13], [-147402000000, 322.16], [-144723600000, 321.87], [-142131600000, 321.21], [-139453200000, 318.87], [-136774800000, 317.81], [-134182800000, 317.30], [-131504400000, 318.87], [-128912400000, 319.42], [-126234000000, 320.62], [-123555600000, 321.59], [-121136400000, 322.39], [-118458000000, 323.70], [-115866000000, 324.07], [-113187600000, 323.75], [-110595600000, 322.40], [-107917200000, 320.37], [-105238800000, 318.64], [-102646800000, 318.10], [-99968400000, 319.79], [-97376400000, 321.03], [-94698000000, 322.33], [-92019600000, 322.50], [-89600400000, 323.04], [-86922000000, 324.42], [-84330000000, 325.00], [-81651600000, 324.09], [-79059600000, 322.55], [-76381200000, 320.92], [-73702800000, 319.26], [-71110800000, 319.39], [-68432400000, 320.72], [-65840400000, 321.96], [-63162000000, 322.57], [-60483600000, 323.15], [-57978000000, 323.89], [-55299600000, 325.02], [-52707600000, 325.57], [-50029200000, 325.36], [-47437200000, 324.14], [-44758800000, 322.11], [-42080400000, 320.33], [-39488400000, 320.25], [-36810000000, 321.32], [-34218000000, 322.90], [-31539600000, 324.00], [-28861200000, 324.42], [-26442000000, 325.64], [-23763600000, 326.66], [-21171600000, 327.38], [-18493200000, 326.70], [-15901200000, 325.89], [-13222800000, 323.67], [-10544400000, 322.38], [-7952400000, 321.78], [-5274000000, 322.85], [-2682000000, 324.12], [-3600000, 325.06], [2674800000, 325.98], [5094000000, 326.93], [7772400000, 328.13], [10364400000, 328.07], [13042800000, 327.66], [15634800000, 326.35], [18313200000, 324.69], [20991600000, 323.10], [23583600000, 323.07], [26262000000, 324.01], [28854000000, 325.13], [31532400000, 326.17], [34210800000, 326.68], [36630000000, 327.18], [39308400000, 327.78], [41900400000, 328.92], [44578800000, 328.57], [47170800000, 327.37], [49849200000, 325.43], [52527600000, 323.36], [55119600000, 323.56], [57798000000, 324.80], [60390000000, 326.01], [63068400000, 326.77], [65746800000, 327.63], [68252400000, 327.75], [70930800000, 329.72], [73522800000, 330.07], [76201200000, 329.09], [78793200000, 328.05], [81471600000, 326.32], [84150000000, 324.84], [86742000000, 325.20], [89420400000, 326.50], [92012400000, 327.55], [94690800000, 328.54], [97369200000, 329.56], [99788400000, 330.30], [102466800000, 331.50], [105058800000, 332.48], [107737200000, 332.07], [110329200000, 330.87], [113007600000, 329.31], [115686000000, 327.51], [118278000000, 327.18], [120956400000, 328.16], [123548400000, 328.64], [126226800000, 329.35], [128905200000, 330.71], [131324400000, 331.48], [134002800000, 332.65], [136594800000, 333.16], [139273200000, 332.06], [141865200000, 330.99], [144543600000, 329.17], [147222000000, 327.41], [149814000000, 327.20], [152492400000, 328.33], [155084400000, 329.50], [157762800000, 330.68], [160441200000, 331.41], [162860400000, 331.85], [165538800000, 333.29], [168130800000, 333.91], [170809200000, 333.40], [173401200000, 331.78], [176079600000, 329.88], [178758000000, 328.57], [181350000000, 328.46], [184028400000, 329.26], [189298800000, 331.71], [191977200000, 332.76], [194482800000, 333.48], [197161200000, 334.78], [199753200000, 334.78], [202431600000, 334.17], [205023600000, 332.78], [207702000000, 330.64], [210380400000, 328.95], [212972400000, 328.77], [215650800000, 330.23], [218242800000, 331.69], [220921200000, 332.70], [223599600000, 333.24], [226018800000, 334.96], [228697200000, 336.04], [231289200000, 336.82], [233967600000, 336.13], [236559600000, 334.73], [239238000000, 332.52], [241916400000, 331.19], [244508400000, 331.19], [247186800000, 332.35], [249778800000, 333.47], [252457200000, 335.11], [255135600000, 335.26], [257554800000, 336.60], [260233200000, 337.77], [262825200000, 338.00], [265503600000, 337.99], [268095600000, 336.48], [270774000000, 334.37], [273452400000, 332.27], [276044400000, 332.41], [278722800000, 333.76], [281314800000, 334.83], [283993200000, 336.21], [286671600000, 336.64], [289090800000, 338.12], [291769200000, 339.02], [294361200000, 339.02], [297039600000, 339.20], [299631600000, 337.58], [302310000000, 335.55], [304988400000, 333.89], [307580400000, 334.14], [310258800000, 335.26], [312850800000, 336.71], [315529200000, 337.81], [318207600000, 338.29], [320713200000, 340.04], [323391600000, 340.86], [325980000000, 341.47], [328658400000, 341.26], [331250400000, 339.29], [333928800000, 337.60], [336607200000, 336.12], [339202800000, 336.08], [341881200000, 337.22], [344473200000, 338.34], [347151600000, 339.36], [349830000000, 340.51], [352249200000, 341.57], [354924000000, 342.56], [357516000000, 343.01], [360194400000, 342.47], [362786400000, 340.71], [365464800000, 338.52], [368143200000, 336.96], [370738800000, 337.13], [373417200000, 338.58], [376009200000, 339.89], [378687600000, 340.93], [381366000000, 341.69], [383785200000, 342.69], [389052000000, 344.30], [391730400000, 343.43], [394322400000, 341.88], [397000800000, 339.89], [399679200000, 337.95], [402274800000, 338.10], [404953200000, 339.27], [407545200000, 340.67], [410223600000, 341.42], [412902000000, 342.68], [415321200000, 343.46], [417996000000, 345.10], [420588000000, 345.76], [423266400000, 345.36], [425858400000, 343.91], [428536800000, 342.05], [431215200000, 340.00], [433810800000, 340.12], [436489200000, 341.33], [439081200000, 342.94], [441759600000, 343.87], [444438000000, 344.60], [446943600000, 345.20], [452210400000, 347.36], [454888800000, 346.74], [457480800000, 345.41], [460159200000, 343.01], [462837600000, 341.23], [465433200000, 341.52], [468111600000, 342.86], [470703600000, 344.41], [473382000000, 345.09], [476060400000, 345.89], [478479600000, 347.49], [481154400000, 348.00], [483746400000, 348.75], [486424800000, 348.19], [489016800000, 346.54], [491695200000, 344.63], [494373600000, 343.03], [496969200000, 342.92], [499647600000, 344.24], [502239600000, 345.62], [504918000000, 346.43], [507596400000, 346.94], [510015600000, 347.88], [512690400000, 349.57], [515282400000, 350.35], [517960800000, 349.72], [520552800000, 347.78], [523231200000, 345.86], [525909600000, 344.84], [528505200000, 344.32], [531183600000, 345.67], [533775600000, 346.88], [536454000000, 348.19], [539132400000, 348.55], [541551600000, 349.52], [544226400000, 351.12], [546818400000, 351.84], [549496800000, 351.49], [552088800000, 349.82], [554767200000, 347.63], [557445600000, 346.38], [560041200000, 346.49], [562719600000, 347.75], [565311600000, 349.03], [567990000000, 350.20], [570668400000, 351.61], [573174000000, 352.22], [575848800000, 353.53], [578440800000, 354.14], [581119200000, 353.62], [583711200000, 352.53], [586389600000, 350.41], [589068000000, 348.84], [591663600000, 348.94], [594342000000, 350.04], [596934000000, 351.29], [599612400000, 352.72], [602290800000, 353.10], [604710000000, 353.65], [607384800000, 355.43], [609976800000, 355.70], [612655200000, 355.11], [615247200000, 353.79], [617925600000, 351.42], [620604000000, 349.81], [623199600000, 350.11], [625878000000, 351.26], [628470000000, 352.63], [631148400000, 353.64], [633826800000, 354.72], [636246000000, 355.49], [638920800000, 356.09], [641512800000, 357.08], [644191200000, 356.11], [646783200000, 354.70], [649461600000, 352.68], [652140000000, 351.05], [654735600000, 351.36], [657414000000, 352.81], [660006000000, 354.22], [662684400000, 354.85], [665362800000, 355.66], [667782000000, 357.04], [670456800000, 358.40], [673048800000, 359.00], [675727200000, 357.99], [678319200000, 356.00], [680997600000, 353.78], [683676000000, 352.20], [686271600000, 352.22], [688950000000, 353.70], [691542000000, 354.98], [694220400000, 356.09], [696898800000, 356.85], [699404400000, 357.73], [702079200000, 358.91], [704671200000, 359.45], [707349600000, 359.19], [709941600000, 356.72], [712620000000, 354.79], [715298400000, 352.79], [717894000000, 353.20], [720572400000, 354.15], [723164400000, 355.39], [725842800000, 356.77], [728521200000, 357.17], [730940400000, 358.26], [733615200000, 359.16], [736207200000, 360.07], [738885600000, 359.41], [741477600000, 357.44], [744156000000, 355.30], [746834400000, 353.87], [749430000000, 354.04], [752108400000, 355.27], [754700400000, 356.70], [757378800000, 358.00], [760057200000, 358.81], [762476400000, 359.68], [765151200000, 361.13], [767743200000, 361.48], [770421600000, 360.60], [773013600000, 359.20], [775692000000, 357.23], [778370400000, 355.42], [780966000000, 355.89], [783644400000, 357.41], [786236400000, 358.74], [788914800000, 359.73], [791593200000, 360.61], [794012400000, 361.58], [796687200000, 363.05], [799279200000, 363.62], [801957600000, 363.03], [804549600000, 361.55], [807228000000, 358.94], [809906400000, 357.93], [812502000000, 357.80], [815180400000, 359.22], [817772400000, 360.44], [820450800000, 361.83], [823129200000, 362.95], [825634800000, 363.91], [828309600000, 364.28], [830901600000, 364.94], [833580000000, 364.70], [836172000000, 363.31], [838850400000, 361.15], [841528800000, 359.40], [844120800000, 359.34], [846802800000, 360.62], [849394800000, 361.96], [852073200000, 362.81], [854751600000, 363.87], [857170800000, 364.25], [859845600000, 366.02], [862437600000, 366.46], [865116000000, 365.32], [867708000000, 364.07], [870386400000, 361.95], [873064800000, 360.06], [875656800000, 360.49], [878338800000, 362.19], [880930800000, 364.12], [883609200000, 364.99], [886287600000, 365.82], [888706800000, 366.95], [891381600000, 368.42], [893973600000, 369.33], [896652000000, 368.78], [899244000000, 367.59], [901922400000, 365.84], [904600800000, 363.83], [907192800000, 364.18], [909874800000, 365.34], [912466800000, 366.93], [915145200000, 367.94], [917823600000, 368.82], [920242800000, 369.46], [922917600000, 370.77], [925509600000, 370.66], [928188000000, 370.10], [930780000000, 369.08], [933458400000, 366.66], [936136800000, 364.60], [938728800000, 365.17], [941410800000, 366.51], [944002800000, 367.89], [946681200000, 369.04], [949359600000, 369.35], [951865200000, 370.38], [954540000000, 371.63], [957132000000, 371.32], [959810400000, 371.53], [962402400000, 369.75], [965080800000, 368.23], [967759200000, 366.87], [970351200000, 366.94], [973033200000, 368.27], [975625200000, 369.64], [978303600000, 370.46], [980982000000, 371.44], [983401200000, 372.37], [986076000000, 373.33], [988668000000, 373.77], [991346400000, 373.09], [993938400000, 371.51], [996616800000, 369.55], [999295200000, 368.12], [1001887200000, 368.38], [1004569200000, 369.66], [1007161200000, 371.11], [1009839600000, 372.36], [1012518000000, 373.09], [1014937200000, 373.81], [1017612000000, 374.93], [1020204000000, 375.58], [1022882400000, 375.44], [1025474400000, 373.86], [1028152800000, 371.77], [1030831200000, 370.73], [1033423200000, 370.50], [1036105200000, 372.18], [1038697200000, 373.70], [1041375600000, 374.92], [1044054000000, 375.62], [1046473200000, 376.51], [1049148000000, 377.75], [1051740000000, 378.54], [1054418400000, 378.20], [1057010400000, 376.68], [1059688800000, 374.43], [1062367200000, 373.11], [1064959200000, 373.10], [1067641200000, 374.77], [1070233200000, 375.97], [1072911600000, 377.03], [1075590000000, 377.87], [1078095600000, 378.88], [1080770400000, 380.42], [1083362400000, 380.62], [1086040800000, 379.70], [1088632800000, 377.43], [1091311200000, 376.32], [1093989600000, 374.19], [1096581600000, 374.47], [1099263600000, 376.15], [1101855600000, 377.51], [1104534000000, 378.43], [1107212400000, 379.70], [1109631600000, 380.92], [1112306400000, 382.18], [1114898400000, 382.45], [1117576800000, 382.14], [1120168800000, 380.60], [1122847200000, 378.64], [1125525600000, 376.73], [1128117600000, 376.84], [1130799600000, 378.29], [1133391600000, 380.06], [1136070000000, 381.40], [1138748400000, 382.20], [1141167600000, 382.66], [1143842400000, 384.69], [1146434400000, 384.94], [1149112800000, 384.01], [1151704800000, 382.14], [1154383200000, 380.31], [1157061600000, 378.81], [1159653600000, 379.03], [1162335600000, 380.17], [1164927600000, 381.85], [1167606000000, 382.94], [1170284400000, 383.86], [1172703600000, 384.49], [1175378400000, 386.37], [1177970400000, 386.54], [1180648800000, 385.98], [1183240800000, 384.36], [1185919200000, 381.85], [1188597600000, 380.74], [1191189600000, 381.15], [1193871600000, 382.38], [1196463600000, 383.94], [1199142000000, 385.44]];

var d = [];
var nusers = Math.floor(Math.random()*10);
var cplot_max_x=0;
var cplot_min_x=0;

for(var i=0; i<200; i++) {
  cplot_min_x = starttime-(i*60000);
  d.push([cplot_min_x, nusers]);
  if(cplot_max_x==0) cplot_max_x = cplot_min_x;
  nusers += Math.floor((Math.random()*5)-2.5);
  if(nusers < 0) nusers = 0;
}

console.log("d is");
console.log(d);

$( document ).ready(function() {
  var conplot = $.plot("#connected_users_chart", [d], {
    lines: { show: true, steps: true },
  	xaxis: { mode: "time" },
      selection: {
  				mode: "x"
        }
  });

  $("#connected_users_chart").dblclick(function () {
    conplot.setSelection({x1: cplot_min_x, x2: cplot_max_x});

  })

  $("#connected_users_chart").bind("plotselected", function (event, ranges) {

  			// do the zooming
  			$.each(conplot.getXAxes(), function(_, axis) {
  				var opts = axis.options;
  				opts.min = ranges.xaxis.from;
  				opts.max = ranges.xaxis.to;
          console.log("opts.min,opts.max");
          console.log(opts.min);
          console.log(opts.max);
  			});
  			conplot.setupGrid();
  			conplot.draw();
  			conplot.clearSelection();

  			// don't fire event on the overview to prevent eternal loop
  		});

      var d2 = [], d3 = [];
      var ndata = Math.floor(Math.random()*10000);

      for(var i=0; i<60; i++) {
        d2.push([starttime-(i*20000), ndata]);
        ndata = Math.floor(Math.random()*10000);
        d3.push([starttime-(i*20000), ndata]);
        ndata = Math.floor(Math.random()*10000);
      }


  var data_plot = $.plot("#data_usage_graph", [{
    data: d2,
  	xaxis: { mode: "time" },
    selection: {
        mode: "x"
      }
  }, {
    data: d3,
  	xaxis: { mode: "time" },
    selection: {
        mode: "x"
      }
  }], {
    xaxis: { mode: "time" },
    selection: {
        mode: "x"
      }
    });

    $("#data_usage_graph").bind("plotselected", function (event, ranges) {

    			// do the zooming
    			$.each(data_plot.getXAxes(), function(_, axis) {
    				var opts = axis.options;
    				opts.min = ranges.xaxis.from;
    				opts.max = ranges.xaxis.to;
    			});
    			data_plot.setupGrid();
    			data_plot.draw();
    			data_plot.clearSelection();

    			// don't fire event on the overview to prevent eternal loop
    		});

  var d10 = [], d11 = [], d12 = [];
  var d10_c = Math.floor(Math.random()*100);
  var d11_c = Math.floor(Math.random()*(100-d10_c));
  var d12_c = 100-d10_c-d11_c;

  for(var i=0; i<60; i++) {
    var delt = Math.floor(Math.random()*7)-3;
    var delt_11 = Math.floor(Math.random()*delt)-(delt/2);
    d10_c += delt;
    d11_c += delt_11;
    d12_c = 100-d10_c-d11_c;
    d10.push([starttime-(i*20000), d10_c]);
    d11.push([starttime-(i*20000), d11_c]);
    d12.push([starttime-(i*20000), d12_c]);
  }


  $.plot("#cpu_usage_graph",  [{
    data: d10
  }, {
    data: d11
  }, {
    data: d12
  }], {
    xaxis: { mode: "time" },
    selection: {
        mode: "x"
      }
  });
})

<?php
  echo "</script>";


  echo "<hr>";
  echo "<div class='mybodysubheading'>Logs <a href='#' onmouseover='show_help(\"logs_help.html\")' onmouseout='hide_help()'>?</a></div>";
  log_viewLogs();
}

function web_doPageMenu()
{
  echo "<div id='menu_name'>Menu</div><hr>";
  global $WEB_HEADCHECK, $MENU_LIST;
  foreach($MENU_LIST as $name => $mlist) {
    echo "<li><a class='menuitem' id='mi_$name' href='$mlist'>$name</a><br>";
  }

  echo "<hr>";
  echo "<a href='?action=logout'>Logout</a>";
}

function web_doPageBottom()
{

  echo "<div id='myfooter'><a href='http://www.gnu.org/licenses/gpl-2.0.html'>GPL</a> software by <a href='https://github.com/takigama'>Takigama</a> &copy 2015</div>";
  global $HOMEDIR, $DB_TYPE, $DB_LOCATION;
  echo "<div id='mydebug'>";
  echo "<pre>\n\nServer:\n";
  print_r($_SERVER);
  echo "\n\nRequest:\n";
  print_r($_REQUEST);
  echo "\n\nGlobal:\n";
  print_r($_GLOBAL);
  echo "\n\sSession:\n";
  print_r($_SESSION);
  echo "\n\nconfigs\nHOMEDIR: $HOMEDIR\nDBTYPE: $DB_TYPE\nDB_LOC: $DB_LOCATION\n";
  echo "\n\nend\n</pre>";
  echo "</div>";
}

/* **********************

Token pickup functions start here

*/


function web_buildPagePickup()
{
  web_pickupCheckPost();

  web_doHeaders();

  if(isset($_REQUEST["tkpuid"])) {
    $tid = $_REQUEST["tkpuid"];
    if(file_exists("../pickup/$tid.url")) {
      web_pickupBeginInstruction();
    } else {
      web_pickupNoToken();
    }
  } else {
    web_pickupNoID();
  }

  web_doPageBottom();
}

function web_pickupCheckPost()
{
  global $HOMEDIR;

  if(isset($_REQUEST["pickupconf"])) {
    ctrl_writeClientFile();
    if(!file_exists("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn")) {
      error_log("fail...");
      echo "<html>Problem...</html>";
    } else {
      $info = file_get_contents("$HOMEDIR/data/".db_getConfig("site.ident").".ovpn");
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . db_getConfig("site.ident").".ovpn");
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: ' . strlen($info));
      echo $info;
    }
    exit(0);
  }

  if(isset($_REQUEST["gettokenimage"])) {
    if(isset($_REQUEST["tkpuid"])) {
      $tid = $_REQUEST["tkpuid"];
      header("Content-Type: image/png");
      echo file_get_contents("../pickup/$tid.png");
      unlink("../pickup/$tid.png");
      db_clearTKIDForUser($tid);
      exit(0);
    }
  }

  if(isset($_REQUEST["sure"])) {
    if(isset($_REQUEST["tkpuid"])) {
      $tid = $_REQUEST["tkpuid"];
      web_pickupDoPickupPage();
    }
  }
}

function web_pickupDoPickupPage()
{

  $tid = $_REQUEST["tkpuid"];

  $url = file_get_contents("../pickup/$tid.url");

  web_doHeaders();

  echo "<html>";
  echo "<h1>Heres your token</h1>";
  echo "<b>Once you have a key in Google Authenticator, be sure to close this page!</b><br><br>";
  echo "If your browsing from the mobile device with google authenticator installed, click the <a href='$url'>Here</a> to import your key.<br><br>";
  echo "If you are browsing this page from your desktop, scan the following QRcode using the google authenticator software<br><img src='pickup.php?tkpuid=$tid&gettokenimage'>";

  echo "<br>While you are here, you can also download the client configuration file, <a href='?pickupconf'>Here</a>";
  echo "</html>";

  unlink("../pickup/$tid.url");
  exit(0);
}

function web_pickupBeginInstruction()
{
  echo file_get_contents("../templates/pickup/page1.html");
}

function web_pickupNoToken()
{
  echo file_get_contents("../templates/pickup/notoken.html");
}

function web_pickupNoID()
{
  echo file_get_contents("../templates/pickup/noid.html");
}



?>
