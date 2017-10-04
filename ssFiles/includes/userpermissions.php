<?php
	if(!isset($_SESSION['useredit'])){
		$this->redirect("?users");
	}else{
		$login = $this->getFullDetailsId($_SESSION['useredit'],"login");
		$loginDetails = $this->getFullDetailsPid($login[1],"login_details");
	}
?>
<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-user"></span> <?php echo $loginDetails[2]; ?> &nbsp;|&nbsp; User Permissions &nbsp;&nbsp;<a href='?users' class='btn btn-xs btn-danger br tooltip-bottom' title='Close'><span class="glyphicon glyphicon-remove"></span></a></legend></div>
</div>

<div class='row' style="margin: 15px;" id="displayRes"></div>

<div class="row" style="margin: 15px;">
	<?php $this->loadAllPermissions(); ?>
</div>
<?php 
	if(isset($_POST['updateBtn'])){
		$this->updatePermissions();
	}
?>