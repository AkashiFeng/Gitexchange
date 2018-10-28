<!DOCTYPE html>
<html lang="en">
<?php include '../lib/common.php';
        
    if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
        Link::redirect('settings.php');
    elseif (User::$awaiting_token)
        Link::redirect('verify-token.php');
    elseif (!User::isLoggedIn())
        Link::redirect('login.php');
        
        if ((!empty($_REQUEST['c_currency']) && array_key_exists(strtoupper($_REQUEST['c_currency']),$CFG->currencies)))
    $_SESSION['ba_c_currency'] = $_REQUEST['c_currency'];
else if (empty($_SESSION['ba_c_currency']))
    $_SESSION['ba_c_currency'] = $_SESSION['c_currency'];


$c_currency = $_SESSION['ba_c_currency'];
API::add('BitcoinAddresses','get',array(false,$c_currency,false,30,1));
API::add('Content','getRecord',array('bitcoin-addresses'));
$query = API::send();

$bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
$content = $query['Content']['getRecord']['results'][0];
$page_title = Lang::string('bitcoin-addresses');

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'add' && $_SESSION["btc_uniq"] == $_REQUEST['uniq']) {
    if (strtotime($bitcoin_addresses[0]['date']) >= strtotime('-1 day'))
        Errors::add('You can only add one new '.$CFG->currencies[$c_currency]['currency'] .' address every 24 hours.');
    
    if (!is_array(Errors::$errors)) {
        API::add('BitcoinAddresses','getNew',array($c_currency));
        API::add('BitcoinAddresses','get',array(false,$c_currency,false,30,1));
        $query = API::send();
        $bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
        
        Messages::add(Lang::string('bitcoin-addresses-added'));
        if($c_currency==45)
        {
        Link::redirect('cryptoaddress.php');
        }
    }
}
$_SESSION["btc_uniq"] = md5(uniqid(mt_rand(),true));
include "includes/sonance_header.php"; 
        ?>
    <style>
        .custom-select {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 2px;
            height: 28px !important;
        }
        .messages,.errors {
            list-style-type: none;
            background: #DFFBE4;
            padding: 15px;
            border-radius: 3px;
            position: relative;
            font-size: 14px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            z-index: 999;
            max-width: 400px;
            margin: 1em 0 0 auto;
        }
        .errors {
            background: #fdbdc3;
        }
    </style>
    <body id="wrapper">
        <?php include "includes/sonance_navbar.php"; ?>
        <header>
            <div class="banner row">
                <div class="container content">
                    <h1>Crypto Addresses</h1>
                </div>
            </div>
        </header>
        <div class="page-container">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">

                    <? Messages::display(); ?>
                    <? Errors::display(); ?>
                   

                    <br><? if($c_currency != 45){ echo $content['content'];  }?>
                    <div class="form-group">
                        <select id="c_currency" class="form-control" style="    margin-top: 1em;height: 40px;">
                            <option value="">--Select Currency--</option>
                        <? 
                        foreach ($CFG->currencies as $key => $currency1) {
                            if (is_numeric($key) || $currency1['is_crypto'] != 'Y')
                                continue;
                            
                            echo '<option value="'.$currency1['id'].'" '.($currency1['id'] == $c_currency ? 'selected="selected"' : '').'>'.$currency1['currency'].'</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <? if($c_currency != 45 || ($c_currency == 45 && empty($bitcoin_addresses)) ) { ?>
                    <a class="btn btn-primary cust-btn" href="cryptoaddress.php?action=add&c_currency=<?= $c_currency ?>&uniq=<?= $_SESSION["btc_uniq"] ?>" class="but_user" > <?= Lang::string('bitcoin-addresses-add') ?></a>
                    <? } ?>
                    </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <br>
                        <div class="info-table-outer">
                            <input type="hidden" id="refresh_transactions" value="1" />
                            <input type="hidden" id="page" value="<?= $page1 ?>" />
                            <table id="info-data-table " class="table row-border info-data-table table-hover balance-table" cellspacing="0 " width="100% ">
                                <thead>
                                    <th><?= Lang::string('currency') ?></th>
                                    <th><?= Lang::string('bitcoin-addresses-date') ?></th>
                                    <th><?= Lang::string('bitcoin-addresses-address') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? 
                    if ($bitcoin_addresses) {
                        foreach ($bitcoin_addresses as $address) {
                    ?>
                    <tr>
                        <td><?= $CFG->currencies[$address['c_currency']]['currency'] ?></td>
                        <td><input type="hidden" class="localdate" value="<?= (strtotime($address['date']) + $CFG->timezone_offset) ?>" /></td>
                        <td><?= $address['address'] ?></td>
                    </tr>
                    <?
                        }
                    }
                    else {
                        echo '<tr><td colspan="3" style="padding:0;"><div class="" style=" text-align:center;    background: #f4f6f8;
                        "><img src="images/no-results.gif" style="width: 300px;height: auto;float:none;" ></div></td></tr>';
                    }
                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "includes/sonance_footer.php"; ?>
        <script type="text/javascript" src="js/ops.js?v=20160210"></script>
       
</html>