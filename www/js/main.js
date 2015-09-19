$( document ).ready(function() {
  console.log("ferk");
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

})

function submit_test_token_check()
{
  $.ajax({
    url: "index.php?action=testtokencheck",
    type: "POST",
    data: $("#tokentestform").serialize(),
    success: function (data) {
      console.log(data);
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

function submit_main_config_form()
{
  $.ajax({
    url: "index.php?action=updateconfig",
    type: "POST",
    data: $("#configform").serialize(),
    success: function (data) {
      console.log(data);
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
