<section class="content-header">
      <h1>
        Dashboard
        <small>Control panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Dashboard</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3><?php ?></h3>

              <p>Total Number of Students</p>
            </div>
            <div class="icon">
              <i class="ion ion-person"></i>
            </div>
            <a href="?students" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3><?php  ?></h3>

              <p>Total Number of Classes</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
            <a href="?classes" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3><?php echo $this->getNumberOfUsers(); ?></h3>

              <p>System Users</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="?users" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3><?php echo $this->getNumberOfLogins($_SESSION['vsoftadmin']); ?></h3>

              <p>Number of Logins</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
            <a href="?logins" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
      </div>
      <!-- /.row -->
     
            <!-- Info boxes -->
      <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="ion ion-ios-gear-outline"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">CPU Traffic</span>
              <span class="info-box-number"><?php echo $this->getCpu()[0]*100; ?><small>%</small></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
        <!-- /.col -->
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-red"><i class="glyphicon glyphicon-floppy-disk"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">Storage</span>
              <span class="info-box-number"><?php $this->getDiskUsage(); ?></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
        <!-- /.col -->

        <!-- fix for small devices only -->
        <div class="clearfix visible-sm-block"></div>

        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-green"><i class="glyphicon glyphicon-globe"></i></span>
             <?php $lastLogin = $this->getLastLogin($_SESSION['vsoftadmin']); ?>
            <div class="info-box-content">
              <span class="info-box-text">Last Login IP</span>
              <span class="info-box-number"><?php echo $lastLogin[1]; ?></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
        <!-- /.col -->
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="glyphicon glyphicon-log-in"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Last Login Date</span>
              <span class="info-box-number"><?php echo $lastLogin[0]; ?></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

      <div class="row" style="margin: 15px;">
          <div class="modal-dialog modal-md" style="border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;">
            <div class="modal-content" style="border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;">
              <div class="modal-body">
                  <h3 style='color: #035888; text-decoration: none; font-family: helvetica' class="form-signin-heading"><center>SPERIXLABS</center></h3>
                  <center><img src="images/crest.jpg" style="width: 80px; height: auto; margin: 5px;" /></center>
              </div>
            </div>
          </div>
      </div>
    </section>
    <!-- /.content -->

    <?php 
      if(isset($_POST['uploadImg'])){
        $this->changeProfilePicAdmin();
      }
    ?>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
  <script src="js/pages/dashboard.js"></script>
  <script>
    $.widget.bridge('uibutton', $.ui.button);
  </script>