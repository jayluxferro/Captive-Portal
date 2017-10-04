<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-user"></span> Users</legend></div>
</div>
<div class="row" style="margin: 5px;">
	<div class="col-md-3">
		<center><a href="#addUser" data-toggle="modal" class="btn btn-xs btn-primary br"><span class="glyphicon glyphicon-plus-sign"></span> Add User</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#delUsers" data-toggle="modal" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove-sign"></span> Delete All Users</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#deactivate" data-toggle="modal" class="btn btn-xs btn-warning br"><span class="glyphicon glyphicon-eject"></span> Activate/Deactivate All Users</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#resetPassword" data-toggle="modal" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-lock"></span> Reset Password</a></center>
	</div>
</div>
<div class="row" id="displayRes" style="margin: 15px;"></div>
<div class="row" style="margin: 15px;">
	<?php $this->loadUsers(); ?>
</div>

<div id="addUser" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Users</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?users" class="form" id="addUserForm">
					<div class="row" style="margin: 5px;">
						<div class="col-md-5 well">
							<div class="form-group">
								<label for="username">Username:</label>
								<input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
							</div>
							<div class="form-group">
								<label for="password">Password:</label>
								<input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
							</div>
							<div class="form-group">
								<label for="password1">Confirm Password:</label>
								<input type="password" id="password1" name="password1" class="form-control" placeholder="Confirm Password" required>
							</div>
						</div>
						<div class="col-md-2"></div>
						<div class="col-md-5 well">
							<div class="form-group">
								<label for="fullname">Full Name:</label>
								<input type="text" id="fullname" name="fullname" class="form-control" placeholder="Full Name" required>
							</div>
							<div class="form-group">
								<label for="email">Email:</label>
								<input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
							</div>
							<div class="form-group">
								<label for="mobileNo">Mobile Number:</label>
								<input type="text" id="mobileNo" name="mobileNo" class="form-control" placeholder="Mobile Number" required>
							</div>
						</div>
					</div>
					<div class="row">
						<center><button type="submit" name="addUserBtn" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-plus-sign"></span> Add User</button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php 
	if(isset($_POST['addUserBtn'])){
		$this->addAdminUser($_POST['username'],$_POST['password'],$_POST['fullname'],$_POST['email'],$_POST['mobileNo']);
	}elseif(isset($_POST['activateBtn'])){
		$this->activateAccount($_POST['activateBtn'],1,"login");
	}elseif(isset($_POST['deactivateBtn'])){
		$this->activateAccount($_POST['deactivateBtn'],0,"login");
	}elseif(isset($_POST['deleteBtn'])){
		$this->deleteUserAccount($_POST['deleteBtn'],"login");
	}elseif(isset($_POST['chngpwdBtn'])){
		$this->resetUserPassword($_POST['user'],$_POST['password'],"login");
	}
?>

<!--deleting all users -->
<div id="delUsers" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Delete All Accounts</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?users" class="form">
					<div class="row" style="margin: 15px;">
						<div class="col-md-6">
							<center>
								<form method="post" action="?users" class="form">
									<button type="submit" name="deleteBtn" value="all" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-ok"></span> Delete All Users</button>
								</form>
							</center>
						</div>
						<div class="col-md-6">
							<center><a href="#" data-dismiss="modal" style="text-decoration: none;" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Close</a></cener>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!--reset password -->
<div id="resetPassword" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-lock"></span> Change Account Password</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?users" class="form">
					<div class="form-group">
						<label for="user">User:</label>
						<select name="user" id="user" class="form-control" required>
							<?php 
								$this->genUsersOption();
							?>
						</select>
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input type="password" id="password" name="password" class="form-control" placeholder="Password" required/>
					</div>
					<div class="form-group">
						<label for="password1">Confirm Password:</label>
						<input type="password" id="password1" name="password1" class="form-control" placeholder="Confirm Password" required/>
					</div>
					<div class="form-group">
						<center><button type="submit" name="chngpwdBtn" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-pencil"></span> Update Password</button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!--deactivate all accounts -->
<div id="deactivate" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Deactivate All Accounts</h3></center>
			</div>
			<div class="modal-body">
					<div class="row" style="margin: 15px;">
						<div class="col-md-6">
							<center>
								<form method="post" action="?users" class="form">
									<button type="submit" name="deactivateBtn" value="all" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Deactivate</button>
								</form>
							</center>
						</div>
						<div class="col-md-6">
							<center>
								<form method="post" action="?users" class="form">
									<button type="submit" name="activateBtn" value="all" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-ok"></span> Activate</button>
								</form>
							</center>
						</div>
					</div>
			</div>
		</div>
	</div>
</div>