<?php 
  session_start();
  ob_start("ob_gzhandler");
  require "../ssFiles/includes/functions.php";
  $vsoft = new VSoft();
  $vsoft->checkIfLoggedIn();
  $vsoft->activatessl();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Stepping Stone School Complex | Log In</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <?php
    include "../".FNAME."/includes/scripts.php";
  ?>
  <style type="text/css">
    .loader {
        border: 16px solid #f3f3f3;
        border-radius: 50%;
        border-top: 16px solid blue;
        border-right: 16px solid red;
        border-bottom: 16px solid yellow;
        border-left: 16px solid green;
        width: 120px;
        height: 120px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
      }
      @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
  </style>
</head>
<body style="background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.6) 100%), url('images/bg.jpg'); background-size: 100%;">
  <div class="row" style="margin: 5px;">
      <div class="col-md-4">

      </div>
      <div class="col-md-4">
          <center><img src="images/logo.png" style="padding-top: 5px;width: 50px; height: auto;" /></center>
      </div>
      <div class="col-md-4">

      </div>
  </div>
  <div class="container">
      <div class="row" style="margin: 15px;">
        <div class="col-md-4"></div>
        <div class="col-md-4">
          <form class="form-signin form well br" method="post" action="#" id="signin1" style="background-color: #fff;">
            <div id="displayRes" style="text-align: center;"></div>
            <h3 style='font-size: 20px; color: #367fa9; font-weight: bold;' class="form-signin-heading"><center>SPERIXLABS</center></h3>
            <h3 style='font-size: 15px; color: grey; text-decoration: underline;' class="form-signin-heading"><center>Log In with your username and password</center></h3>
            <center><img src="images/gofike_logo.png" style="width: 80px; height: auto; margin: 5px;" /></center>
            <label for="username" class="sr-only">User Name</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="User Name" required autofocus>
            <label for="password" class="sr-only">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
            <button class="btn btn-lg btn-primary btn-block br" name="loginBtn" type="submit"><span class="glyphicon glyphicon-log-in"></span> Sign in</button>
          </form>
        </div>
        <div class="col-md-4"></div>
      </div>
    </div> <!-- /container -->
    <div class="row">
      <div class="col-md-2">

      </div>
      <div class="col-md-8" style="color: grey">
          <center><img src="images/separator.png" /><br/>
          <p style="color: #fff; font-weight: bold;">For support and further inquiries please contact Administrator</p>
          </center>

      </div>
      <div class="col-md-2">

      </div>
  </div>
<?php
  if(isset($_POST['loginBtn'])){
    $vsoft->verifyAdmin($_POST['username'],$_POST['password']);
  }
?>
</body>
</html>
