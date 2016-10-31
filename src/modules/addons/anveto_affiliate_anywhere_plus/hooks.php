<?php
/**
 * Copyright Anveto AB
 * Author: Markus Tenghamn
 * Date: 10/04/15
 * Time: 02:09
 */

use WHMCS\Cookie;
use Illuminate\Database\Capsule\Manager as Capsule;

function anveto_affiliate_anywhere_plus_clientPageLoad()
{
    global $whmcs;
    define("CLIENTAREA", true);
    require_once("init.php");
    if ($aff = $whmcs->get_req_var('aff')) {
        update_query("tblaffiliates", array("visitors" => "+1"), array("id" => $aff));
        Cookie::set('AffiliateID', $aff, '3m');
        if (isset($_GET['site']) && strlen($_GET['site'])) {
            $table = "mod_anveto_affiliate_anywhere_plus";
            $fields = "id,domainname";
            $result = select_query($table, $fields);
            $valid = false;
            if (isset($_SERVER['SERVER_NAME'])) {
                if (strpos($_GET['site'], $_SERVER['SERVER_NAME']) !== false) {
                    $valid = true;
                }
            }
            if (!$valid) {
                while ($data = mysql_fetch_array($result)) {
                    if (strpos($_GET['site'], $data['domainname']) !== false) {
                        $valid = true;
                        break;
                    }
                }
            }
            if ($valid) {
                $is_ssl = false;
                $site = str_replace("?aff=" . $_GET['aff'], "", $_GET['site']);
                if (strpos($site, "https://") !== false) {
                    $site = str_replace('https://', '', $site);
                    $is_ssl = true;
                } else {
                    $site = str_replace('http://', '', $site);
                }
                $site = str_replace("&aff=" . $_GET['aff'], "", $site);
                $site = str_replace("&aff=", "", $site);
                $site = str_replace("?aff=", "", $site);
                $site = str_replace('//', '/', $site);
                if ($is_ssl) {
                    $site = "https://".$site;
                } else {
                    $site = "http://".$site;
                }
                if (!headers_sent()) {
                    header("Location: " . $site);
                } else {
                    echo '<script type="text/javascript">';
                    echo 'setTimeout(function(){window.location.href = "'. $site . '"},500);';
                    echo '</script>';
                    echo '<noscript>';
                    echo '<meta http-equiv="refresh" content="0;url=' . $site . '" />';
                    echo '</noscript>';
                }
                die();
            }
        }
    }
}

function anveto_affiliate_anywhere_plus_get_currencies()
{
    $currenciestable = "tblcurrencies";
    $currenciesfields = "id,code";
    $currencies = select_query($currenciestable, $currenciesfields);
    $currs = array();
    while ($c = mysql_fetch_array($currencies)) {
        $currs[] = array('code' => $c['code'], 'id' => $c['id']);
    }
    return $currs;
}

function anveto_affiliate_anywhere_plus_get_pricing($id)
{
    $pricingtable = "tblpricing";
    $pricingfields = "currency,monthly,quarterly,semiannually,annually,biennially,triennially";
    $pricingwhere = array('relid' => $id, 'type' => 'product');
    $pricing = select_query($pricingtable, $pricingfields, $pricingwhere);
    $currs = anveto_affiliate_anywhere_plus_get_currencies();
    $prices = array();
    while ($p = mysql_fetch_array($pricing)) {
        $currency = "USD";
        foreach ($currs as $cr) {
            if ($cr['id'] == $p['currency']) {
                $currency = $cr['code'];
            }
        }
        $prices[$currency] = array(
            0 => $p['monthly'],
            1 => $p['quarterly'],
            2 => $p['semiannually'],
            3 => $p['annually'],
            4 => $p['biennially'],
            5 => $p['triennially']
        );
    }
    return $prices;
}

function anveto_affiliate_anywhere_plus_get_sections()
{
    return array(
        'key1' => array('key' => "monthly", 'name' => "One Time/Monthly"),
        'key2' => array('key' => "quarterly", 'name' => "Quarterly"),
        'key3' => array('key' => "semiannually", 'name' => "Semi-Annually"),
        'key4' => array('key' => "annually", 'name' => "Annually"),
        'key5' => array('key' => "biennially", 'name' => "Biennially"),
        'key6' => array('key' => "triennially", 'name' => "Triennially")
    );
}

function anveto_affiliate_anywhere_plus_adminProductConfigFields($vars)
{
    if (anveto_affiliate_anywhere_plus_check_for_tables()) {
        $productid = $vars['pid'];

        $table = "mod_anveto_affiliate_anywhere_plus_exclude";
        $table2 = "mod_anveto_affiliate_anywhere_plus_payout";
        $fields = "id,pid,term,currency,excluded";
        $fields2 = "id,pid,term,currency,payout";
        $where = array('pid' => $productid);
        $where2 = array('pid' => $productid);
        $result = select_query($table, $fields, $where);
        while ($d = mysql_fetch_array($result)) {
            $term = $d['term'];
            if (is_numeric($term)) {
                if (!isset($terms["key" . $term])) {
                    $terms["key" . $term] = array();
                }
                $terms["key" . $term]["curr-" . $d['currency']] = array('excluded' => $d['excluded']);
            }
        }
        $result2 = select_query($table2, $fields2, $where2);
        while ($d2 = mysql_fetch_array($result2)) {
            $term = $d2['term'];
            if (is_numeric($term)) {
                if (!isset($terms["payout-key" . $term])) {
                    $terms["payout-key" . $term] = array();
                }
                $terms["payout-key" . $term]["curr-" . $d2['currency']] = array('payout' => $d2['payout']);
            }
        }

        $exclude = "Exclude Affiliate Payout ";
        $payout = "Affiliate Payout ";

        $sections = anveto_affiliate_anywhere_plus_get_sections();

        $prices = anveto_affiliate_anywhere_plus_get_pricing($productid);

        $fields = array();

        //This is for exclusion
        foreach ($prices as $pk => $pr) {
            $x = 0;
            foreach ($sections as $k => $s) {
                if ($pr[$x] > 0) {
                    if (!isset($fields[$exclude . trim($pk)])) {
                        $fields[$exclude . trim($pk)] = "";
                    }
                    $fields[$exclude . trim($pk)] .= "<input type='checkbox' name='anveto_affiliate_anywhere_plus_exclude_" . strtolower(trim($pk)) . "_" . $s['key'] . "' value='1' ";
                    if ($terms[$k]["curr-" . strtolower($pk)]['excluded'] == 1) {
                        $fields[$exclude . trim($pk)] .= "CHECKED";
                    }
                    $fields[$exclude . trim($pk)] .= "/> " . $s['name'] . " ";
                }

                $x++;
            }
        }
        //This is for custom payouts based on period
        foreach ($prices as $pk => $pr) {
            $x = 0;
            foreach ($sections as $k => $s) {
                if ($pr[$x] > 0) {
                    if (!isset($fields[$payout . trim($pk)])) {
                        $fields[$payout . trim($pk)] = "";
                    }
                    $fields[$payout . trim($pk)] .= "<input size='5' placeholder='$pr[$x]' type='text' name='anveto_affiliate_anywhere_plus_payout_" . strtolower(trim($pk)) . "_" . $s['key'] . "' value='";
                    $fields[$payout . trim($pk)] .= $terms["payout-" . $k]["curr-" . strtolower($pk)]['payout'];
                    $fields[$payout . trim($pk)] .= "'/> " . $s['name'] . " ";
                }
                $x++;
            }
        }

        $fields['Custom Affilate Fields'] = "Enter a full amount (10.00) or a percentage (15.00%) above. Entering a value will override the affiliate settings below. The exclude will override all other settings if checked.";

        return $fields;
    }
}


function anveto_affiliate_anywhere_plus_adminProductConfigFieldsSave()
{
    if (anveto_affiliate_anywhere_plus_check_for_tables()) {
        $sections = anveto_affiliate_anywhere_plus_get_sections();
        global $_REQUEST;
        $productid = $_REQUEST['id'];
        $prices = anveto_affiliate_anywhere_plus_get_pricing($productid);
        //This is for exclusion
        $x = 0;
        foreach ($sections as $k => $s) {
            foreach ($prices as $pk => $pr) {
                if ($pr[$x] > 0) {
                    if (isset($_REQUEST['anveto_affiliate_anywhere_plus_exclude_' . strtolower(trim($pk)) . "_" . $s['key']])) {
                        anveto_affiliate_anywhere_plus_update_exclude($productid, ($x + 1), 1, strtolower(trim($pk)));
                    } else {
                        anveto_affiliate_anywhere_plus_update_exclude($productid, ($x + 1), 0, strtolower(trim($pk)));
                    }

                    if (isset($_REQUEST['anveto_affiliate_anywhere_plus_payout_' . strtolower(trim($pk)) . "_" . $s['key']])) {
                        anveto_affiliate_anywhere_plus_update_payout($productid, ($x + 1), $_REQUEST['anveto_affiliate_anywhere_plus_payout_' . strtolower(trim($pk)) . "_" . $s['key']], strtolower(trim($pk)));
                    }
                }
            }
            $x++;
        }
    }
}

function anveto_affiliate_anywhere_plus_update_payout($pid, $term, $field, $currency)
{
    $field = trim($field);
    if (strpos($field, "%") !== false) {
        if (!is_float(str_replace("%", "", $field)) && !is_numeric(str_replace("%", "", $field))) {
            return false;
        }
    } else if ($field == "") {
        //Do nothing
    } else {
        if (!is_float($field) && !is_numeric($field)) {
            return false;
        }
    }
    $table = "mod_anveto_affiliate_anywhere_plus_payout";
    $fields = "id,pid,term,currency,payout";
    $where = array('pid' => $pid, 'term' => $term, "currency" => $currency);
    $result = select_query($table, $fields, $where);
    $found = false;
    while ($data = mysql_fetch_array($result)) {
        if ($data['term'] == $term && $data['payout'] == $field && $data['currency'] == $currency) {
            $found = true;
        } else if ($data['term'] == $term && $data['payout'] != $field && $data['currency'] == $currency) {
            $where2 = array('pid' => $pid, 'term' => $term, "currency" => $currency);
            update_query($table, array("payout" => $field), $where2);
            $found = true;
        }
    }
    if (!$found) {
        insert_query($table, array('pid' => $pid, 'term' => $term, 'payout' => $field, "currency" => $currency));
    }
}

function anveto_affiliate_anywhere_plus_update_exclude($pid, $term, $excluded, $currency)
{
    $table = "mod_anveto_affiliate_anywhere_plus_exclude";
    $fields = "id,pid,term,currency,excluded";
    $where = array('pid' => $pid, 'term' => $term, "currency" => $currency);
    $result = select_query($table, $fields, $where);
    $found = false;
    while ($data = mysql_fetch_array($result)) {
        if ($data['term'] == $term && $data['excluded'] == $excluded && $data['currency'] == $currency) {
            $found = true;
        } else if ($data['term'] == $term && $data['excluded'] != $excluded && $data['currency'] == $currency) {
            $where2 = array('pid' => $pid, 'term' => $term, "currency" => $currency);
            $res = update_query($table, array("excluded" => $excluded), $where2);
            $found = true;
        }
    }
    if (!$found) {
        insert_query($table, array('pid' => $pid, 'term' => $term, 'excluded' => $excluded, "currency" => $currency));
    }
}

function anveto_affiliate_anywhere_plus_calcAffiliateCommission($vars)
{
    if (anveto_affiliate_anywhere_plus_check_for_tables()) {
        $affiliateid = $vars['affid'];
        $hostingid = $vars['relid'];
        $amount = $vars['amount'];
        $comission = $vars['commission'];
        $productid = 0;
        $term = 0;
        $firsttime = 0;
        $periodmount = 0;

        $currencysql = "SELECT tblclients.currency FROM tblhosting LEFT JOIN tblclients ON tblhosting.userid = tblclients.id WHERE tblhosting.id=".$hostingid;
        $currency = mysql_result(mysql_query($currencysql), 0);

        $res = select_query('tblhosting', 'id,packageid,billingcycle,firstpaymentamount,amount', array('id' => $hostingid));

        while ($d = mysql_fetch_array($res)) {
            $productid = $d['packageid'];
            $firsttime = $d['firstpaymentamount'];
            $periodmount = $d['amount'];
            if ($d['billingcycle'] == 'Monthly') {
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
            $vars['commission'] = 0;

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
                if (!$percent) {
                    $vars['commission'] = number_format($payout, 2);
                } else {
                    $vars['commission'] = number_format(floatval(($amount) * (str_replace("%", "", $payout) / 100)), 2);
                }
            }
        }
    }
    return $vars;
}

function anveto_affiliate_anywhere_plus_clientAreaPageAffiliates($vars)
{
    //TODO Hook into affiliate link code here
    //$vars['affiliatelinkscode']);
    if (anveto_affiliate_anywhere_plus_check_for_tables() && count($vars['referrals']) > 0) {
        $pendingcomissions = 0;
        $pendingpre = "$";
        $pendinglast = " USD";
        foreach ($vars['referrals'] as &$ref) {
            //fetch user by userid from tblhosting
            //currency from tblclients

            $hostingid = 0;
            $firsttime = 0;
            $periodmount = 0;
            $res2 = select_query('tblaffiliatesaccounts', 'id,relid', array('id' => $ref['id']));
            while ($d2 = mysql_fetch_array($res2)) {
                $hostingid = $d2['relid'];
            }
            $productid = 0;
            $term = 0;

            $currencysql = "SELECT tblclients.currency FROM tblhosting LEFT JOIN tblclients ON tblhosting.userid = tblclients.id WHERE tblhosting.id=".$hostingid;
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
                    if (!$percent) {
                        $desc .= number_format($payout, 2);
                    } else {
                        $desc .= number_format(floatval(($periodmount)*(str_replace("%", "", $payout)/100)), 2);
                    }
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
    return $vars;
}

function anveto_affiliate_anywhere_plus_tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

function anveto_affiliate_anywhere_plus_check_for_tables()
{
    $val = false;
    $val2 = false;
    $val3 = false;
    if (function_exists("full_query")) {
        $val = full_query('SELECT * FROM mod_anveto_affiliate_anywhere_plus LIMIT 1');
        $val2 = full_query('SELECT * FROM mod_anveto_affiliate_anywhere_plus_exclude LIMIT 1');
        $val3 = full_query('SELECT * FROM mod_anveto_affiliate_anywhere_plus_payout LIMIT 1');
    } else {
        return false;
    }

    if ($val !== FALSE && $val2 !== FALSE && $val3 !== false) {
        $exists = true;
        while ($d = mysql_fetch_array($val2)) {
            if (!key_exists('currency', $d)) {
                $exists = false;
            }
        }
        if (!$exists) {
            $res = full_query('ALTER TABLE mod_anveto_affiliate_anywhere_plus_exclude ADD COLUMN currency VARCHAR( 255 ) NOT NULL AFTER term');
            $res2 = full_query('ALTER TABLE mod_anveto_affiliate_anywhere_plus_payout ADD COLUMN currency VARCHAR( 255 ) NOT NULL AFTER term');
            $table = "mod_anveto_affiliate_anywhere_plus_exclude";
            $table2 = "mod_anveto_affiliate_anywhere_plus_payout";
            $currencies = anveto_affiliate_anywhere_plus_get_currencies();
            if (count($currencies) > 0) {
                $currency = strtolower(trim($currencies[0]['code']));
                update_query($table, array("currency" => $currency));
                update_query($table2, array("currency" => $currency));
            }
        }
        //"ALTER TABLE mod_anveto_affiliate_anywhere_plus_exclude ADD COLUMN currency INT(11) NOT NULL AFTER term;";
        return true;
    }
    $run = true;

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
    return $run;
}

function anveto_affiliate_anywhere_plus_adminAreaPage($vars) {
    if (isset($vars['filename']) && $vars['filename'] == "affiliates") {
        $affiliateid = 0;
        if (isset($_GET['action']) && $_GET['action'] == "edit") {
            $affiliateid = $_GET['id'];
        }
        if (false) {
            if ($affiliateid > 0) {
                header("Location: " . "addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff&affid=" . $affiliateid);
            } else {
                header("Location: " . "addonmodules.php?module=anveto_affiliate_anywhere_plus&view=aff");
            }
        }
    }
}

function dump_all($vars) {
    foreach ($vars as $name => $variable) {
        echo $name." - ".$variable."<br/>";
    }
    die();
}


//add_hook("AdminAreaPage", 11, "dump_all");
add_hook("AdminAreaPage", 10, "anveto_affiliate_anywhere_plus_adminAreaPage");
add_hook("ClientAreaPage", 5, "anveto_affiliate_anywhere_plus_clientPageLoad");
add_hook("AdminProductConfigFields", 6, "anveto_affiliate_anywhere_plus_adminProductConfigFields");
add_hook("AdminProductConfigFieldsSave", 7, "anveto_affiliate_anywhere_plus_adminProductConfigFieldsSave");
add_hook("CalcAffiliateCommission", 8, "anveto_affiliate_anywhere_plus_calcAffiliateCommission");
add_hook('ClientAreaPageAffiliates', 1, "anveto_affiliate_anywhere_plus_clientAreaPageAffiliates");