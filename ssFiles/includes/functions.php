<?php
	require "dbconfig.php";

	Class VSoft{

		private $con;

		function __construct(){
			$this->con = new PDO("mysql:host=".HOST.";dbname=".DB_NAME."",DB_USERNAME,DB_PASSWORD);
			$this->con->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}

		function sanitize($data){
			return htmlentities(trim($data));
		}

		function sanitize2($data){
			return trim($data);
		}

		function activatessl(){
			echo "<script>
					if (window.location.protocol != \"https:\")
			    		window.location.href = \"https:\" + window.location.href.substring(window.location.protocol.length);
				</script>";
		}

		function redirect($location){
			echo "<script>
					window.location.assign('".$location."');
				</script>";
		}

		function sendSMS($phone,$message){
			$message=urlencode($message);
			$sender_id="SteppingStone";
			$url="https://apps.mnotify.net/smsapi?key=".SMS_API_KEY."&to=".$phone."&msg=".$message."&sender_id=".$sender_id;
			$result=file_get_contents($url); 

			switch($result){                                           
			    case "1000":
					return "Message sent";
					break;
			    case "1002":
					return "Message not sent";
					break;
			    case "1003":
					return "You don't have enough balance";
					break;
			    case "1004":
					return "Invalid API Key";
					break;
			    case "1005":
					return "Phone number not valid";
					break;
			    case "1006":
					return "Invalid Sender ID";
					break;
			    case "1008":
					return "Empty message";
					break;
				default:
					return "";
			}
		}

		function displayMsg($message,$status){
			$message=$this->sanitize($message);
			$status=$this->sanitize($status);

			if($status==1){
				echo "<script>
					$('#displayRes').html('<center><span class=\'alert alert-success\' role=\'alert\'>".$message."</span></center>').fadeOut(5000);
					</script>";
			}else{
				echo "<script>
					$('#displayRes').html('<center><span class=\'alert alert-danger\' role=\'alert\'>".$message."</span></center>').fadeOut(5000);
					</script>";
			}
		}

		function genPid(){
			return substr(md5(uniqid(mt_rand(), true)) , 0, 8);
		}

		function genReceiptNo(){
			return substr(md5(uniqid(mt_rand(), true)) , 0, 7);
		}

		function checkIfLoggedIn(){
			if(isset($_SESSION['vsoftadmin']) && isset($_SESSION['lock']) && $_SESSION['lock']==0){
				$login = $this->getFullDetailsPid($_SESSION['vsoftadmin'], "login");
				if(intval($login[4])==0){
					$this->redirect("index.php");
				}else{
					$this->redirect("https://sperixlabs.org");
				}
			}
		}

		function checkIfNotLoggedIn(){
			if(!isset($_SESSION['vsoftadmin']) || !isset($_SESSION['lock']) || $_SESSION['lock']==1){
				$this->redirect("login.php");
			}else{
				$login = $this->getFullDetailsPid($_SESSION['vsoftadmin'], "login");
				if(intval($login[4])==0){
					//$this->redirect("index.php");
				}else{
					$this->redirect("https://sperixlabs.org");
				}
			}
		}

		function getBillingItems(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$sql = "select * from billing_item where acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($acyear,$term));
			$data="<option value='0'>--Choose Billing Item--</option>";
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<option value='".$row['id']."'>".$row['name']."</option>";
			}
			echo $data;
		}

		function getFullDetails($username,$table){
			$username=$this->sanitize($username);
			$table=$this->sanitize($table);
			$query="select * from ".$table." where username=?";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$username);
			$result->execute(array($username));
			return $result->fetch();
		}

		function updateUserProfile($pid,$username,$fullname,$email,$mobileNo){
			if($this->updateAdminProfile($pid, $username, $fullname, $email, $mobileNo)){
				$this->displayMsg("Profile updated..", 1);
				unset($_SESSION['useredit']);
				$this->redirect("?users");
			}
		}

		function resetUserPassword($pid,$password,$table){
			if($this->resetPassword($pid,$password,$table)){
				$this->updateLastPassChng($pid);
				$this->displayMsg("Password updated..",1);
			}else{
				$this->displayMsg("Process failed...",0);
			}
			$this->redirect("?users");
		}

		function resetPassword($pid,$password,$table){
			$pid=$this->sanitize($pid);
			$password=sha1($this->sanitize($password));
			$table=$this->sanitize($table);
			$sql="update ".$table." set password=? where pid=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$password);
			$result->bindParam("s",$pid);
			if($result->execute(array($password,$pid))){
				return true;
			}else{
				return false;
			}
		}

		function updateAdminProfile($pid,$username,$fullname,$email,$mobileNo){
			$pid=$this->sanitize($pid);
			$username=$this->sanitize($username);
			$fullname=$this->sanitize($fullname);
			$email=$this->sanitize($email);
			$mobileNo=$this->sanitize($mobileNo);
			$query="update login set username=? where pid=?";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$username);
			$result->bindParam("s",$pid);
			if($result->execute(array($username,$pid))){
				$query1="update login_details set fullname=?,email=?,mobileNo=? where pid=?";
				$result1=$this->con->prepare($query1);
				$result1->bindParam("s",$fullname);
				$result1->bindParam("s",$email);
				$result1->bindParam("s",$mobileNo);
				$result1->bindParam("s",$pid);
				if($result1->execute(array($fullname,$email,$mobileNo,$pid))){
					return true;
				}else{
					return false;
				}
			}else{	
				return false;
			}
		}

		function getFullDetailsPid($pid,$table){
			$pid=$this->sanitize($pid);
			$table=$this->sanitize($table);
			if($table=="classes"){
				$query="select * from ".$table." where id=?";
			}else{
				$query="select * from ".$table." where pid=?";
			}
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->execute(array($pid));
			return $result->fetch();
		}

		function getFullDetailsIndexNumber($indexnumber,$table){
			$pid=$this->sanitize($indexnumber);
			$table=$this->sanitize($table);
			$sql="select * from ".$table." where student_id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->execute(array($pid));
			return $result->fetch();
		}

		function genDate(){
			return date('Y-m-d h:i:s');
		}

		function updateLastLogin($pid){
			$pid=$this->sanitize($pid);
			$curDate=$this->genDate();
			$ip=$this->sanitize($_SERVER['REMOTE_ADDR']);
			$query="insert into lastlogin(pid,date,ip) values(?,?,?)";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$curDate);
			$result->bindParam("s",$ip);
			$result->execute(array($pid,$curDate,$ip));
		}

		function updateLastPassChng($pid){
			$pid=$this->sanitize($pid);
			$curDate=$this->genDate();
			$query="insert into lastpasschng(pid,date) values(?,?)";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$curDate);
			$result->execute(array($pid,$curDate));
		}

		function getNumberOfUsers(){
			$sql = "select * from login where status=1";
			$result = $this->con->query($sql);
			return $result->rowCount();
		}

		function getCpu(){
			return sys_getloadavg();
		}

		function getDiskUsage(){
			$bytes = disk_free_space(".");
		    $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		    $base = 1024;
		    $class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		    echo sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class] . '/';
		    $bytes = disk_total_space(".");
		    $class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		    echo sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class] . '';
		}

		function getNumberOfLogins($pid){
			$sql = "select * from lastlogin where pid=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($pid));
			return $result->rowCount();
		}

		function getNumberOfClasses(){
			$sql = "select * from classes where status=1";
			$result = $this->con->query($sql);
			return $result->rowCount();
		}

		function getLastLogin($pid){
			$data=null;
			$pid=$this->sanitize($pid);
			$query="select date,ip from lastlogin where pid=? order by date desc limit 2";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->execute(array($pid));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data[0]=$row['date'];
				$data[1]=$row['ip'];
			}
			return $data;
		}

		function getLastPassChng($pid){
			$pid=$this->sanitize($pid);
			$query="select date from lastpasschng where pid=? order by date limit 1";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->execute(array($pid));
			return $result->fetch();
		}

		function verifyDataApi($pid,$table){
			$pid=$this->sanitize($pid);
			$table=$this->sanitize($table);
			if($table=='classes' || $table=='students' || $table == "login"){
				$sql="select * from ".$table." where id=?";
			}elseif($table=='studentspay'){
				$sql="select * from students where student_id=?";
			}elseif($table == "login1"){
				$sql = "select * from login where pid=?";
			}else{
				$sql="select * from ".$table." where pid=?";
			}
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->execute(array($pid));
			if($result->rowCount() >=1){
				return true;
			}else{
				return false;
			}
		}

		function lockSession(){
			$_SESSION['lock']=1;
			//echo "Data: ".$_SESSION['lock'];
		}

		function unlockSession($password){
			$password=$this->sanitize($password);
			$password=sha1($password);
			$pid=$_SESSION['vsoftadmin'];
			$sql="select * from login where pid=? and password=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$password);
			$result->execute(array($pid,$password));
			if($result->rowCount() >= 1){
				$_SESSION['lock']=0;
				echo 1;
			}else{
				echo 0;
			}
		}

		function verifyAdmin($username,$password){
			$username=$this->sanitize($username);
			$password=sha1($this->sanitize($password));
			$sql="select * from login where username=? and password=? limit 1";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$username);
			$result->bindParam("s",$password);
			$result->execute(array($username,$password));
			if($result->rowCount() >=1 ){
				//authentication successful
				$details=$this->getFullDetails($username, "login");
				//checking if user is admin or ordinary
				if(intval($details[4])==0){
					//redirect to main page
					$_SESSION['vsoftadmin']=$details[1];
					$_SESSION['lock']=0;
					$this->updateLastLogin($details[1]);
					$this->displayMsg("LogIn successful...", 1);
					$this->redirect("index.php");
				}else{
					//redirect to google
					$_SESSION['vsoftadmin']=$details[1];
					$_SESSION['lock']=0;
					$this->updateLastLogin($details[1]);
					//adding to firewall
					$arp = "/usr/sbin/arp";
					$users = "/var/lib/users";
					$mac = shell_exec("$arp -a ".$_SERVER['REMOTE_ADDR']);
					preg_match('/..:..:..:..:..:../',$mac , $matches);
					@$mac = $matches[0];
					// Add PC to the firewall
					exec("sudo iptables -I internet 1 -t mangle -m mac --mac-source $mac -j RETURN");
					// The following line removes connection tracking for the PC
					// This clears any previous (incorrect) route info for the redirection
					exec("rmtrack ".$_SERVER['REMOTE_ADDR']);
					sleep(1);
					$this->displayMsg("Log In successful", 1);
					$this->redirect("https://www.sperixlabs.org");
				}
			}else{
				$this->displayMsg("LogIn failed...", 0);
				$this->redirect("login.php");
			}
		}

		function genNameID($table){
			$table=$this->sanitize($table);
			if($table=="term" || $table=='acyear'){
				$sql="select * from ".$table." where status=1";
			}else{
				$sql="select * from ".$table."";
			}
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				echo "<option value='".$row['id']."'>".$row['name']."</option>";
			}
		}

		function genIndexNumberSelect(){
			$sql="select pid,student_id from students where status=1";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				echo "<option value='".$row['student_id']."'>".$row['student_id']."</option>";
			}
		}

		function genFullNameSelect(){
			$sql="select pid,first_name,surname,other_names from students where status=1";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				echo "<option value='".$row['first_name']." ".$row['other_names']." ".$row['surname']."'>".$row['first_name']." ".$row['other_names']." ".$row['surname']."</option>";
			}
		}

		function genNameID2($table){
			$data=null;
			$table=$this->sanitize($table);
			if($table=="term" || $table=='acyear'){
				$sql="select * from ".$table." where status=1";
			}else{
				$sql="select * from ".$table."";
			}
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<option value='".$row['id']."'>".$row['name']."</option>";
			}
			return $data;
		}

		function loadContentAdmin(){
			if(isset($_GET['home'])){
				include "dashboard.php";
			}elseif(isset($_GET['logout'])) {
				unset($_SESSION['vsoftadmin']);
				$this->redirect("login.php");
			}elseif(isset($_GET['passwd'])){
				include "password.php";
			}elseif(isset($_GET['profile'])){
				include "profile.php";
			}elseif(isset($_GET['users'])){
				if(isset($_GET['edit'])){
					include "usersedit.php";
				}elseif(isset($_GET['permissions'])){
					include "userpermissions.php";
				}else{
					include "users.php";
				}
			}elseif(isset($_GET['classes'])){
				include "classes.php";
			}elseif(isset($_GET['students'])){
				if(isset($_GET['edit'])){
					include "studentsedit.php";
				}else{
					include "students.php";	
				}
			}elseif(isset($_GET['promotion'])){
				if(isset($_GET['dismissal'])){
					include "dismissal.php";
				}elseif(isset($_GET['repetition'])){
					include "repetition.php";
				}else{
					include "promotion.php";
				}
			}elseif(isset($_GET['house'])){
				include "house.php";
			}elseif(isset($_GET['promotionclasses'])){
				include "promotionclasses.php";
			}elseif(isset($_GET['acyear'])){
				include "acyear.php";
			}elseif(isset($_GET['billing'])){
				include "billing.php";
			}elseif(isset($_GET['payments'])){
				include "paymentsDefault.php";
			}elseif(isset($_GET['pay'])){
				include "payments.php";
			}elseif(isset($_GET['classStatements'])){
				include "classStatements.php";
			}elseif(isset($_GET['arrears'])){
				include "arrears.php";
			}elseif(isset($_GET['general'])){
				include "general.php";
			}elseif(isset($_GET['bursary'])){
				include "bursary.php";
			}elseif(isset($_GET['bills'])){
				include "bills.php";
			}elseif(isset($_GET['scholarship'])){
				include "scholarship.php";
			}elseif(isset($_GET['logins'])){
				include "logins.php";
			}else{
				include "dashboard.php";
			}

		}

		function loadUserLogins(){
			$sql = "select * from lastlogin where pid=? order by date desc";
			$result = $this->con->prepare($sql);
			$result->execute(array($_SESSION['vsoftadmin']));

			$data="<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Date</center></th><th><center>Remote IP/Location</center></th></tr></thead><tbody>";
			$count=1;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['date']."</center></td><td><center>".$row['ip']."</center></td></tr>";
				$count++;
			}
			$data.="</tbody></table>";
			echo $data;
		}

		function getNumberOfStudents(){
			$sql = "select * from students where status=1";
			$res = $this->con->query($sql);
			return $res->rowCount();
		}

		function getSelectAllReceiptNo(){
			$sql = "select distinct receipt_no from paid_arrears";
			$result = $this->con->query($sql);
			$data = null;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$data.= "<option value='".$row['receipt_no']."'>".$row['receipt_no']."</option>";
			}

			$sql = "select distinct receipt_no from payments";
			$result = $this->con->query($sql);
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$data.= "<option value='".$row['receipt_no']."'>".$row['receipt_no']."</option>";
			}
			return $data;
		}

		function previewReceipt(){
			$receipt_no = $this->sanitize($_POST['receipt_no']);
			$view = $this->sanitize($_POST['view']);
			if($view == 0){
				$data="<center>
					<embed src='print.php?payfees=".$receipt_no."&view=".$view."' width='500' height='700' type='application/pdf'>
				</center>";	
			}else{
				$data="<center>
					<embed src='print.php?payfees=".$receipt_no."&view=".$view."' width='900' height='680' type='application/pdf'>
				</center>";
			}
			
			echo $data;
		}

		function loadPayFeeReceipt($category){
			$category = $this->sanitize($category);
			$data="<div class='row'>
			<center>
			<form class='form-inline'>
				<div class='form-group'>
					<label for='receipt'>Receipt No:</label>
					<input list='receipts' onselect='loadReceipt(this.value,".$category.")' onkeyup='loadReceipt(this.value,".$category.")' class='form-control' placeholder='Receipt No.' id='receipt' name='receipt'/>
					<datalist id='receipts'>";
						$data.=$this->getSelectAllReceiptNo();
					$data.="</datalist>
				</div>
			</form>
			</center></div>
			<hr style='margin: 0px; padding: 0px;'>";

			echo $data;
		}

		function activateAccountPid($pid,$status,$table){
			$pid=$this->sanitize($pid);
			$status=$this->sanitize($status);
			$table=$this->sanitize($table);
			if($pid=="all"){
				$sql="update ".$table." set status=? where uid !=0";
				$result=$this->con->prepare($sql);
				$result->bindParam("i",$status);
				if($result->execute(array($status))){
					return true;
				}else{
					return false;
				}
			}else{
				$sql="update ".$table." set status=? where pid=?";
				$result=$this->con->prepare($sql);
				$result->bindParam("i",$status);
				$result->bindParam("s",$pid);
				if($result->execute(array($status,$pid))){
					return true;
				}else{
					return false;
				}
			}
		}

		function verifyData($pid,$table){
			if($this->verifyDataApi($pid,$table)){
				$_SESSION['useredit']=$this->sanitize($pid);
				echo 1;
			}else{
				echo 0;
			}
		}

		function activateAccount($pid,$status,$table){
			if($this->activateAccountPid($pid,$status,$table)){
				$this->displayMsg("Account status updated..",1);
			}else{
				$this->displayMsg("Process failed..",0);
			}
			$this->redirect("?users");
		}

		function deleteUserAccount($pid,$table){
			if($this->delAccount($pid,$table)){
				$this->displayMsg("Account Deleted..",1);
			}else{
				$this->displayMsg("Process failed..",0);
			}
			$this->redirect("?users");
		}

		function delAccount($pid,$table){
			$pid=$this->sanitize($pid);
			$table=$this->sanitize($table);
			if($pid=="all"){
				$sql="update ".$table." set status=2 where uid !=0";
				if($this->con->query($sql)){
					return true;
				}else{
					return false;
				}
			}else{
				$sql="update ".$table." set status=2 where pid=?";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$pid);
				if($result->execute(array($pid))){
					return true;
				}else{
					return false;
				}
			}
		}

		function deleteReq($pid,$table){
			$pid=$this->sanitize($pid);
			$table=$this->sanitize($table);
			if($table=='classes' || $table == "house" || $table=='acyear' || $table=="term" || $table=='promotionclass' || $table =='billing_item' || $table=='billing'){
				$sql="delete from ".$table." where id=?";
				if($table=="billing"){
					//delete from student bill
					$this->delStudentBill($pid);
				}
			}elseif($table=='students'){
				$sql="update ".$table." set status=2 where id=?";
			}elseif($table=='dismissal'){
				$this->con->query("update students set status=1 where pid='".$pid."'");
				$sql="delete from ".$table." where pid=?";
			}elseif($table=="bursary"){
				$details = $this->getFullDetailsId($pid, "bursary");
				$this->con->query("delete from payments where receipt_no='".$details[5]."'");
				$sql="delete from ".$table." where id=?";
			}elseif($table=='payments'){
				$sql="delete from  ".$table." where receipt_no=?";
				//$this->con->query("delete from bursary where receipt_no='".$pid."'");
			}else{
				$sql="delete from ".$table." where pid=?";
			}
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			if($result->execute(array($pid))){
				return true;
			}else{
				return false;
			}
		}

		function updateStatusAdminPid($pid,$table){
			if($this->updateStatusPid($pid, $table)){
				echo 1;
			}else{
				echo 0;
			}
		}

		function deleteReqAdmin($status,$table){
			if($this->deleteReq($status, $table)){
				echo 1;
			}else{
				echo 0;
			}
		}

		function updateStatusPid($pid,$table){
			$pid=$this->sanitize($pid);
			$table=$this->sanitize($table);
			if($table=='classes'){
				$sql="update ".$table." set status=(status + 1)%2 where id=?";
			}elseif($table=='acyear' || $table=='term'){
				$this->con->query("update ".$table." set status=0");
				$sql="update ".$table." set status=(status + 1)%2 where id=?";
			}elseif($table == "promotionclass"){
				$this->con->query("update ".$table." set final=0");
				$sql="update ".$table." set final=1 where id=?";
			}else{
				$sql="update ".$table." set status=(status + 1)%2 where pid=?";
			}
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			if($result->execute(array($pid))){
				return true;
			}else{
				return false;
			}
		}

		function updatePassword($pid,$oldpassword,$newpassword,$table){
			$pid=$this->sanitize($pid);
			$oldpassword=sha1($this->sanitize($oldpassword));
			$newpassword=sha1($this->sanitize($newpassword));
			$table=$this->sanitize($table);
			if($table=="student_login"){
				$query="select * from ".$table." where student_pid=? and password=?";
			}else{
				$query="select * from ".$table." where pid=? and password=?";	
			}
			$result=$this->con->prepare($query);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$oldpassword);
			$result->execute(array($pid,$oldpassword));
			if($result->rowCount()>=1){
				//user found
				if($table=="student_login"){
					$query1="update ".$table." set password=? where student_pid=?";
				}else{
					$query1="update ".$table." set password=? where pid=?";
				}
				$result1=$this->con->prepare($query1);
				$result1->bindParam("s",$newpassword);
				$result1->bindParam("s",$pid);
				if($result1->execute(array($newpassword,$pid))){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

		function updateAdminProfile2($pid,$username,$fullname,$email,$mobileNo){
			if($this->updateAdminProfile($pid, $username, $fullname, $email, $mobileNo)){
				$this->displayMsg("Profile updated..", 1);
				$this->redirect("?profile");
			}
		}

		function updateAdminPassword($pid,$oldpassword,$newpassword){
			if($this->updatePassword($pid, $oldpassword, $newpassword, "login")){
				$this->updateLastPassChng($pid);
				$this->displayMsg("Password updated...", 1);
				$this->redirect("?logout");
			}else{
				$this->displayMsg("Process failed..", 0);
				$this->redirect("?passwd");
			}
		}

		function genUsersOption(){
			$sql="select pid from login where status=1 and uid !=0 or status=0 and uid !=0";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$details=$this->getFullDetailsPid($row['pid'],"login_details");
				echo "<option value='".$row['pid']."'>".$details[2]."</option>";
			}
		}

		function changeProfilePicAdmin(){
			$pid=$this->sanitize($_SESSION['vsoftadmin']);
			$base64 = $this->sanitize($_POST['picture']);
			
			$query="update dp set image=? where pid=?";
			$result=$this->con->prepare($query);
			$result->bindParam("s",$base64);
			$result->bindParam("s",$pid);
			if($result->execute(array($base64,$pid))){
				$this->displayMsg("Profile Picture updated", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
					
			}
				$this->redirect("?dashboard");
		}


		function loadUsers(){
			$sql="select * from login where uid !=0 and status != 2";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-condensed table-hover' id='tableList'>";
			$data.="<thead>
					<tr><th><center>No.</center></th><th><center>Full Name</center></th><th><center>Email</center></th><th><center>Mobile Number</center></th><th></th></tr>
				   </thead>";
			$data.="<tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$details=$this->getFullDetailsPid($row['pid'], "login_details");
				$data.="<tr><td><center>".$count."</center></td><td>".$details[2]."</td><td>".$details[4]."</td><td>".$details[3]."</td>";
				$data.="<td>
					<div class='row' style='margin: 0px; padding: 0px;'>
							<center>
								<!--<button type='button' onclick=\"view('".$row['id']."','login','?users&permissions')\" class='btn btn-xs btn-warning br'><span class='glyphicon glyphicon-plus'></span></button>-->
								<a href='#".$row['pid']."toggle' data-toggle='modal' ";
								if($row['status']==1){
									$data.="class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span>";
								}else{
									$data.="class='btn btn-xs btn-warning br tooltip-bottom' title='Click to activate'><span class='glyphicon glyphicon-eye-close'></span>";
								}
								$data.="</a>
								<button type='button' class='btn btn-xs btn-info br tooltip-bottom' title='View/Edit Details' onclick=\"view('".$row['pid']."','login1','?users&edit')\"><span class='glyphicon glyphicon-pencil'></span></button>
								<a href='#".$row['pid']."delete' data-toggle='modal' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete User Account'><span class='glyphicon glyphicon-remove-sign'></span></a>
							</center>
					</div>
				</td></tr>";
				$count++;
			}
			$data.="</tbody>";
			$data.="</table>";

			//generating toggle modals
			$sql="select * from login where uid !=0";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<div id='".$row['pid']."toggle' class='modal fade'>";
				$data.="<div class='modal-dialog modal-sm'>";
				$data.="<div class='modal-content'>";
				$data.="<div class='modal-header' style='background-color: #035888; color: #fff;'>";
				$data.="<center><h3 class='panel-title'><span class='glyphicon glyphicon-flash'></span> Account Status</h3></center>";
				$data.="</div>";
				$data.="<div class='modal-body'>";
				$data.="<div class='row' style='margin: 15px;'>";
				$data.="<div class='col-md-6'>";
				$data.="<form method='post' action='?users'>
					<center><button type='submit' class='btn btn-xs btn-success br' name='activateBtn' value='".$row['pid']."'><span class='glyphicon glyphicon-ok'></span> Activate</button></center>
					</form>";
				$data.="</div>";
				$data.="<div class='col-md-6'>";
				$data.="<form method='post' action='?users'>
					<center><button type='submit' class='btn btn-xs btn-danger br' name='deactivateBtn' value='".$row['pid']."'><span class='glyphicon glyphicon-remove'></span> Deactivate</button></center>
					</form>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";	
				$data.="</div>";				
			}

			//generating delete modals
			$sql="select * from login where uid !=0";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<div id='".$row['pid']."delete' class='modal fade'>";
				$data.="<div class='modal-dialog modal-sm'>";
				$data.="<div class='modal-content'>";
				$data.="<div class='modal-header' style='background-color: #035888; color: #fff;'>";
				$data.="<center><h3 class='panel-title'><span class='glyphicon glyphicon-flash'></span> Delete Account</h3></center>";
				$data.="</div>";
				$data.="<div class='modal-body'>";
				$data.="<div class='row' style='margin: 15px;'>";
				$data.="<div class='col-md-6'>";
				$data.="<form method='post' action='?users'>
					<center><button type='submit' class='btn btn-xs btn-success br' name='deleteBtn' value='".$row['pid']."'><span class='glyphicon glyphicon-ok'></span> Delete Account</button></center>
					</form>";
				$data.="</div>";
				$data.="<div class='col-md-6'>";
				$data.="<center><a href='#' style='text-decoration: none;' class='btn btn-xs btn-danger br' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span> Close</a></center>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";
				$data.="</div>";	
				$data.="</div>";				
			}

			echo $data;
		}

		function addAdminUser($username,$password,$fullname,$email,$mobileNo){
			if($this->addUser($username, $password, $fullname, $email, $mobileNo)){
				$this->displayMsg("User Added", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
			$this->redirect("?users");
		}

		function addUser($username,$password,$fullname,$email,$mobileNo){
			$username=$this->sanitize($username);
			$password=sha1($this->sanitize($password));
			$fullname=$this->sanitize($fullname);
			$email=$this->sanitize($email);
			$mobileNo=$this->sanitize($mobileNo);
			$pid=$this->genPid();
			$uid=1;
			$status=1;
			$logo="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAADICAYAAACtWK6eAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABNmlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjarY6xSsNQFEDPi6LiUCsEcXB4kygotupgxqQtRRCs1SHJ1qShSmkSXl7VfoSjWwcXd7/AyVFwUPwC/0Bx6uAQIYODCJ7p3MPlcsGo2HWnYZRhEGvVbjrS9Xw5+8QMUwDQCbPUbrUOAOIkjvjB5ysC4HnTrjsN/sZ8mCoNTIDtbpSFICpA/0KnGsQYMIN+qkHcAaY6addAPAClXu4vQCnI/Q0oKdfzQXwAZs/1fDDmADPIfQUwdXSpAWpJOlJnvVMtq5ZlSbubBJE8HmU6GmRyPw4TlSaqo6MukP8HwGK+2G46cq1qWXvr/DOu58vc3o8QgFh6LFpBOFTn3yqMnd/n4sZ4GQ5vYXpStN0ruNmAheuirVahvAX34y/Axk/96FpPYgAAACBjSFJNAAB6JQAAgIMAAPn/AACA6AAAUggAARVYAAA6lwAAF2/XWh+QAAAOmUlEQVR42uyd6XPaVheHzxVgmx2BDATwksRup520007//6/92Ok0dep4d22DjVkkIYT2+35onNexAQPGC7q/ZyYzmXbGsa706JxzlyPGOSfwvHDOyfM8cl2XbNumfr9Pqqpyy7LIcRxyHIds2ybf90mSJJIkiWKxGKVSKcrlcpTP5ymTybBoNEqMMQzoE8IgyPOKoWkatdttrmkaDQYD6vf75LruVD8nFotRqVSizc1Nls1mMbAQZHHxfZ9s26br62u6vLzkuq6T53kUBMGjf3YikaAPHz6wfD5PkUgEgw1BFocgCEjXdTo/P+dXV1dkWdaT/DuxWIw2NjZoY2ODraysYOAhyOsXw7IsOjw85K1Wi0zTfPJ/U5IkymazVK1WWbVaJUmSiDGG+gSCvK76wjRNOj4+5hcXF+R53ov8HrFYjPL5PBWLRZZKpSiZTNLS0hJkgSAvK0e9XqfT01Pe7XZfze+1srJCqVSK0uk0ra6uslwuR7FYDDcMgjyfGL1ej05OTvjFxcVcCu+nIhKJUDQaJUVRSFEUJssyxeNxkiQJNxKCPI0c7XabdnZ2uGEYC/f7p9NpqlartL6+zhBVIMhc8X2fLi4u6ODggA8Gg4W9DkmSSJZlevv2LVMU5WtxDyDIzDiOQ6enp7S/v8/DMnaMMarVavTu3TuWSqVwkyHIbARBQH///TfV63Xu+364HgTGSJZl+vXXX7GeAkFmixyfP3+mf//9N9QDls1m6ccff2T5fB43nYgwhTFhQb6/v08XFxehf5tomkY7Oztc0zTceAjyMJ7n0cHBAZ2enoYurRqFruv08eNHrus6BIEC4yNHvV6no6MjLloqqmmakNcNQabANE3a29vjL7Vt5KW5vLykZrNJIksCQUZgWRbt7Oxw27aFHQPf92lvb4/3+30IAr5NrU5PT3m73RZ+LHq9Xuhn7iDIDPn34eHhq95b9Zwvi7OzMxI1kkKQO7iui+L0Dp7n0dXVFQQBRNfX13R9fY2BuD8uQk5WQJBbBEFAx8fHws5ajWMwGJDjOBBE9NoDK8jDsW0bgoiM7/t0cnKC2mMElmU9WeMJCLIAGIZBqqpiIMYwbf8uCBIiVFV9lg4kiwyKdEHhnFOz2URuNUEaCkEEfTNi1XwiQTgEEZButyvk2xERBIJMBKZ2IQgEGXPTVVVF/QFBIMgwbNsmkbe0QxAIMhbXdYWc34cgEGQiPM8j7L2CIBAEgkAQCDI9juPgYBQEgSDD4JyjQJ8CEV8kwkcQy7IwxYsIAkHGCIInH4JAEAgynxRLtPMyEASCIIpAkNFFOjYpolCHILjhiCAQBOCFAkFww184JRVt3QgRBEzFIn+0FILM8EYE042XaZpCjRsEAVPR7XaF6jwJQcBUqKoqVIML1CBgKlzXpX/++UeYDpRCC4IZrNmwLEuY2SykWGCmcRPls2wQBGDsIAiY+4MjSRAENxmMIhqNQhAIAobBGKOVlRUIAkHAMJaXlykWi0EQEd6EYHqSyaQwYyf8KxRRZHry+bw4z4foEUSUYnNexGIxyufzDIIIlE+D6dKreDyOCCIKIt3seZDL5SCISIgyXTmvlFRRFCbS5IbwgqRSKUxlTUg0GqVCoSDUNQtfpGcyGVpaWsLTPwGFQkG4SQ3hI0gymaRkMomn/6EHRZKoXC4LF20xi7W8TJVKBWnWA8iyLFx6BUG+pFnVapVSqRQsGEO5XGYiToljGZn+W/z67rvvmCj7i6YllUpRrVYTcmsOBPmCoihUKpUwEHeIRqP0/v17JuqOAwhyK4psb28zzGjdS62EfnFAkFvE43F6+/Ytwy7f/1heXqatrS2hU08IcqdgVxQF20++jMXm5qbwU+AQ5A6ZTEao7dyjKBaLtLa2JnwohSB3B0SSqFarMdHH4N27dww7nSHIUGRZpvX1dWEPU5XLZZJlGQ8CBBn9Bt3a2mKKogh57eVyGRMVEGQ88XicfvjhB+GmfVOpFGUyGTwAEORhkskkbW1tMZFSrUwmg1k8CDIZjDEqFotCTXWm02k0soAg00WRYrHIRHkhiNSQAYLMCVG2eS8tLWFXMwSZHlmWhThJVyqV0AYJgkxPJBIJ/bpANBqlUqmE9AqCzJxmhfrhSafTlE6ncaMhyGzFayaTCXXD5tXVVYYWSBBkZuLxeGi7MDLGqFwuo5k3BHmcIIlEIpTXJssyZq8gyOML9Ww2G8pre/PmDfZeQZDHoyhK6J6ieDyOnbsQZD7kcrnQbeRLJBLoTwxB5lfMVqvVUEWRZDKJ1qsQZH6CrK6uhqqglWUZ9QcEme8bN5fLhUb4sM7MQZCXGjBJClWahXPnEOQp0pJQHCpijKH+gCBPE0XCMpuF3bsQ5EkIS+7u+z5uJgSZPysrK6GoQzzPw82EIPPP3cOwuMY5J9u2cUMhCBgliK7rGAgIgtx9FKqqcs45bigEme+b1zTNUFyLpmk0GAxwUyHI/AiCgK6vr0Px2u31etRqtXBTIch8Ikev16OdnR2uaVpoZD86OuIo1ofDkH9OhmVZdH5+To1GgxuGQUEQhOr61tfX6fvvv8cn6CDIdDiOQ61Wi3Z3d7llWRTW8WKMUaVSoe3tbRaPx9F+FII8nE7puk6Hh4e82WwKs+KcTqepVqvR5uYmgyQQZKQcl5eXtLe3xw3DEO76I5EI1Wo1ev/+PRO90zsEuYPv+9RsNumvv/7iom/DyOfz9OHDByZyQznE0DuRo16v06dPnzj2KBF1Oh3a3d3lIq+TIIJ8wfM8Oj8/p52dHQzIHYrFIv3yyy9Cfi8dEeRL5Dg5OaG9vT3IMYRms0knJydCbo0X/rSM67p0enpK+/v7PGxrG/Pk8PCQR6NRtrm5KVSLUqEFMU2T9vb2+OXlJUGO8fi+T/v7+zyRSLBisSiMJFFRb3a73aaDgwPe7Xbx9E8RbT99+sQlSWKKogghiVBFOuecOp0O1et1fn5+jqgxIysrK/Tbb7+xbDYbekmEEaTf79PZ2RnV63WO7d2PR5Zl+umnn0K/RhJaQTjn5DgOqapKjUaDNxoNRIw5k0wm6eeff2a5XC60e7dCJ0gQBGQYBl1fX1On0+HtdhudO56QRCJBGxsbbG1tLZRf4Fp4QYIgIM/zaDAYULvdpkajwQeDAbmui4jxTEiSROl0msrlMiuXy7S8vBwaWRZWENu2qdfrUafT4d1ulzqdDoR4BSwvL1OhUKBSqcRyudzC9w9bCEE451//aJpGzWaTt1otsiwLbWteKdFolBKJBK2urlKlUmHJZJIkSVq4Wa9XK4jrumRZFpmmSYZhUKfT4ZqmQYgFRVEUKpVKrFAoUCKRoEgkAkGmxXEc6nQ6pOs6aZrGbwTBztqQFLyMUSqVomw2S9Vqlcmy/OpFeRFBgiAgx3HIdV3q9/ukqiq1Wi1umib5vo9aQpDCPpFIUK1WY4VCgZLJJEWj0VeXgj2bIEEQfJXBMAzSdZ33+330ZAK0vLxMiqJQoVBgq6urr6qt65MIEgQB+b5Pvu+Trutf1yNs2ybHcbAuAYYSiUQokUiQoihUq9VYPB5/8agyN0GCICDTNKnX61G/36dOp8N1XUdRDWaWJZ/P05s3b1gul6NUKvUiojxaEM/zqNPp0MXFBTcMA0U1mHutkkwmSVEUqlQq7Lm/DzmzIKZpUr1e50dHR+S6Lu4keBZkWaZKpfJs08UzC/Lnn3/yer1OONMOXoJ0Ok25XI4qlcqTThfPJIiqqvT777+jbT54cWKxGMmyTNvb2yydTs9dlKlPFHqeR8fHx5ADvApc16Vms0mapvFKpUIbGxsskUjMraCfWhDDMAjHVMFrw7ZtOjk5oXa7zdfW1litVqNIJPJoUaZOsQ4PD2l3dxfhA7xqFEWh7e1tls/nH/Vzpj4GdnV1BTnAq6fVatHHjx95q9V61ETSVIL0+32kV2BhMAyD/vjjD358fDzz7o2pBLm8vET0AAtXxH/+/HnmJYmJBblZMQdg0QiCgPb393m73X46QUzTpH6/j9EGC8lgMPj6lbAnEWQwGNC0PxyA14SmaXR2djbVGt7EgnQ6HY5t6mDRaTQaU2VCEwsyS/4GwGvj5tDeXAWxbZt0XcfoglAU7Ofn53yugqiqil27IDTouj5x34OJBNE0DXaA0OD7PjmOMx9Bbs6VAxAmJp2RfVAQx3FwrhyECs75/AQZDAZozQNCJ4iqqhOthzwoyE2rHgDCRL/fn2gD44OC6LqOGSwQOkzTnKh0eFAQVVVhBwhlBOn1eo8T5OZzAwCEDd/3qdFoPFiHjBWk1+uh5xUILVdXVw9OQI0VBNEDCBBFZhPkyzfFUX+AUNNsNseeERkpiOM4ZJomRhCEGl3XxxbrIwWxLAsHpEDo8TxvbKeekYKYpglBgDDF+qjJKGlc6MGn0IAIOI4z8hDVSEFarRYKdCAEQRBQp9MZuiYijcrLsMUdiES/3x+aMQ0VRFVVpFdAKEbtzRoqSLvdRnoFhGIwGEwmCNIrIGqhPmzbiTTMJCwQAhHpdrv8QUEcx8EBKSAkw1bUpWHVPAQBImIYxr3DgdIQi1CgAyFxXfdeoX5PEGxxByJzt/7+RpAgCCY6hghAGOGc32ts/Y0gg8GAPM/DSAFhBTFNk48UZJqu1wCENcW6vYtEQoEOwP9xHIdc1+X3BPF9H/UHEB7btm/OhnAi4tLt/4EevAARxPmmDocgANwR5PZC+TeCYAUdiA7n/JtNi18FGXVgBADRGCqIruuYwQKAvl1N/yqIYRgYGQCGRZAgCCAIALciyM2uXunmP6D+AOA/bn/kUyIiLBACcIvb3zCUUKADcF+Qm0JdCoLg3hZfAETnawRxHIejBy8A30aQm5ksCU0aALiPbdvk+z4EAWAYruuS53kk3fwFAPB/vpwLIcmyLHwHHYBREQRdFAEYE0Ee+gwuACLieR45jkMS1kAAGM5gMEAEAWCsIL7vYyQAGIJlWaO/UQiA6JimCUEAGBdB/jcAFDMwQYvTqsYAAAAASUVORK5CYII=";
			$sql="insert into login(pid,username,password,uid,status) values(?,?,?,?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$username);
			$result->bindParam("s",$password);
			$result->bindParam("i",$uid);
			$result->bindParam("i",$status);
			if($result->execute(array($pid,$username,$password,$uid,$status))){
				//processing second query
				$sql1="insert into login_details(pid,fullname,email,mobileNo) values(?,?,?,?)";
				$result1=$this->con->prepare($sql1);
				$result1->bindParam("s",$pid);
				$result1->bindParam("s",$fullname);
				$result1->bindParam("s",$email);
				$result1->bindParam("s",$mobileNo);
				if($result1->execute(array($pid,$fullname,$email,$mobileNo))){
					//processing default dp
					$sql2="insert into dp(pid,image) values(?,?)";
					$result2=$this->con->prepare($sql2);
					$result2->bindParam("s",$pid);
					$result2->bindParam("s",$logo);
					if($result2->execute(array($pid,$logo))){
						return true;
					}else{
						return false;
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

		function addClass(){
			$class=$this->sanitize($_POST['class']);
			$pid="class".$this->genPid();
			$sql="insert into classes(pid,name) values(?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$class);
			if($result->execute(array($pid,$class))){
				$this->displayMsg("New Class Added", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?classes");
		}

		function addTerm(){
			$class=$this->sanitize($_POST['class']);
			$sql="insert into term(name) values(?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$class);
			if($result->execute(array($class))){
				$this->displayMsg("New Term Added", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?acyear&term");
		}

		function addHouse(){
			$class=$this->sanitize($_POST['house']);
			$sql="insert into house(name) values(?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$class);
			if($result->execute(array($class))){
				$this->displayMsg("New House/Section Added", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?house");
		}

		function loadHouses(){
			$sql="select * from house order by name";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-hover table-striped table-condensed' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>House/Section Name</center></th><th></th></tr></thead><tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<tr><td><center>".$count."</center></td><td>".$row['name']."</td><td>";
				$data.="<div class='row' style='margin: 0px; padding: 0px;'>";
					$data.="<div class='col-md-6' style='margin: 0px; padding: 0px;'>
								<center><a href='#classEdit".$row['id']."' style='text-decoration: none;' data-toggle='modal' class='btn btn-xs btn-info br'><span class='glyphicon glyphicon-pencil'></span></a></center>
							</div>";

					$data.="<div class='col-md-6' style='margin: 0px; padding: 0px;'>
							<center><button type='button' onclick=\"deleteReq('".$row['id']."','house','?house')\" class='btn btn-xs btn-danger br'><span class='glyphicon glyphicon-remove-circle'></span></button></center>
							</div>";
				$data.="</div></td></tr>";
				$count++;
			}
			$data.="</tbody></table>";

			## generating modal
			$sql1="select * from house order by name";
			$result1=$this->con->query($sql1);
			while($row1=$result1->fetch(PDO::FETCH_ASSOC)){
				$data.="<div id='classEdit".$row1['id']."' class='modal fade'>";
				$data.=" 	<div class='modal-dialog modal-sm'>";
				$data.="		<div class='modal-content'>";
				$data.="			<div class='modal-header' style='background-color: #035888; color: #fff;'>";
				$data.="				<center><h3 class='panel-title'><span class='glyphicon glyphicon-th-list'></span> Houses/Sections</h3></center>";
				$data.="			</div>";
				$data.="			<div class='modal-body'>";
				$data.="				<form method='post' action='#' class='form well'>
											<div class='form-group'>
												<label for='house'>Class:</label>
												<input type='text' id='house' name='house' class='form-control' placeholder='House/Section Name' value='".$row1['name']."' required/>
												<input type='hidden' name='id' value='".$row1['id']."'/>
											</div>
											<div class='form-group'>
												<center><button type='submit' name='updateHouseBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-plus-sign'></span> Update Detail</button></center>
											</div>
										</form>";
				$data.="			</div>";
				$data.="		</div>";
				$data.="	</div>";
				$data.="</div>";
			}
			echo $data;
		}

		function loadTerm(){
			$sql="select * from term order by status desc";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-hover table-striped table-condensed' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Term</center></th><th></th></tr></thead><tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<tr><td><center>".$count."</center></td><td><center>Term ".$row['name']."</center></td><td>";
				$data.="<div class='row' style='margin: 0px; padding: 0px;'>";
				if($row['status']==1){
					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
								<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','term','?acyear&term')\" class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-eye-open'></span></button></center>
							</div>";
				}else{
					$data.="<div class='col-md-4' style='margin: 0p;x padding: 0px;'>
							<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','term','?acyear&term')\" class='btn btn-xs btn-warning br'><span class='glyphicon glyphicon-eye-close'></span></button></center>
						</div>";
				}
					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
								<center><a href='#classEdit".$row['id']."' style='text-decoration: none;' data-toggle='modal' class='btn btn-xs btn-info br'><span class='glyphicon glyphicon-pencil'></span></a></center>
							</div>";

					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
							<center><button type='button' onclick=\"deleteReq('".$row['id']."','term','?acyear&term')\" class='btn btn-xs btn-danger br'><span class='glyphicon glyphicon-remove-circle'></span></button></center>
							</div>";
				$data.="</div></td></tr>";
				$count++;
			}
			$data.="</tbody></table>";

			## generating modal
			$sql1="select * from term order by status desc";
			$result1=$this->con->query($sql1);
			while($row1=$result1->fetch(PDO::FETCH_ASSOC)){
				$data.="<div id='classEdit".$row1['id']."' class='modal fade'>";
				$data.=" 	<div class='modal-dialog modal-sm'>";
				$data.="		<div class='modal-content'>";
				$data.="			<div class='modal-header' style='background-color: #035888; color: #fff;'>";
				$data.="				<center><h3 class='panel-title'><span class='glyphicon glyphicon-th-list'></span> Term</h3></center>";
				$data.="			</div>";
				$data.="			<div class='modal-body'>";
				$data.="				<form method='post' action='#' class='form well'>
											<div class='form-group'>
												<label for='class'>Class:</label>
												<input type='number' min='1' max='3' id='class' name='class' class='form-control' placeholder='Term' value='".$row1['name']."' required/>
												<input type='hidden' name='id' value='".$row1['id']."'/>
											</div>
											<div class='form-group'>
												<center><button type='submit' name='updateClassBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-plus-sign'></span> Update Term</button></center>
											</div>
										</form>";
				$data.="			</div>";
				$data.="		</div>";
				$data.="	</div>";
				$data.="</div>";
			}
			echo $data;
		}

		function loadClasses(){
			$sql="select * from classes order by name";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-hover table-striped table-condensed' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class Name</center></th><th></th></tr></thead><tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<tr><td><center>".$count."</center></td><td>".$row['name']."</td><td>";
				$data.="<div class='row' style='margin: 0px; padding: 0px;'>";
				if($row['status']==1){
					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
								<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','classes','?classes')\" class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-eye-open'></span></button></center>
							</div>";
				}else{
					$data.="<div class='col-md-4' style='margin: 0p;x padding: 0px;'>
							<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','classes','?classes')\" class='btn btn-xs btn-warning br'><span class='glyphicon glyphicon-eye-close'></span></button></center>
						</div>";
				}
					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
								<center><a href='#classEdit".$row['id']."' style='text-decoration: none;' data-toggle='modal' class='btn btn-xs btn-info br'><span class='glyphicon glyphicon-pencil'></span></a></center>
							</div>";

					$data.="<div class='col-md-4' style='margin: 0px; padding: 0px;'>
							<center><button type='button' onclick=\"deleteReq('".$row['id']."','classes','?classes')\" class='btn btn-xs btn-danger br'><span class='glyphicon glyphicon-remove-circle'></span></button></center>
							</div>";
				$data.="</div></td></tr>";
				$count++;
			}
			$data.="</tbody></table>";

			## generating modal
			$sql1="select * from classes order by name";
			$result1=$this->con->query($sql1);
			while($row1=$result1->fetch(PDO::FETCH_ASSOC)){
				$data.="<div id='classEdit".$row1['id']."' class='modal fade'>";
				$data.=" 	<div class='modal-dialog modal-sm'>";
				$data.="		<div class='modal-content'>";
				$data.="			<div class='modal-header' style='background-color: #035888; color: #fff;'>";
				$data.="				<center><h3 class='panel-title'><span class='glyphicon glyphicon-th-list'></span> Classes</h3></center>";
				$data.="			</div>";
				$data.="			<div class='modal-body'>";
				$data.="				<form method='post' action='#' class='form well'>
											<div class='form-group'>
												<label for='class'>Class:</label>
												<input type='text' id='class' name='class' class='form-control' placeholder='Class' value='".$row1['name']."' required/>
												<input type='hidden' name='id' value='".$row1['id']."'/>
											</div>
											<div class='form-group'>
												<center><button type='submit' name='updateClassBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-plus-sign'></span> Update Class</button></center>
											</div>
										</form>";
				$data.="			</div>";
				$data.="		</div>";
				$data.="	</div>";
				$data.="</div>";
			}
			echo $data;
		}

		function updateClass(){
			$id=$this->sanitize($_POST['id']);
			$class=$this->sanitize($_POST['class']);
			$sql="update classes set name=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$class);
			$result->bindParam("s",$id);
			if($result->execute(array($class,$id))){
				$this->displayMsg("Class updated..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?classes");
		}

		function updateTerm(){
			$id=$this->sanitize($_POST['id']);
			$class=$this->sanitize($_POST['class']);
			$sql="update term set name=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$class);
			$result->bindParam("s",$id);
			if($result->execute(array($class,$id))){
				$this->displayMsg("Term details updated..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?acyear&term");
		}


		function updateHouse(){
			$id=$this->sanitize($_POST['id']);
			$class=$this->sanitize($_POST['house']);
			$sql="update house set name=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$class);
			$result->bindParam("s",$id);
			if($result->execute(array($class,$id))){
				$this->displayMsg("House/Section Name updated..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?house");
		}

		function loadStudents(){
			$sql="select * from students where status=1 order by class";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-striped table-condensed table-hover' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Class</center></th><th></th><th></th></tr></thead><tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$classes=$this->getFullDetailsId($row['class'], "classes");
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['student_id']."</center></td><td>".$row['first_name']." ".$row['other_names']." " .$row['surname']."</td><td><center>".$classes[2]."</center></td>";
				//adding image
				$data.="<td>
					<div style='text-align: center;'>
						<img src='".$dp[2]."' style='width: 150px; height: auto;' class='img-thumbnail'/>
					</div>
					</td><td>";

				$data.="<div class='row' style='margin: 0px; padding: 0px;'>
							<div class='col-md-6' style='padding: 0px; margin: 0px;'>
								<center><button type='button' onclick=\"view('".$row['id']."','students','?students&edit')\" class='btn btn-xs btn-info br tooltip-bottom' title='Edit'><span class='glyphicon glyphicon-pencil'></span></button></center>
							</div>
							<div class='col-md-6' style='padding: 0px; margin: 0px;'>
								<center><button type='button' onclick=\"deleteReq('".$row['id']."','students','?students')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Delete'><span class='glyphicon glyphicon-remove-circle'></span></button></center>
							</div>
						</div>";

				$data."</td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			echo $data;
		}

		function genIndexNumber(){
			$main = "SSSC/".date("Y")."/";
			$sql="select id from students order by id desc limit 1";
			$result=$this->con->query($sql);
			$result = $result->fetch();
			$newId = $result[0]+1;
			//$newId = "23212";
			$lastId = strval($newId);
			//converting string to array 
			$studentId = str_split($lastId,1);
			$add=null;
			$count = sizeof($studentId);
			while($count <= 3){
				$add.="0";
				$count++;
			}
			return $main.$add.$newId;
		}

		function getFullDetailsId($id,$table){
			$id=$this->sanitize($id);
			$table=$this->sanitize($table);
			if($table == "billingPrice"){
				$sql="select * from billing where item=?";	
			}else{
				$sql="select * from ".$table." where id=?";
			}
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$id);
			$result->execute(array($id));
			return $result->fetch();
		}

		function addStudent(){
			$pid="std".$this->genPid();
			$student_id=$this->genIndexNumber();
			$admission_fee = $this->sanitize($_POST['admission_fee']);
			$class=$this->sanitize($_POST['class']);
			$first_name=$this->sanitize($_POST['firstname']);
			$surname=$this->sanitize($_POST['surname']);
			$other_names=$this->sanitize($_POST['othernames']);
			$date_of_birth=$this->sanitize($_POST['dateofbirth']);
			$house=$this->sanitize($_POST['house']);
			$gender=$this->sanitize($_POST['gender']);
			$nationality=$this->sanitize($_POST['nationality']);
			$religion=$this->sanitize($_POST['religion']);
			$guardian_fullname=$this->sanitize($_POST['guardian']);
			$guardian_occupation=$this->sanitize($_POST['occupation']);
			$mobileNo=$this->sanitize($_POST['mobile']);
			$email=$this->sanitize($_POST['email']);
			$residence=$this->sanitize($_POST['residence']);
			$workplace=$this->sanitize($_POST['workplace']);
			$relationship=$this->sanitize($_POST['relationship']);
			$previous_school=$this->sanitize($_POST['previousSchool']);
			$previous_class=$this->sanitize($_POST['previousClass']);
			$admission_term=$this->sanitize($_POST['term']);
			$admission_date=$this->genDate();
			$date=$this->genDate();
			$acyear=$this->sanitize($_POST['acyear']);
			$new_file_name = $this->sanitize($_POST['picture']);
			$query="insert into dp(pid,image) values(?,?)";
			$res=$this->con->prepare($query);
			$res->bindParam("s",$pid);
			$res->bindParam("s",$new_file_name);
			$res->execute(array($pid,$new_file_name));
					
			$sql="insert into students(pid,student_id,class,first_name,surname,other_names,date_of_birth,house,gender,nationality,religion,guardian_fullname,guardian_occupation,mobileNo,email,residence,workplace,relationship,previous_school,previous_class,admission_term,admission_date,date,acyear,admission_fee,admission_class) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$student_id);
			$result->bindParam("s",$class);
			$result->bindParam("s",$first_name);
			$result->bindParam("s",$surname);
			$result->bindParam("s",$other_names);
			$result->bindParam("s",$date_of_birth);
			$result->bindParam("s",$house);
			$result->bindParam("s",$gender);
			$result->bindParam("s",$nationality);
			$result->bindParam("s",$religion);
			$result->bindParam("s",$guardian_fullname);
			$result->bindParam("s",$guardian_occupation);
			$result->bindParam("s",$mobileNo);
			$result->bindParam("s",$email);
			$result->bindParam("s",$residence);
			$result->bindParam("s",$workplace);
			$result->bindParam("s",$relationship);
			$result->bindParam("s",$previous_school);
			$result->bindParam("s",$previous_class);
			$result->bindParam("s",$admission_term);
			$result->bindParam("s",$admission_date);
			$result->bindParam("s",$date);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$admission_fee);
			$result->bindParam("s",$admission_class);
			if($result->execute(array($pid,$student_id,$class,$first_name,$surname,$other_names,$date_of_birth,$house,$gender,$nationality,$religion,$guardian_fullname,$guardian_occupation,$mobileNo,$email,$residence,$workplace,$relationship,$previous_school,$previous_class,$admission_term,$admission_date,$date,$acyear,$admission_fee,$admission_class))){
				$this->displayMsg("Student Added...", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?students");
		}

		function getIndexNumberSelect(){
			$sql="select * from students where status=1";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				echo "<option value='".$row['student_id']."'>".$row['student_id']."</option>";
			}
		}

		function getFullNameSelect(){
			$sql="select * from students where status=1";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$fullname = $row['first_name']. " ".$row['other_names']." ".$row['surname'];
				echo "<option value='".$fullname."'>".$fullname."</option>";
			}
		}

		function verifyIndexNumber(){
			$student_id = $this->sanitize($_POST['verifyIndexNumber']);
			$sql = "select * from students where student_id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$student_id);
			$result->execute(array($student_id));
			if($result->rowCount() == 1){
				$row = $result->fetch(PDO::FETCH_ASSOC);
				$fullname = $row['first_name']." ".$row['other_names']." ".$row['surname'];
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				echo "<script>
						document.getElementById('fullname').value = '".$fullname."';
						$('#imgResult1').attr('src','".$dp[2]."');
						document.getElementById('fix').value = '".$row['pid']."';
					</script>";
			}else{
				echo "<script>
						var preview = \"<div style='text-align: center;'><img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHsAAAB7CAMAAABjGQ9NAAAAbFBMVEX///+kpKT7+/unp6ehoaH4+Pj19fUAAACurq7d3d26urrFxcW3t7fg4OCqqqrw8PDp6enR0dHX19d/f388PDxERESIiIg3NzdYWFiOjo51dXWUlJQwMDBLS0soKChlZWUdHR1tbW0XFxcMDAyY57vjAAAGiUlEQVRoge1biZKDKBAFxQtE0XglanQm//+PS6OJmpBbh6rdfVWTjEB4QDfdLQdC/+NtWJb115ROzFNGaTiAUpby2PkD3jhgYUQIwQDbttW3fI5CFsQb8loBA1qCQ5YEXMQuIBY8SFgI/CRiwUbElGCg5a423+XQAExosLYOxCzC0K3HYnVgYHDE1hx8QW1iU/6KPjlclRWrMWPsJ6/3JU58jFdhj6WYo/Q9GVqplBD9euSZnEmfKG8gZx/7ipnLGpIPf5vIVvOPmR3ZaaafUa/AZQSzD+2d8HH0ecMBPML+RzqX2ph+a6Udiu30/Z+xzyU9R/K+ylkhttexD8LG4Vsz1PWx/7mSfVVX7GO6nkeQY+i/bGeAejVmAH2Z3F2bWpG/NOzW+tSK/BUhbkEN5OHzQuy1Fr4LOZpP53mK7bUm1xKujZ9YOGkJ1go5bqp+Yq0cfxVDqkeC/Uf+gW2iZ2fQRyLnONry/cKJ8H2fbD/IWwOc2Pey2JcR1nPcZYgx3mZ6TXAx1ht2uqGOn5HolVncF8aKsIluklO8zUvkEqmu4wJHf0CNUKQxnPSZuV0Jmo7H9ibu6xaWb1+rOiPbK/mAhFzNcSu6ac1WiO1oOcLBpk5kiesJRcm2lnwOTpb9JJs6sCWciMwfg829yByMBHeftkaw0PQI/+XCqDM3oTHRBM+8qqBBrIXP9MfzjvXFxRYFZHSqwV2ueuH+VEOeWzee1+yl7iZ5m+d5eSvOkEzOOrie7oo7a0An2NGCjyKIWdWef7LbQVoGVjjNGqW2SZOpSkTWMSGS3UEm9Qxwa7/nIma6GcbzupdcrLFQnCnNjKvdnPvXhsf9vlXch339o767oYgA7ttah6pnfZ2PwVSgTLt64A7zwejR3p1xexySTzwHblGmgSdzRT+5pLvc7iRjGT/qGlcGqRcr7uJn7MsxmXOjikqpowy4/cpBrQ+SmAxFcoTFZV2wMMXDWlWDfqOqUNyHw1iwWXLTDh38gbuVUsGtHMIGOlOVuVTU5LgDaLhDcvYeXKdqijs98Uf9jtvk17GAO+2DOOZeMPY7COrcuj/mMwVLtf4TuNFhp+SdneXtLLhRkR2Q4t43WVlmZS3lPdQVKu57hjohZ61g2khNcfOsLkHPa0iJ2wItudMqGbj7SEj4uXvW8zB71O/JhlPt6whvoG1Fk8H8bnapYFV1md+HgRtglRTRk8pxe4pEXlHOWQfy7ilAE4vxi8um2vdeXsFoiE5Zr/TQexm5DOF+L8ctG7k7hor98H8hx8Ops9MpB2VKKkBLbusWF+7wzsvCdohn3Fu/C13DPa++WOFGyxwPuO1xnfO/ym1S3kb1XD+/kUsD2KIa8+DBGe0EbHumbFkQkqUhUZGCxUZjlSjjcuupxIxbG5z7J+mXrNGOck/Shl6uHkrpc4t2WVAWzaquqyDPaceAbJd1Ei26xmTX9PYcVfVRtskvlTWrgbWre1Uyl1Xvq6uCyMplBy3bc6QP9Ufu4qZWhcme6/1YmolDDX4TirnQE16K3f6WeywouYFRyHjjKffkx/T+WzptdoTfg/NmvVTHuhvd85L7XFD1O97l6Dn35L+1cYvbyAad5F8iO6IiFxec5S+95r4UtNre8zyI4S7cxVGmeM1N5VPcoo3XaCt7WEOwlNtIQP1MemekgtEF96UgjLkTExlPTv3eWY7ETeWz9UtdnNq1+2J/OMopQ1pkQ/0/Qwq/4r4UHOSNWvvpmM/iVF18Lo77uq5JZoP2BJX0waJRKTlZck8FR+6qfso9VzDNewkZJiWRoS/qqky2zVYvRyiSQ6C4OzjeYk0FLeC2LCZFD9wqU3JbmkMw8/cSjbKd6qFVJzn96AlisNMQm/BfhhqwLb2MDstS/E4Fy6Ysjx4BXWsgr0W7I3z/Xo/qQsYRvtIHJx0TAjF+WOcULhCcZBGBgjsrCM8DDR8yz4WuK18s5Zl8/za57mByvcXoOpPJ9bW/XVe8SjG4nmp0Hdnk+rnRfQOT+yVG94lM7o8Z3Rc0uR8Kwth4HxjfVymT+98m9/2Nnncwec7D6PkWo+d6YAVkC30LXznPZPQcl8nza0bP7Sny985WPsRb5xWNntM0ej4VmTyXi4yeRzZ6DludPydfnT/HH58/RybP3QPgvsEnEku/vm+AjN6zQCbvlwzspu7VAMzdJwLAPSry9B4V2eAe1YD798f8Te+PjXAf3Jv7i50uU/cFFzBwT/LfgH8A29lgxWwRBG0AAAAASUVORK5CYII=' style='width: 350px; height: auto;' id='imgResult1' class='img-thumbnail'></div>\";
							$('#preview').html(preview);
							document.getElementById('fullname').value = '';
							document.getElementById('fix').value = '';
						</script>";
			}
		}

		function verifyFullName(){
			$fullname = $this->sanitize($_POST['verifyFullName']);
			
		}

		function addBursary(){
			$pid = $this->sanitize($_POST['fix']);
			$amount = $this->sanitize($_POST['amount']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$receipt_no = $this->genReceiptNo();
			$sql = "insert into bursary(pid,amount,acyear,term,receipt_no) values(?,?,?,?,?)";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$amount);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$receipt_no);
			if($result->execute(array($pid,$amount,$acyear,$term,$receipt_no))){
				$this->payScholarship($receipt_no);
				$this->displayMsg("Bursary Added", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?bursary");
		}

		function updateStudentDetails(){
			$id=$_SESSION['useredit'];
			$pid=$this->sanitize($_POST['pid']);
			$admission_fee = $this->sanitize($_POST['admission_fee']);
			$dp=$this->sanitize($_POST['dp']);
			$class=$this->sanitize($_POST['class']);
			$first_name=$this->sanitize($_POST['firstname']);
			$surname=$this->sanitize($_POST['surname']);
			$other_names=$this->sanitize($_POST['othernames']);
			$date_of_birth=$this->sanitize($_POST['dateofbirth']);
			$house=$this->sanitize($_POST['house']);
			$gender=$this->sanitize($_POST['gender']);
			$nationality=$this->sanitize($_POST['nationality']);
			$religion=$this->sanitize($_POST['religion']);
			$guardian_fullname=$this->sanitize($_POST['guardian']);
			$guardian_occupation=$this->sanitize($_POST['occupation']);
			$mobileNo=$this->sanitize($_POST['mobile']);
			$email=$this->sanitize($_POST['email']);
			$residence=$this->sanitize($_POST['residence']);
			$workplace=$this->sanitize($_POST['workplace']);
			$relationship=$this->sanitize($_POST['relationship']);
			$previous_school=$this->sanitize($_POST['previousSchool']);
			$previous_class=$this->sanitize($_POST['previousClass']);
			$admission_term=$this->sanitize($_POST['term']);
			$date=$this->genDate();
			$acyear=$this->sanitize($_POST['acyear']);
			$new_file_name = $this->sanitize($_POST['picture']);
			
			$query="update dp set image=? where pid=?";
			$res=$this->con->prepare($query);
			$res->bindParam("s",$new_file_name);
			$res->bindParam("s",$pid);
			$res->execute(array($new_file_name,$pid));

					
			$sql="update students set admission_class=?,first_name=?,surname=?,other_names=?,date_of_birth=?,house=?,gender=?,nationality=?,religion=?,guardian_fullname=?,guardian_occupation=?,mobileNo=?,email=?,residence=?,workplace=?,relationship=?,previous_school=?,previous_class=?,admission_term=?,date=?,acyear=?,admission_fee=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$student_id);
			$result->bindParam("s",$class);
			$result->bindParam("s",$first_name);
			$result->bindParam("s",$surname);
			$result->bindParam("s",$other_names);
			$result->bindParam("s",$date_of_birth);
			$result->bindParam("s",$house);
			$result->bindParam("s",$gender);
			$result->bindParam("s",$nationality);
			$result->bindParam("s",$religion);
			$result->bindParam("s",$guardian_fullname);
			$result->bindParam("s",$guardian_occupation);
			$result->bindParam("s",$mobileNo);
			$result->bindParam("s",$email);
			$result->bindParam("s",$residence);
			$result->bindParam("s",$workplace);
			$result->bindParam("s",$relationship);
			$result->bindParam("s",$previous_school);
			$result->bindParam("s",$previous_class);
			$result->bindParam("s",$admission_term);
			$result->bindParam("s",$admission_date);
			$result->bindParam("s",$date);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$admission_fee);
			$result->bindParam("s",$id);
			if($result->execute(array($class,$first_name,$surname,$other_names,$date_of_birth,$house,$gender,$nationality,$religion,$guardian_fullname,$guardian_occupation,$mobileNo,$email,$residence,$workplace,$relationship,$previous_school,$previous_class,$admission_term,$date,$acyear,$admission_fee,$id))){
				$this->displayMsg("Student Details updated...", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?students&edit");
		}

		function addAcyear(){
			$from = $this->sanitize($_POST['from']);
			$end = $this->sanitize($_POST['end']);
			$name = $from."/".$end;
			$sql="insert into acyear(name) values(?)";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$name);
			if($result->execute(array($name))){
				$this->displayMsg("Details Added...", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?acyear");
		}

		function loadAcyear(){
			$data = "<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.= "<thead>
					<tr><th><center>No.</center></th><th><center>Academic Year</center></th><th></th></tr>
				</thead>";
			$data.="<tbody>";

			$sql = "select * from acyear order by status desc";
			$result=$this->con->query($sql);
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$dat=explode("/", $row['name']);
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['name']."</center></td><td>";
				$data.="<div class='row' style='padding: 0px;'>";

				if($row['status']==0){
					$data.="<div class='col-md-4' style='padding: 0px;'>
								<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','acyear','?acyear')\" class='btn btn-xs btn-warning br tooltip-bottom' title='Activate'><span class='glyphicon glyphicon-eye-close'></span></button></center>
							</div>";
				}else{
					$data.="<div class='col-md-4' style='padding: 0px;  margin: 0px;'>
								<center><button type='button' onclick=\"updateStatusPid('".$row['id']."','acyear','?acyear')\" class='btn btn-xs btn-success br tooltip-bottom' title='Deactivate'><span class='glyphicon glyphicon-eye-close'></span></button></center>
							</div>";
				}

				$data.="<div class='col-md-4' style='padding: 0px;  margin: 0px;'>
								<center><a href='#edit".$row['id']."' data-toggle='modal' class='btn btn-xs btn-info br tooltip-bottom' title='Edit'><span class='glyphicon glyphicon-pencil'></span></a></center>
						</div>";

				$data.="<div class='col-md-4' style='padding: 0px; margin: 0px;'>
								<center><button type='button' onclick=\"deleteReq('".$row['id']."','acyear','?acyear')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Delete'><span class='glyphicon glyphicon-remove-circle'></span></button></center>
						</div>";

				$data.="</div>";
				$data.="</td></tr>";

				$data.="<div id='edit".$row['id']."' class='modal fade'>
						<div class='modal-dialog modal-sm'>
							<div class='modal-content'>
								<div class='modal-header bgblue'>
									<center><h3 class='panel-title'><span class='glyphicon glyphicon-th-list'></span> Academic Year</h3></center>
								</div>
								<div class='modal-body'>
									<form method='post' action='#' class='form'>
										<div class='form-group'>
											<label for='from'><span class='glyphicon glyphicon-dashboard'></span> Begin:</label>
											<input type='number' min='";
				$data.= (date('2017')-1)."'";
				$data.=" max='";
				$data.= date('Y')."' value='";
				$data.= $dat[0]."' id='from' name='from' class='form-control' placeholder='From' required>
										<input type='hidden' name='id' value='".$row['id']."'/>
										</div>
										<div class='form-group'>
											<label for='end'><span class='glyphicon glyphicon-dashboard'></span> End:</label>
											<input type='number' min='";
											$data.=date('2017')."' max='";
											$data.=date('Y')."' value='"; 
											$data.=$dat[1]."' id='end' name='end' class='form-control' placeholder='End' required>
										</div>
										<div class='form-group'>
											<center><button type='submit' name='updateYearBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-pencil'></span> Update</button></center>
										</div>
									</form>
								</div>
							</div>
						</div>
						</div>";
				$count++;
			}

			$data.="</tbody>";
			$data.="</table>";
			echo $data;
		}


		function updateAcyear(){
			$id=$this->sanitize($_POST['id']);
			$from=$this->sanitize($_POST['from']);
			$end=$this->sanitize($_POST['end']);
			$name=$from."/".$end;
			$sql="update acyear set name=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$name);
			$result->bindParam("s",$id);
			if($result->execute(array($name,$id))){
				$this->displayMsg("Details updated..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?acyear");
		}

		function addPC(){
			$from = $this->sanitize($_POST['from']);
			$to = $this->sanitize($_POST['to']);
			$final = $this->sanitize($_POST['upper']);
			
			//checking if classes are the same
			if($from == $to){
				$this->displayMsg("Promotion class invalid", 0);
				$this->redirect("?promotionclasses");
				return;
			}

			//checking if promotion class already exists
			$query="select * from promotionclass where current=? and next=?";
			$res=$this->con->prepare($query);
			$res->bindParam("s",$from);
			$res->bindParam("s",$to);
			$res->execute(array($from,$to));
			if($res->rowCount() >= 1){
				$this->displayMsg("Promotion class exits", 0);
				$this->redirect("?promotionclasses");
				return;
			}

			if($final == 1){
				$this->con->query("update promotionclass set final=0");
			}

			$sql="insert into promotionclass(current,next,final) values(?,?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$from);
			$result->bindParam("s",$to);
			$result->bindParam("s",$final);
			if($result->execute(array($from,$to,$final))){
				$this->displayMsg("New Promotion Class Added..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?promotionclasses");
		}

		function loadPC(){
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>From</center></th><th><center>To</center></th><th></th></tr></thead>";
			$data.="<tbody>";

			$sql="select * from promotionclass order by current";
			$result=$this->con->query($sql);
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$details=$this->getFullDetailsPid($row['current'], "classes");
				$details1=$this->getFullDetailsPid($row['next'], "classes");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$details[2]."</center></td><td><center>".$details1[2]."</center></td><td><center>";
				if($row['final']==1){
					$data.="<button type='button' class='btn btn-xs btn-success br tooltip-bottom' title='Toggle upper limit' onclick=\"\"><span class='glyphicon glyphicon-eye-open'></span></button>";
				}else{
					$data.="<button type='button' class='btn btn-xs btn-warning br tooltip-bottom' title='Toggle upper limit' onclick=\"updateStatusPid('".$row['id']."','promotionclass','?promotionclasses')\"><span class='glyphicon glyphicon-eye-close'></span></button>";
				}
				$data.="&nbsp;<button type='button' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete' onclick=\"deleteReq('".$row['id']."','promotionclass','?promotionclasses')\"><span class='glyphicon glyphicon-remove-sign'></span></button></center></td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			echo $data;
		}

		function updatePC(){

		}

		function promoteStudents(){
			//getting current students
			$sql="select * from students where status=1";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				//looping through each student
				$id = $row['id'];
				$std_class=$row['class']; //getting student class

				//getting promotion class
				$sql1="select next from promotionclass where current=? limit 1";
				$res1=$this->con->prepare($sql1);
				$res1->bindParam("s",$std_class);
				$res1->execute(array($std_class));
				$promo_class=$res1->fetch();
				// checking if promotion class exists
				if($res1->rowCount() >=1){
					// promotion class is available
					$sql2="update students set class=? where id=?";
					$res2=$this->con->prepare($sql2);
					$res2->bindParam("s",$promo_class[0]);
					$res2->bindParam("s",$id);
					$res2->execute(array($promo_class[0],$id));
				}else{
					//checking whether status is final
					if($promo_class[3]==1){
						//deactive student account
						$sql2="update students set status=3 where id=?";
						$res2=$this->con->prepare($sql2);
						$res2->bindParam("s",$id);
						$res2->execute(array($id)); // student account deactivated
					}
				}

			}
			$this->displayMsg("Process Complete", 1);
			$this->redirect("?promotion");
		}

		function loadRepetition(){
			$sql="select * from students where status=1 order by class";
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Current Class</center></th><th></th><th></th></tr></thead>";
			$data.="<tbody>";

			$result=$this->con->query($sql);
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$class=$this->getFullDetailsId($row['class'], "classes");
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['student_id']."</center></td><td>".$row['first_name']." ".$row['other_names']." ".$row['surname']."</td><td><center>".$class[2]."</center></td>";
				$data.="<td>
					<div style='text-align: center;'>
						<img src='".$dp[2]."' style='width: 150px; height: auto;' class='img-thumbnail'/>
					</div>
					</td><td>";

				$data.="
								<center><a href='#view".$row['id']."' data-toggle='modal' class='btn btn-xs btn-info br tooltip-bottom' title='View'><span class='glyphicon glyphicon-eye-open'></span></a>&nbsp;<a href='#edit".$row['id']."' data-toggle='modal' class='btn btn-xs btn-danger br tooltip-bottom' title='Repeat'><span class='glyphicon glyphicon-refresh'></span></a></center>
							";

				$data.="</td></tr>";

				//adding view modal
				$dp=$this->getFullDetailsPid($row['pid'], "dp");
				$data.="<div id='view".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<center><h3 class='panel-title'><span class='glyphicon glyphicon-picture'></span> Picture</h3></center>
									</div>
									<div class='modal-body'>
										<center><img src='uploads/images/".$dp[2]."' class='img-thumbnail' style='width: 200px; height: auto'/></center>
									</div>
								</div>
							</div>
						</div>";


				//adding edit modal
				$data.="<div id='edit".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<center><h3 class='panel-title'><span class='glyphicon glyphicon-repeat'></span> Repeat</h3></center>
									</div>
									<div class='modal-body'>
										<form method='post' action='#' class='form'>
											<div class='form-group'>
												<label for='to'>Repeat to:</label>
												<select id='to' name='to' class='form-control' placeholder='Repeat to' required>";
												$my="select current from promotionclass where next=? limit 1";
												$myres=$this->con->prepare($my);
												$myres->bindParam("s",$row['class']);
												$myres->execute(array($row['class']));
												$repeatClassId=$myres->fetch();
												$repeatClassName=$this->getFullDetailsId($repeatClassId[0], "classes");
												$data.="<option value='".$repeatClassId[0]."' selected>".$repeatClassName[2]."</option>";
												$data.=$this->genNameID2("classes");
												$data.="</select>
												<input type='hidden' name='id' value='".$row['pid']."'/>
												<input type='hidden' name='from' value='".$row['class']."'/>
											</div>
											<div class='form-group'>
												<center><button type='submit' name='repeatBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-repeat'></span></button></center>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>";

				$count++;
			}

			$data.="</tbody></table>";
			echo $data;
		}

		function getAcyear(){
			$sql="select * from acyear where status=1 limit 1";
			$result=$this->con->query($sql);
			return $result->fetch();
		}

		function getTerm(){
			$sql="select * from term where status=1 limit 1";
			$result=$this->con->query($sql);
			return $result->fetch();
		}

		function repeatStudent(){
			$pid=$this->sanitize($_POST['id']);
			$current=$this->sanitize($_POST['from']);
			$next=$this->sanitize($_POST['to']);
			$acyear=$this->getAcyear();
			$term=$this->getTerm();
			//repeating student
			$sql="update students set class=? where pid=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$next);
			$result->bindParam("s",$pid);
			if($result->execute(array($next,$pid))){
				//add to log
				$sql1="insert into repetition(pid,current,next,acyear,term) values(?,?,?,?,?)";
				$result1=$this->con->prepare($sql1);
				$result1->bindParam("s",$pid);
				$result1->bindParam("s",$current);
				$result1->bindParam("s",$next);
				$result1->bindParam("s",$acyear[0]);
				$result1->bindParam("s",$term[0]);
				if($result1->execute(array($pid,$current,$next,$acyear[0],$term[0]))){
					$this->displayMsg("Student Repated..", 1);
				}else{
					$this->displayMsg("Failed to log process", 0);
				}
			}else{
					$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?promotion&repetition");

		}

		function genSelectOption($table){
			$table = $this->sanitize($table);
			$sql="select * from ".$table." order by status desc";
			$result=$this->con->query($sql);
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				echo "<option value='".$row['id']."'>".$row['name']."</option>";
			}
		}

		function dismissedList(){
			$acyear = $this->sanitize($_POST['acyear']);
			$classes = $this->sanitize($_POST['class']);
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Gender</th><th><center>Date</center></th></th><th><center>Last Class</center></th><th><center>Reason</center></th></tr></thead>";
			$data.="<tbody>";

			$sql = "select * from dismissal where acyear=? order by date desc";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->execute(array($acyear));
			$count=1;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
				$fullname = $studentDetails[4]. " ".$studentDetails[6]." ".$studentDetails[5];
				$gender = $this->getFullDetailsId($studentDetails[9], "gender");
				$class = $this->getFullDetailsId($studentDetails[3],"classes");
				if($classes!="0"){
					if($class[0] != intval($classes)){
						continue;
					}
				}
				$data.="<tr><td><center>".$count."</center></td><td><center>".$studentDetails[2]."</center></td><td>".$fullname."</td><td><center>".$gender[1]."</center></td><td><center>".$row['date']."</center></td><td><center>".$class[2]."</center></td><td>".$row['reason']."</td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			$data.="<script>$('#tableList').DataTable({responsive: true});</script>";
			echo $data;
		}


		function dalllist(){
			$sql="select * from students where status=1 order by class";
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Current Class</center></th><th></th><th></th></tr></thead>";
			$data.="<tbody>";

			$result=$this->con->query($sql);
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$class=$this->getFullDetailsId($row['class'], "classes");
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['student_id']."</center></td><td>".$row['first_name']." ".$row['other_names']." ".$row['surname']."</td><td><center>".$class[2]."</center></td>";
				$data.="<td>
					<div style='text-align: center;'>
						<img src='".$dp[2]."' style='width: 150px; height: auto;' class='img-thumbnail'/>
					</div>
					</td><td>";

				$data.="
								<center><a href='#view".$row['id']."' data-toggle='modal' class='btn btn-xs btn-info br tooltip-bottom' title='View'><span class='glyphicon glyphicon-eye-open'></span></a>&nbsp;<a href='#edit".$row['id']."' data-toggle='modal' class='btn btn-xs btn-danger br tooltip-bottom' title='Dismiss'><span class='glyphicon glyphicon-remove-circle'></span></a></center>
							";

				$data.="</td></tr>";

				//adding view modal
				$dp=$this->getFullDetailsPid($row['pid'], "dp");
				$data.="<div id='view".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<center><h3 class='panel-title'><span class='glyphicon glyphicon-picture'></span> Picture</h3></center>
									</div>
									<div class='modal-body'>
										<center><img src='uploads/images/".$dp[2]."' class='img-thumbnail' style='width: 200px; height: auto'/></center>
									</div>
								</div>
							</div>
						</div>";


				//adding edit modal
				$data.="<div id='edit".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<center><h3 class='panel-title'><span class='glyphicon glyphicon-remove-circle'></span> Dismissal</h3></center>
									</div>
									<div class='modal-body'>
										<form method='post' action='#' class='form'>
											<div class='form-group'>
												<label for='reason'><span class='glyphicon glyphicon-comment'></span> Reason for dismissal:</label>
												<textarea id='reason' name='reason' class='form-control' placeholder='Reason for dismissal' required></textarea>
												<input type='hidden' name='pid' value='".$row['pid']."'/>
											</div>
											<div class='form-group'>
												<center><button type='submit' name='dismissBtn' class='btn btn-xs btn-danger br'><span class='glyphicon glyphicon-remove-circle'></span> Dismiss Student</button></center>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>";

				$count++;
			}

			$data.="</tbody></table>";
			echo $data;
		}

		function dalldlist(){
			$sql="select * from students where status=0 order by class";
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Last Class</center></th><th><center>Reason For Dismissal</center></th><th></th><th></th></tr></thead>";
			$data.="<tbody>";

			$result=$this->con->query($sql);
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$class=$this->getFullDetailsId($row['class'], "classes");
				$reason=$this->getFullDetailsPid($row['pid'], "dismissal");
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['student_id']."</center></td><td>".$row['first_name']." ".$row['other_names']." ".$row['surname']."</td><td><center>".$class[2]."</center></td>";
				$data.="<td>
					<div style='text-align: center;'>
						<img src='".$dp[2]."' style='width: 150px; height: auto;' class='img-thumbnail'/>
					</div>
					</td><td>";

				$data.="
								<center><a href='#view".$row['id']."' data-toggle='modal' class='btn btn-xs btn-info br tooltip-bottom' title='View'><span class='glyphicon glyphicon-eye-open'></span></a>&nbsp;<button  class='btn btn-xs btn-danger br tooltip-bottom' onclick=\"deleteReq('".$row['pid']."','dismissal','?promotion&dismissal&dlist')\" title='Revert'><span class='glyphicon glyphicon-refresh'></span></button></center>
							";

				$data.="</td></tr>";

				//adding view modal
				$dp=$this->getFullDetailsPid($row['pid'], "dp");
				$data.="<div id='view".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-md'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<center><h3 class='panel-title'><span class='glyphicon glyphicon-picture'></span> Picture</h3></center>
									</div>
									<div class='modal-body'>
										<center><img src='uploads/images/".$dp[2]."' class='img-rounded' style='width: 200px; height: auto'/></center>
										<hr/>
										<div class='form-group well'>
												<label for='reason'><span class='glyphicon glyphicon-comment'></span> Reason for dismissal:</label>
												<textarea id='reason' name='reason' class='form-control' placeholder='Reason for dismissal' style='background-color: #fff;' readonly>".$reason[2]."</textarea>
										</div>
									</div>
								</div>
							</div>
						</div>";

				$count++;
			}

			$data.="</tbody></table>";
			echo $data;
		}

		function dismissStudent(){
			$pid=$this->sanitize($_POST['pid']);
			$reason=$this->sanitize($_POST['reason']);
			$acyear=$this->getAcyear();
			$term=$this->getTerm();
			//dismissing student
			$sql="update students set status=0 where pid=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$pid);
			if($result->execute(array($pid))){
				//log data
				$sql1="insert into dismissal(pid,reason,acyear,term) values(?,?,?,?)";
				$result1=$this->con->prepare($sql1);
				$result1->bindParam("s",$pid);
				$result1->bindParam("s",$reason);
				$result1->bindParam("s",$acyear[0]);
				$result1->bindParam("s",$term[0]);
				if($result1->execute(array($pid,$reason,$acyear[0],$term[0]))){
					$this->displayMsg("Student dismissed", 1);
				}else{
					$this->displayMsg("Failed to log data.", 0);
				}
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?promotion&dismissal");
		}

		function updateBursary(){
			$id = $this->sanitize($_POST['id']);
			$amount = $this->sanitize($_POST['amount']);
			$sql = "update bursary set amount=? where id=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$amount);
			$result->bindParam("s",$id);
			if($result->execute(array($amount,$id))){
				echo 1;
			}else{
				echo 0;
			}
		}

		function bursary(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data = "<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";

			$data .="<thead><tr><th><center>No.</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Class</center></th><th><center>Amount(GH&cent;)</center></th><th></th></tr></thead><tbody>";

			$sql = "select * from bursary where acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($acyear,$term));

			$count =1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$details = $this->getFullDetailsPid($row['pid'],"students");
				$fullname = $details[4]. " " . $details[6] . " " .$details[5];
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$class = $this->getFullDetailsPid($details[3],"classes");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$details[2]."</center></td><td>".$fullname."</td><td><center>".$class[2]."</center></td><td><center>".$this->formatNumber($row['amount'])."</center></td><td><center>";
					/*<a href='#edit".$row['id']."' data-toggle='modal' class='btn btn-xs btn-info br tooltip-bottom' title='Edit'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;*/
					$data.="<button type='button' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete' onclick=\"deleteReq('".$row['id']."','bursary','?bursary')\"><span class='glyphicon glyphicon-remove'></span></button></center></td></tr>";
				//generating modal for edit
				$data.="<div id='edit".$row['id']."' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Bursary</h3>
									</div>
									<div class='modal-body'>
										<div class='row' id='displayRes".$row['id']."'></div>
										<div class='row' style='margin: 5px;'>
											<div class='form-group'>
												<div style='text-align: center;'>
													<img src='".$dp[2]."' style='height: 120px; width: auto' class='img-thumbnail'/>
													<br>
													<h4 style='color: #333;'>".$fullname."</h4>
												</div>
											</div>
											<div class='form-group'>
												<label for='amount".$row['id']."'>Amount (GH&cent;):</label>
												<input type='text' id='amount".$row['id']."' name='amount' class='form-control' value='".$row['amount']."' placeholder='Amount (GH&cent;)' required>
												<input type='hidden' name='id' value='".$row['id']."'/>
											</div>
											<div class='form-group'>
												<div style='text-align: center;'>
													<button type='button' onclick=\"updateBursary".$row['id']."('".$row['id']."')\" name='editBursaryBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-pencil'></span> Update Bursary</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>";
					$data.="<script>
								function updateBursary".$row['id']."(id){
									var amount = $('#amount".$row['id']."').val();
									$.post('ajax.php',{'editBursaryBtn':'y','id': id, 'amount':amount},function(data){
										if(data==1){
											displayMessage('Bursary details updated', 1);
											displayMessage2('Bursary details updated','displayRes".$row['id']."', 1);
										}else{
											displayMessage('Process failed', 0);
											displayMessage2('Bursary details updated','displayRes".$row['id']."', 0);
											//console.log(data);
										}
											redirect('?bursary');
									});
								}
							</script>";
				$count++;
			}


			$data .= "</tbody></table>";
			$data .= "<script>$('#tableList').DataTable({responsive: true});</script>";
			echo $data;
		}

		function loadBillingItems(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Item Name</center></th><th><center>Academic Year</center></th><th><center>Term</center></th><th></th></tr></thead>";
			$data.="<tbody>";

			$count=1;
			$sql="select * from billing_item where acyear=? and term=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($acyear,$term));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$acyear = $this->getFullDetailsId($row['acyear'],"acyear");
				$term = $this->getFullDetailsId($row['term'],"term");

				$data.="<tr><td><center>".$count."</center></td><td>".$row['name']."</td><td><center>".$acyear[1]."</center></td><td><center> Term ".$term[1]."</center></td><td>";
				$data.="<center><a href='#edit".$row['id']."' class='btn btn-xs btn-info br tooltip-bottom' title='Edit Item' data-toggle='modal'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;<button type='button' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete' onclick=\"deleteReq('".$row['id']."','billing_item','?billing')\"><span class='glyphicon glyphicon-remove-sign'></span></button></center>";
				$data.="</td></tr>";
				//adding modal
				$data.="
				<div id='edit".$row['id']."' class='modal fade'>
					<div class='modal-dialog modal-sm'>
						<div class='modal-content'>
							<div class='modal-header bgblue'>
								<center><h3 class='panel-title'><span class='glyphicon glyphicon-th-list'></span> Billing Item</h3></center>
							</div>
							<div class='modal-body'>
								<form method='post' action='#' class='form'>
									<div class='form-group'>
										<label for='item'>Item Name:</label>
										<input type='text' id='item' name='item' placeholder='Item' class='form-control' value='".$row['name']."' required='required'>
										<input type='hidden' name='id' value='".$row['id']."'/>
									</div>
									<div class='form-group'>
										<label for='acyear'>Academic Year:</label>
										<select id='acyear' name='acyear' class='form-control' required>
											<option value='".$row['acyear']."'>".$acyear[1]."</option>";
											$data.=$this->genNameID2("acyear");
										$data.="</select>
									</div>
									<div class='form-group'>
										<label for='term'>Term:</label>
										<select id='term' name='term' class='form-control' required>
											<option value='".$row['term']."'>".$term[1]."</option>";
											$data.=$this->genNameID2("term");
										$data.="</select>
									</div>
									<div class='form-group'>
										<center><button type='submit' name='updateListBtn' class='btn btn-xs btn-success br'><span class='glyphicon glyphicon-pencil'></span> Update Detail</button></center>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>";
				$count++;
			}

			$data.="</tbody>";
			$data.="</table>";
			$data.="<script>$('#tableList').DataTable({responsive: true});</script>";
			echo $data;
		}

		function addBillingItem(){
			$name=$this->sanitize($_POST['item']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);

			$sql="insert into billing_item(name,acyear,term) values(?,?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$name);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			if($result->execute(array($name,$acyear,$term))){
				$this->displayMsg("New Billing Item Added..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?billing");
		}

		function updateBillingItem(){
			$name=$this->sanitize($_POST['item']);
			$id=$this->sanitize($_POST['id']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$sql="update billing_item set name=?,acyear=?,term=? where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$name);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$id);
			if($result->execute(array($name,$acyear,$term,$id))){
				$this->displayMsg("Billing Item updated..", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?billing");
		}

		function addFee(){
			$acyear=$this->sanitize($_POST['acyear']);
			$term=$this->sanitize($_POST['term']);
			$item=$this->sanitize($_POST['item']);
			$amount=$this->sanitize($_POST['amount']);
			$category=$this->sanitize($_POST['category']);
			$class=$this->sanitize($_POST['class']);
			$sql="insert into billing(acyear,term,item,amount,category,class) values(?,?,?,?,?,?)";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$item);
			$result->bindParam("s",$amount);
			$result->bindParam("s",$category);
			$result->bindParam("s",$class);
			if($result->execute(array($acyear,$term,$item,$amount,$category,$class))){
				$billingId = $this->con->lastInsertId();
				$this->billStudent($acyear, $term, $item, $class, $billingId);
				$this->displayMsg("New Fee Added...", 1);
			}else{
				$this->displayMsg("Process failed..", 0);
			}
				$this->redirect("?billing&manage");
		}

		function billStudent($acyear,$term,$item,$class,$billing){
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$item = $this->sanitize($item);
			$billing = $this->sanitize($billing);
			$class = intval($this->sanitize($class));
			if($class==0){
				//all students
				$sql = "select * from students where status=1";
				$result = $this->con->query($sql);
			}else{
				//class-based
				$sql = "select * from students where status=1 and class=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$class);
				$result->execute(array($class));
			}
			//looping through students
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$student_pid = $row['pid'];
				$query = "insert into student_bill(student_pid,acyear,term,item,billing) values(?,?,?,?,?)";
				$res = $this->con->prepare($query);
				$res->execute(array($student_pid,$acyear,$term,$item,$billing));
			}
		}

		function loadBilling(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Bill Item</center></th><th><center>Amount(GH&cent;)</center></th><th><center>Category</center></th><th></th></tr></thead>";
			$data.="<tbody>";

			$sql="select * from billing where acyear=? and term=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($acyear,$term));
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$item=$this->getFullDetailsId($row['item'], "billing_item");
				$category=$this->getFullDetailsId($row['category'], "billing_category");
				$class=$this->getFullDetailsId($row['class'], "classes");
				if($category[1]=="Class Fees"){
					$category[1]=$category[1]."(".$class[2].")";
				}
				$data.="<tr><td><center>".$count."</center></td><td>".$item[1]."</td><td><center>".$row['amount']."</center></td><td><center>".$category[1]."</center></td><td><center><button type='button' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete' onclick=\"deleteReq('".$row['id']."','billing','?billing&manage')\"><span class='glyphicon glyphicon-remove-circle'></span></button></center></td></tr>";
				$count++;
			}
			$data.="</tbody>";
			$data.="</table>";
			$data.="<script>$('#tableList').DataTable({responsive: true});</script>";
			echo $data;
		}


		function processPaymentsFees($student_id,$fullname){
			$student_id=$this->sanitize($student_id);
			$fullname=$this->sanitize($fullname);
			//restricting search to only student_id
			$sql="select * from students where student_id like '%?%'";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$student_id);
			$result->execute(array($student_id));
			$data = null;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$data.="<div class='modal-dialog modal-lg'>
							<div class='modal-content'>
								<div class='modal-header bgblue'>
									<h3 style='align: center;' class='panel-title'><span class='glyphicon glyphicon-folder-open'></span> Pay Fees/Bills</h3>
								</div>
								<div class='modal-body'>

								</div>
							</div>
						</div>";
				$data.="<hr/>";
			}
			echo $data;
		}

		function genPaymentStudentList(){
			$sql="select * from students order by student_id";
			$result=$this->con->query($sql);
			$data="<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Class</center></th><th></th><th></th></tr><thead><tbody>";
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$fullname = $row['first_name']. " ". $row['other_names']." ".$row['surname'];
				$class = $this->getFullDetailsId($row['class'], "classes");
				$dp = $this->getFullDetailsPid($row['pid'],"dp");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$row['student_id']."</center></td><td>".$fullname."</td><td><center>".$class[2]."</center></td><td><center><img src='".$dp[2]."' class='img-thumbnail' style='width: 160px; height: auto'/></center></td><td style='padding-top: 50px;'><center><button type='button' class='btn btn-xs btn-success br'  onclick=\"view('".$row['id']."','students','?pay')\"><span class='glyphicon glyphicon-forward'></span> Proceed</button></center></td></tr>";
				$count++;
			}
			$data.="</tbody></table>";
			echo $data;
		}

		function computeAllArrears($id){
			$arrears = array();
			$counter = 0;
			$id = $this->sanitize($id);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$student_id = $studentDetails[1];
			$regAcyear = $studentDetails[25];
			$regTerm = $studentDetails[21]; //gettting student reg acyear and term
			$currentAcyear = $this->getAcyear();
			$currentAcyear = $currentAcyear[0];
			$currentTerm = $this->getTerm();
			$currentTerm = $currentTerm[0]; //getting current acyear and term

			//getting range of acyear
			$sql="select * from acyear where id>=? and id<=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$regAcyear);
			$result->bindParam("s",$currentAcyear);
			$result->execute(array($regAcyear,$currentAcyear));
			//return $result->rowCount();


			//looping through all the various academic years
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				//checking if the acyear is current
				if($row['id']==$currentAcyear){
					//getting all past terms
					$cTerm = $this->getTerm();
					$query="select * from term where name < ?";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$cTerm[1]);
					$res->execute(array($cTerm[1]));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $currentAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $currentAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$arrears[$counter][0]=$currentAcyear;
							$arrears[$counter][1]=$pTerms[0];
							$amount = $this->formatNumber(floatval($payable)-floatval($paid));
							$arrears[$counter][2]=$amount;
							$counter++;
						}
					}
				}else{
					//previous acyear
					$prevAcyear = $row['id'];
					
					//looping through all the terms in the academic year
					$query="select * from term";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$prevAcyear);
					$res->execute(array($prevAcyear));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $prevAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $prevAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$arrears[$counter][0]=$prevAcyear;
							$arrears[$counter][1]=$pTerms[0];
							$amount = $this->formatNumber(floatval($payable)-floatval($paid));
							$arrears[$counter][2]=$amount;
							$counter++;
						}
					}
				}
			}//end of while loop
			return $arrears;
		}

		function computeArrearsPreviousAcyear($id,$acyear,$allYears){
			$id = $this->sanitize($id);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$student_pid = $studentDetails[1];
			$acyear = $this->sanitize($acyear);
			$allYears = $this->sanitize($allYears);

			//getting range of acyear
			if(intval($allYears)==0){
				$sql="select * from acyear where id=?";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->execute(array($acyear));
			}else{
				$sql="select * from acyear where id < ?";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->execute(array($acyear));
			}

			//looping through all the various academic years
			$payable = 0;
			$paid = 0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$query="select * from term";
				$res=$this->con->query($query);
				while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
					$payable += $this->getPayableFees($id, $acyear, $term);
					$paid += $this->getPaidFees($id, $acyear, $term);
					$paid += $this->get_paid_arrears($student_pid,$acyear, $term);
				}
			}
			return floatval($payable) - floatval($paid);
		}

		function computeArrearsAcyearTerm($id,$acyear,$term){
			$id = $this->sanitize($id);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$student_pid = $studentDetails[1];
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);

			//getting range of acyear
			$sql="select * from acyear where id=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->execute(array($acyear));

			//looping through all the various academic years
			$payable = 0;
			$paid = 0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				if($term == 0){
					$query="select * from term";
					$res=$this->con->query($query);
				}else{
					$query="select * from term where id=?";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$term);
					$res->execute(array($term));
				}
				while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
					$payable += $this->getPayableFees($id, $acyear, $term);
					$paid += $this->getPaidFees($id, $acyear, $term);
					$paid += $this->get_paid_arrears($student_pid,$acyear, $term);
				}
			}
			return floatval($payable) - floatval($paid);
		}

		function computeArrears($id){
			$id = $this->sanitize($id);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$student_pid = $studentDetails[1];
			$regAcyear = $studentDetails[25];
			$regTerm = $studentDetails[21]; //gettting student reg acyear and term
			$currentAcyear = $this->getAcyear();
			$currentAcyear = $currentAcyear[0];
			$currentTerm = $this->getTerm();
			$currentTerm = $currentTerm[0]; //getting current acyear and term

			//getting range of acyear
			$sql="select * from acyear where id>=? and id<=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$regAcyear);
			$result->bindParam("s",$currentAcyear);
			$result->execute(array($regAcyear,$currentAcyear));
			//return $result->rowCount();


			//looping through all the various academic years
			$payable = 0;
			$paid = 0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				//checking if the acyear is current
				if($row['id']==$currentAcyear){
					//getting all past terms
					$cTerm = $this->getTerm();
					$query="select * from term where name < ?";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$cTerm[1]);
					$res->execute(array($cTerm[1]));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable += $this->getPayableFees($id, $currentAcyear, $pTerms[0]);
						$paid += $this->getPaidFees($id, $currentAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($student_pid,$currentAcyear, $pTerms[0]);
					}
				}else{
					//previous acyear
					$prevAcyear = $row['id'];
					
					//looping through all the terms in the academic year
					$query="select * from term";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$prevAcyear);
					$res->execute(array($prevAcyear));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable += $this->getPayableFees($id, $prevAcyear, $pTerms[0]);
						$paid += $this->getPaidFees($id, $prevAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($student_pid,$prevAcyear, $pTerms[0]);
					}
				}
			}//end of while loop
			return floatval($payable) - floatval($paid);
		}

		function getPayableFees($id,$acyear,$term){
			$amount=0;
			$id = $this->sanitize($id);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$studentDetails = $this->getFullDetailsId($id, "students");

			//getting default fees
			$sql="select * from billing where term=? and acyear=? and class=0 order by category";
			$nTerm=$term[0];
			$nAcYear=$acyear[0];
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$nTerm);
			$result->bindParam("s",$nAcYear);
			$result->execute(array($nTerm,$nAcYear));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
			}

			//getting special fees
			$sql="select * from billing where term=? and acyear=? and class=? order by category";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$term);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$studentDetails[3]);
			$result->execute(array($term,$acyear,$studentDetails[3]));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
			}
			return $amount;
		}

		function getPaidFees($id,$acyear,$term){
			$amount=0;
			$id = $this->sanitize($id);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$sql="select * from payments where student_pid=? and acyear=? and term=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$studentDetails[1]);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($studentDetails[1],$acyear,$term));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
			}
			return $amount;
		}


		function formatNumber($number){
			return number_format(floatval($number), 2, '.','');
		}

		function getScholarship($pid,$acyear,$term){
			$amount = 0.0;
			$pid = $this->sanitize($pid);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$sql = "select * from bursary where pid=? and acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$pid);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->execute(array($pid,$acyear,$term));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amount += $row['amount'];
			}
			return $this->formatNumber($amount);
		}

		function loadPayableFees(){
			$id = $_SESSION['useredit'];
			$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
			$acyear = $this->getAcyear(); //getting academic year
			$term = $this->getTerm(); //getting academic term
			$class=0;
			//loading current fees payable
			$sql="select * from billing where term=? and acyear=? and class=? order by category";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$term[0]);
			$result->bindParam("s",$acyear[0]);
			$result->bindParam("s",$class);
			$result->execute(array($term[0],$acyear[0],$class));
			$data="<div id='loaderView' style='text-align: center;'><center><div style='text-align: center;' class='loader'></div><br/><b>Loading Arrears.. Please wait..</b></center><br/></div>";
			$data.="<table class='table table-hover table-condensed table-striped table-bordered'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Bill Items</center></th><th><center>Amount(GH&cent;)</center></th><th><center>Category</center></th></tr></thead>";
			$data.="<tbody>";
			$count=1;
			$amount=0.00;

			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
				$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
				$count++;
			}

			//loading special fees
			$sql="select * from billing where term=? and acyear=? and class=? order by category";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$term[0]);
			$result->bindParam("s",$acyear[0]);
			$result->bindParam("s",$studentDetails[3]);
			$result->execute(array($term[0],$acyear[0],$studentDetails[3]));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
				$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td style='text-align: right;'><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
				$count++;
			}

			//scholarship
			//$bursary = $this->getScholarship($studentDetails[1], $acyear[0], $term[0]);
			/*$data.="<tr><td><center>".$count."</center></td><td>Bursary</td><td style='text-align: right;'><center>".$bursary."</center></td><td><center>Scholarship</center></td></tr>";*/

			//computing arrears
			$arrears = $this->computeArrears($studentDetails[0]);
			//if(!$arrears==0){
				$arrears=$this->formatNumber($arrears);
				$amount+=$this->formatNumber($arrears);
				$data.="<tr><td><center>".$count."</center></td><td><b>Arrears</b></td><td><center>".$arrears."</center></td><td></td></tr>";
			//}

			//getting arrears breakdown
			$arrearsBreakdown = $this->computeAllArrears($studentDetails[0]);
			//looping through arrears breakdown
			for($i=0; $i < sizeof($arrearsBreakdown); $i++){
				$data.="<tr><td></td><td></td><td><center>".$arrearsBreakdown[$i][2]."</center></td><td><center>".$arrearsBreakdown[$i][0]." Academic Year/Term ".$arrearsBreakdown[$i][1]."</center></td></tr>";
			}

			$amountPaid = $this->getPaidFees($id, $acyear[0], $term[0]);
			//$amountPaid += $bursary;
			$deficit = $this->formatNumber(floatval($amount-$amountPaid));
			$data.="<tr style='color: #fff;' class='bg-aqua'><td></td><td><b>Total Amount(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amount)."</b></center></td><td></td></tr>";
			$data.="<tr style='color: #fff;' class='bg-green'><td></td><td><b>Amount Paid(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amountPaid)."</b></center></td><td></td></tr>";
			$data.="<tr style='color: #fff;' class='bg-yellow'><td></td><td><b>Amount Remaining(GH&cent;)</b></td><td><center><b>".$this->formatNumber($deficit)."</b></center></td><td></td></tr>";
			$data.="</tbody>";
			$data.="</table>";
			echo $data;
			echo "<script>$('#loaderView').hide();</script>";
			echo "<div class='modal fade' id='payFee'>
					<div class='modal-dialog modal-md'>
						<div class='modal-content'>
							<div class='modal-header bgblue'>
								<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Make Payment</h3>
							</div>
							<div class='modal-body'>
								<form method='post' action='#' class='form'>
									<div class='row' style='margin: 2px;'>
										<div class='col-md-5 well'>
											<div class='form-group'>
												<label for='amountPaying'>Amount Paying(GH&cent;):</label>
												<input type='text' class='form-control' placeholder='Amount Paying(GH&cent;)' name='amountPaying' id='amountPaying' onkeyup=\"processFee()\" onselect=\"processFee()\" required='' autofocus=''>
											</div>
											<div class='form-group'>
												<label for='receipt'>Receipt type:</label>
												<select id='receipt' name='receipt' class='form-control' required=''>
													<option value='0'>Small Receipt</option>
													<option value='1'>Large Receipt</option>
												</select>
											</div>
											<div class='form-group'>
												<label for='sms'>SMS Number:</label>
												<input type='text' name='sms' id='sms' placeholder='SMS Number' value='".$studentDetails[14]."' class='form-control' required=''>
											</div>
										</div>
										<div class='col-md-1'></div>
										<div class='col-md-6'>
											<div class='form-group'>
												<label for='payableAmountView'>Payable Amount(GH&cent;):</label>
												<div id='payableAmountView' style='font-size: 25px; font-weight: bold;'>".$deficit."</div>
											</div>
											<div class='form-group'>
												<label for='amountLeftView'>Amount Left(GH&cent;):</label>
												<div id='amountLeftView' style='font-size: 25px; font-weight: bold;'></div>
											</div>
											<div class='form-group well'>
												<label for='paid_by'>Paid by:</label>
												<input type='text' id='paid_by'	name='paid_by' class='form-control' placeholder='Paid by' required/>
											</div>
										</div>
									</div>
									<div class='row'>
										<div style='text-align: center;'><button type='submit' name='payFeeBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-ok-sign'></span> Pay</button>&nbsp;<a href='#' data-dismiss='modal' class='btn btn-xs btn-danger'><span class='glyphicon glyphicon-remove'></span> Close</a></div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

				<script>
					function processFee(){
						//console.log('working');
						var payable = document.getElementById('payableAmountView').innerHTML;
						var amountPaying = document.getElementById('amountPaying').value;
						var amountLeft = parseFloat(payable)-parseFloat(amountPaying);
						//console.log(amountLeft);
						if(isNaN(amountPaying) || amountPaying.trim().length < 1){
							document.getElementById('amountLeftView').innerHTML = payable;
							return;
						}else{
							document.getElementById('amountLeftView').innerHTML = amountLeft;
						}
					}
				</script>";
		}

		function getPayableFee($id){
			$data = array();
			$id = $this->sanitize($id);
			$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
			$acyear = $this->getAcyear(); //getting academic year
			$term = $this->getTerm(); //getting academic term
			$class=0;
			//loading current fees payable
			$sql="select * from billing where term=? and acyear=? and class=? order by category";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$term[0]);
			$result->bindParam("s",$acyear[0]);
			$result->bindParam("s",$class);
			$result->execute(array($term[0],$acyear[0],$class));
			
			$count=1;
			$amount=0.00;

			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
				$count++;
			}

			//loading special fees
			$sql="select * from billing where term=? and acyear=? and class=? order by category";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$term[0]);
			$result->bindParam("s",$acyear[0]);
			$result->bindParam("s",$studentDetails[3]);
			$result->execute(array($term[0],$acyear[0],$studentDetails[3]));
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount+=floatval($row['amount']);
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
				$count++;
			}

			//computing arrears
			$arrears = $this->computeArrears($studentDetails[0]);
			$arrears=$this->formatNumber($arrears);
			$amount+=$this->formatNumber($arrears);
				
			//getting arrears breakdown
			$arrearsBreakdown = $this->computeAllArrears($studentDetails[0]);
			//looping through arrears breakdown
			for($i=0; $i < sizeof($arrearsBreakdown); $i++){
				$data.="<tr><td></td><td></td><td><center>".$arrearsBreakdown[$i][2]."</center></td><td><center>".$arrearsBreakdown[$i][0]." Academic Year/Term ".$arrearsBreakdown[$i][1]."</center></td></tr>";
			}

			$amountPaid = $this->getPaidFees($id, $acyear[0], $term[0]);
			//$amountPaid += $bursary;
			$deficit = $this->formatNumber(floatval($amount-$amountPaid));
			$data[0] = $this->formatNumber($amount);
			$data[1] = $this->formatNumber($amountPaid);
			$data[2] = $this->formatNumber($deficit);
			return $data;
		}

		function paid_arrears($amount,$arrears_acyear,$arrears_term,$acyear,$term,$receipt_no,$cashier_pid,$paid_by,$student_pid){
			$amount=$this->sanitize($amount);
			$arrears_acyear = $this->sanitize($arrears_acyear);
			$arrears_term = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$receipt_no = $this->sanitize($receipt_no);
			$cashier_pid = $this->sanitize($cashier_pid);
			$paid_by = $this->sanitize($paid_by);
			$student_pid = $this->sanitize($student_pid);
			$sql = "insert into paid_arrears(amount,arrears_acyear,arrears_term,acyear,term,receipt_no,cashier_pid,paid_by,student_pid) values(?,?,?,?,?,?,?,?,?)";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$amount);
			$result->bindParam("s",$arrears_acyear);
			$result->bindParam("s",$arrears_term);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$receipt_no);
			$result->bindParam("s",$cashier_pid);
			$result->bindParam("s",$paid_by);
			$result->bindParam("s",$student_pid);
			$result->execute(array($amount,$arrears_acyear,$arrears_term,$acyear,$term,$receipt_no,$cashier_pid,$paid_by,$student_pid));
		}

		function get_paid_arrears($pid,$arrears_acyear,$arrears_term){
			$amount = 0.0;
			$pid = $this->sanitize($pid);
			$arrears_acyear = $this->sanitize($arrears_acyear);
			$arrears_term = $this->sanitize($arrears_term);
			$sql ="select * from paid_arrears where arrears_acyear=? and arrears_term=? and student_pid=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$arrears_acyear);
			$result->bindParam("s",$arrears_term);
			$result->bindParam("s",$pid);
			$result->execute(array($arrears_acyear,$arrears_term,$pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amount += floatval($row['amount']);
			}
			return floatval($amount);
		}

		function getAmountPaid($pid,$billingItem,$acyear,$term){
			$amount = 0.0;
			$pid = $this->sanitize($pid);
			$billingItem = $this->sanitize($billingItem);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$sql = "select * from payments where item=? and acyear=? and term=? and student_pid=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$billingItem);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$pid);
			$result->execute(array($billingItem,$acyear,$term,$pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amount += floatval($row['amount']);
			}
			return floatval($amount);
		}

		function makePayment($student_pid,$acyear,$term,$cashier_pid,$amount,$receipt_no,$paid_by,$item){
			$student_pid = $this->sanitize($student_pid);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$cashier_pid = $this->sanitize($cashier_pid);
			$amount = $this->sanitize($amount);
			$receipt_no = $this->sanitize($receipt_no);
			$paid_by = $this->sanitize($paid_by);
			$item = $this->sanitize($item);
			$sql = "insert into payments(student_pid,acyear,term,cashier_pid,amount,receipt_no,paid_by,item) values(?,?,?,?,?,?,?,?)";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$student_pid);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$cashier_pid);
			$result->bindParam("s",$amount);
			$result->bindParam("s",$receipt_no);
			$result->bindParam("s",$paid_by);
			$result->bindParam("s",$item);
			$result->execute(array($student_pid,$acyear,$term,$cashier_pid,$amount,$receipt_no,$paid_by,$item));
		}

		function payFee(){
			$id = $_SESSION['useredit'];
			$studentDetails = $this->getFullDetailsId($id, "students");
			$acyear = $this->getAcyear();
			$term = $this->getTerm();
			$student_pid = $studentDetails[1];
			$cashier_pid = $_SESSION['vsoftadmin'];
			$amount = $this->sanitize($_POST['amountPaying']);
			$receipt_no = $this->genReceiptNo();
			$receiptType = $this->sanitize($_POST['receipt']);
			$smsNo = $this->sanitize($_POST['sms']);
			$paid_by = $this->sanitize($_POST['paid_by']);
			$currentClass = $studentDetails[3];

			//checking if student owes
			#####################################################################
			$arrears = array();
			$counter = 0;
			$id = $this->sanitize($id);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$student_pid = $studentDetails[1];
			$regAcyear = $studentDetails[25];
			$regTerm = $studentDetails[21]; //gettting student reg acyear and term
			$currentAcyear = $this->getAcyear();
			$currentAcyear = $currentAcyear[0];
			$currentTerm = $this->getTerm();
			$currentTerm = $currentTerm[0]; //getting current acyear and term

			//getting range of acyear
			$sql="select * from acyear where id>=? and id<=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$regAcyear);
			$result->bindParam("s",$currentAcyear);
			$result->execute(array($regAcyear,$currentAcyear));
			//return $result->rowCount();


			//looping through all the various academic years
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				//checking if the acyear is current
				if($row['id']==$currentAcyear){
					//getting all past terms
					$cTerm = $this->getTerm();
					$query="select * from term where name < ?";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$cTerm[1]);
					$res->execute(array($cTerm[1]));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $currentAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $currentAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($currentAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$amount_owe = floatval($payable) - floatval($paid);
							if($amount_owe <= $amount && $amount != 0){
								//pay arrears
								$this->paid_arrears($amount_owe, $currentAcyear, $pTerms[0], $currentAcyear, $cTerm[1],$receipt_no,$cashier_pid,$paid_by,$student_pid);
								$amount -= $amount_owe;
							}else{
								if($amount > 0){
									$this->paid_arrears($amount, $currentAcyear, $pTerms[0], $currentAcyear, $cTerm[1],$receipt_no,$cashier_pid,$paid_by,$student_pid);
									$amount = 0;
								}
							}
							$counter++;
						}
					}
				}else{
					//previous acyear
					$prevAcyear = $row['id'];
					
					//looping through all the terms in the academic year
					$query="select * from term";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$prevAcyear);
					$res->execute(array($prevAcyear));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $prevAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $prevAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($prevAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$amount_owe = floatval($payable) - floatval($paid);
							if($amount_owe <= $amount && $amount != 0){
								//pay arrears
								$this->paid_arrears($amount_owe, $prevAcyear, $pTerms[0], $currentAcyear, $currentTerm,$receipt_no,$cashier_pid,$paid_by,$student_pid);
								$amount -= $amount_owe;
							}else{
								//pay arrears
								if($amount > 0){
									$this->paid_arrears($amount, $prevAcyear, $pTerms[0], $currentAcyear, $currentTerm,$receipt_no,$cashier_pid,$paid_by,$student_pid);
									$amount = 0;
								}
							}
							$counter++;
						}
					}
				}
			}//end of while loop
			#####################################################################
			if($amount > 0){
				//get all billing items ; check payments and see how much has been paid and pay
				############## all bill ########
				$sql="select * from billing where acyear=? and term=? and class=0";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentAcyear);
				$result->bindParam("s",$currentTerm);
				$result->execute(array($currentAcyear,$currentTerm));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountDue = $row['amount'];
					$amountPaid = $this->getAmountPaid($student_pid, $row['item'], $currentAcyear, $currentTerm);
					if($amountPaid < $amountDue){
						//still owing
						$amountOwe = floatval($amountDue - $amountPaid);
						if($amountOwe <= $amount && $amount != 0){
							$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amountOwe, $receipt_no, $paid_by, $row['item']);
							$amount -= $amountOwe;
						}else{
							//pay the amount and set amount to 0
							if($amount > 0){
								$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amount, $receipt_no, $paid_by, $row['item']);
								$amount = 0;
							}
						}
					}
				}
			}

			#################### special fees ##########################
			if($amount > 0){
				//get all billing items ; check payments and see how much has been paid and pay
				############## all bill ########
				$sql="select * from billing where acyear=? and term=? and class=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentAcyear);
				$result->bindParam("s",$currentTerm);
				$result->bindParam("s",$currentClass);
				$result->execute(array($currentAcyear,$currentTerm,$currentClass));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountDue = $row['amount'];
					$amountPaid = $this->getAmountPaid($student_pid, $row['item'], $currentAcyear, $currentTerm);
					if($amountPaid < $amountDue){
						//still owing
						$amountOwe = floatval($amountDue - $amountPaid);
						if($amountOwe <= $amount && $amount != 0){
							$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amountOwe, $receipt_no, $paid_by, $row['item']);
							$amount -= $amountOwe;
						}else{
							//pay the amount and set amount to 0
							if($amount != 0){
								$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amount, $receipt_no, $paid_by, $row['item']);
								$amount = 0;
							}
						}
					}
				}
			}
			############################################################
			$message = "Amount of GHC ".$amount." has been paid";
			$smsMessage = $this->sendSMS($smsNo, $message);
			echo "<script>window.open('print.php?payfee=".$receipt_no."&view=".$receiptType."');</script>";
			$this->displayMsg("Process Complete..", 1);
			$this->redirect("?pay");
		}


		function payScholarship($receipt_no){
			$indexnumber = $this->sanitize($_POST['indexnumber']);
			$studentDetails = $this->getFullDetailsIndexNumber($indexnumber, "students");
			$acyear = $this->getAcyear();
			$term = $this->getTerm();
			$student_pid = $studentDetails[1];
			$cashier_pid = $_SESSION['vsoftadmin'];
			$amount = $this->sanitize($_POST['amount']);
			$receipt_no = $this->sanitize($receipt_no);
			$smsNo = $studentDetails[14];
			$paid_by = "Scholarship";
			$currentClass = $studentDetails[3];

			//checking if student owes
			#####################################################################
			$arrears = array();
			$counter = 0;
			$student_pid = $studentDetails[1];
			$regAcyear = $studentDetails[25];
			$regTerm = $studentDetails[21]; //gettting student reg acyear and term
			$currentAcyear = $this->getAcyear();
			$currentAcyear = $currentAcyear[0];
			$currentTerm = $this->getTerm();
			$currentTerm = $currentTerm[0]; //getting current acyear and term

			//getting range of acyear
			$sql="select * from acyear where id>=? and id<=?";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$regAcyear);
			$result->bindParam("s",$currentAcyear);
			$result->execute(array($regAcyear,$currentAcyear));
			//return $result->rowCount();


			//looping through all the various academic years
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				//checking if the acyear is current
				if($row['id']==$currentAcyear){
					//getting all past terms
					$cTerm = $this->getTerm();
					$query="select * from term where name < ?";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$cTerm[1]);
					$res->execute(array($cTerm[1]));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $currentAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $currentAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($currentAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$amount_owe = floatval($payable) - floatval($paid);
							if($amount_owe <= $amount && $amount != 0){
								//pay arrears
								$this->paid_arrears($amount_owe, $currentAcyear, $pTerms[0], $currentAcyear, $cTerm[1],$receipt_no,$cashier_pid,$paid_by,$student_pid);
								$amount -= $amount_owe;
							}else{
								if($amount > 0){
									$this->paid_arrears($amount, $currentAcyear, $pTerms[0], $currentAcyear, $cTerm[1],$receipt_no,$cashier_pid,$paid_by,$student_pid);
									$amount = 0;
								}
							}
							$counter++;
						}
					}
				}else{
					//previous acyear
					$prevAcyear = $row['id'];
					
					//looping through all the terms in the academic year
					$query="select * from term";
					$res=$this->con->prepare($query);
					$res->bindParam("s",$prevAcyear);
					$res->execute(array($prevAcyear));
					while($pTerms=$res->fetch(PDO::FETCH_ASSOC)){
						$payable = $this->getPayableFees($id, $prevAcyear, $pTerms[0]);
						$paid = $this->getPaidFees($id, $prevAcyear, $pTerms[0]);
						$paid += $this->get_paid_arrears($prevAcyear, $pTerms[0]);
						if($paid < $payable){
							//the person owe
							$amount_owe = floatval($payable) - floatval($paid);
							if($amount_owe <= $amount && $amount != 0){
								//pay arrears
								$this->paid_arrears($amount_owe, $prevAcyear, $pTerms[0], $currentAcyear, $currentTerm,$receipt_no,$cashier_pid,$paid_by,$student_pid);
								$amount -= $amount_owe;
							}else{
								//pay arrears
								if($amount > 0){
									$this->paid_arrears($amount, $prevAcyear, $pTerms[0], $currentAcyear, $currentTerm,$receipt_no,$cashier_pid,$paid_by,$student_pid);
									$amount = 0;
								}
							}
							$counter++;
						}
					}
				}
			}//end of while loop
			#####################################################################
			if($amount > 0){
				//get all billing items ; check payments and see how much has been paid and pay
				############## all bill ########
				$sql="select * from billing where acyear=? and term=? and class=0";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentAcyear);
				$result->bindParam("s",$currentTerm);
				$result->execute(array($currentAcyear,$currentTerm));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountDue = $row['amount'];
					$amountPaid = $this->getAmountPaid($student_pid, $row['item'], $currentAcyear, $currentTerm);
					if($amountPaid < $amountDue){
						//still owing
						$amountOwe = floatval($amountDue - $amountPaid);
						if($amountOwe <= $amount && $amount != 0){
							$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amountOwe, $receipt_no, $paid_by, $row['item']);
							$amount -= $amountOwe;
						}else{
							//pay the amount and set amount to 0
							if($amount > 0){
								$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amount, $receipt_no, $paid_by, $row['item']);
								$amount = 0;
							}
						}
					}
				}
			}

			#################### special fees ##########################
			if($amount > 0){
				//get all billing items ; check payments and see how much has been paid and pay
				############## all bill ########
				$sql="select * from billing where acyear=? and term=? and class=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentAcyear);
				$result->bindParam("s",$currentTerm);
				$result->bindParam("s",$currentClass);
				$result->execute(array($currentAcyear,$currentTerm,$currentClass));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountDue = $row['amount'];
					$amountPaid = $this->getAmountPaid($student_pid, $row['item'], $currentAcyear, $currentTerm);
					if($amountPaid < $amountDue){
						//still owing
						$amountOwe = floatval($amountDue - $amountPaid);
						if($amountOwe <= $amount && $amount != 0){
							$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amountOwe, $receipt_no, $paid_by, $row['item']);
							$amount -= $amountOwe;
						}else{
							//pay the amount and set amount to 0
							if($amount != 0){
								$this->makePayment($student_pid, $acyear[0], $term[0], $cashier_pid, $amount, $receipt_no, $paid_by, $row['item']);
								$amount = 0;
							}
						}
					}
				}
			}
			############################################################
			$message = "Amount of GHC ".$amount." has been paid";
			$smsMessage = $this->sendSMS($smsNo, $message);
		}


		function loadCurrentPaidFee(){
			$data = null;
			$id = $this->sanitize($_GET['payfees']);
			$sql="select * from payments where receipt_no=? order by date";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$id);
			$result->execute(array($id));
			$studentDetails = array();
			$amountPaid = 0.0;
			$receipt_no = null;
			$date = null;
			$paidItems = null;
			$acyear = array();
			$term = array();
			$class = array();
			$paymentFor = null;
			$billing_item = array();
			$billing = array();
			$paid_by = null;
			if($result->rowCount() < 1){
				exit();
				return;
			}
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['student_pid'], "students");
				$cashierDetails = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
				$receipt_no = $row['receipt_no'];
				$date = $row['date'];
				$amountPaid += floatval($row['amount']);
				$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
				$term = $this->getFullDetailsId($row['term'], "term");
				$class = $this->getFullDetailsId($studentDetails[3],"classes");
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing = $this->getFullDetailsId($row['item'], "billingPrice");
				$paymentFor .= "<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>".$billing_item[1].":</td><td style='text-align: right; padding-left: 15px;'> ".$this->formatNumber($row['amount'])."</td></tr>";
				$paid_by = $row['paid_by'];
			}

			//getting paid arrears
			$acyear1 = $this->getAcyear();
			$term1 = $this->getTerm();
			$acyear1 = $acyear1[0];
			$term1 = $term1[0];
			$sql = "select * from paid_arrears where acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear1);
			$result->bindParam("s",$term1);
			$result->execute(array($acyear1,$term1));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$arrears_acyear = $this->getFullDetailsId($row['arrears_acyear'], "acyear");
				$arrears_term = $this->getFullDetailsId($row['arrears_term'], "term");
				$amountPaid += floatval($row['amount']);
				$paymentFor .= "<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Arrears for ".$arrears_acyear[1]." Academic Year (".$arrears_term[1]."):</td><td style='text-align: right; padding-left: 15px;'> ".$this->formatNumber($row['amount'])."</td></tr>";
			}

			$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];

			$payableFee = $this->getPayableFee($studentDetails[0]);

			$data.="<hr style='margin: 0px; padding: 0px;'>";
			$data.="<div class='row' style='margin: 0px;'>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding: 0px;'>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Receipt Number:</td><td style='text-align: left; padding-left: 5px;'> ".$receipt_no."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Date Paid:</td><td style='text-align: left; padding-left: 5px;'> ".$date."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Academic Year:</td><td style='text-align: left; padding-left: 5px;'> ".$acyear[1]."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Term:</td><td style='text-align: left; padding-left: 5px;'> ".$term[1]."</td></tr>";
			$data.="</table>";
			$data.="</div>";
			$data.="<hr style='margin: 0px; padding: 0px;'>";
			$data.="<p style='font-weight: normal; font-size: 13px; margin: 0px; padding: 0px;'><b>Student Name:</b> ".$fullname." (".$class[2].")</p>";
			$data.="<p style='font-weight: normal; font-size: 13px; margin: 0px; padding: 0px;'><b>Amount Paid:</b> ".$this->formatNumber($amountPaid)."</p>";
			$data.="<p style='font-weight: normal; font-size: 13px; margin: 0px; padding: 0px;'><b>Being Payment For:</b> </p>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding: 0px;'>";
			$data.=$paymentFor;
			$data.="</table>";
			$data.="<hr style='margin: 0px; padding: 0px;'>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding: 0px;'>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Total Debt:</td><td style='text-align: right;'>".$payableFee[0]."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Total Payment:</td><td style='text-align: right;'>".$payableFee[1]."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Balance:</td><td style='text-align: right; padding-left: 180px'>".$payableFee[2]."</td></tr>";
			$data.="</table>";
			$data.="<div class='row' style='margin-top: 0px;'>";
			$data.="<p style='font-weight: normal; font-size: 13px; margin-top: 10px; padding: 0px;'>Paid by: ".$paid_by."</p>";
			$data.="<textarea style='border: 1px solid black; margin-bottom: 0px; padding-bottom: 0px;'></textarea>";
			$data.="<p style='font-size: 11px; margin-top: 0px; padding: 0px;'><center>Cashier: ".$cashierDetails[2]."</center></p>";
			$data.="</div>";
			return $data;
		}

		function number2words($amount){
			$amount = $this->sanitize($amount);
			$result = explode(".",$amount);
			/*$f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
				$f->setTextAttribute(NumberFormatter::DEFAULT_RULESET, "%spellout-numbering-verbose");*/
			if(sizeof($result)==2){
				// $first = $f->format($result[0]);
				// $second = $f->format($result[1]);
				$first = NumbersToWords::convert($result[0]);
				$second = NumbersToWords::convert($result[1]);
				return ucwords($first." Ghana Cedis, ".$second." Pesewas.");
			}elseif(sizeof($result)==1){
				//return $f->format($result[0])." Ghana Cedis.";
				return ucwords(NumbersToWords::convert($result[0])." Ghana Cedis.");
			}
		}

		function loadCurrentPaidFee2(){
			$data = null;
			$id = $this->sanitize($_GET['payfees']);
			$sql="select * from payments where receipt_no=? order by date";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$id);
			$result->execute(array($id));
			$studentDetails = array();
			$amountPaid = 0.0;
			$receipt_no = null;
			$date = null;
			$paidItems = null;
			$acyear = array();
			$term = array();
			$class = array();
			$paymentFor = null;
			$billing_item = array();
			$billing = array();
			$paid_by = null;
			if($result->rowCount() < 1){
				exit();
				return;
			}
			$count=0;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['student_pid'], "students");
				$cashierDetails = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
				$receipt_no = $row['receipt_no'];
				$date = $row['date'];
				$amountPaid += floatval($row['amount']);
				$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
				$term = $this->getFullDetailsId($row['term'], "term");
				$class = $this->getFullDetailsId($studentDetails[3],"classes");
				$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
				$billing = $this->getFullDetailsId($row['item'], "billingPrice");
				//$paymentFor .= "<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>".$billing_item[1].":</td><td style='text-align: right; padding-left: 15px;'> ".$this->formatNumber($row['amount'])."</td></tr>";
				if($count==0){
					$paymentFor .= $billing_item[1];
				}else{
					$paymentFor .= ", ".$billing_item[1];
				}
				$paid_by = $row['paid_by'];
				$count++;
			}

			//getting paid arrears
			$acyear1 = $this->getAcyear();
			$term1 = $this->getTerm();
			$acyear1 = $acyear1[0];
			$term1 = $term1[0];
			$sql = "select * from paid_arrears where acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear1);
			$result->bindParam("s",$term1);
			$result->execute(array($acyear1,$term1));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$arrears_acyear = $this->getFullDetailsId($row['arrears_acyear'], "acyear");
				$arrears_term = $this->getFullDetailsId($row['arrears_term'], "term");
				//$paymentFor .= "<tr style='margin: 0px; padding: 0px; font-size: 13px;'><td>Arrears for ".$arrears_acyear[1]." Academic Year (".$arrears_term[1]."):</td><td style='text-align: right; padding-left: 15px;'> ".$this->formatNumber($row['amount'])."</td></tr>";
				/*$paymentFor .= "Arrears for ".$arrears_acyear[1]." Academic Year (".$arrears_term[1].")";*/
				$amountPaid += floatval($row['amount']);
				if($count==0){
					$paymentFor .= "Arrears for ".$arrears_acyear[1]." Academic Year (".$arrears_term[1].")";
				}else{
					$paymentFor .= ", Arrears for ".$arrears_acyear[1]." Academic Year (".$arrears_term[1].")";
				}
				$count++;
			}

			$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];

			$payableFee = $this->getPayableFee($studentDetails[0]);
			$data.="<div style='page-break-after: always;'>";
			$data.="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 13px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><span style='font-weight: bold;font-size: 18px;'>Stepping Stone School Complex</span><br>
				c/o P. O. Box 51<br>
				Bawdie - Dompim, W/R<br>
				Tel: 024 886 9645<br>
				</p>
				<p style='float: left;font-size: 15px;padding-left: 520px; padding-top: 0px; margin-top: 0px;'><span style='font-weight: bold;font-size: 18px; background-color: #000;'><u>Official Receipt</u></span><br>
					<span style='padding-left: 15px; padding-top: 10px;'>No: <b><u>".$receipt_no."</u></b></span><u></u></center><br>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";	
			$data.="<hr style='margin: 0px; padding: 0px;'>";
			$data.="<div class='row' style='margin: 0px;'>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding-top: 5px;'>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 15px;'><td>Date and Time Paid:</td><td style='text-align: left; padding-left: 5px;'> <b><u>".$date."</u></b></td><td style='padding-left: 50px;'>&nbsp;</td><td>Student:</td><td style='padding-left: 10px; font-weight: bold;font-decoration: underline;'><u>".$fullname." (".$class[2].")</u></td></tr>";
			$data.="</table>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding-top: 5px;'>";
			$data.="<tr style='margin-top: 10px; padding: 0px; font-size: 14px;'><td><span style='background-color: black; color: white; padding: 15px; font-size: 20px;'>The Sum of:</span></td><td style='width: 570px;padding-top: 10px;'><textarea style='border: 1px solid black;'>".$this->number2words($amountPaid)."</textarea></td></tr>";
			$data.="<tr style='margin-top: 10px; padding: 0px; font-size: 14px;'><td><span style='background-color: black; color: white; padding: 15px; font-size: 20px;'>Being:</span></td><td style='width: 570px;padding-top: 10px;'><textarea style='border: 1px solid black;'>".$paymentFor."</textarea></td></tr>";
			$data.="</table>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding-top: 5px;'>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 14px;'><td style='padding-left: 130px;'></td><td>Total Debt:</td><td style='text-align: right;'>".$payableFee[0]."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 14px;'><td style='padding-left: 130px;'></td><td>Total Payment:</td><td style='text-align: right;'>".$payableFee[1]."</td></tr>";
			$data.="<tr style='margin: 0px; padding: 0px; font-size: 14px;'><td style='padding-left: 130px;'></td><td>Balance:</td><td style='text-align: right; padding-left: 180px'>".$payableFee[2]."</td></tr>";
			$data.="</table>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding-top: 5px;'>";
			$data.="<tr style='margin-top: 10px; padding: 0px; font-size: 14px;'><td style='padding-left: 127px;'></td><td><textarea style='border: 1px solid black;width: 300px;'><center>STAMP/SIGNATURE</center></textarea></td></tr>";
			$data.="</table>";
			$data.="<table border='0' cellspacing='0' cellpadding='0' style='margin: 0px; padding-top: 5px;padding-bottom: 10px;'>";
			$data.="<tr style='margin: 0px; padding-bottom: 4px; font-size: 14px;'><td style='padding-left: 180px;'></td><td>Cashier:</td><td style='text-align: right;padding-left: 10px;'> ".$cashierDetails[2]."</td><td style='padding-left: 30px;'>Paid by:</td><td style='padding-left: 20px;'>".$paid_by."</td></tr>";
			$data.="</table>";
			return $data;
		}

		function getStudentPaidArrears($student_pid,$receipt_no){
			$student_pid = $this->sanitize($student_pid);
			$receipt_no = $this->sanitize($receipt_no);
			$amount = 0.0;
			$sql = "select amount from paid_arrears where student_pid=? and receipt_no=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$student_pid);
			$result->bindParam("s",$receipt_no);
			$result->execute(array($student_pid,$receipt_no));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amount += $this->formatNumber(floatval($row['amount']));
			}
			return $this->formatNumber(floatval($amount));
		}

		function getArrearsDetails($student_pid,$receipt_no){
			$student_pid = $this->sanitize($student_pid);
			$receipt_no = $this->sanitize($receipt_no);
			$sql = "select * from paid_arrears where student_pid=? and receipt_no=? order by date desc";
			$result = $this->con->prepare($sql);
			$data = array();
			$count = 0;
			$amount = 0.0;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				if($count == 0){
					$data[0] = $row['id'];
					$data[1] = $row['amount'];
					$data[2] = $row['arrears_acyear'];
					$data[3] = $row['arrears_term'];
					$data[4] = $row['acyear'];
					$data[5] = $row['term'];
					$data[6] = $row['date'];
					$data[7] = $row['receipt_no'];
					$data[8] = $row['cashier_pid'];
					$data[9] = $row['paid_by'];
					$data[10] = $row['student_pid'];
					$count++;
				}
				$amount += floatval($row['amount']);
			}
			//changing amount to total
			$data[1] = $amount;
			return $data;
		}

		function loadStudentPaymentHistory(){
			$receipt_no = array();
			$counter = 0;
			$id = $this->sanitize($_SESSION['useredit']);
			$studentDetails = $this->getFullDetailsId($id, "students");
			$sql="select distinct receipt_no from payments where student_pid=? order by date desc";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$studentDetails[1]);
			$result->execute(array($studentDetails[1]));
			$count=1;
			$data="<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Academic Year</center></th><th><center>Term</center></th><th><center>Amount (GH&cent;)</center></th><th><center>Cashier</center></th><th><center>Receipt No.</center></th><th><center>Date</center></th><th></th></tr></thead><tbody>";
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$receipt_no[$counter] = $row['receipt_no'];
				$counter++;
			}

			//checking paid arrears
			$sql = "select distinct receipt_no from paid_arrears where student_pid=? order by date desc";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$studentDetails[1]);
			$result->execute(array($studentDetails[1]));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				if(!in_array($row['receipt_no'], $receipt_no)){
					$receipt_no[$counter] = $row['receipt_no'];
					$counter++;
				}
			}

			for($i=0; $i < sizeof($receipt_no); $i++){
				$currentReceiptNo = $receipt_no[$i];
				$sql = "select * from payments where receipt_no=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentReceiptNo);
				$result->execute(array($currentReceiptNo));
				$amount = 0;
				$acyear = null;
				$term = null;
				$cashier = array();
				$date = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amount += floatval($row['amount']);
					$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
					$term = $this->getFullDetailsId($row['term'], "term");
					$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
					$date = $row['date'];
				}

				if(floatval($amount)==0.0 || floatval($amount)==0){
						//it is an arrears paid
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
						$acyear = $this->getFullDetailsId($arrearsDetails[4], "acyear");
						$term = $this->getFullDetailsId($arrearsDetails[5], "term");
						$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
						$date = $arrearsDetails[6];
				}else{
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
				}

				$data.="<tr><td><center>".$count."</center></td><td><center>".$acyear[1]."</center></td><td><center>Term ".$term[1]."</center></td><td><center>".$this->formatNumber($amount)."</center></td><td>".$cashier[2]."</td><td><center>".$currentReceiptNo."</center></td><td><center>".$date."</center></td><td><center><button type='button' onclick=\"window.open('print.php?payfees=".$currentReceiptNo."&view=0')\" class='btn btn-xs btn-info br tooltip-bottom' title='Small Receipt'><span class='glyphicon glyphicon-print'></span></button>&nbsp;<button type='button' onclick=\"window.open('print.php?payfees=".$currentReceiptNo."&view=1')\" class='btn btn-xs btn-info br tooltip-bottom' title='Large Receipt'><span class='glyphicon glyphicon-print'></span></button>&nbsp;<button type='button' onclick=\"deleteReq('".$currentReceiptNo."','payments','?pay&history')\" class='btn btn-xs btn-danger br'><span class='glyphicon glyphicon-remove'></span></button></center></td></tr>";
				$count++;
			}
			
			$data.="</tbody></table>";
			echo $data;
		}

		function loadDefaulters2(){
			$class = $this->sanitize($_POST['class']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<center>
					<embed src='print.php?report&defaulters&class=".$class."&acyear=".$acyear."&term=".$term."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadDefaulters(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$term = $this->getFullDetailsId($term, "term");
			$class = $this->sanitize($_GET['class']);
			if($class=="0"){
				$sql="select * from students where status=1";
				$res=$this->con->query($sql);
				$displayClass = "";
			}else{
				$sql = "select * from students where class=? and status=1";
				$res = $this->con->prepare($sql);
				$res->bindParam("s",$class);
				$res->execute(array($class));
				$classDetails = $this->getFullDetailsId($class, "classes");
				$displayClass = "<b>Class:</b> &nbsp;".$classDetails[2]."&nbsp;&nbsp;";
			}
			//$data="<div id='loaderView' style='text-align: center;'><center><div style='text-align: center;' class='loader'></div><br/><b>Loading Arrears.. Please wait..</b></center><br/></div>";
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>List of Defaulting Students</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>" . $displayClass ."<b>Academic Year:</b> ".$acyear[1]."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Fees(GH&cent;)</center></th><th><center>Amount Paid(GH&cent;)</center></th><th><center>Balance</center></th></tr></thead>";
			$data.="<tbody>";
			$count=1;
			$termFees = 0.0;
			$paidFees = 0.0;
			$balance = 0.0;
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$id = $row['id'];
				$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
				$class=0;
				//loading current fees payable
				$sql="select * from billing where term=? and acyear=? and class=? order by category";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$term[0]);
				$result->bindParam("s",$acyear[0]);
				$result->bindParam("s",$class);
				$result->execute(array($term[0],$acyear[0],$class));
				//performing data crunching to compute arrears
				$amount=0.00;
				while($row=$result->fetch(PDO::FETCH_ASSOC)){
					$amount+=floatval($row['amount']);
					//$termFees += $amount;
					$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
					$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					//$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
					//$count++;
				}

				//loading special fees
				$sql="select * from billing where term=? and acyear=? and class=? order by category";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$term[0]);
				$result->bindParam("s",$acyear[0]);
				$result->bindParam("s",$studentDetails[3]);
				$result->execute(array($term[0],$acyear[0],$studentDetails[3]));
				while($row=$result->fetch(PDO::FETCH_ASSOC)){
					$amount+=floatval($row['amount']);
					//$termFees += $amount;
					$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
					$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					//$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td style='text-align: right;'><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
					//$count++;
				}



				// //total amount
				// $data.="<tr><td></td><td><b>Total Amount(GH&cent;)</b></td><td><center><b>".floatval($amount)."</b></center></td><td></td></tr>";

					//computing arrears
				$arrears = $this->computeArrears($studentDetails[0]);
				//if(!$arrears==0){
					$arrears=$this->formatNumber($arrears);
					$amount+=$this->formatNumber($arrears);
					//$data.="<tr><td><center>".$count."</center></td><td><b>Arrears</b></td><td><center>".$arrears."</center></td><td></td></tr>";
				//}
				$amountPaid = $this->getPaidFees($id, $acyear[0], $term[0]);
				$paidFees += $amountPaid;
				$deficit = $this->formatNumber(floatval($amount-$amountPaid));
				$balance += $deficit;
				if(intval($deficit) > 0){
					//preview student
					$studentClass = $studentDetails[3]; 
					$termFees += $amount;
					$classDetails = $this->getFullDetailsId($studentClass, "classes");
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$amount."</center></td><td><center>".$amountPaid."</center></td><td><center>".$deficit."</center></td></tr>";
				}
				// $data.="<tr style='background-color: #2980b9; color: #fff;'><td></td><td><b>Total Amount(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amount)."</b></center></td><td></td></tr>";
				// $data.="<tr style='background-color: #2ecc71; color: #fff;'><td></td><td><b>Amount Paid(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amountPaid)."</b></center></td><td></td></tr>";
				// $data.="<tr style='background-color: #e74c3c; color: #fff;'><td></td><td><b>Amount Remaining(GH&cent;)</b></td><td><center><b>".$this->formatNumber($deficit)."</b></center></td><td></td></tr>";
				$count++;
			}
			$data.="<tr><td colspan='4'><b>Total</b></td><td><center><b>".$this->formatNumber($termFees)."</b></center></td><td><center><b>".$this->formatNumber($paidFees)."</b></center></td><td><center><b>".$this->formatNumber($balance)."</b></center></td></tr>";
			$data.="</tbody>";
			$data.="</table>";
			return $data;
			//echo "<script>$('#loaderView').hide();</script>";
		}

		function delStudentBill($billingId){
			$billingId = $this->sanitize($billingId);
			$sql = "delete from student_bill where billing=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$billingId);
			$result->execute(array($billingId));
		}

		function loadScholarshipAll(){
			$acyear = $this->sanitize($_POST['acyear']);
			$allAcyear = $this->sanitize($_POST['allAcyear']);
			$term = $this->sanitize($_POST['term']);
			$allTerm = $this->sanitize($_POST['allTerm']);
			$class = $this->sanitize($_POST['classes']);
			$allClass = $this->sanitize($_POST['allClasses']);
			$data="<center>
					<embed src='print.php?report&scholarshipAll&acyear=".$acyear."&allAcyear=".$allAcyear."&term=".$term."&allTerm=".$allTerm."&class=".$class."&allClass=".$allClass."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadScholarshipAll2(){
			$acyear = $this->sanitize($_GET['acyear']);
			$allAcyear = intval($this->sanitize($_GET['allAcyear']));
			$term = $this->sanitize($_GET['term']);
			$allTerm = intval($this->sanitize($_GET['allTerm']));
			$class = $this->sanitize($_GET['class']);
			$allClass = intval($this->sanitize($_GET['allClass']));
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'>Students with Bursary/Scholarship Form</h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			//$data.="<p style='font-size: 13px;'>" . $displayClass ."<b>Academic Year:</b> ".$acyear[1]."</p>";
			$data.="<p>&nbsp;</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Full Name</center></th><th><center>Credit(GH&cent;)</center></th><th><center>Debit(GH&cent;)</center></th><th><center>Balance Left to Pay(GH&cent;)</center></th></tr></thead>";
			$data.="<tbody>";
			$totalCredit = 0.0;
			$totalDebit = 0.0;
			$totalBalance = 0.0;
			$count = 1;
			//getting a list of all scholarship students
			if($allAcyear == 0 && $allTerm == 0 && $allClass == 0){
				//use normal parameters
				$sql = "select * from bursary where acyear=? and term=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($acyear,$term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					if($studentDetails[3] == $class){
						//populate data
						$classDetails = $this->getFullDetailsId($class, "classes");
						$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
						$credit = $this->formatNumber($row['amount']);
						$totalCredit += $credit;
						$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
						$totalDebit += $debit;
						$newDate = $this->getDay($row['date']);
						$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
						$totalBalance += $balance;
						$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}
				}
			}elseif($allAcyear==0 && $allTerm == 0 && $allClass == 1){
				$sql = "select * from bursary where acyear=? and term=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($acyear,$term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					//populate data
					$class = $studentDetails[3];
					$classDetails = $this->getFullDetailsId($class, "classes");
					$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
					$credit = $this->formatNumber($row['amount']);
					$totalCredit += $credit;
					$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
					$totalDebit += $debit;
					$newDate = $this->getDay($row['date']);
					$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
					$totalBalance += $balance;
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
					$count++;
				}
			}elseif($allAcyear == 0 && $allTerm == 1 && $allClass == 0){
				$sql = "select * from bursary where acyear=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($acyear,$term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					if($studentDetails[3] == $class){
						//populate data
						$classDetails = $this->getFullDetailsId($class, "classes");
						$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
						$credit = $this->formatNumber($row['amount']);
						$totalCredit += $credit;
						$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
						$totalDebit += $debit;
						$newDate = $this->getDay($row['date']);
						$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
						$totalBalance += $balance;
						$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}
				}
			}elseif($allAcyear == 0 && $allTerm == 1 && $allClass == 1){
				$sql = "select * from bursary where acyear=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($acyear,$term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					//populate data
					$class = $studentDetails[3];
					$classDetails = $this->getFullDetailsId($class, "classes");
					$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
					$credit = $this->formatNumber($row['amount']);
					$totalCredit += $credit;
					$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
					$totalDebit += $debit;
					$newDate = $this->getDay($row['date']);
					$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
					$totalBalance += $balance;
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
					$count++;
				}
			}elseif($allAcyear == 1 && $allTerm == 0 && $allClass == 0){
				$sql = "select * from bursary where term=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					if($studentDetails[3] == $class){
						//populate data
						$classDetails = $this->getFullDetailsId($class, "classes");
						$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
						$credit = $this->formatNumber($row['amount']);
						$totalCredit += $credit;
						$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
						$totalDebit += $debit;
						$newDate = $this->getDay($row['date']);
						$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
						$totalBalance += $balance;
						$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}
				}
			}elseif($allAcyear == 1 && $allTerm == 0 && $allClass == 1){
				$sql = "select * from bursary where term=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($term));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					//populate data
					$class = $studentDetails[3];
					$classDetails = $this->getFullDetailsId($class, "classes");
					$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
					$credit = $this->formatNumber($row['amount']);
					$totalCredit += $credit;
					$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
					$totalDebit += $debit;
					$newDate = $this->getDay($row['date']);
					$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
					$totalBalance += $balance;
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
					$count++;
				}
			}elseif($allAcyear == 1 && $allTerm ==1 && $allClass == 0){
				$sql = "select * from bursary order by date";
				$result = $this->con->query($sql);
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					if($studentDetails[3] == $class){
						//populate data
						$classDetails = $this->getFullDetailsId($class, "classes");
						$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
						$credit = $this->formatNumber($row['amount']);
						$totalCredit += $credit;
						$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
						$totalDebit += $debit;
						$newDate = $this->getDay($row['date']);
						$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
						$totalBalance += $balance;
						$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}
				}
			}elseif($allAcyear == 1 && $allTerm ==1 && $allClass == 1){
				$sql = "select * from bursary order by date";
				$result = $this->con->query($sql);
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					//getting student class
					$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
					//populate data
					$class = $studentDetails[3];
					$classDetails = $this->getFullDetailsId($class, "classes");
					$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
					$credit = $this->formatNumber($row['amount']);
					$totalCredit += $credit;
					$debit = $this->getPaidFees($studentDetails[0], $acyear, $term);
					$totalDebit += $debit;
					$newDate = $this->getDay($row['date']);
					$balance = $this->getBalance($newData, $studentDetails[0], $acyear, $term);
					$totalBalance += $balance;
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td>".$fullname."</td><td><center>".$this->formatNumber($credit)."</center></td><td><center>".$this->formatNumber($debit)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
					$count++;
				}
			}
			$data.="<tr><td colspan='3'><b>Total</b></td><td><center><b>".$this->formatNumber($totalCredit)."</b></center></td><td><center><b>".$this->formatNumber($totalDebit)."</b></center></td><td><center><b>".$this->formatNumber($totalBalance)."</b></center></td></tr>";
			$data.="</tbody></table>";
			return $data;
		}


		function loadScholarshipAccount(){
			$student_id = $this->sanitize($_POST['student_id']);
			$data="<center>
					<embed src='print.php?report&scholarshipAccount&student_id=".$student_id."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function getDay($date){
			$date = $this->sanitize($date);
			$data = explode(" ", $date);
			return $data[0];
		}

		function loadScholarshipAccount2(){
			$student_id = $this->sanitize($_GET['student_id']);
			$studentDetails = $this->getFullDetailsIndexNumber($student_id, "students");
			$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'>Account History of (".$fullname.")</h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			//$data.="<p style='font-size: 13px;'>" . $displayClass ."<b>Academic Year:</b> ".$acyear[1]."</p>";
			$data.="<p>&nbsp;</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Date</center></th><th><center>Description/Transaction</center></th><th><center>Credit(GH&cent;)</center></th><th><center>Debit(GH&cent;)</center></th><th><center>Balance Left to Pay(GH&cent;)</center></th></tr></thead>";
			$data.="<tbody>";
			$totalCredit = 0.0;
			$totalDebit = 0.0;
			$totalBalance = 0.0;
			$allDays = array();
			$count = 0;
			//checking scholarship
			$pid = $studentDetails[1];
			$sql = " select * from bursary where pid=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$cDate = $this->getDay($row['date']);
				if(!in_array($cDate, $allDays)){
					//add date
					$allDays[$count] = $cDate;
					$count++;
				}
			}

			//checking payments
			$sql = " select * from payments where student_pid=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$cDate = $this->getDay($row['date']);
				if(!in_array($cDate, $allDays)){
					//add date
					$allDays[$count] = $cDate;
					$count++;
				}
			}

			//checking paid_arrears
			$sql = " select * from paid_arrears where student_pid=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$cDate = $this->getDay($row['date']);
				if(!in_array($cDate, $allDays)){
					//add date
					$allDays[$count] = $cDate;
					$count++;
				}
			}


			//looping through all dates
			$allDays = sort($allDays); //ordering all days
			for($i=0; $i < sizeof($allDays); $i++){
				############################################################################################################################################################
				//checking scholarships
				$sql = "select * from bursary where pid=? and date like ?";
				$result = $this->con->prepare($sql);
				$result->execute(array($pid,$allDays[$i]."%"));
				$bursary = 0;
				$cDate = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$bursary += floatval($row['amount']);
					$cDate = $this->getDay($row['date']);
				}
				//displaying data
				if($bursary > 0){
					$totalCredit += $this->formatNumber($bursary);
					$data.="<tr><td><center>".$count."</center></td><td><center>".$cDate."</center></td><td>Scholarship Credit</td><td><center>".$this->formatNumber($bursary)."</center></td><td><center>0.0</center></td><td><center></center></td></tr>";
					$count++;
					$totalBalance = $this->formatNumber($bursary);
				}

				################################################
				//checking payments
				$sql = "select * from payments where student_pid=? and date like ?";
				$result = $this->con->prepare($sql);
				$result->execute(array($pid,$allDays[$i]."%"));
				$amountPaid = 0.0;
				$pDate = null;
				$pAcyear = null;
				$pTerm = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountPaid += floatval($row['amount']);
					$pDate = $this->getDay($row['date']);
					$pAcyear = $row['acyear'];
					$pTerm = $row['term'];
				}
				if($amountPaid > 0){
					$balance = $this->getBalance($pDate, $studentDetails[0], $pAcyear, $pTerm);
					if($pDate == $cDate){
						//don't display balance
						$data.="<tr><td><center>".$count."</center></td><td><center>".$pDate."</center></td><td>Payment of Fees</td><td><center>0.00</center></td><td><center>".$this->formatNumber($amountPaid)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
					}else{
						$data.="<tr><td><center>".$count."</center></td><td><center>".$pDate."</center></td><td>Payment of Fees</td><td><center>0.00</center></td><td><center>".$this->formatNumber($amountPaid)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}	
					$totalDebit += $amountPaid;
					$totalBalance = $balance;
				}


				################################################
				//checking paid_arrears
				$sql = "select * from paid_arrears where student_pid=? and date like ?";
				$result = $this->con->prepare($sql);
				$result->execute(array($pid,$allDays[$i]."%"));
				$amountPaid = 0.0;
				$paDate = null;
				$paAcyear = null;
				$paTerm = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amountPaid += floatval($row['amount']);
					$paDate = $this->getDay($row['date']);
					$paAcyear = $row['acyear'];
					$paTerm = $row['term'];
				}
				if($amountPaid > 0){
					$balance = $this->getBalance($paDate, $studentDetails[0], $paAcyear, $paTerm);
					if($paDate == $pDate){
						//don't display balance
						$data.="<tr><td><center></center></td><td><center>".$paDate."</center></td><td>Payment of Arrears</td><td><center>0.00</center></td><td><center>".$this->formatNumber($amountPaid)."</center></td><td></td></tr>";
					}else{
						$data.="<tr><td><center>".$count."</center></td><td><center>".$paDate."</center></td><td>Payment of Arrears</td><td><center>0.00</center></td><td><center>".$this->formatNumber($amountPaid)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}	
					$totalDebit += $amountPaid;
					$totalBalance = $balance;
				}
			}

			$data.="<tr><td colspan='3'><b>Total</b></td><td><center><b>".$this->formatNumber($totalCredit)."</b></center></td><td><center><b>".$this->formatNumber($totalDebit)."</b></center></td><td><center><b>".$this->formatNumber($totalBalance)."</b></center></td></tr>";
			$data.="</tbody></table>";
			return $data;
		}

		function getBalance($date,$id,$acyear,$term){
			$date = $this->sanitize($date);
			$id = $this->sanitize($id);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);

			$amountPaying = $this->getPayableFees($id, $acyear, $term);
			//getting amount that was paid 
			$studentDetails = $this->getFullDetailsId($id, "students");
			$sql = "select amount from payments where student_pid=? and acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($studentDetails[1],$acyear,$term));
			$amount = 0.0;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$cDate = $this->getDay($date);
				if($cDate <= $date){
					$amount += floatval($row['amount']);	
				}
			}

			//arrears
			$sql = "select amount from paid_arrears where student_pid=? and acyear=? and term=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($studentDetails[1],$acyear,$term));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$cDate = $this->getDay($date);
				if($cDate <= $date){
					$amount += floatval($row['amount']);	
				}
			}

			return floatval($amountPaying - $amount);
		}

		function loadLedger2(){
			$class = $this->sanitize($_POST['class']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<center>
					<embed src='print.php?report&ledgers&class=".$class."&acyear=".$acyear."&term=".$term."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadLedger(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$term = $this->getFullDetailsId($term, "term");
			$class = $this->sanitize($_GET['class']);
			if($class=="0"){
				$sql="select * from students where status=1";
				$res=$this->con->query($sql);
				$displayClass = "";
			}else{
				$sql = "select * from students where class=? and status=1";
				$res = $this->con->prepare($sql);
				$res->bindParam("s",$class);
				$res->execute(array($class));
				$classDetails = $this->getFullDetailsId($class, "classes");
				$displayClass = "<b>Class:</b> &nbsp;".$classDetails[2]."&nbsp;&nbsp;";
			}
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Class Ledger</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>" . $displayClass ."<b>Academic Year:</b> ".$acyear[1]."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Fees(GH&cent;)</center></th><th><center>Amount Paid(GH&cent;)</center></th><th><center>Balance</center></th></tr></thead>";
			$data.="<tbody>";
			$count=1;
			$termFees = 0.0;
			$paidFees = 0.0;
			$balance = 0.0;
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$id = $row['id'];
				$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
				$class=0;
				//loading current fees payable
				$sql="select * from billing where term=? and acyear=? and class=? order by category";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$term[0]);
				$result->bindParam("s",$acyear[0]);
				$result->bindParam("s",$class);
				$result->execute(array($term[0],$acyear[0],$class));
				//performing data crunching to compute arrears
				$amount=0.00;
				while($row=$result->fetch(PDO::FETCH_ASSOC)){
					$amount+=floatval($row['amount']);
					$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
					$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					//$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
					//$count++;
				}

				//loading special fees
				$sql="select * from billing where term=? and acyear=? and class=? order by category";
				$result=$this->con->prepare($sql);
				$result->bindParam("s",$term[0]);
				$result->bindParam("s",$acyear[0]);
				$result->bindParam("s",$studentDetails[3]);
				$result->execute(array($term[0],$acyear[0],$studentDetails[3]));
				while($row=$result->fetch(PDO::FETCH_ASSOC)){
					$amount+=floatval($row['amount']);
					$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
					$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					//$data.="<tr><td><center>".$count."</center></td><td>".$billing_item[1]."</td><td style='text-align: right;'><center>".$this->formatNumber($row['amount'])."</center></td><td><center>".$billing_category[1]."</center></td></tr>";
					//$count++;
				}



				// //total amount
				// $data.="<tr><td></td><td><b>Total Amount(GH&cent;)</b></td><td><center><b>".floatval($amount)."</b></center></td><td></td></tr>";

					//computing arrears
				$arrears = $this->computeArrears($studentDetails[0]);
				//if(!$arrears==0){
					$arrears=$this->formatNumber($arrears);
					$amount+=$this->formatNumber($arrears);
					//$data.="<tr><td><center>".$count."</center></td><td><b>Arrears</b></td><td><center>".$arrears."</center></td><td></td></tr>";
				//}
				$amountPaid = $this->getPaidFees($id, $acyear[0], $term[0]);
				$deficit = $this->formatNumber(floatval($amount-$amountPaid));
				$termFees += $amount;
				$paidFees += $amountPaid;
				$balance += $deficit;
				//if(intval($deficit) > 0){
					//preview student
					$studentClass = $studentDetails[3]; 
					$classDetails = $this->getFullDetailsId($studentClass, "classes");
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$amount."</center></td><td><center>".$amountPaid."</center></td><td><center>".$deficit."</center></td></tr>";
				//}
				// $data.="<tr style='background-color: #2980b9; color: #fff;'><td></td><td><b>Total Amount(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amount)."</b></center></td><td></td></tr>";
				// $data.="<tr style='background-color: #2ecc71; color: #fff;'><td></td><td><b>Amount Paid(GH&cent;)</b></td><td><center><b>".$this->formatNumber($amountPaid)."</b></center></td><td></td></tr>";
				// $data.="<tr style='background-color: #e74c3c; color: #fff;'><td></td><td><b>Amount Remaining(GH&cent;)</b></td><td><center><b>".$this->formatNumber($deficit)."</b></center></td><td></td></tr>";
				$count++;
			}
			$data.="<tr><td colspan='4'><b>Total</b></td><td><center><b>".$this->formatNumber($termFees)."</b></center></td><td><center><b>".$this->formatNumber($paidFees)."</b></center></td><td><center><b>".$this->formatNumber($balance)."</b></center></td></tr>";
			$data.="</tbody>";
			$data.="</table>";
			return $data;
			//echo "<script>$('#loaderView').hide();</script>";
		}

		function loadClassSummary2(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<center>
					<embed src='print.php?report&classSummary&acyear=".$acyear."&term=".$term."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadClassSummary(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$term = $this->getFullDetailsId($term, "term");
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Class Ledger</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'><b>Academic Year:</b> ".$acyear[1]."&nbsp;&nbsp;<b>Term:</b> ".$term[1]."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$sql1="select * from classes where status=1";
			$result1=$this->con->query($sql1);
			// $data="<div id='loaderView' style='text-align: center;'><center><div style='text-align: center;' class='loader'></div><br/><b>Loading Arrears.. Please wait..</b></center><br/></div>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Expected Amount(GH&cent;)</center></th><th><center>Amount Paid(GH&cent;)</center></th><th><center>Balance</center></th></tr></thead>";
			$data.="<tbody>";
			$count=1;
			$expectedFees = 0.0;
			$totalPaid = 0.0;
			$balance = 0.0;
			while($row1=$result1->fetch(PDO::FETCH_ASSOC)){
				$sql="select * from students where status=1 and class=?";
				$res=$this->con->prepare($sql);
				$res->bindParam("s",$row1["id"]);
				$res->execute(array($row1["id"]));
				$amount=0.00;
				$arrears=0.00;
				$deficit=0.00;
				$amountPaid=0.00;
				while($row=$res->fetch(PDO::FETCH_ASSOC)){
					$id = $row['id'];
					$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
					$acyear = $this->getAcyear(); //getting academic year
					$term = $this->getTerm(); //getting academic term
					$class=0;
					//loading current fees payable
					$sql="select * from billing where term=? and acyear=? and class=? order by category";
					$result=$this->con->prepare($sql);
					$result->bindParam("s",$term[0]);
					$result->bindParam("s",$acyear[0]);
					$result->bindParam("s",$class);
					$result->execute(array($term[0],$acyear[0],$class));
					//performing data crunching to compute arrears
					while($row=$result->fetch(PDO::FETCH_ASSOC)){
						$amount+=floatval($row['amount']);
						$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
						$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					}

					//loading special fees
					$sql="select * from billing where term=? and acyear=? and class=? order by category";
					$result=$this->con->prepare($sql);
					$result->bindParam("s",$term[0]);
					$result->bindParam("s",$acyear[0]);
					$result->bindParam("s",$studentDetails[3]);
					$result->execute(array($term[0],$acyear[0],$studentDetails[3]));
					while($row=$result->fetch(PDO::FETCH_ASSOC)){
						$amount+=floatval($row['amount']);
						$billing_item = $this->getFullDetailsId($row['item'], "billing_item");
						$billing_category = $this->getFullDetailsId($row['category'], "billing_category");
					}


					//computing arrears
					$arrears = $this->computeArrears($studentDetails[0]);
					$arrears=$this->formatNumber($arrears);
					$amount+=$this->formatNumber($arrears);
						
					$amountPaid = $this->getPaidFees($id, $acyear[0], $term[0]);
					$deficit = $this->formatNumber(floatval($amount-$amountPaid));
					//preview student
					$studentClass = $studentDetails[3]; 
					$classDetails = $this->getFullDetailsId($studentClass, "classes");
				}
				$data.="<tr><td><center>".$count."</center></td><td>".$row1['name']."</td><td><center>".$amount."</center></td><td><center>".$amountPaid."</center></td><td><center>".$deficit."</center></td></tr>";
				$expectedFees += $amount;
				$totalPaid += $amountPaid;
				$balance += $deficit;
				$count++;
			}
			$data.="<tr><td colspan='2'><b>Total</b></td><td><center><b>".$this->formatNumber($expectedFees)."</b></center></td><td><center><b>".$this->formatNumber($totalPaid)."</b></center></td><td><center><b>".$this->formatNumber($balance)."</b></center></td></tr>";
			$data.="</tbody>";
			$data.="</table>";
			return $data;
			//echo "<script>$('#loaderView').hide();</script>";
		}

		function loadDailyAccounts(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$date = $this->sanitize($_POST['date']);
			$data="<center>
					<embed src='print.php?report&dailyAccounts&acyear=".$acyear."&term=".$term."&date=".$date."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;
		}

		function loadDailyAccountsReport(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$date = $this->sanitize($_GET['date']);
			$acyear = $this->getFullDetailsId($acyear,"acyear");
			$term = $this->getFullDetailsId($term, "term");
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Day Book</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'><b>Academic Year:</b> ".$acyear[1]."&nbsp;&nbsp;&nbsp;&nbsp;<b>Term:</b> ".$term[1]." &nbsp;&nbsp;&nbsp;&nbsp;<b>Date:</b> ".$date."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Date</center></th><th><center>Particulars/Details</center></th><th><center>Amount (GH&cent;)</center></th></tr></thead>";
			$data.="<tbody>";
			
			//getting all payments with such date
			$student_pid = array();
			$count = 0;
			//checking payments
			$sql = "select * from payments where date like ?";
			$result =  $this->con->prepare($sql);
			$result->execute(array($date."%"));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$student_pid[$count] = $row['student_pid'];
				$count++;
			}

			//checking paid arrears
			$sql = "select * from paid_arrears where date like ?";
			$result = $this->con->prepare($sql);
			$result->execute(array($date."%"));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				if(!in_array($row['student_pid'], $student_pid)){
					$student_pid[$count] = $row['student_pid'];
					$count++;
				}
			}

			// looping through all the student_pid
			$count=1;
			$totalAmount = 0.0;
			for($i = 0; $i < sizeof($student_pid); $i++){
				$currentPid = $student_pid[$i];
				$studentDetails = $this->getFullDetailsPid($currentPid, "students");
				$fullname = $studentDetails[4]. " " . $studentDetails[6] . " " . $studentDetails[5];
				//search through payments
				$amount = 0.0;
				$item = 0;
				$details = null;
				$sql = "select * from payments where student_pid=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentPid);
				$result->execute(array($currentPid));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amount += floatval($row['amount']);
					$itemDetails = $this->getFullDetailsId($row['item'], "billing_item");
					if($item==0){
						$details = $itemDetails[1];
					}else{
						$details = "," . $item;
					}
					$item++;
				}

				//searching through paid arrears
				$sql = "select * from paid_arrears where student_pid=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentPid);
				$result->execute(array($currentPid));
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amount += floatval($row['amount']);
					$itemDetails = $this->getFullDetailsId($row['item'], "billing_item");
					if($item==0){
						$details = $itemDetails[1];
					}else{
						$details = "," . $item;
					}
					$item++;
				}

				$totalAmount += floatval($amount);
				//displaying data
				$data.="<tr><td><center>".$count."</center></td><td><center>".$date."</center></td><td>".$fullname." (".$details.")</td><td><center>".$this->formatNumber($amount)."</center></td></tr>";
				$count++;
			}
			$data.="<tr><td colspan='3'><b>Daily Total:</b></td><td><center><b>".$this->formatNumber($totalAmount)."</b></center></td></tr>";

			$data.="</tbody></table>";
			return $data;
		}

		function loadArrearsTermly2(){
			$allTerm = $this->sanitize($_POST['allTerm']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$data="<center>
					<embed src='print.php?report&arrearsTermly&allTerm=".$allTerm."&acyear=".$acyear."&term=".$term."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadArrearsTermly(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$allTerm = $this->sanitize($_GET['allTerm']);
			$term = $this->getFullDetailsId($term, 'term');
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			if($allTerm == "0"){
				$displayMsg = "<b>Term: </b> ".$term[1]."&nbsp;&nbsp;";
			}else{
				$displayMsg = "All Terms&nbsp;&nbsp;";
				$term[0] = 0;
			}
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Arrears From Previous Term(s)</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>" . $displayMsg ."<b>Academic Year:</b> ".$acyear[1]."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Amount Paid(GH&cent;)</center></th></tr></thead>";
			$data.="<tbody>";
			$totalSum = 0.0;

			$sql="select * from students where status=1";
			$res=$this->con->query($sql);
			$count=1;
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$id = $row['id'];
				$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
				$arrears = $this->computeArrearsAcyearTerm($studentDetails[0],$acyear[0],$term[0]);
				$arrears=$this->formatNumber($arrears);

				if(floatval($arrears) > 0){
					//preview student
					$studentClass = $studentDetails[3]; 
					$classDetails = $this->getFullDetailsId($studentClass, "classes");
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$arrears."</center></td></tr>";
					$totalSum += floatval($arrears);
					$count++;
				}
			}
			$data.="<tr><td colspan='4'><b>Total</b></td><td><center><b>".$this->formatNumber($totalSum)."</b></center></td></tr>";
			$data.="</tbody></table>";
			return $data;

		}

		function loadArrearsYearly2(){
			$allYears = $this->sanitize($_POST['allYears']);
			$acyear = $this->sanitize($_POST['acyear']);
			$data="<center>
					<embed src='print.php?report&arrearsYearly&allYears=".$allYears."&acyear=".$acyear."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadArrearsYearly(){
			$acyear = $this->sanitize($_GET['acyear']);
			$allYears = $this->sanitize($_GET['allYears']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			if($allYears == "0"){
				$displayMsg = "<b>Academic Year:</b> ".$acyear[1];
			}else{
				$displayMsg = "All Previous Academic Year(s) - ".$acyear[1];
			}
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Arrears From Previous Academic Year(s)</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>" . $displayMsg ."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Class</center></th><th><center>Index Number</center></th><th><center>Full Name</center></th><th><center>Amount Paid(GH&cent;)</center></th></tr></thead>";
			$data.="<tbody>";

			$totalSum = 0.0;

			$sql="select * from students where status=1";
			$res=$this->con->query($sql);
			$count=1;
			while($row=$res->fetch(PDO::FETCH_ASSOC)){
				$id = $row['id'];
				$studentDetails=$this->getFullDetailsId($id, "students"); //getting student details
				$arrears = $this->computeArrearsPreviousAcyear($studentDetails[0], $acyear[0], $allYears);
				$arrears=$this->formatNumber($arrears);

				if(floatval($arrears) > 0){
					//preview student
					$studentClass = $studentDetails[3]; 
					$classDetails = $this->getFullDetailsId($studentClass, "classes");
					$data.="<tr><td><center>".$count."</center></td><td>".$classDetails[2]."</td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$arrears."</center></td></tr>";
					$totalSum += floatval($arrears);
					$count++;
				}
			}

			$data.="<tr><td colspan='4'><b>Total</b></td><td><center><b>".$this->formatNumber($totalSum)."</b></center></td></tr>";
			$data.="</tbody></table>";
			return $data;
		}

		function admissionList(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$allTerm = $this->sanitize($_POST['allTerm']);
			$admissionClass = $this->sanitize($_POST['admissionClass']);
			$allClass = $this->sanitize($_POST['allClasses']);
			$data="<center>
					<embed src='print.php?report&admissionList=y&acyear=".$acyear."&term=".$term."&allTerm=".$allTerm."&admissionClass=".$admissionClass."&allClass=".$allClass."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;
		}
		
		function admissionList2(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$allTerm = $this->sanitize($_GET['allTerm']);
			$admissionClass = $this->sanitize($_GET['admissionClass']);
			$allClasses = $this->sanitize($_GET['allClasses']);
			if($allTerm == "1" && $allClasses == "0"){
				$sql="select * from students where acyear=? and admission_class=? order by class";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$admission_class);
				$result->execute(array($acyear,$admission_class));
			}elseif($allTerm == "0" && $allClasses == "0"){
				$sql="select * from students where acyear=? and admission_term=? and admission_class=? order by class";	
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->bindParam("s",$admissionClass);
				$result->execute(array($acyear,$term,$admissionClass));
			}elseif($allTerm == "0" && $allClasses == "1"){
				$sql="select * from students where acyear=? and admission_term=? order by class";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->execute(array($acyear,$term));
			}elseif($allTerm == "1" && $allClasses == "1"){
				$sql="select * from students where acyear=? order by class";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->execute(array($acyear));
			}else{
				$sql="select * from students where acyear=? and admission_term=? and admission_class=? order by class";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->bindParam("s",$admissionClass);
			}

			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 20px; padding-left: 14px;'><u>Admission List</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;'>";
			$data.="<thead><tr><th>No.</th><th>Student ID</th><th>Full Name</th><th>Class Admitted</th><th>Previous School</th><th>Admission Date</th><th>Admission Fees</th></tr></thead><tbody>";
			$count=1;
			$amount = 0.0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$amount += $this->formatNumber($row['admission_fee']);
				$classes=$this->getFullDetailsId($row['class'], "classes");
				$admitted_class = $this->getFullDetailsId($row['admission_class'], "classes");
				$data.="<tr><td>".$count."</td><td>".$row['student_id']."</td><td>".$row['first_name']." ".$row['other_names']." " .$row['surname']."</td><td>".$admitted_class[2]."</td><td>".$row['previous_school']."</td><td>".$row['date']."</td><td>".$this->formatNumber($row['admission_fee'])."</td></tr>";
				$count++;
			}
			$number = $count-1;

			$data.="</tbody></table>";
			//$data.="<hr style='padding: 0px; margin: 0px;'>";
			$data.="<table border='0' style='font-size: 12px;'>
						<tr><td>Total Number:</td><td><b>".$number."</b></td><td style='padding-left: 580px;'><b>".$this->formatNumber($amount)."</b></td></tr>
					</table>";
			$data.="<div class='row' style='padding-top: 5px;position: absolute;right: 0; bottom: 0;left:0;'>
					<center>
						<hr style='padding: 0px; margin: 0px;'>
						<h5 style='margin: 0px; padding: 0px;font-weight: normal;'>Powered by GOFIKE Systems (0208744604, 0249436973, 0205737153)&nbsp;&nbsp; www.gofikesystems.net</h5>
					</center>
				</div>";
			return $data;
		}

		function loadAllLedger(){
			$fullname = $this->sanitize($_POST['fullname']);
			$student_id = $this->sanitize($_POST['student_id']);
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$allTerm = $this->sanitize($_POST['allTerm']);
			$studentDetails = $this->getFullDetailsIndexNumber($student_id, "students");
			$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];

			//getting all receipt numbers
			if($allTerm == "0"){
				$sql="select distinct receipt_no from payments where student_pid=? and acyear=? and term=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->execute(array($studentDetails[1],$acyear,$term));
			}else{
				$sql="select distinct receipt_no from payments where student_pid=? and acyear=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->execute(array($studentDetails[1],$acyear));
			}

			$receipts = array();
			$count = 0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$receipts[$count] = $row['receipt_no'];
				$count++;
			}

			//checking paid arrears
			if($allTerm == "0"){
				$sql = "select distinct receipt_no from paid_arrears where student_pid=? and acyear=? and term=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->execute(array($studentDetails[1],$acyear,$term));
			}else{
				$sql = "select distinct receipt_no from paid_arrears where student_pid=? and acyear=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->execute(array($studentDetails[1],$acyear));
			}

			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				if(in_array($row['receipt_no'], $receipts)){
					continue;
				}else{
					$receipts[$count] = $row['receipt_no'];
					$count++;
				}
			}

			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'><thead><tr><th><center>No.</center></th><th><center>Date Paid</center></th><th><center>Amount Paid(GH&cent;)</center></th><th><center>Receipt No</center></th><th><center>Paid by</center></th><th><center>Cashier</center></th></tr></thead><tbody>";
			$count=1;
			for($i=0; $i < sizeof($receipts); $i++){
				//looping through all receipts
				$currentReceiptNo = $receipts[$i];
				$sql = "select * from payments where receipt_no=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentReceiptNo);
				$result->execute(array($currentReceiptNo));
				$amount = 0;
				$acyear = null;
				$term = null;
				$cashier = array();
				$date = null;
				$paid_by = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amount += floatval($row['amount']);
					$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
					$term = $this->getFullDetailsId($row['term'], "term");
					$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
					$date = $row['date'];
					$paid_by = $row['paid_by'];
				}

				if(floatval($amount)==0.0 || floatval($amount)==0){
						//it is an arrears paid
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
						$acyear = $this->getFullDetailsId($arrearsDetails[4], "acyear");
						$term = $this->getFullDetailsId($arrearsDetails[5], "term");
						$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
						$date = $arrearsDetails[6];
						$paid_by = $arrearsDetails[9];
				}else{
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
				}

				$data.="<tr><td><center>".$count."</center></td><td><center>".$date."</center></td><td><center>".$this->formatNumber($amount)."</center></td><td><center>".$currentReceiptNo."</center></td><td>".$paid_by."</td><td>".$cashier[2]."</td></tr>";
			}

			$data.="</tbody></table>";
			$data.="<script>$('#tableList').DataTable({responsive: true});$('#fullname').attr('value','".$fullname."');</script>";
			echo $data;
		}

		function loadAllLedger2(){
			$student_id = $this->sanitize($_GET['student_id']);
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$allTerm = $this->sanitize($_GET['allTerm']);
			$studentDetails = $this->getFullDetailsIndexNumber($student_id, "students");


			//getting all receipt numbers
			if($allTerm == "0"){
				$sql="select distinct receipt_no from payments where student_pid=? and acyear=? and term=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->execute(array($studentDetails[1],$acyear,$term));
			}else{
				$sql="select distinct receipt_no from payments where student_pid=? and acyear=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->execute(array($studentDetails[1],$acyear));
			}

			$receipts = array();
			$count = 0;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$receipts[$count] = $row['receipt_no'];
				$count++;
			}

			//checking paid arrears
			if($allTerm == "0"){
				$sql = "select distinct receipt_no from paid_arrears where student_pid=? and acyear=? and term=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->bindParam("s",$term);
				$result->execute(array($studentDetails[1],$acyear,$term));
			}else{
				$sql = "select distinct receipt_no from paid_arrears where student_pid=? and acyear=? order by date desc";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$studentDetails[1]);
				$result->bindParam("s",$acyear);
				$result->execute(array($studentDetails[1],$acyear));
			}

			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				if(in_array($row['receipt_no'], $receipts)){
					continue;
				}else{
					$receipts[$count] = $row['receipt_no'];
					$count++;
				}
			}
			$fullname = $studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5];
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Payment History of ".$fullname."</u></h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'><thead><tr><th><center>No.</center></th><th><center>Date Paid</center></th><th><center>Amount Paid(GH&cent;)</center></th><th><center>Receipt No</center></th><th><center>Paid by</center></th><th><center>Cashier</center></th></tr></thead><tbody>";
			$count=1;
			for($i=0; $i < sizeof($receipts); $i++){
				//looping through all receipts
				$currentReceiptNo = $receipts[$i];
				$sql = "select * from payments where receipt_no=?";
				$result = $this->con->prepare($sql);
				$result->bindParam("s",$currentReceiptNo);
				$result->execute(array($currentReceiptNo));
				$amount = 0;
				$acyear = null;
				$term = null;
				$cashier = array();
				$date = null;
				$paid_by = null;
				while($row = $result->fetch(PDO::FETCH_ASSOC)){
					$amount += floatval($row['amount']);
					$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
					$term = $this->getFullDetailsId($row['term'], "term");
					$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
					$date = $row['date'];
					$paid_by = $row['paid_by'];
				}

				if(floatval($amount)==0.0 || floatval($amount)==0){
						//it is an arrears paid
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
						$acyear = $this->getFullDetailsId($arrearsDetails[4], "acyear");
						$term = $this->getFullDetailsId($arrearsDetails[5], "term");
						$cashier = $this->getFullDetailsPid($row['cashier_pid'], "login_details");
						$date = $arrearsDetails[6];
						$paid_by = $arrearsDetails[9];
				}else{
						$arrearsDetails = $this->getArrearsDetails($studentDetails[1],$currentReceiptNo);
						$amount += $arrearsDetails[1];
				}

				$data.="<tr><td><center>".$count."</center></td><td><center>".$date."</center></td><td><center>".$this->formatNumber($amount)."</center></td><td><center>".$currentReceiptNo."</center></td><td>".$paid_by."</td><td>".$cashier[2]."</td></tr>";
			}

			$data.="</tbody></table>";
			//$data.="<script>$('#tableList').DataTable({responsive: true});</script>";
			return $data;
		}

		function loadRepeated(){
			$acyear = $this->sanitize($_POST['acyear']);
			$class = $this->sanitize($_POST['class']);
			$data="<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'><thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Class Repeated</center></th><th><center>Gender</center></th></tr></thead><tbody>";
			$sql="select * from repetition where acyear=? and next=? order by date desc";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$class);
			$result->execute(array($acyear,$class));
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
				$class = $this->getFullDetailsId($row['next'], "classes");
				$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
				$term = $this->getFullDetailsId($row['term'], "term");
				$gender = $this->getFullDetailsId($studentDetails[9], "gender");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$class[2]."</center></td><td><center>".$gender[1]."</center></td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			$data.="<script>$('#tableList').DataTable({responsive:true});</script>";
			echo $data;
		}

		function loadRepeated2(){
			$acyear = $this->sanitize($_GET['acyear']);
			$class = $this->sanitize($_GET['class']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$class = $this->getFullDetailsId($class, "classes");
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>List of Repeaters</u></h5>
				</p>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Class:</b> ".$class[2]." &nbsp; <b>Academic Year:</b> ".$acyear[1]."</p><br>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;padding-top: 12px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'><thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Class Repeated</center></th><th><center>Gender</center></th></tr></thead><tbody>";
			$sql="select * from repetition where acyear=? and next=? order by date desc";
			$result=$this->con->prepare($sql);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$class);
			$result->execute(array($acyear,$class));
			$count=1;
			while($row=$result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
				$class = $this->getFullDetailsId($row['next'], "classes");
				$acyear = $this->getFullDetailsId($row['acyear'], "acyear");
				$term = $this->getFullDetailsId($row['term'], "term");
				$gender = $this->getFullDetailsId($studentDetails[9], "gender");
				$data.="<tr><td><center>".$count."</center></td><td><center>".$studentDetails[2]."</center></td><td>".$studentDetails[4]." ".$studentDetails[6]." ".$studentDetails[5]."</td><td><center>".$class[2]."</center></td><td><center>".$gender[1]."</center></td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			//$data.="<script>$('#tableList').DataTable({responsive:true});</script>";
			return $data;
		}

		function loadDismissed2(){
			$acyear = $this->sanitize($_GET['acyear']);
			$classes = $this->sanitize($_GET['class']);
			$class = $this->getFullDetailsId($classes, "classes");
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 17px; padding-left: 70px;'><u>Dismissed/Withdrawn Students</u></h5>
				</p>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'>";
				if($class=="0"){
					$data.="";
				}else{
					$data.="<b>Class:</b> ";
					$data.=$class[2]."&nbsp;&nbsp;";
				}
				$data.="<b>Academic Year:</b> ".$acyear[1]."</p><br>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 13px;padding-top: 12px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;' class='table table-bordered table-condensed table-hover table-striped' id='tableList'><thead><tr><th><center>No.</center></th><th><center>Student ID</center></th><th><center>Full Name</center></th><th><center>Gender</th><th><center>Date</center></th></th><th><center>Last Class</center></th><th><center>Reason</center></th></tr></thead><tbody>";

			$sql = "select * from dismissal where acyear=? order by date desc";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear[0]);
			$result->execute(array($acyear[0]));
			$count=1;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$studentDetails = $this->getFullDetailsPid($row['pid'], "students");
				$fullname = $studentDetails[4]. " ".$studentDetails[6]." ".$studentDetails[5];
				$gender = $this->getFullDetailsId($studentDetails[9], "gender");
				$class = $this->getFullDetailsId($studentDetails[3],"classes");
				if($classes!="0"){
					if($class[0] != intval($classes)){
						continue;
					}
				}
				$data.="<tr><td><center>".$count."</center></td><td><center>".$studentDetails[2]."</center></td><td>".$fullname."</td><td><center>".$gender[1]."</center></td><td><center>".$row['date']."</center></td><td><center>".$class[2]."</center></td><td>".$row['reason']."</td></tr>";
				$count++;
			}

			$data.="</tbody></table>";
			return $data;
		}

		function loadBillingItemsReport2(){
			$acyear = $this->sanitize($_POST['acyear']);
			$term = $this->sanitize($_POST['term']);
			$item = $this->sanitize($_POST['item']);
			$data="<center>
					<embed src='print.php?report&billingItem&acyear=".$acyear."&term=".$term."&item=".$item."' width='900' height='700' type='application/pdf'>
				</center>";
			echo $data;	
		}

		function loadBillingItemsReport(){
			$acyear = $this->sanitize($_GET['acyear']);
			$term = $this->sanitize($_GET['term']);
			$item = $this->sanitize($_GET['item']);
			$acyear = $this->getFullDetailsId($acyear, "acyear");
			$term = $this->getFullDetailsId($term, "term");
			$item = $this->getFullDetailsId($item, "billing_item");

			$data="<div class='row' style='padding: 0px; margin: 0px;'>
				<img src='images/crest.jpg' style='margin-top: 0px; padding-top: 0px;float: left;width: 60px; height: auto;'/>
				<p style='float: left;font-size: 15px;padding-left: 10px; padding-top: 0px; margin-top: 0px;'><b>Stepping Stone School Complex</b>
					<h5 style='font-size: 20px; padding-left: 14px;'><u>Payment Details of: </u> ".$item[1]."</h5>
				</p>
			</div><div style='clear: both;margin: 0px; padding: 0px;'></div>";
			$data.="<p style='font-size: 12px;'><b>Academic Year:</b> ".$acyear[1]."&nbsp;&nbsp;<b>Term:</b> ".$term[1]."</p>";
			$data.="<p style='font-size: 13px;'>As of " . date('d/m/y') ."</p>";
			$data.="<table border='1' cellpadding='2' cellspacing='1' class='table table-bordered table-striped table-condensed table-hover' id='tableList' style='font-size: 12px;width: 700px;border: 1px solid black;border-collapse: collapse;'>";
			$data.="<thead><tr><th>No.</th><th>Full Name</th><th>Class</th><th>Expected Amount(GH&cent;)</th><th>Total Paid(GH&cent;)</th><th>Balance</th></tr></thead><tbody>";

			//getting billing for that item for that particular academic year
			$sql = "select * from billing where acyear=? and term=? and item=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$acyear[0]);
			$result->bindParam("s",$term[0]);
			$result->bindParam("s",$item[0]);
			$result->execute(array($acyear[0],$term[0],$item[0]));
			$count=1;
			$amountExpectSum = 0.0;
			$balanceSum = 0.0;
			$totalPaidSum = 0.0;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amountExpect = floatval($row['amount']);
				$amountExpectSum += $amountExpect;
				//checking if it's a general fee or  class based
				if($row['class']==0){
					//general fee
					$query = "select * from students where status=1";
					$res = $this->con->query($query);
					while($d = $res->fetch(PDO::FETCH_ASSOC)){
						$class = $this->getFullDetailsId($d['class'], "classes");
						$totalPaid = $this->getAmountPaidBillingItem($item[0], $acyear[0], $term[0], $d['pid']);
						$totalPaidSum += $totalPaid;
						$balance = floatval($amountExpect) - floatval($totalPaid);
						$balanceSum += $balance;
						$fullname = $d['first_name']." ".$d['other_names']." ".$d['surname'];
						$data.="<tr><td><center>".$count."</center></td><td>".$fullname."</td><td>".$class[2]."</td><td><center>".$this->formatNumber($amountExpect)."</center></td><td><center>".$this->formatNumber($totalPaid)."</center></td><td><center>".$this->formatNumber($balance)."</center></td></tr>";
						$count++;
					}
				}
			}
			$data.="<tr><td colspan='3'><b>Total</b></td><td><center><b>".$this->formatNumber($amountExpectSum)."</b></center></td><td><center><b>".$this->formatNumber($totalPaidSum)."</b></center></td><td><center><b>".$this->formatNumber($balanceSum)."</b></center></td></tr>";
			$data.="</tbody></table>";
			return $data;
		}

		function getAmountPaidBillingItem($item,$acyear,$term,$student_pid){
			$item = $this->sanitize($item);
			$acyear = $this->sanitize($acyear);
			$term = $this->sanitize($term);
			$student_pid = $this->sanitize($student_pid);
			$amount = 0.0;
			$sql = "select * from payments where item=? and acyear=? and term=? and student_pid=?";
			$result = $this->con->prepare($sql);
			$result->bindParam("s",$item);
			$result->bindParam("s",$acyear);
			$result->bindParam("s",$term);
			$result->bindParam("s",$student_pid);
			$result->execute(array($item,$acyear,$term,$student_pid));
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$amount += floatval($row['amount']);
			}
			$amount += $this->get_paid_arrears($student_pid, $acyear, $term);
			return floatval($amount);
		}

		function loadAllPermissions(){
			$login = $this->getFullDetailsId($_SESSION['useredit'],"login");
			$loginDetails = $this->getFullDetailsPid($login[1],"login_details");

			$data="<div class='row' style='margin: 15px;'>";
			$data.="<form method='post' action='#' class='form'>";
			//displaying table
			$data.="<div class='row'>";
			$data.="<table class='table table-bordered table-hover table-condensed table-striped' id='tableList'>";
			$data.="<thead><tr><th><center>No.</center></th><th><center>Category</center></th><th><center>Authorized</center></th></tr></thead>";
			$data.="<tbody>";

			//looping through all permissions
			$sql = "select * from permissions order by id";
			$result = $this->con->query($sql);
			$count = 1;
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$data.="<tr><td><center>".$count."</center></td><td>".$row['name']."</td><td><center>";
				$authorize = $this->checkAuthorize($row['id'], $login[1]);

				$data.="<div class='form-group'>
								<select id='".$row['id']."' class='form-control' name='".$row['id']."'>";
				if($authorize == 1){
					$data.="<option value='1'>Yes</option>";
					$data.="<option value='0'>No</option>";
				}else{
					$data.="<option value='0'>No</option>";
					$data.="<option value='1'>Yes</option>";
				}				
				$data.="</select></div>";

				$data.="</center></td></tr>";
				$count++;
			}

			$data.="</tbody>";
			$data.="</table>";
			$data.="</div>"; //end of table row

			//submit row 
			$data.="<div class='row'>
					<center><button type='submit' name='updateBtn' class='btn btn-xs btn-success tooltip-bottom' title='Update Permissions'><span class='glyphicon glyphicon-plus'></span> Update Permissions</button></center>
				</div>";

			$data.="</form>";
			$data.="</div>";
			echo $data;
		}

		function checkAuthorize($id,$pid){
			$id = $this->sanitize($id);
			$pid = $this->sanitize($pid);
			//check whether user is admin or ordinary
			$login = $this->getFullDetailsPid($pid, "login");
			if(intval($login[4])==0){
				return 1;
			}else{
				$sql = "select * from user_permissions where permission=? and pid=?";
				$result = $this->con->prepare($sql);
				$result->execute(array($id,$pid));
				if($result->rowCount() >= 1){
					return 1;
				}else{
					return 0;
				}
			}
		}

		function delAuthorize($permission,$pid){
			$permission = $this->sanitize($permission);
			$pid = $this->sanitize($pid);
			$sql = "delete from user_permissions where permission=? and pid=?";
			$result = $this->con->prepare($sql);
			$result->execute(array($permission,$pid));
		}

		function updateAuthorize($permission,$pid){
			$permission = $this->sanitize($permission);
			$pid = $this->sanitize($pid);
			$sql = "insert into user_permissions(permission,pid) values(?,?)";
			$result = $this->con->prepare($sql);
			$result->execute(array($permission,$pid));
		}

		function updatePermissions(){
			$login = $this->getFullDetailsId($_SESSION['useredit'],"login");
			//getting all post data
			$sql = "select * from permissions order by id";
			$result = $this->con->query($sql);
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				//getting post data
				$id = $row['id'];
				$permission = intval($this->sanitize($_POST[$id]));
				if($permission == 1){
					//add/update to user_permissions
					$this->updateAuthorize($id, $login[1]);
				}else{
					//delete from user_permissions
					$this->delAuthorize($id, $login[1]);
				}
			}
			$this->displayMsg("Permissions updated", 1);
			$this->redirect("?users&permissions");
		}

		function checkPermission($url,$pid){
			$login = $this->getFullDetailsPid($pid, "login");
			$uid = intval($login[4]);
			if($uid =! 0){
				$sql = "select * from permissions where url=? limit 1";
				$result = $this->con->prepare($sql);
				$result->execute(array($url));
				$data = $result->fetch();
				if($result->rowCount() >= 1){
					//valid
					$permission = $data[0];
					$res = $this->checkAuthorize($permission, $login[1]);
					if($res == 1){
						//authorize
					}else{
						include "permissionDenied.php";
						return false;
					}
				}
			}
		}

	}//end of class

	################### number to words class ##############
	class NumbersToWords{
    
    public static $hyphen      = '-';
    public static $conjunction = ' and ';
    public static $separator   = ', ';
    public static $negative    = 'negative ';
    public static $decimal     = ' point ';
    public static $dictionary  = array(
      0                   => 'zero',
      1                   => 'one',
      2                   => 'two',
      3                   => 'three',
      4                   => 'four',
      5                   => 'five',
      6                   => 'six',
      7                   => 'seven',
      8                   => 'eight',
      9                   => 'nine',
      10                  => 'ten',
      11                  => 'eleven',
      12                  => 'twelve',
      13                  => 'thirteen',
      14                  => 'fourteen',
      15                  => 'fifteen',
      16                  => 'sixteen',
      17                  => 'seventeen',
      18                  => 'eighteen',
      19                  => 'nineteen',
      20                  => 'twenty',
      30                  => 'thirty',
      40                  => 'fourty',
      50                  => 'fifty',
      60                  => 'sixty',
      70                  => 'seventy',
      80                  => 'eighty',
      90                  => 'ninety',
      100                 => 'hundred',
      1000                => 'thousand',
      1000000             => 'million',
      1000000000          => 'billion',
      1000000000000       => 'trillion',
      1000000000000000    => 'quadrillion',
      1000000000000000000 => 'quintillion'
    );
    public static function convert($number){
      if (!is_numeric($number) ) return false;
      $string = '';
      switch (true) {
        case $number < 21:
            $string = self::$dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = self::$dictionary[$tens];
            if ($units) {
                $string .= self::$hyphen . self::$dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = self::$dictionary[$hundreds] . ' ' . self::$dictionary[100];
            if ($remainder) {
                $string .= self::$conjunction . self::convert($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = self::convert($numBaseUnits) . ' ' . self::$dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? self::$conjunction : self::$separator;
                $string .= self::convert($remainder);
            }
            break;
      }
      return $string;
    }
  }//end class
?>


