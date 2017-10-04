<div class="row" id="displayRes"></div>
<div class="row" style="margin: 0px;">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header bgblue">
				<h3 class="panel-title"><center><span class="glyphicon glyphicon-lock"></span> Change Password</center></h3>
			</div>
			<div class="modal-body">
				<form method="post" action="#" class="form well" id="pwdform">
					<div class="form-group">
						<label for="oldpassword">Old Password:</label>
						<input type="password" id="oldpassword" name="oldpassword" required class="form-control" placeholder="Old Password" autofocus/>
					</div>
					<div class="form-group">
						<label for="newpassword">New Password:</label>
						<input type="password" id="newpassword" name="newpassword" required class="form-control" placeholder="New Password"/>
					</div>
					<div class="form-group">
						<label for="newpassword1">Confirm New Password:</label>
						<input type="password" id="newpassword1" name="newpassword1" required class="form-control" placeholder="Confirm New Password">
					</div>
					<div class="form-group">
						<center><button type="submit" class="btn btn-xs btn-success" name="pwdBtn"><span class="glyphicon glyphicon-edit"></span> Update Password</button></center>
					</div>	
				</form>
			</div>
		</div>
	</div>
</div>
<?php 
	if(isset($_POST['pwdBtn'])){
		$this->updateAdminPassword($_SESSION['vsoftadmin'],$_POST['oldpassword'],$_POST['newpassword']);
	}
?>


<script type="text/javascript">
	$('#pwdform').bootstrapValidator({
	  message:  'This value is not valid',
	  feedbackIcons:{
	    valid: 'glyphicon glyphicon-ok',
	    invalid: 'glyphicon glypicon-remove',
	    validating: 'glyphicon glyphicon-refresh'
	  },
	  fields:{
	    oldpassword:{
	      validators:{
	        notEmpty:{
	          message: 'Old Password can\'t be empty'
	        },
	        stringLength:{
	          min: 2,
	          max: 100,
	          message: 'Invalid length'
	        },
	        regexp:{
	          regexp: /^[a-zA-Z0-9\-\_\.]+$/,
	          message: 'Invalid input character'
	        }
	      }
	    },
	    newpassword:{
	    	validators:{
	    		notEmpty:{
	    			message: 'New Password can\'t be empty'
	    		},
	    		stringLength:{
	    			min: 2,
	    			max: 100,
	    			message: 'Invalid length'
	    		},
	    		regexp:{
	    			regexp: /^[a-zA-Z0-9\_\-\.]+$/,
	    			message: 'Invalid input character'
	    		},
	    		identical:{
	    			field: 'newpassword1',
	    			message: 'Passwords don\'t match'
	    		}
	    	}
	    },
	    newpassword1:{
	    	validators:{
	    		notEmpty:{
	    			message: 'New Password can\'t be empty'
	    		},
	    		stringLength:{
	    			min: 2,
	    			max: 100,
	    			message: 'Invalid length'
	    		},
	    		regexp:{
	    			regexp: /^[a-zA-Z0-9\_\-\.]+$/,
	    			message: 'Invalid input character'
	    		},
	    		identical:{
	    			field: 'newpassword',
	    			message: 'Passwords don\'t match'
	    		}
	    	}
	    }
	  }
	});

</script>