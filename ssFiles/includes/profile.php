<?php 
	$details=$this->getFullDetailsPid($_SESSION['vsoftadmin'],"login_details");
	$details1=$this->getFullDetailsPid($_SESSION['vsoftadmin'],"login");
?>
<div class="row" id="displayRes"></div>
<div class="row" style="margin: 0px;">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header bgblue">
				<h3 class="panel-title"><center><span class="glyphicon glyphicon-pushpin"></span> User Profile</center></h3>
			</div>
			<div class="modal-body">
				<form method="post" action="#" class="form well" id="profileForm">
					<div class="form-group">
						<label for="username">User Name:</label>
						<input type="text" id="username" name="username" class="form-control" placeholder="User Name" value="<?php echo $details1[2]; ?>" required autofocus/>
					</div>
					<div class="form-group">
						<label for="fullname">Full Name:</label>
						<input type="text" id="fullname" name="fullname" class="form-control" placeholder="Full Name" value="<?php echo $details[2]; ?>" required/>
					</div>
					<div class="form-group">
						<label for="email">Email Address:</label>
						<input type="email" id="email" name="email" class="form-control" placeholder="Email" value="<?php echo $details[4]; ?>" required>
					</div>
					<div class="form-group">
						<label for="mobileNo">Mobile Number:</label>
						<input type="text" id="mobileNo" name="mobileNo" class="form-control" placeholder="Mobile Number" value="<?php echo $details[3]; ?>" required/>
					</div>
					<div class="form-group">
						<center><button type="submit" class="btn btn-xs btn-success" name="updateBtn"><span class="glyphicon glyphicon-edit"></span> Update Details</button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php 
	if(isset($_POST['updateBtn'])){
		$this->updateAdminProfile2($_SESSION['vsoftadmin'],$_POST['username'],$_POST['fullname'],$_POST['email'],$_POST['mobileNo']);
	}
?>
<script type="text/javascript">
	$('#profileForm').bootstrapValidator({
		message: 'This is not valid',
		feedbackIcons:{
			valid: 'glyphicon glyphicon-ok',
			invalid: 'glyphicon glyphicon-remove',
			validating: 'glyphicon glyphicon-refresh'
		},
		fields:{
			username:{
				validators:{
					notEmtpy:{
						message: 'User Name can\'t be empty'
					},
					stringLength:{
						min: 5,
						max: 50,
						message: 'Invalid character length'
					},
					regexp:{
						regexp: /^[a-zA-Z0-9\-\ \.]+$/,
						message: 'Invalid input character'
					}
				}
			},
			fullname:{
				validators:{
					notEmtpy:{
						message: 'Full Name can\'t be empty'
					},
					stringLength:{
						min: 8,
						max: 50,
						message: 'Invalid character length'
					},
					regexp:{
						regexp: /^[a-zA-Z0-9\-\ \.]+$/,
						message: 'Invalid input character'
					}
				}
			},
			email:{
				validators:{
					notEmtpy:{
						message: 'Email can\'t be empty'
					},
					stringLength:{
						min: 8,
						max: 100,
						message: "Invalid character length"
					},
					regexp:{
						regexp: /^[a-zA-Z0-9\-\.\@]+$/,
						message: 'Invalid input character'
					},
					email:{
						message: 'Invalid email'
					}
				}
			},
			mobileNo:{
				validators:{
					notEmpty:{
						message: 'Mobile Number can\'t be empty'
					},
					stringLength:{
						min: 10,
						max: 15,
						message: 'Invalid input length'
					},
					regexp:{
						regexp: /^[0-9\+]+$/,
						message: 'Invalid input character'
					}
				}
			},
		}
	});
</script>