<?php
/**
 * Copyright Anveto AB
 * Author: Markus Tenghamn
 * Date: 10/04/15
 * Time: 02:09
 */

$modulename = "Affiliates Pro";
$moduleversion = "2.0";


function anveto_affiliate_anywhere_plus_sidebar($vars)
{
    global $modulename;
    global $moduleversion;
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $LANG = $vars['_lang'];

    $sidebar = '<span class="header">
            <img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" />
                '.$modulename.'
                </span>
    <ul class="menu">
        <li>Version: ' . $version . '</li>
        <li><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus">Settings</a></li>
        <li><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff">Affiliates</a></li>
        <li><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=banners">Banners</a></li>
    </ul>';
    return $sidebar;
}

function anveto_affiliate_anywhere_plus_config()
{
    global $modulename;
    global $moduleversion;
    $fields = array();
    $configarray = array(
        "name" => $modulename,
        "description" => "This addon allows you to use ?aff= get variable on any WHMCS page",
        "version" => $moduleversion,
        "author" => "Anveto",
        "language" => "english",
        "fields" => $fields,
    );
    return $configarray;
}

function anveto_affiliate_anywhere_plus_activate()
{
    global $modulename;
    global $moduleversion;
    $path = str_replace("/anveto_affiliate_anywhere_plus/anveto_affiliate_anywhere_plus.php", "/anveto_affiliate_anywhere/anveto_affiliate_anywhere.php", __FILE__);
    if (file_exists($path)) {
        return array(
            'status' => 'error',
            'description' => 'Please remove the outdated free version of '.$modulename.' as it may conflict with '.$modulename
        );
    }
    $val = false;
    $val2 = false;
    $val3 = false;
    if (function_exists("full_query")) {
        $val = full_query('SELECT 1 FROM mod_anveto_affiliate_anywhere_plus');
        $val2 = full_query('SELECT 1 FROM mod_anveto_affiliate_anywhere_plus_exclude');
        $val3 = full_query('SELECT 1 FROM mod_anveto_affiliate_anywhere_plus_payout');
    }

    if ($val !== FALSE && $val2 !== FALSE && $val3 !== false) {
        return array(
            'status' => 'success',
            'description' => $modulename.' has been activated.'
        );
    }
    $run = true;

    if (function_exists("full_query")) {
        if (!($val !== FALSE)) {
            $query = "CREATE TABLE mod_anveto_affiliate_anywhere_plus (id INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,domainname TEXT NOT NULL )";
            if (full_query($query)) {

            } else {
                $run = false;
            }
        }
        if (!($val2 !== FALSE)) {
            $query2 = "CREATE TABLE mod_anveto_affiliate_anywhere_plus_exclude (id INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,pid INT( 11 ) NOT NULL, term INT( 11 ) NOT NULL, currency VARCHAR( 255 ) NOT NULL, excluded INT( 11 ) NOT NULL )";
            if (full_query($query2)) {

            } else {
                $run = false;
            }
        }
        if (!($val3 !== FALSE)) {
            $query3 = "CREATE TABLE mod_anveto_affiliate_anywhere_plus_payout (id INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,pid INT( 11 ) NOT NULL, term INT( 11 ) NOT NULL, currency VARCHAR( 255 ) NOT NULL, payout VARCHAR( 255 ) NOT NULL )";
            if (full_query($query3)) {

            } else {
                $run = false;
            }
        }
    }
    if (function_exists("full_query")) {
        if ($run) {
            return array(
                'status' => 'success',
                'description' => $modulename.' has been activated.'
            );
        }

    }
    return array(
        'status' => 'error',
        'description' => 'Could not create database table'
    );
}

function anveto_affiliate_anywhere_plus_deactivate()
{
    global $modulename;
    global $moduleversion;
    return array(
        'status' => 'success',
        'description' => $modulename.' has been deactivated'
    );
}

function anveto_affiliate_anywhere_plus_upgrade($vars)
{
    global $modulename;
    global $moduleversion;
    $version = $moduleversion;
    // For future updates
}

function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
        && preg_match("/^.{1,253}$/", $domain_name) //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)); //length of each label
}

function anveto_affiliate_anywhere_plus_output($vars)
{
    global $modulename;
    global $moduleversion;
    if (isset($_GET['view']) && $_GET['view'] == "aff") {
        if (isset($_GET['affid']) && is_numeric($_GET['affid']) && $_GET['affid'] > 0) {
            // Affiliate overview
            // Affiliate id and Client Name
            // Commission Type & Commission Amount that override product settings
            // Visitors Referred - can be changed
            // Signup Date
            // Pending comission
            // Available to Withdraw Balance
            // Withdrawn amount - changable
            // Conversion Rate - Visits/signups
            // Referred signups table
            //   ID	Signup Date	Client Name 	Product/Service	Commission	Last Paid	Product Status	Manual payout
            // Pending comissions table
            //   Referral ID	Client Name	Product/Service	Product Status	Amount	Clearing Date
            // Comissions History table
            //   Date	Referral ID	Client Name	Product/Service	Product Status	Description	Amount
            // Withdrawals history table
            //   Date	Amount

            // At the bottom there is a form to Make Withdrawal Payout
            // https://i.gyazo.com/abc2fa5db313fce04ed601457c873b28.png
            $show = 10;
            $sort = 'DESC';
            $orderby = 'tblaffiliatesaccounts.id';
            $affid = $_GET['affid'];

            $table = "tblaffiliates";
            $fields = "id,date,clientid,visitors,paytype,payamount,onetime,balance,withdrawn";
            $affilliate = mysql_fetch_assoc(mysql_query("SELECT " . $fields . " FROM " . $table . " WHERE id = '".$affid."' ORDER BY date DESC LIMIT 1"));

            $signups = mysql_fetch_assoc(mysql_query("SELECT COUNT(id) as total FROM tblaffiliatesaccounts WHERE affiliateid = '" . $affid . "'"));
            $referalls = mysql_fetch_assoc(mysql_query("SELECT id,relid,lastpaid FROM tblaffiliatesaccounts INNER JOIN tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid WHERE tblaffiliatesaccounts.affiliateid = ".$affid." LIMIT ".$show));
            print_r($referalls);
            //$referralls = mysql_fetch_assoc(select_query('tblaffiliatesaccounts', 'tblaffiliatesaccounts.*,tblproducts.name,tblhosting.userid,tblhosting.domainstatus,tblhosting.amount,tblhosting.firstpaymentamount,tblhosting.regdate,tblhosting.billingcycle', array( 'affiliateid' => $affid ), $orderby, $sort, $show, 'tblhosting ON tblhosting.id=tblaffiliatesaccounts.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblclients ON tblclients.id=tblhosting.userid' ));
            $client = mysql_fetch_assoc(mysql_query("SELECT firstname, lastname FROM tblclients WHERE id = '" . $affilliate['clientid'] . "'"));
            $currencyData = getCurrency($affilliate['clientid']);

            $pending = 0; //TODO calculate this
            $withdrawbalance = 0; //TODO change this
            $commtype = ""; //TODO change this
            $payonetime = 0; //TODO change
            $commissionamnt = 0; //TODO change
            $withdrawnamnt = 0; //TODO change
            $referallcount = 0; //TODO change
            $conversionrate = 0; //TODO change
            $pendingcommissioncount = 0; //TODO change
            $token = "";
//            foreach ($referalls as $ref) {
//
//            }

            ?>
            <form method="post" action="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>">

                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr><td class="fieldlabel">Affiliate ID</td><td class="fieldarea"><?php echo $affid; ?></td><td width="20%" class="fieldlabel">Signup Date</td><td class="fieldarea"><?php echo $affilliate['date']; ?></td></tr>
                    <tr><td width="15%" class="fieldlabel">Client Name</td>
                        <td class="fieldarea"><a href="clientssummary.php?userid=<?php echo $affilliate['clientid']; ?>"><?php echo $client['firstname'] . " " . $client['lastname']; ?></a></td>
                        <td class="fieldlabel">Pending Commissions</td><td class="fieldarea"><?php echo $pending; ?></td></tr>
                    <tr><td class="fieldlabel">Commission Type</td><td class="fieldarea">
                            <label class="radio-inline"><input type="radio" name="paymenttype" value="" <?php if ($commtype == "") { echo "checked"; } ?>> Use Default</label>
                            <label class="radio-inline"><input type="radio" name="paymenttype" value="percentage" <?php if ($commtype == "percentage") { echo "checked"; } ?>> Percentage</label>
                            <label class="radio-inline"><input type="radio" name="paymenttype" value="fixed" <?php if ($commtype == "") { echo "fixed"; } ?>> Fixed Amount</label>
                        </td>
                        <td class="fieldlabel">Available to Withdraw Balance</td><td class="fieldarea"><input type="text" name="balance" size=10 value="<?php echo $withdrawbalance; ?>"></td></tr>
                    <tr><td class="fieldlabel">Commission Amount</td><td class="fieldarea"><input type="text" name="payamount" size=10 value="<?php echo $commissionamnt; ?>">
                            <label class="checkbox-inline"><input type="checkbox" name="onetime" id="onetime" value="1" <?php if ($payonetime == 1) { echo "checked"; } ?>/> Pay One Time Only</label></td>
                        <td class="fieldlabel">Withdrawn Amount</td><td class="fieldarea"><input type="text" name="withdrawn" size=10 value="<?php echo $withdrawnamnt; ?>"></td></tr>
                    <tr><td class="fieldlabel">Visitors Referred</td><td class="fieldarea"><input type="text" name="visitors" size=5 value="<?php echo $referallcount; ?>"></td>
                        <td class="fieldlabel">Conversion Rate</td><td class="fieldarea"><?php echo $conversionrate; ?></td></tr>
                </table>

                <div class="btn-container">
                    <input type="submit" value="Save Changes" class="btn btn-primary">
                    <input type="reset" value="Cancel Changes" class="btn btn-default" />
                </div>

            </form>

            <ul class="nav nav-tabs admin-tabs" role="tablist">
                <li class="active"><a href="#tab1" role="tab" data-toggle="tab" id="tabLink1">Referred Signups</a></li>
                <li><a href="#tab2" role="tab" data-toggle="tab" id="tabLink2">Pending Commissions (<?php echo $pendingcommissioncount; ?>)</a></li>
                <li><a href="#tab3" role="tab" data-toggle="tab" id="tabLink3">Commissions History</a></li>
                <li><a href="#tab4" role="tab" data-toggle="tab" id="tabLink4">Withdrawals History</a></li>
            </ul>
            <div class="tab-content admin-tabs">
                <div class="tab-pane active" id="tab1"><form method="post" action="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff">
                        <input type="hidden" name="token" value="<?php echo $token; ?>" />
                        <input type="hidden" name="action" value="edit" />
                        <input type="hidden" name="id" value="1" />

                        <table width="100%" border="0" cellpadding="3" cellspacing="0"><tr>
                                <td width="50%" align="left">3 Records Found, Page 1 of 1</td>
                                <td width="50%" align="right">Jump to Page: <select name="page" onchange="submit()"><option value="0" selected>1</option></select> <input type="submit" value="Go" class="btn btn-xs btn-default" /></td>
                            </tr></table>
                    </form>

                    <div class="tablebg">
                        <table id="sortabletbl1" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                            <tr>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=id">ID</a></th>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=regdate">Signup Date</a></th>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=clientname">Client Name</a> <img src="images/asc.gif" class="absmiddle" /></th>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=name">Product/Service</a></th>
                                <th>Commission</th>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=lastpaid">Last Paid</a></th>
                                <th><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=edit&id=1&orderby=domainstatus">Product Status</a></th>
                                <th> </th>
                                <th width="20"></th>
                            </tr>
                            <?php
                            if (count($referalls) > 0) {
                                foreach ($referalls as $ref) {
                                    echo '<tr><td>'.$ref['id'].'</td><td>27/03/2016</td><td><a href="clientssummary.php?userid=1654">Markus Tenghamn</a></td><td><a href="clientshosting.php?userid=1654&id=153">Basic</a><br>$10.00 USD Monthly</td><td>$2.00 USD</td><td>27/03/2016</td><td>Cancelled</td><td><a href="affiliates.php?action=edit&id=1&pay=true&affaccid=5">Manual<br>Payout</a></td><td><a href="#" onClick="doAccDelete(\'5\');return false"><img src="images/delete.gif" border="0"></a></td></tr>';
                                }
                            } else {
                                echo '<tr><td colspan="9">No Records Found</td></tr>';
                            }
                            ?>
                        </table>
                    </div>
                    <ul class="pager"><li class="previous disabled"><a href="#">&laquo; Previous Page</a></li><li class="next disabled"><a href="#">Next Page &raquo;</a></li></ul>  </div>
                <div class="tab-pane" id="tab2">
                    <div class="tablebg">
                        <table id="sortabletbl2" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                            <tr><th>Referral ID</th><th>Client Name</th><th>Product/Service</th><th>Product Status</th><th>Amount</th><th>Clearing Date</th><th width="20"></th></tr>
                            <tr><td>5</td><td><a href="clientssummary.php?userid=1654">Markus Tenghamn</a></td><td><a href="clientshosting.php?userid=1654&id=153">Basic</a></td><td>Cancelled</td><td>$2.00 USD</td><td>26/04/2016</td><td><a href="#" onClick="doPendingCommissionDelete('1');return false"><img src="images/delete.gif" border="0"></a></td></tr>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="tab3">
                    <div class="tablebg">
                        <table id="sortabletbl3" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                            <tr><th>Date</th><th>Referral ID</th><th>Client Name</th><th>Product/Service</th><th>Product Status</th><th>Description</th><th>Amount</th><th width="20"></th></tr>
                            <tr><td>13/04/2016</td><td>7</td><td><a href="clientssummary.php?userid=1654">Markus Tenghamn</a></td><td><a href="clientshosting.php?userid=1654&id=165">Basic</a></td><td>Active</td><td>&nbsp;</td><td>$2.00 USD</td><td><a href="#" onClick="doAffHistoryDelete('1');return false"><img src="images/delete.gif" border="0"></a></td></tr>
                        </table>
                    </div>

                    <br />

                    <form method="post" action="/admin/affiliates.php?action=addcomm&id=1">
                        <input type="hidden" name="token" value="32cc5ab5759a8d01150d30dc86a9d37f1f46d766" />
                        <p align="left"><b>Add Manual Commission Entry</b></p>
                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tr><td class="fieldlabel">Date:</td><td class="fieldarea"><input type="text" name="date" value="17/04/2016" class="datepick" /></td></tr>
                            <tr><td class="fieldlabel">Related Referral:</td><td class="fieldarea"><select name="refid" class="form-control select-inline"><option value="">None</option><option value="5">ID 5 - Markus Tenghamn - Basic</option><option value="6">ID 6 - Markus Tenghamn - Basic</option><option value="7">ID 7 - Markus Tenghamn - Basic</option></select></td></tr>
                            <tr><td class="fieldlabel">Description:</td><td class="fieldarea"><input type="text" name="description" size="45" /> (Optional)</td></tr>
                            <tr><td class="fieldlabel">Amount:</td><td class="fieldarea"><input type="text" name="amount" size="10" value="0.00" /></td></tr>
                        </table>
                        <div class="btn-container">
                            <input type="submit" value="Submit" class="btn btn-primary" />
                        </div>
                    </form>

                </div>
                <div class="tab-pane" id="tab4">
                    <div class="tablebg">
                        <table id="sortabletbl4" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                            <tr><th>Date</th><th>Amount</th><th width="20"></th></tr>
                            <tr><td colspan="3">No Records Found</td></tr>
                        </table>
                    </div>

                    <br />

                    <form method="post" action="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=withdraw&id=1">
                        <input type="hidden" name="token" value="<?php echo $token; ?>" />
                        <p align="left"><b>Make Withdrawal Payout</b></p>
                        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                            <tr><td class="fieldlabel">Amount:</td><td class="fieldarea"><input type="text" name="amount" size="10" value="<?php echo $withdrawbalance; ?>" /></td></tr>
                            <tr><td class="fieldlabel">Payout Type:</td><td class="fieldarea"><select name="payouttype" class="form-control select-inline"><option value="1">Create Transaction to Client</option><option value="2">Add Amount to Credit Balance</option><option>Record in Withdrawals Only</option></select></td></tr>
                            <tr><td class="fieldlabel">Transaction ID:</td><td class="fieldarea"><input type="text" name="transid" size="25" /> (Only applies to Transaction Payout Type)</td></tr>
                            <?php
                                //TOOD fetch payment methods and check transaction id
                            ?><tr><td class="fieldlabel">Payment Method:</td><td class="fieldarea"><select name="paymentmethod" class="form-control select-inline"><option value="">N/A</option><option value="stripe">Credit Card</option><option value="paypal">PayPal</option><option value="bitpay">Bitpay ( Bitcoin )</option><option value="payson">Payson</option><option value="banktransfer">Invoice</option></select></td></tr>
                        </table>
                        <div class="btn-container">
                            <input type="submit" value="Submit" class="btn btn-primary" />
                        </div>
                    </form>

                </div></div>
            <script type="text/javascript">
                var datepickerformat = "dd/mm/yy";
                $(document).ready(function(){
                    $(".admin-tabs").tabdrop(); $(window).resize();
                    $( "a[href^='#tab']" ).click( function() {
                        var tabID = $(this).attr('href').substr(4);
                        $("#tab").val(tabID);
                    });
                });
                function doAccDelete(id) {
                    if (confirm("Are you sure you want to delete this affiliate referral?")) {
                        window.location='addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=deletereferral&affaccid='+id+'&token=<?php echo $token; ?>';
                    }}
                function doPendingCommissionDelete(id) {
                    if (confirm("affiliates.pendeletesure")) {
                        window.location='addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=deletecommission&cid='+id+'&token=<?php echo $token; ?>';
                    }}
                function doAffHistoryDelete(id) {
                    if (confirm("Are you sure you want to delete this payment history record?")) {
                        window.location='addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=deletehistory&hid='+id+'&token=<?php echo $token; ?>';
                    }}
                function doWithdrawHistoryDelete(id) {
                    if (confirm("Are you sure you want to delete this withdrawal record?")) {
                        window.location='addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $affid; ?>&action=deletewithdrawal&wid='+id+'&token=<?php echo $token; ?>';
                    }}
            </script>
            <?php
        } else {
            //Affiliate main page
            // Table
            //  ID	Signup Date	Client Name 	Visitors Referred	Signups	Balance	Withdrawn
            $page = $_GET['page'];
            if (!isset($_GET['page'])) {
                $page = 1;
                $_SESSION['client'] = "";
                $_SESSION['balancetype'] = "";
                $_SESSION['balance'] = "";
                $_SESSION['visitorstype'] = "";
                $_SESSION['visitors'] = "";
                $_SESSION['withdrawntype'] = "";
                $_SESSION['withdrawn'] = "";
            }
            $filter = $_GET['filter'];
            if (!isset($_GET['filter'])) {
                $filter = 1;
            }
            $count = mysql_fetch_assoc(mysql_query("SELECT COUNT(id) AS total FROM tblaffiliates"));
            ?>
            <div id="content_padded">
                <ul class="nav nav-tabs admin-tabs" role="tablist">
                    <li><a href="#tab1" role="tab" data-toggle="tab" id="tabLink1">Search/Filter</a></li>
                </ul>
                <div class="tab-content admin-tabs">
                    <div class="tab-pane" id="tab1">
                        <form action="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&page=1" method="get">

                            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                                <tr>
                                    <td width="15%" class="fieldlabel">Client Name</td>
                                    <td class="fieldarea"><input type="text" name="client"
                                                                 class="form-control input-250" value=""></td>
                                    <td width="10%" class="fieldlabel">Balance</td>
                                    <td class="fieldarea"><select name="balancetype" class="form-control select-inline">
                                            <option value="greater">Greater Than
                                            <option>Less Than
                                        </select> <input type="text" name="balance"
                                                         class="form-control input-100 input-inline" value=""></td>
                                </tr>
                                <tr>
                                    <td class="fieldlabel">Visitors Referred</td>
                                    <td class="fieldarea"><select name="visitorstype"
                                                                  class="form-control select-inline">
                                            <option value="greater">Greater Than
                                            <option>Less Than
                                        </select> <input type="text" name="visitors"
                                                         class="form-control input-100 input-inline" value=""></td>
                                    <td class="fieldlabel">Withdrawn</td>
                                    <td class="fieldarea"><select name="withdrawntype"
                                                                  class="form-control select-inline">
                                            <option value="greater">Greater Than
                                            <option>Less Than
                                        </select> <input type="text" name="withdrawn"
                                                         class="form-control input-100 input-inline" value=""></td>
                                </tr>
                            </table>

                            <div class="btn-container">
                                <input type="submit" value="Search" class="btn btn-default">
                            </div>

                        </form>

                    </div>
                </div>
                <br>

                <form method="post" action="/admin/affiliates.php">
                    <table width="100%" border="0" cellpadding="3" cellspacing="0">
                        <tr>
                            <td width="50%" align="left"><?php echo $count['total']; ?> Records Found, Page <?php echo $page; ?>  of <?php echo ceil($count['total']/20); ?> </td>
                            <td width="50%" align="right">Jump to Page: <select name="page" onchange="submit()">
                                    <?php
                                    $pagei=0;
                                    while ($pagei < ceil($count['total']/20)) {
                                        $pagei++;
                                        if ($pagei == $page) {
                                    ?>
                                        <option value="0" selected><?php echo $pagei; ?></option>
                                    <?php } else { ?>
                                            <option value="<?php echo $pagei; ?>"><?php echo $pagei; ?></option>
                                       <?php }
                                    } ?>
                                </select> <input type="submit" value="Go" class="btn btn-xs btn-default"/></td>
                        </tr>
                    </table>
                </form>
                <form method="post" action="sendmessage.php?type=affiliate&multiple=true">
                    <div class="tablebg">
                        <table id="sortabletbl1" class="datatable" width="100%" border="0" cellspacing="1"
                               cellpadding="3">
                            <tr>
                                <th width="20"><input type="checkbox" id="checkall1"></th>
                                <th><a href="/admin/affiliates.php?orderby=id">ID</a></th>
                                <th><a href="/admin/affiliates.php?orderby=date">Signup Date</a></th>
                                <th><a href="/admin/affiliates.php?orderby=clientname">Client Name</a> <img
                                        src="images/asc.gif" class="absmiddle"/></th>
                                <th><a href="/admin/affiliates.php?orderby=visitors">Visitors Referred</a></th>
                                <th>Signups</th>
                                <th><a href="/admin/affiliates.php?orderby=balance">Balance</a></th>
                                <th><a href="/admin/affiliates.php?orderby=withdrawn">Withdrawn</a></th>
                                <th width="20"></th>
                                <th width="20"></th>
                            </tr>
                            <?php
                            $table = "tblaffiliates";
                            $fields = "id,date,clientid,visitors,paytype,payamount,onetime,balance,withdrawn";
                            $affilliates = mysql_query("SELECT " . $fields . " FROM " . $table . " ORDER BY date DESC LIMIT 20 OFFSET ".(($page-1)*20)."");
                            while ($a = mysql_fetch_array($affilliates)) {
                                $signups = mysql_fetch_assoc(mysql_query("SELECT COUNT(id) as total FROM tblaffiliatesaccounts WHERE affiliateid = '" . $a['id'] . "'"));
                                $client = mysql_fetch_assoc(mysql_query("SELECT firstname, lastname FROM tblclients WHERE id = '" . $a['clientid'] . "'"));
                                $currencyData = getCurrency($a['clientid']);

                                ?>
                                <tr>
                                    <td><input type="checkbox" name="selectedclients[]" value="<?php echo $a['id']; ?>"
                                               class="checkall"/></td>
                                    <td>
                                        <a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $a['id']; ?>"><?php echo $a['id']; ?></a>
                                    </td>
                                    <td><?php echo $a['date']; ?></td>
                                    <td>
                                        <a href="clientssummary.php?userid=<?php echo $a['clientid']; ?>"><?php echo $client['firstname'] . " " . $client['lastname']; ?></a>
                                    </td>
                                    <td><?php echo $a['visitors']; ?></td>
                                    <td><?php echo $signups['total']; ?></td>
                                    <td><?php echo formatCurrency($a['balance'], $currencyData); ?></td>
                                    <td><?php echo formatCurrency($a['withdrawn'], $currencyData); ?></td>
                                    <td><a href="addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=<?php echo $a['id']; ?>"><img src="images/edit.gif" width="16" height="16"
                                                                          border="0" alt="Edit"></a></td>
                                    <td><a href="#" onClick="doDelete('<?php echo $a['id']; ?>');return false"><img src="images/delete.gif"
                                                                                               width="16" height="16"
                                                                                               border="0" alt="Delete"></a>
                                    </td>
                                </tr>

                                <?php
                            }
                            ?>
                        </table>
                    </div>
                    With Selected: <input type="submit" value="Send Message" class="button btn btn-default"></form>
                <ul class="pager">
                    <li class="previous <?php if ($page == 1) { ?>disabled<?php } ?>"><?php if ($page == 1) { ?><a href="#">&laquo; Previous Page</a><?php } else { ?><a href="?page=<?php echo $page-1; ?>&filter=1">&laquo; Previous Page</a><?php } ?></li>
                    <li class="next <?php if ($page == 1) { ?>disabled<?php } ?>"><?php if ($page == 1) { ?><a href="#">Next Page &raquo;</a><?php } else { ?><a href="?page=<?php echo $page+1; ?>&filter=1">Next Page &raquo;</a><?php } ?></li>
                </ul>
            </div>
            </div>
            <script type="text/javascript">
                var datepickerformat = "dd/mm/yy";
                $(document).ready(function(){
                    $( "a[href^='#tab']" ).click( function() {
                        var tabID = $(this).attr('href').substr(4);
                        $("#tab").val(tabID);
                    });
                    /**
                     * We want to make the adminTabs on this page toggle
                     */
                    $( "a[href^='#tab']" ).click( function() {
                        var tabID = $(this).attr('href').substr(4);
                        var tabToHide = $("#tab" + tabID);
                        if(tabToHide.hasClass('active')) {
                            tabToHide.removeClass('active');
                        }  else {
                            tabToHide.addClass('active')
                        }
                    });
                    $("#checkall1").click(function () {
                        $("#sortabletbl1 .checkall").attr("checked",this.checked);
                    });
                });
                function doDelete(id) {
                    if (confirm("Are you sure you want to delete this affiliate?")) {
                        window.location='affiliates.php?sub=delete&ide='+id+'&token=27ef961d6518e57881cf79e33c7e299e51f8b538';
                    }}
            </script>
            <?php
        }

    } else if (isset($_GET['view']) && $_GET['view'] == "banners") {

    } else {
        $modulelink = $vars['modulelink'];
        $version = $vars['version'];
        $LANG = $vars['_lang'];
        $table = "mod_anveto_affiliate_anywhere_plus";

        if (isset($_POST['add'])) {
            if (is_valid_domain_name($_POST['add'])) {
                $values = array("domainname" => $_POST['add']);
                $newid = insert_query($table, $values);
                echo '<p>Domain added</p>';
            } else {
                echo '<p>Domain not valid</p>';
            }
        } else if (isset($_POST['remove'])) {
            full_query("DELETE FROM " . $table . " WHERE id = " . $_POST['remove']);
            echo '<p>Domain removed</p>';
        }

        echo '<p>'.$modulename.' will work automatically with any WHMCS pages by simply adding ?aff=[affiliateid]<br/>
            to the end of the url of any WHMCS page. It will also allow users to add ?aff=[affiliateid] to any external url with<br/>
            an approved domain. Please add any domains which you would like to allow below.</p>';

        echo '<form method="post" action="">
            <input type="text" name="add" placeholder="Domain"><input type="submit" value="Add">
            </form>';
        echo '<br/>';
        echo '<h3>Approved domains</h3>';
        echo '<table border="0">';
        $fields = "id,domainname";
        $result = select_query($table, $fields);
        while ($data = mysql_fetch_array($result)) {
            echo '<tr><td>';
            echo '<p>' . $data['domainname'] . '</td><td style="text-align: center; width: 200px;"><form method="post" action=""><input type="hidden" name="remove" value="' . $data['id'] . '"><input type="submit" value="Remove"></form></p></td></tr>';
        }
        echo '</table>';

        echo '<br/>';
        echo '<h3>Installation</h3>';
        echo '<p>In order to make the ?aff variable work on remote websites you will need to include the affiliate.php file which was<br/>
        included in your download. Optionally you can also copy and paste the code below onto all pages of your website where you<br/>
        would like the affilliate link to work. Make sure to include your WHMCS url, do <b>not</b> link to the aff.php file in WHMCS.</p>';
        echo '<textarea cols="200" rows="10">
$whmcsurl = "http://yourwhmcsurl";
if (isset($_GET["aff"]) && $_GET["aff"] > 0) {
    // Will only work with Affiliates Pro
    $anvetoUrl = $whmcsurl."?aff=".$_GET["aff"]."&site=http" . (isset($_SERVER["HTTPS"]) ? "s" : "") . "://" . $_SERVER["HTTP_HOST"]."/".$_SERVER["REQUEST_URI"];
    header("Location: ".$anvetoUrl);
    echo \'<script>window.location = "\'.$anvetoUrl.\'";</script>\';
}</textarea>';
    }


}

function anveto_affiliate_anywhere_plus_calcComission($referalls) {
    $pendingcomissions = 0;
    $pendingpre = "$";
    $pendinglast = " USD";
    foreach ($referalls as $ref) {
        //fetch user by userid from tblhosting
        //currency from tblclients

        $hostingid = 0;
        $firsttime = 0;
        $periodmount = 0;
        $hostingid = $ref['relid'];
        $productid = 0;
        $term = 0;

        $currencysql = "SELECT tblclients.currency FROM tblhosting LEFT JOIN tblclients ON tblhosting.userid = tblclients.id WHERE tblhosting.id=".$hostingid;
        $csql = "SELECT tblclients.id FROM tblhosting LEFT JOIN tblclients ON tblhosting.userid = tblclients.id WHERE tblhosting.id=".$hostingid;
        $cid = mysql_result(mysql_query($csql), 0);
        $currencyData = getCurrency($cid );
        $currency = mysql_result(mysql_query($currencysql), 0);

        $res = select_query('tblhosting', 'id,packageid,billingcycle,firstpaymentamount,amount', array('id' => $hostingid));

        while ($d = mysql_fetch_array($res)) {
            $productid = $d['packageid'];
            $firsttime = $d['firstpaymentamount'];
            $periodmount = $d['amount'];
            if ($d['billingcycle'] == 'Monthly') {
                $term = 1;
            } else if ($d['billingcycle'] == 'One Time') {
                $term = 1;
            } else if ($d['billingcycle'] == 'Quarterly') {
                $term = 2;
            } else if ($d['billingcycle'] == 'Semi-Annually') {
                $term = 3;
            } else if ($d['billingcycle'] == 'Annually') {
                $term = 4;
            } else if ($d['billingcycle'] == 'Biennially') {
                $term = 5;
            } else if ($d['billingcycle'] == 'Triiennially') {
                $term = 6;
            }
        }
        $currencies = anveto_affiliate_anywhere_plus_get_currencies();
        $code = "usd";
        foreach ($currencies as $c) {
            if ($c['id'] == $currency) {
                $code = strtolower($c['code']);
            }
        }
        $table = "mod_anveto_affiliate_anywhere_plus_exclude";
        $fields = "id,pid,term,currency,excluded";
        $where = array('pid' => $productid, 'currency' => $code);
        $result = select_query($table, $fields, $where);
        $excluded = false;
        $payout = "";
        while ($data = mysql_fetch_array($result)) {
            if ($data['term'] == $term && $data['excluded'] == 1) {
                $excluded = true;
            }
        }
        $table2 = "mod_anveto_affiliate_anywhere_plus_payout";
        $fields2 = "id,pid,term,currency,payout";
        $where2 = array('pid' => $productid, 'currency' => $code);
        $result2 = select_query($table2, $fields2, $where2);
        while ($data2 = mysql_fetch_array($result2)) {
            if ($data2['term'] == $term && $data2['payout'] != "" && strlen($data2['payout']) > 0) {
                $payout = $data2['payout'];
            }
        }
        if ($excluded) {
            $desc = "";
            $pre = substr($ref['commission'], 0, 1);
            if (!is_numeric($pre)) {
                $pre2 = substr($ref['commission'], 1, 1);
                if (!is_numeric($pre2) && $pre2 != " ") {
                    $pre3 = substr($ref['commission'], 2, 1);
                    if (!is_numeric($pre3) && $pre3 != " ") {
                        $desc = $pre . $pre2 . $pre3;
                    } else {
                        $desc = $pre . $pre2;
                    }
                } else {
                    $desc = $pre;
                }
                $pendingpre = $desc;
            }
            $desc .= "0.00";
            $explode = explode(" ", $ref['commission']);
            if (count($explode) > 1) {
                $last = $explode[count($explode) - 1];
                $check = substr($last, -1);
                if (!is_numeric($check)) {
                    $desc .= " " . $last;
                    $pendinglast = " " . $last;
                }
            }
            $ref['commission'] = $desc;
            $pendingcomissions = $pendingcomissions + anveto_affiliate_anywhere_plus_tofloat($desc);
        } else if ($payout != "" && strlen($payout) > 0) {
            $valid = true;
            $field = trim($payout);
            $percent = false;
            if (strpos($field, "%") !== false) {
                $percent = true;
                if (!is_float(str_replace("%", "", $field)) && !is_numeric(str_replace("%", "", $field))) {
                    $valid = false;
                }
            } else if ($field == "") {
                $valid = false;
            } else {
                if (!is_float($field) && !is_numeric($field)) {
                    $valid = false;
                }
            }
            if ($valid) {
                //TODO get the currency and convert before adding to total
                //TODO update tblhosting with actual comission
                //TODO make a seperate table to store comissions in the future and make it convert currency for the day
                $pppayy = formatCurrency($payout, $currencyData);
                $pendingcomissions = $pendingcomissions + anveto_affiliate_anywhere_plus_tofloat($pppayy);
            }
        } else {
            $desc = "";
            $pre = substr($ref['commission'], 0, 1);
            if (!is_numeric($pre)) {
                $pre2 = substr($ref['commission'], 1, 1);
                if (!is_numeric($pre2) && $pre2 != " ") {
                    $pre3 = substr($ref['commission'], 2, 1);
                    if (!is_numeric($pre3) && $pre3 != " ") {
                        $desc = $pre . $pre2 . $pre3;
                    } else {
                        $desc = $pre . $pre2;
                    }
                } else {
                    $desc = $pre;
                }
            }
            $pendingpre = $desc;
            $explode = explode(" ", $ref['commission']);
            if (count($explode) > 1) {
                $last = $explode[count($explode) - 1];
                $check = substr($last, -1);
                if (!is_numeric($check)) {
                    $pendinglast = " " . $last;

                }
            }
            $pendingcomissions = $pendingcomissions + anveto_affiliate_anywhere_plus_tofloat($ref['commission']);
        }
    }

    $vars['pendingcommissions'] = $pendingpre.number_format($pendingcomissions, 2).$pendinglast;
}