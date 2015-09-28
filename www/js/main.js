$( document ).ready(function() {
  //console.log("ferk");
  $('#main_configuration_form').click(function(e) {
    e.preventDefault();
    submit_main_config_form();
  })

  $("#submit_create_user_form_button").click(function(e) {
    e.preventDefault();
    submit_create_user_form_clicked();
  })

  $("#test_token_submit_button").click(function(e) {
    e.preventDefault();
    submit_test_token_check();
  })

  $("#send_test_email").click(function(e) {
    e.preventDefault();
    send_test_email();
  })


})

function send_test_email()
{

  document.getElementById("send_test_email_scroller").style.width = 100;
  document.getElementById("send_test_email_scroller").style.background = "#00FF00";
  var animate = $("#send_test_email_scroller").animate({ width: '-='+100}, 22000, function() {
    document.getElementById("send_test_email_scroller").style.width = 0;
  });

  $.ajax({
    url: "index.php?action=sendtestemail",
    type: "POST",
    data: $("#testemailform").serialize(),
    success: function (data) {
      //console.log(data);
      result = JSON.parse(data);
      if(result.result == "failure") {
        alert("Failed: " + result.reason);
        animate.stop();
        document.getElementById("send_test_email_scroller").style.width = 0;
      }
      if(result.result == "success") {
        alert("E-Mail was sent successully!");
        animate.stop();
        document.getElementById("send_test_email_scroller").style.width = 0;
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
}

function submit_test_token_check()
{
  $.ajax({
    url: "index.php?action=testtokencheck",
    type: "POST",
    data: $("#tokentestform").serialize(),
    success: function (data) {
      //console.log(data);
      result = JSON.parse(data);
      if(result.result == "failure") {
        alert("Failed: " + result.reason);
      }
      if(result.result == "success") {
        alert("Yes, it authed successfully!");
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
}

function show_help(page)
{

  document.getElementById("userhelpboxid").innerHTML = "<div class='mybodyheading'>Loading....</div>";

  var e = window.event;

  var posX = e.clientX;
  var posY = e.clientY;

  //document.getElementById("userhelpboxid").innerHTML = page;
  $("#userhelpboxid").load("help/"+page);

  document.getElementById("userhelpboxid").style.top = posY-60;
  document.getElementById("userhelpboxid").style.left = posX+30
  document.getElementById("userhelpboxid").style.display = "block";
}

function hide_help()
{
  document.getElementById("userhelpboxid").style.display = "none";
}

function bring_up_edit(edittype, user, currentval)
{
  if(!$("#usereditboxid").hasClass("show")) {
    //console.log("would bring up "+edittype+" for "+user);

    var e = window.event;

    var posX = e.clientX;
    var posY = e.clientY;
    //console.log("x: "+posX+" y: "+posY);
    // fill in the stuff for this thingy...
    var insides = "<form id='edituserformpopup'><input type='hidden' name='action' value='edituservals'>";
    insides += "<input type='hidden' name='type' value='"+edittype+"'><input type='hidden' name='user' value='"+user+"'>";

    switch(edittype) {
      case 'email':
        insides += "EMail Address: <input type='text' name='email' value='"+currentval+"'>";
        document.getElementById("usereditboxid").style.width = "340px";
        document.getElementById("usereditboxid").style.height = "18px";
      break;
      case 'password':
        document.getElementById("usereditboxid").style.width = "340px";
        document.getElementById("usereditboxid").style.height = "88px";
        insides += "<table><tr><td>Password</td><td><input type='text' name='pass1' value=''></td></tr>";
        insides += "<tr><td>Confirm</td><td><input type='text' name='pass2' value=''></td></tr>";
        insides += "<tr><td>Clear Password</td><td><input type='checkbox' name='clear'></td></tr></table>";
      break;
      case 'enabled':
        var checked = "";
        if(currentval == 'Yes') {
          checked = " checked";
        }
        document.getElementById("usereditboxid").style.height = "18px";
        document.getElementById("usereditboxid").style.width = "130px";
        insides += "Enabled: <input type='hidden' name='user_enabled' value='off'><input type='checkbox' name='user_enabled'"+checked+">";
      break;
      case 'token':
        document.getElementById("usereditboxid").style.height = "18px";
        document.getElementById("usereditboxid").style.width = "240px";
        insides += "Token Type: <select name='tokentype'><option value='totp'>TOTP</option><option value='hotp'>HOTP</option><option value='none'>none</option></select>";
      break;
      case 'radius':
        var checked = "";
        if(currentval == "Enabled") {
          checked = " checked";
        }
        document.getElementById("usereditboxid").style.height = "18px";
        document.getElementById("usereditboxid").style.width = "130px";
        insides += "Radius Auth: <input type='hidden' name='radius_enabled' value='off'><input type='checkbox' name='radius_enabled'"+checked+">";
      break;

    }
    insides += "<div class='tickforedits'><img src='images/tick.png' width='22px' height='22px' onclick='send_update_user_values()'></div></form>";

    document.getElementById("usereditboxid").innerHTML = insides;

    //$("#usereditboxid").innerHTML = insides;


    document.getElementById("usereditboxid").style.top = posY-60;
    document.getElementById("usereditboxid").style.left = posX+30
    document.getElementById("usereditboxid").style.display = "block";
  }
}

function send_update_user_values()
{
  $.ajax({
    url: "index.php?action=edituservals",
    type: "POST",
    data: $("#edituserformpopup").serialize(),
    success: function (data) {
      //console.log(data);
      result = JSON.parse(data);
      if(result.result == "failure") {
        $("#usereditboxid").toggleClass("show");
        $("#usereditboxid").hide();
        //alert("Failed: " + result.reason);
      }
      if(result.result == "success") {
        $("#usereditboxid").toggleClass("show");
        $("#usereditboxid").hide();
        //alert("User Updated");
        //console.log("no do reload...");
        location.reload(true);
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
}

function edit_clicked(id)
{
  //console.log("\"click\"");
  //document.getElementById("usereditboxid").style += " show";
  $("#usereditboxid").toggleClass("show");
}


function drop_edit()
{
  var did = document.getElementById("usereditboxid");
  //console.log("checking for show...");
  if(!$("#usereditboxid").hasClass("show")) {
    //console.log("i have no class");
    did.style.display = "none";
  }
}


function submit_create_user_form_clicked()
{
  var username = document.getElementById("cr_username").value;
  var pass1 = document.getElementById("cr_pass1").value;
  var pass2 = document.getElementById("cr_pass2").value;
  var email = document.getElementById("cr_email").value;
  var enabled = document.getElementById("cr_enabled").checked;
  var radius = document.getElementById("cr_rad_on").checked;
  var token_type = document.getElementById("cr_tok_on").value;
  var token = false;
  if(token_type != "none") {
    token = true;
  }

  //alert("would submit: user: "+username+", pass1: " + pass1 + " pass2: " + pass2 + " email: " + email + " enabled: " + enabled + " radius: " + radius + " token: " + token);

  // validate here...
  if(pass1 != "") {
    if(pass1 != pass2) {
      alert("Passwords do not much");
      return;
    }

    if(pass1.length < 4) {
      alert("password minimum length must be 4");
      return;
    }
  }

  if(username.length < 2) {
    alert("Username minimum length must be 2");
    return;
  }

  if(username.length > 32) {
    alert("Username maximum length must be 32")
    return;
  }

  if(pass1 == "" && radius == false && token == false) {
    alert("At least one auth method must be defined");
    return;
  }

  if(pass1 != "" && radius == true) {
    alert("A password or radius can be used, but not both at the same time");
    return;
  }


  // post next...
  $.ajax({
    url: "index.php?action=createuser",
    type: "POST",
    data: $("#createuserform").serialize(),
    success: function (data) {
      result = JSON.parse(data);
      if(result.result == "failure") {
        alert("Failed: " + result.reason);
      }
      if(result.result == "success") {
        alert("User Created!");
        clearCreateUserForm();
        location.reload(true);
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
  // if post succeeds clear form
}

function confirm_restart_server()
{
  return window.confirm("Really restart server? All users will be disconnected");
}

function confirm_stop_server()
{
  return window.confirm("Really stop server? All users will be disconnected");
}

function send_do_backup()
{
    $.ajax({
      url: "index.php?action=createbackup",
      type: "POST",
      data: $("#createuserform").serialize(),
      success: function (data) {
        result = JSON.parse(data);
        if(result.result == "failure") {
          alert("Failed: " + result.reason);
        }
        if(result.result == "success") {
          alert("Backup Started!");
          location.reload(true);
        }
      },
      error: function (jXHR, textStatus, errorThrown) {
          alert(errorThrown);
      }
    })

  return false;
}


function change_line_class_in(id)
{
  // couldnt make this work sas a class change, im not sure why really
  document.getElementById(id).style.backgroundColor="#DDDDFF";
  //document.getElementById(id).className="confighovertable";
  //console.log(document.getElementById(id));
}

function change_line_class_out(id)
{
  var did = document.getElementById(id);


    did.style.backgroundColor="#EEEEEE";
  //document.getElementById(id).className="configtable";
}


function send_do_restore_inplace()
{
  var definite = window.confirm("READ CAREFULLY! You are about to delete all configuration and replace it with what is in the current backup, are you sure?");

  if(definite) {
    $.ajax({
      url: "index.php?action=restorefrominplace",
      type: "POST",
      data: $("#createuserform").serialize(),
      success: function (data) {
        result = JSON.parse(data);
        if(result.result == "failure") {
          alert("Failed: " + result.reason);
        }
        if(result.result == "success") {
          alert("Restore Started!");
          location.reload(true);
        }
      },
      error: function (jXHR, textStatus, errorThrown) {
          alert(errorThrown);
      }
    })
  }

}

function submit_main_config_form()
{
  $.ajax({
    url: "index.php?action=updateconfig",
    type: "POST",
    data: $("#configform").serialize(),
    success: function (data) {
      //2console.log(data);
      result = JSON.parse(data);
      if(result.result == "failure") {
        alert("Failed: " + result.reason);
      }
      if(result.result == "success") {
        alert("Configuration Updated!");
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
}

function confirmDeleteUser(username) {
  var definite = window.confirm("Are you sure you wish to delete "+username+"?");

  if(definite) {
    $.ajax({
      url: "index.php?action=deleteuser&user="+username,
      type: "POST",
      success: function (data) {
        result = JSON.parse(data);
        if(result.result == "failure") {
          alert("Failed: " + result.reason);
        }
        if(result.result == "success") {
          //alert("User Deleted!");
          var table = document.getElementById("userlisttable");
          var rowIndex = document.getElementById("row_"+username).rowIndex;
          table.deleteRow(rowIndex);
        }
      },
      error: function (jXHR, textStatus, errorThrown) {
          alert(errorThrown);
      }
    })
  }
  return false;
}

function has_class(element, cls) {
    return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
}


function validateConfigForm()
{
  // TODO: need to actually do this
}

function clearCreateUserForm() {
  document.getElementById("cr_username").value = "";
  document.getElementById("cr_pass1").value = "";
  document.getElementById("cr_pass2").value = "";
  document.getElementById("cr_email").value = "";
  document.getElementById("cr_enabled").checked = false;
  document.getElementById("cr_rad_on").checked = false;
  document.getElementById("cr_tok_on").value = "none";
}
