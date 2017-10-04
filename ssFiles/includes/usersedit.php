<?php
    if(!isset($_SESSION['useredit'])){
        $this->redirect("?users");
    }else{
        $details=$this->getFullDetailsPid($_SESSION['useredit'],"login");
        $details1=$this->getFullDetailsPid($_SESSION['useredit'],"login_details");
    }
?>
<div class="row" id="displayRes"></div>
<div class="row" style="margin: 15px;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #035888; color: #fff;">
                <center><h3 class="panel-title"><span class="glyphicon glyphicon-pencil"></span> Edit Details</h3></center>
            </div>
            <div class="modal-body">
                <form method="post" action="?users&edit" class="form">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="Username" class="form-control" value="<?php echo $details[2]; ?>" required/>
                    </div>
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Full Name" class="form-control" value="<?php echo $details1[2]; ?>" required/>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $details1[4]; ?>" placeholder="Email" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label for="mobileNo">Mobile Number:</label>
                        <input type="text" id="mobileNo" value="<?php echo $details1[3]; ?>" name="mobileNo" placeholder="Mobile Number" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <center><button type="submit" name="updateUserBtn" value="<?php echo $_SESSION['useredit']; ?>" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-pencil"></span> Update Details</button>&nbsp;<a href='?users' class="btn btn-xs btn-danger br tooltip-bottom" title="Close/Exit" style="text-decoration: none;"><span class="glyphicon glyphicon-remove"></span> Close</a></center>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    if(isset($_POST['updateUserBtn'])){
        $this->updateUserProfile($_SESSION['useredit'],$_POST['username'],$_POST['fullname'],$_POST['email'],$_POST['mobileNo']);
    }
?>