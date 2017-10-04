<?php
    require "../ssFiles/includes/functions.php";
    $ajax = new VSoft();
    if(isset($_POST['edit'])){
        //edit user details
        $ajax->verifyData($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['updateStatusPid'])){
    	$ajax->updateStatusAdminPid($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['deleteReq'])){
    	$ajax->deleteReqAdmin($_POST['pid'],$_POST['table']);
    }elseif(isset($_POST['paymentsFees']) && isset($_POST['studentId']) && isset($_POST['studentName'])){
        $ajax->processPaymentsFees($_POST['studentId'],$_POST['studentName']);
    }elseif(isset($_POST['unlock'])){
        $ajax->unlockSession($_POST['unlock']);
    }elseif(isset($_POST['lock'])){
        $ajax->lockSession();
    }elseif(isset($_POST['loadItems'])){
        $ajax->loadBillingItems();
    }elseif(isset($_POST['loadBillingItems'])){
        $ajax->loadBilling();
    }elseif(isset($_POST['bursary'])){
        $ajax->bursary();
    }elseif(isset($_POST['verifyIndexNumber'])){
        $ajax->verifyIndexNumber();
    }elseif(isset($_POST['verifyFullName'])){
        $ajax->verifyFullName();
    }elseif(isset($_POST['editBursaryBtn'])){
        $ajax->updateBursary();
    }elseif(isset($_POST['admissionList'])){
        $ajax->admissionList();
    }elseif(isset($_POST['loadAllLedger'])){
        $ajax->loadAllLedger();
    }elseif(isset($_POST['loadRepeated'])){
        $ajax->loadRepeated();
    }elseif(isset($_POST['dismissed'])){
        $ajax->dismissedList();
    }elseif(isset($_POST['previewReceipt'])){
        $ajax->previewReceipt();
    }elseif(isset($_POST['loadDefaulters'])){
        $ajax->loadDefaulters2();
    }elseif(isset($_POST['loadLedger'])){
        $ajax->loadLedger2();
    }elseif(isset($_POST['loadClassSummary'])){
        $ajax->loadClassSummary2();
    }elseif(isset($_POST['arrearsTermly'])){
        $ajax->loadArrearsTermly2();
    }elseif(isset($_POST['arrearsYearly'])){
        $ajax->loadArrearsYearly2();
    }elseif(isset($_POST['getBillingItems'])){
        $ajax->getBillingItems();
    }elseif(isset($_POST['loadBillingItemsReport'])){
        $ajax->loadBillingItemsReport2();
    }elseif(isset($_POST['loadDailyAccounts'])){
        $ajax->loadDailyAccounts();
    }elseif (isset($_POST['loadScholarshipAccount'])) {
        $ajax->loadScholarshipAccount();
    }elseif (isset($_POST['loadScholarshipAll'])) {
        $ajax->loadScholarshipAll();
    }
?>