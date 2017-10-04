<?php
  $userDetails = $vsoft->getFullDetailsPid($_SESSION['vsoftadmin'],"login_details");
  $dp = $vsoft->getFullDetailsPid($_SESSION['vsoftadmin'],"dp");
?>
  <header class="main-header">
    <!-- Logo -->
    <a href="?home" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>S</b>L</span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>SPERIX</b>LABS</span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>

      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <!-- User Account: style can be found in dropdown.less -->
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <img src="<?php echo $dp[2]; ?>" class="user-image" alt="User Image">
              <span class="hidden-xs"><?php echo $userDetails[2]; ?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- User image -->
              <li class="user-header">
                <img src="<?php echo $dp[2]; ?>" class="img-circle" alt="User Image">

                <p>
                  <?php echo $userDetails[2]; ?>
                  <small>...</small>
                </p>
              </li>
              <!-- Menu Body -->
              <li class="user-body">
                <div class="row">
                  <div class="col-xs-4 text-center">
                    <a href="?passwd"><span class="glyphicon glyphicon-pencil"></span> Password</a>
                  </div>
                  <div class="col-xs-4 text-center">
                    <a href="#profilePic" data-backdrop="static" data-toggle="modal"><span class="glyphicon glyphicon-picture"></span> Profile Picture</a>
                  </div>
                  <div class="col-xs-4 text-center">
                    <a href="#lockScreen" onclick="lockSession()" data-toggle="modal" data-backdrop="static"><span class="glyphicon glyphicon-lock"></span> Lock Session</a>
                  </div>
                </div>
                <!-- /.row -->
              </li>
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a href="?profile" class="btn btn-default btn-flat">Profile</a>
                </div>
                <div class="pull-right">
                  <a href="?logout" class="btn btn-default btn-flat">Sign out</a>
                </div>
              </li>
            </ul>
          </li>
          <!-- Control Sidebar Toggle Button -->
          <li>
            <a href="#" data-toggle="control-sidebar1"><i class="fa fa-gears"></i></a>
          </li>
        </ul>
      </div>
    </nav>
  </header>

  <!--modal for lockscreen -->
<div id="lockScreen" class="modal fade">  
  <div class="modal-dialog modal-md" style="width: 40%;">
    <div class="modal-content">
      <div class="modal-header bgblue">
          <h3 style="text-align: center;" class="panel-title"><span class="glyphicon glyphicon-lock"></span> Session Lock</h3>
      </div>
      <div class="modal-body hold-transition lockscreen">
        <div class="lockscreen-wrapper" id="view1">
            <div class="lockscreen-logo">
              <a href="#" style="font-size: 23px;"><b>Stepping Stone</b> School Complex</a>
            </div>
            <!-- User name -->
            <div class="lockscreen-name"><?php echo $userDetails[2]; ?></div>

            <!-- START LOCK SCREEN ITEM -->
            <div class="lockscreen-item">
              <!-- lockscreen image -->
              <div class="lockscreen-image">
                <img src="<?php echo $dp[2]; ?>" alt="User Image">
              </div>
              <!-- /.lockscreen-image -->

              <!-- lockscreen credentials (contains the form) -->
              <form class="lockscreen-credentials">
                <div class="input-group">
                  <input type="password" id="password" class="form-control" placeholder="password">

                  <div class="input-group-btn">
                    <button type="button" name="unlockBtn" id="unlockBtn" class="btn"><i class="fa fa-arrow-right text-muted"></i></button>
                  </div>
                </div>
              </form>
              <!-- /.lockscreen credentials -->

            </div>
            <!-- /.lockscreen-item -->
            <div class="help-block text-center">
              Enter your password to retrieve your session
            </div>
            <div class="text-center">
              <a href="?logout">Or sign in as a different user</a>
            </div>
            <div class="lockscreen-footer text-center">
              <?php include "footer2.php"; ?>
            </div>
          </div>
          <div class="row" style="margin: 15px;" id="view2">
              <div style="text-align: center;"><a href="#" onclick="restoreView()" data-dismiss="modal" class="btn btn-lg btn-success"><span class="glyphicon glyphicon-ok"></span> Click to unlock</a></div>
          </div>
      </div>
    </div>
  </div>
</div>

  <!-- change profile pic modal -->
<div id="profilePic" class="modal fade">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #035888; color: #fff;">
        <h3 class="panel-title"><center><span class="glyphicon glyphicon-picture"></span> Profile Picture</center></h3>
      </div>
      <div class="modal-body">
          <form method="post" action="?dashboard" class="form" enctype="multipart/form-data">
              <div class="form-group">
                  <label for="image"><span class="glyphicon glyphicon-picture"></span> Profile Picture:</label>
                  <input type="file" accept="image/*" id="image" onchange="displayImage(this,'imgResult')" name="image" class="form-control" placeholder="Profile Picture" autofocus/>
                  <br/><center><a href='#showWebcam' data-toggle='modal' class='btn btn-xs btn-info'><span class='glyphicon glyphicon-camera'></span> Use Webcam</a></center>
              </div>
              <div class="form-group">
               <input type='hidden' id="picture"  name="picture" value="<?php echo $dp[2]; ?>"/>
              </div>
              <div class="form-group">
                  <center><img id="imgResult" class="img-thumbnail" src="<?php echo $dp[2]; ?>" style="width: auto; height: 150px;"/></center>
              </div>
              <div class="form-group">
                  <center><button type="submit" onclick="fixError()" class="btn btn-xs btn-success br" name="uploadImg"><span class="glyphicon glyphicon-cloud-upload"></span> Upload</button>&nbsp;<a href="#" data-dismiss="modal" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Close</a></center>
              </div>
          </form>
      </div>
    </div>
  </div>
</div>

<div id="showWebcam" class="modal fade">
  <div class="modal-dialog modal-md" style="width: 40%;">
    <div class="modal-content">
      <div class="modal-header bgblue">
        <h3 class="panel-title" style="text-align: center;"><span class="glyphicon glyphicon-record"></span> Capture Image</h3>
      </div>
      <div class="modal-body">
        <div class="row" style="margin: 15px;">
          <center><div id="my_camera"></div></center>
        </div>
        <div class="row">
          <div id="pre_take_buttons">
            <center><input type=button class='btn btn-xs btn-success' value="Take Snapshot" onClick="preview_snapshot()"></center>
          </div>
          <div id="post_take_buttons" style="display:none">
            <center><input type=button class='btn btn-xs btn-warning' value="&lt; Take Another" onClick="cancel_preview()">
            <input type=button class='btn btn-xs btn-info' value="Save Photo &gt;" onClick="save_photo()" style="font-weight:bold;"></center>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$('#view2').hide();
$('#unlockBtn').click(function(){
    var password = $('#password').val();
    $.post('ajax.php',{'unlock':password},function(data){
      if(data==1){
        $('#view1').hide();
        $('#view2').show();
        $('#view3').hide();
      }else{
        alertify.alert('Stepping Stone Complex','Invalid password');
      }
    });
});

function lockSession(){
  $.post('ajax.php',{'lock':'y'},function(data){
      //console.log(data);
  });
}

function restoreView(){
  $('#view2').hide();
  $('#view1').show();
  $('#view3').show();
  document.getElementById('password').value = "";
}
</script>

<script language="JavaScript">
    Webcam.set({
      width: 320,
      height: 240,
      image_format: 'jpeg',
      jpeg_quality: 90
    });
    Webcam.attach( '#my_camera' );
</script>

<script language="JavaScript">
    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }
    function preview_snapshot() {
      // freeze camera so user can preview pic
      Webcam.freeze();
      
      // swap button sets
      document.getElementById('pre_take_buttons').style.display = 'none';
      document.getElementById('post_take_buttons').style.display = '';
    }
    
    function cancel_preview() {
      // cancel preview freeze and return to live camera feed
      Webcam.unfreeze();
      
      // swap buttons back
      document.getElementById('pre_take_buttons').style.display = '';
      document.getElementById('post_take_buttons').style.display = 'none';
    }
    
    function save_photo() {
      // actually snap photo (from preview freeze) and display it
      Webcam.snap( function(data_uri) {
        // display results in page
        document.getElementById('imgResult').setAttribute('src',data_uri);
        document.getElementById('imgResult').setAttribute('style','width: 215px; height; auto');
        //setting data uri to picture value
        document.getElementById('picture').setAttribute('value',data_uri);
        
        // swap buttons back
        document.getElementById('pre_take_buttons').style.display = '';
        document.getElementById('post_take_buttons').style.display = 'none';
      } );
    }

    function displayImage(fileInput,id){
      showMyImage(fileInput, id);
      document.getElementById('imgResult').setAttribute('style','width: auto; height: 160px');
      //getting picture data uri
      var data_uri = document.getElementById('imgResult').src;
      //console.log(data_uri);
      //setting data uri to picture value
        document.getElementById('picture').setAttribute('value',data_uri);
    }

    function fixError(){
      var data_uri = document.getElementById('imgResult').src;
      document.getElementById('picture').setAttribute('value',data_uri);
    }
  </script>