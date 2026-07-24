<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css" type="text/css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
</head>
<body>

 

    <!-- Navigation Tabs -->
   <div class="sidebar">
    <div class="tab " style="margin:50px 0 70px 0px;">
        <div class="logo-container ">
            <img src="logo2.png" alt="PSC Logo" class="logo">
            <h3 class="h3b">PEOPLES SERVICE CENTER (LARMIS), <br> BOARD OF REVENUE,SINDH<br> TANDO MUHAMMAD KHAN</h3> 
        </div>
    </div>
    <div class="tab"><a href="applicant_reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'applicant_reports.php' ? 'active' : ''; ?>">Add Applicant</a></div>
    <div class="tab"><a href="view_reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_reports.php' ? 'active' : ''; ?>">Applicants Report</a></div>
    <div class="tab"><a href="chalan_paid.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chalan_paid.php' ? 'active' : ''; ?>">Collection of Challan</a></div>
    <div class="tab"><a href="view_challan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_challan.php' ? 'active' : ''; ?>">Challan View</a></div>
    <div class="tab"><a href="monthly_paid_challan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'monthly_paid_challan.php' ? 'active' : ''; ?>">Monthly Paid Challan</a></div>
    <div class="tab"><a href="security_paper_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'security_paper_report.php' ? 'active' : ''; ?>">Security Paper Report</a></div>
    <div class="tab"><a href="security_paper_monthly_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'security_paper_monthly_report.php' ? 'active' : ''; ?>">Security Paper Monthly Report</a></div>
    <div class="tab"><a href="wasted_paper_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'wasted_paper_report.php' ? 'active' : ''; ?>">Wasted Security Paper Report</a></div>
    <div class="tab"><a href="yearly_financial_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'yearly_financial_report.php' ? 'active' : ''; ?>">Yearly Financial Report</a></div>
    <div class="tab"><a href="search.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>">Search 🔍</a></div>
</div>

<div class="page-wrapper">
