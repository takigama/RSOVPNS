$( document ).ready(function() {
  console.log("ferk");
  $("#submit_create_form_button").click(function(e) {
    e.preventDefault();
    submit_form_clicked();
  })
})

function submit_form_clicked()
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
      }
    },
    error: function (jXHR, textStatus, errorThrown) {
        alert(errorThrown);
    }
  })
  // if post succeeds clear form

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
