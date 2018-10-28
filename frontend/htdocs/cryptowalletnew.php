<!DOCTYPE html>
<html lang="en">
    <?php
        // error_reporting(E_ALL);
        // ini_set('display_errors', 1);
        include '../lib/common.php';
        // echo "<pre>"; print_r($CFG); exit;
        if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
        	Link::redirect('userprofile');
        elseif (User::$awaiting_token)
        	Link::redirect('verify-token');
        elseif (!User::isLoggedIn())
            Link::redirect('login'); 
            
        //     if(empty(User::$ekyc_data) || User::$ekyc_data[0]->status != 'accepted')
        // {
        //     Link::redirect('ekyc');
        // }
        
        $page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
        $currencies = Settings::sessionCurrency();
        API::add('BankAccounts','get');
        API::add('User','getAvailable');
        API::add('BitcoinAddresses','get',array(false,$currencies['c_currency'],false,1,1));
        API::add('Content','getRecord',array('deposit-bank-instructions'));
        API::add('Content','getRecord',array('deposit-no-bank'));
        API::add('Wallets','getWallet',array($currencies['c_currency']));
        foreach ($CFG->currencies as $key => $currency) {
        	if (is_numeric($key) || $currency['is_crypto'] != 'Y')
        		continue;
        		
        	API::add('Stats','getCurrent',array($currency['id'], 27));
        }


        API::add('Transactions','get24hData',array(28,27)); //btc
        API::add('Transactions','get24hData',array(42,27)); //ltc
        API::add('Transactions','get24hData',array(44,27)); //bch
        API::add('Transactions','get24hData',array(45,27)); //eth
        API::add('Transactions','get24hData',array(43,27)); //zec
        
        $query = API::send();

        $transactions_24hrs_btc_usd = $query['Transactions']['get24hData']['results'][0] ;
        $transactions_24hrs_ltc_usd = $query['Transactions']['get24hData']['results'][1] ;
        $transactions_24hrs_bch_usd = $query['Transactions']['get24hData']['results'][2] ;
        $transactions_24hrs_eth_usd = $query['Transactions']['get24hData']['results'][3] ;
        $transactions_24hrs_zec_usd = $query['Transactions']['get24hData']['results'][4] ;
       
        $inrtoall = $query['Stats']['getCurrent']['results'];
        
        foreach ($inrtoall as $row) {
            $checkinr[$row['market']] = $row;
        }
        
        $bank_accounts = $query['BankAccounts']['get']['results'][0];
        $bitcoin_addresses = $query['BitcoinAddresses']['get']['results'][0];
        $user_available = $query['User']['getAvailable']['results'][0];
        // echo "<pre>"; print_r($user_available); exit;
        
        $wallet = $query['Wallets']['getWallet']['results'][0];
        $c_currency_info = $CFG->currencies[$currencies['c_currency']];
        $btc_address1 = (!empty($_REQUEST['btc_address'])) ?  preg_replace("/[^\da-z]/i", "",$_REQUEST['btc_address']) : false;
        // echo "string ".$btc_address1; exit;
        $btc_amount1 = (!empty($_REQUEST['btc_amount'])) ? Stringz::currencyInput($_REQUEST['btc_amount']) : 0;
        $btc_total1 = ($btc_amount1 > 0) ? $btc_amount1 - $wallet['bitcoin_sending_fee'] : 0;
        $account1 = (!empty($_REQUEST['account'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['account']) : false;
        $fiat_amount1 = (!empty($_REQUEST['fiat_amount'])) ? Stringz::currencyInput($_REQUEST['fiat_amount']) : 0;
        $fiat_total1 = ($fiat_amount1 > 0) ? $fiat_amount1 - $CFG->fiat_withdraw_fee : 0;
        $token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
        $authcode1 = (!empty($_REQUEST['authcode'])) ? $_REQUEST['authcode'] : false;
        $request_2fa = false;
        $no_token = false;
        
        if ($authcode1) {
        	API::add('Requests','emailValidate',array(urlencode($authcode1)));
        	$query = API::send();
        
        	if ($query['Requests']['emailValidate']['results'][0]) {
        		Link::redirect('cryptowalletnew?message=withdraw-2fa-success');
        	}
        	else {
        		Errors::add(Lang::string('settings-request-expired'));
        	}
        }
        API::add('Requests','get',array(1,false,false,1));
        API::add('Requests','get',array(false,$page1,15,1));
        $query = API::send();
        
        $withdraw_requests = $query['Requests']['get']['results'][1];
        // echo "<pre>"; print_r($withdraw_requests); exit;
        
        API::add('Requests','get',array(1));
        API::add('Requests','get',array(false,$page1,15));
        $query = API::send();
        $deposit_requests = $query['Requests']['get']['results'][1];
        // echo "<pre>"; print_r($deposit_requests); exit;
        
        if ($CFG->withdrawals_status == 'suspended')
            Errors::add(Lang::string('withdrawal-suspended'));
        
        if ($btc_address1)
            API::add('BitcoinAddresses','validateAddress',array($currencies['c_currency'],$btc_address1));
            $query = API::send();
         // echo "<pre>"; print_r($query['BitcoinAddresses']['validateAddress']['results']);
        
        if (!empty($_REQUEST['bitcoins'])) {
            // echo "string"; exit;
            $btc_to_send = $btc_amount1 - $wallet['bitcoin_sending_fee'];
            $btc_amount1 = $btc_to_send;
        	if ($btc_amount1 < 0.00000001)
        		Errors::add(Lang::string('withdraw-amount-zero'));
        	if ($btc_amount1 > $user_available[$c_currency_info['currency']])
        		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-too-much')));
        	if (!$query['BitcoinAddresses']['validateAddress']['results'][0])
        		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-address-invalid')));
        	
        	if (!is_array(Errors::$errors)) {
        		if (User::$info['confirm_withdrawal_email_btc'] == 'Y' && !$request_2fa && !$token1) {
        			API::add('Requests','insert',array($c_currency_info['id'],$btc_amount1,$btc_address1));
        			$query = API::send();
        			Link::redirect('cryptowalletnew?notice=email');
        		}
        		elseif (!$request_2fa) {
        			API::token($token1);
        			API::add('Requests','insert',array($c_currency_info['id'],$btc_amount1,$btc_address1));
        			$query = API::send();
        			
        			if ($query['error'] == 'security-com-error')
        				Errors::add(Lang::string('security-com-error'));
        			
        			if ($query['error'] == 'authy-errors')
        				Errors::merge($query['authy_errors']);
        			
        			if ($query['error'] == 'security-incorrect-token')
        				Errors::add(Lang::string('security-incorrect-token'));
        			
        			if (!is_array(Errors::$errors)) {
        				if ($query['Requests']['insert']['results'][0]) {
        					if ($token1 > 0)
        						Link::redirect('cryptowalletnew?message=withdraw-2fa-success');
        					else
        						Link::redirect('cryptowalletnew?message=withdraw-success');
        				}	
        			}
        			elseif (!$no_token) {
        				$request_2fa = true;
        			}
        		}
        	}
        	elseif (!$no_token) {
        		$request_2fa = false;
        	}
        }
        
        if (!empty($_REQUEST['message'])) {
            if ($_REQUEST['message'] == 'withdraw-2fa-success')
                Messages::add(Lang::string('withdraw-2fa-success'));
            elseif ($_REQUEST['message'] == 'withdraw-success')
                Messages::add(Lang::string('withdraw-success'));
        }
        
        if (!empty($_REQUEST['notice']) && $_REQUEST['notice'] == 'email')
            $notice = Lang::string('withdraw-email-notice');
        include "includes/sonance_header.php"; 
        $page_title = Lang::string('withdraw');
       
        ?>
    <style>
        .custom-select {
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 2px;
        height: 28px !important;
        }
        .left-side-inner .media.active
        {
            border-left: 3px solid #fcae51;
        }
        
        .errors
        {
            background: #ff000029;
            color: red;
            position: relative;
            width: 100%;
            right: 0;
            margin-top:20px;
        }
        .messages
        {
            background: #00800038;
            color: green;
            position: relative;
            width: 100%;
            right: 0;
            margin-top: 20px;
        }
        .manage-accounts:hover
        {
            text-decoration : none;
        }

        .info-data-table1  tbody tr td, .info-data-table2  tbody tr td
        {
            cursor: auto;
        }
        .left-side-inner{
            padding:10px;
        }
    </style>
    <body id="wrapper">
        <?php include "includes/sonance_navbar.php"; ?>
        <header>
            <div class="banner row">
                <div class="container content">
                    <h1>Crypto Wallet</h1>
                     <p class="text-white text-center">Send / Receive Cryptocurrencies and also View Cryptocurrency Balances on the Exchange</p>
                </div>
            </div>
        </header>
        <div class="page-container">
            <div class="container"> 
                <? Errors::display(); ?>
	                <? Messages::display(); ?>    
                <?php if(!empty($notice)): ?>
                <div class="notice">
                    <div class="message-box-wrap alert alert-info"><?=$notice?></div>
                </div>
                <?php endif; ?>          
                <div class="row"> 
                <div class="col-md-12">
                    <div class="left-side-widget">
                            <div class="bg-white">
                               
                                <ul style="padding-top: 4px;">
                                    <li>If you are here for the first time, generate a cryptocurrency address for each cryptocurrency.</li>
                                    <li>Click on Manage Crypto addresses to create/manage the addresses.</li>
                                    <li>To Send Cryptocurrencies to other wallets, paste the recipients' address in the  Send to Address box.</li>
                                    <li>Receive Cryptos to your wallet by sharing the addresses displayed below.</li>
                                </ul>
                            </div>
                        </div>
                </div>                   
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="left-side-widget">
                            <div class="left-side-inner">
                                <h6 class="title">
                                    <strong>Send Cryptos</strong>
                                    <a href="#sendcrypto" data-toggle="modal" class="float-right">
                                        <svg style="width:15px;height:15px;" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" xml:space="preserve">
                                    <circle style="fill:#47a0dc" cx="25" cy="25" r="25"></circle>
                                    <line style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" x1="25" y1="37" x2="25" y2="39"></line>
                                    <path style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" d="M18,16
                                        c0-3.899,3.188-7.054,7.1-6.999c3.717,0.052,6.848,3.182,6.9,6.9c0.035,2.511-1.252,4.723-3.21,5.986
                                        C26.355,23.457,25,26.261,25,29.158V32"></path><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                    <g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                    </svg>
                                </a>
                                </h6>
                                <form id="buy_form" action="cryptowalletnew.php" method="POST">
                                    <div>
                                        <p>
                                            <?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('sell-btc-available')) ?>
                                            <span class="float-right"><?= Stringz::currency($user_available[$c_currency_info['currency']],true) ?> <?= $c_currency_info['currency'] ?></span>
                                        </p>
                                    </div>
                                    <div>
                                        <div>
                                            <p><?= Lang::string('withdraw-withdraw') ?></p>
                                        </div>
                                        <div>
                                            <div>
                                                <div class="form-group">
                                                    <select id="c_currency" name="currency" class="form-control">
                                                    <?
                                                        if ($CFG->currencies) {
                                                            foreach ($CFG->currencies as $key => $currency) {
                                                                if (is_numeric($key) || $currency['is_crypto'] != 'Y')
                                                                    continue;
                                                                
                                                                echo '<option '.(($currency['id'] == $currencies['c_currency']) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
                                                            }
                                                        }   
                                                        ?>
                                                    </select>   
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <p><?= Lang::string('withdraw-send-to-address') ?></p>
                                        </div>
                                        <div>
                                            <div>
                                                <div class="form-group">
                                                    <input type="text" class="form-control " id="btc_address" name="btc_address" value="<?= $btc_address1 ?>" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <p><?= Lang::string('withdraw-send-amount') ?></p>
                                        </div>
                                        <div>
                                            <div>
                                                <div class="form-group">
                                                    <input type="text" class="form-control" id="btc_amount" name="btc_amount" value="<?= Stringz::currency($btc_amount1,true) ?>" />
                                                    <div class="input-caption"><?= $c_currency_info['currency'] ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="current-otr">
                                        <p>
                                            <?= Lang::string('withdraw-network-fee') ?>
                                            <span class="float-right"><span id="withdraw_btc_network_fee"><?= Stringz::currencyOutput($wallet['bitcoin_sending_fee']) ?></span> <?= $c_currency_info['currency'] ?></span>
                                        </p>
                                    </div>
                                    <div >
                                        <p>
                                            <span id="withdraw_btc_total_label"><?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-btc-total')) ?> </span>
                                            <span class="float-right"><span id="withdraw_btc_total"><?= Stringz::currency($btc_total1,true) ?></span></span>
                                        </p>
                                    </div>
                                    <input type="hidden" name="bitcoins" value="1" />
                                    <input type="submit" name="submit" value="<?= Lang::string('withdraw-send-bitcoins') ?>" class="btn " />
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="left-side-widget">
                            <div class="left-side-inner">
                                <div>
                                    <h6 class="title">
                                        <strong>Receive Cryptos</strong>
                                        <a href="#receivecrypto" data-toggle="modal" class="float-right">
                                        <svg style="width:15px;height:15px;" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" xml:space="preserve">
                                    <circle style="fill:#47a0dc" cx="25" cy="25" r="25"></circle>
                                    <line style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" x1="25" y1="37" x2="25" y2="39"></line>
                                    <path style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" d="M18,16
                                        c0-3.899,3.188-7.054,7.1-6.999c3.717,0.052,6.848,3.182,6.9,6.9c0.035,2.511-1.252,4.723-3.21,5.986
                                        C26.355,23.457,25,26.261,25,29.158V32"></path><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                    <g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                    </svg>
                                </a>
                                    </h6>
                                    <div>
                                        <p>Select Currency</p>
                                    </div>
                                    <div>
                                        <div>
                                            <div class="form-group">
                                                <select id="c_currency" name="currency" class="form-control">
                                                <?
                                                    if ($CFG->currencies) {
                                                        foreach ($CFG->currencies as $key => $currency) {
                                                            if (is_numeric($key) || $currency['is_crypto'] != 'Y')
                                                                continue;
                                                            
                                                            echo '<option '.(($currency['id'] == $currencies['c_currency']) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
                                                        }
                                                    }   
                                                    ?>
                                                </select>   
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div>
                                        <p><?= Lang::string('deposit-send-to-address') ?>
                                        <p>
                                    </div>
                                    <div>
                                        <div>
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="deposit_address" name="deposit_address" value="<?= $bitcoin_addresses[0]['address'] ?>" />
                                            </div>
                                            <div class="form-group" style="text-align: center;margin-top:2em;">
                                                <img class="qrcode" src="includes/qrcode.php?code=<?= $bitcoin_addresses[0]['address'] ?>" style="width: 114px;height: 114px; "/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a href="cryptoaddress" class="manage-accounts">
                                    <button class="Button__Container-hQftQV kZBVvC btn" style="cursor: pointer;">
                                        <div>
                                            <div><?= Lang::string('deposit-manage-addresses') ?></div>
                                        </div>
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="left-side-widget">
                            <div class="left-side-inner media-otr">
                                <div class="media <?= ($c_currency_info['id']==28) ? 'active' : ''?>">
                                    <svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" class="CurrencyIcon-gdWZMT crJeiC">
                                        <g fill="none" fill-rule="evenodd">
                                            <circle fill="#FFAD02" cx="19" cy="19" r="19"></circle>
                                            <path d="M24.7 19.68a3.63 3.63 0 0 0 1.47-2.06c.74-2.77-.46-4.87-3.2-5.6l.89-3.33a.23.23 0 0 0-.16-.28l-1.32-.35a.23.23 0 0 0-.28.15l-.89 3.33-1.75-.47.88-3.32a.23.23 0 0 0-.16-.28l-1.31-.35a.23.23 0 0 0-.28.15l-.9 3.33-3.73-1a.23.23 0 0 0-.27.16l-.36 1.33c-.03.12.04.25.16.28l.22.06a1.83 1.83 0 0 1 1.28 2.24l-1.9 7.09a1.83 1.83 0 0 1-2.07 1.33.23.23 0 0 0-.24.12l-.69 1.24a.23.23 0 0 0 0 .2c.02.07.07.12.14.13l3.67.99-.89 3.33c-.03.12.04.24.16.27l1.32.35c.12.03.24-.04.28-.16l.89-3.32 1.76.47-.9 3.33c-.02.12.05.24.16.27l1.32.35c.12.03.25-.04.28-.16l.9-3.32.87.23c2.74.74 4.83-.48 5.57-3.25.35-1.3-.05-2.6-.92-3.48zm-5.96-5.95l2.64.7a1.83 1.83 0 0 1 1.28 2.24 1.83 1.83 0 0 1-2.23 1.3l-2.64-.7.95-3.54zm1.14 9.8l-3.51-.95.95-3.54 3.51.94a1.83 1.83 0 0 1 1.28 2.24 1.83 1.83 0 0 1-2.23 1.3z" fill="#FFF"></path>
                                        </g>
                                    </svg>
                                    <div class="media-body">
                                        <h6 class="title"><strong>
                                            BTC Wallet</strong>
                                        </h6>
                                        <div>
                                            <span>
                                            <span><?= Stringz::currency($user_available['BTC'],true) ?> BTC</span>
                                            </span>
                                            <span>
                                            <span>≈</span>
                                            <span>
                                            <span>$<?=Stringz::currency($transactions_24hrs_btc_usd['lastPrice'] * Stringz::currency($user_available['BTC'],true));?></span>
                                            </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media <?= ($c_currency_info['id']==42) ? 'active' : ''?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 38 38" class="CurrencyIcon-fhkqpi iMGyub">
                                        <g fill="none" fill-rule="evenodd">
                                            <circle cx="19" cy="19" r="19" fill="#B5B5B5" fill-rule="nonzero"></circle>
                                            <path fill="#FFF" d="M12.29 28.04l1.29-5.52-1.58.67.63-2.85 1.64-.68L16.52 10h5.23l-1.52 7.14 2.09-.74-.58 2.7-2.05.8-.9 4.34h8.1l-.99 3.8z"></path>
                                        </g>
                                    </svg>
                                    <div class="media-body">
                                        <h6 class="title"><strong>
                                            LTC Wallet</strong>
                                        </h6>
                                        <div>
                                            <span>
                                            <span><?= Stringz::currency($user_available['LTC'],true) ?> LTC</span>
                                            </span>
                                            <span>
                                            <span>≈</span>
                                            <span>
                                            <span>$<?=Stringz::currency($transactions_24hrs_ltc_usd['lastPrice'] * Stringz::currency($user_available['LTC'],true));?></span>
                                            </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media <?= ($c_currency_info['id']==44) ? 'active' : ''?>">
                                    <img src="https://i.warosu.org/data/biz/img/0042/92/1510358947541.png" width="38" height="38">
                                    <div class="media-body">
                                        <h6 class="title"><strong>
                                            BCH Wallet</strong>
                                        </h6>
                                        <div>
                                            <span>
                                            <span><?= Stringz::currency($user_available['BCH'],true) ?> BCH</span>
                                            </span>
                                            <span>
                                            <span>≈</span>
                                            <span>
                                            <span>$<?=Stringz::currency($transactions_24hrs_bch_usd['lastPrice'] * Stringz::currency($user_available['BCH'],true));?></span>
                                            </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media <?= ($c_currency_info['id']==43) ? 'active' : ''?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="38" viewBox="0 0 256 256" width="38">
                                        <defs>
                                            <style>
                                                .cls-1 {
                                                fill: #252525;
                                                }
                                                .cls-2 {
                                                fill: #fff;
                                                fill-rule: evenodd;
                                                }
                                            </style>
                                        </defs>
                                        <g data-name="zcash zec" id="zcash_zec">
                                            <g data-name="zcash zec" id="zcash_zec-2">
                                                <circle class="cls-1" cx="128" cy="128" data-name="Эллипс 27" id="Эллипс_27" r="128"></circle>
                                                <path class="cls-2" d="M568,1958a79,79,0,1,1-79,79A79,79,0,0,1,568,1958Zm0,17.77A61.225,61.225,0,1,1,506.775,2037,61.231,61.231,0,0,1,568,1975.77Zm-27.65,23.7H560.1v-13.82h15.8v13.82h21.725v17.78l-33.575,37.52h33.575v17.78H575.9v13.83H560.1v-13.83H538.375v-17.78l33.575-37.52h-31.6v-17.78Z" data-name="Эллипс 26" id="Эллипс_26" transform="translate(-440 -1909)"></path>
                                            </g>
                                        </g>
                                    </svg>
                                    <div class="media-body">
                                        <h6 class="title"><strong>
                                            ZEC Wallet</strong>
                                        </h6>
                                        <div>
                                            <span>
                                            <span><?= Stringz::currency($user_available['ZEC'],true) ?> ZEC</span>
                                            </span>
                                            <span>
                                            <span>≈</span>
                                            <span>
                                            <span>$<?=Stringz::currency($transactions_24hrs_zec_usd['lastPrice'] * Stringz::currency($user_available['ZEC'],true));?></span>
                                            </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media <?= ($c_currency_info['id']==45) ? 'active' : ''?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 32 32" class="CurrencyIcon-lapLDb kJWJBN">
                    <g fill="none" fill-rule="evenodd">
                        <ellipse cx="16" cy="16" fill="#6F7CBA" rx="16" ry="16"></ellipse>
                        <path fill="#FFF" d="M10.13 17.76c-.1-.15-.06-.2.09-.12l5.49 3.09c.15.08.4.08.56 0l5.58-3.08c.16-.08.2-.03.1.11L16.2 25.9c-.1.15-.28.15-.38 0l-5.7-8.13zm.04-2.03a.3.3 0 0 1-.13-.42l5.74-9.2c.1-.15.25-.15.34 0l5.77 9.19c.1.14.05.33-.12.41l-5.5 2.78a.73.73 0 0 1-.6 0l-5.5-2.76z"></path>
                    </g>
                </svg>
                                    <div class="media-body">
                                        <h6 class="title"><strong>
                                            ETH Wallet</strong>
                                        </h6>
                                        <div>
                                            <span>
                                            <span><?= Stringz::currency($user_available['ETH'],true) ?> ETH</span>
                                            </span>
                                            <span>
                                            <span>≈</span>
                                            <span>
                                            <span><?=Stringz::currency($transactions_24hrs_eth_usd['lastPrice'] * Stringz::currency($user_available['ETH'],true));?></span>
                                            </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <br>
                        <div class="info-table-outer">
                            <h6><b><?= Lang::string('deposit-recent') ?></b></h6>
                            <input type="hidden" id="refresh_transactions" value="1" />
                            <input type="hidden" id="page" value="<?= $page1 ?>" />
                            <table class="table row-border info-data-table1 table-hover balance-table table-border" cellspacing="0 " width="100% ">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?= Lang::string('deposit-date') ?></th>
                                        <th><?= Lang::string('deposit-description') ?></th>
                                        <th><?= Lang::string('deposit-amount') ?></th>
                                        <th><?= Lang::string('withdraw-net-amount') ?></th>
                                        <th><?= Lang::string('deposit-status') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? if ($deposit_requests): ?>
                                        <? foreach ($deposit_requests as $request): ?>
                                            <?php if($CFG->currencies[$request['currency']]['is_crypto'] != 'Y') continue ?>
                                        <tr>
                                            <td><?= $request['id'] ?></td>
                                            <td><input type="hidden" class="localdate" value="<?= strtotime($request['date']) ?>" /></td>
                                            <td><?= $request['description'] ?></td>
                                            <td><?=(($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? Stringz::currency($request['amount'],true).' '.$request['fa_symbol'] : $request['fa_symbol'].Stringz::currency($request['amount'])) ?></td>
                                            <td><?= (($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? Stringz::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])),true).' '.$request['fa_symbol'] : $request['fa_symbol'].Stringz::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee']))))?></td>
                                            <td><?=$request['status']?></td>
                                        </tr>
                                        <? endforeach;?>
                                    <? else: ?>
                                        <tr><td colspan="6">No Deposits</td></tr>
                                    <? endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <br>
                        <div class="info-table-outer">
                            <h6><b><?= Lang::string('withdrawal-recent') ?></b></h6>
                            <input type="hidden" id="refresh_transactions" value="1" />
                            <input type="hidden" id="page" value="<?= $page1 ?>" />
                            <table class="table row-border info-data-table2 table-hover balance-table table-border " cellspacing="0 " width="100% ">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?= Lang::string('deposit-date') ?></th>
                                        <th><?= Lang::string('deposit-description') ?></th>
                                        <th><?= Lang::string('deposit-amount') ?></th>
                                        <th><?= Lang::string('withdraw-net-amount') ?></th>
                                        <th><?= Lang::string('deposit-status') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? if ($withdraw_requests): ?>
                                        <? foreach ($withdraw_requests as $request): ?>
                                        <?php if($CFG->currencies[$request['currency']]['is_crypto'] != 'Y') continue ?>
                                        <tr>
                                            <td><?= $request['id'] ?></td>
                                            <td><input type="hidden" class="localdate" value="<?= strtotime($request['date']) ?>" /></td>
                                            <td><?= $request['description'] ?></td>
                                            <td><?=(($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? Stringz::currency($request['amount'],true).' '.$request['fa_symbol'] : $request['fa_symbol'].Stringz::currency($request['amount'])) ?></td>
                                            <td><?=(($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? Stringz::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])),true).' '.$request['fa_symbol'] : $request['fa_symbol'].Stringz::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee']))))?></td>
                                            <td><?=$request['status']?></td>
                                        </tr>
                                        <? endforeach;?>
                                    <? else: ?>
                                        <tr><td colspan="6">No Withdrawals</td></tr>
                                    <? endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--modal-1-->
<div class="modal fade" id="sendcrypto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Send Cryptos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>To send any supported Cryptocurrency,</p>
        <p><b>Step 1:</b> Select the Cryptocurrency you'd like to send. <br/>
        (Wait for the page to reflect and display the balance of the selected currency)</p>
        <p><b>Step 2:</b> Paste the Recipient's Cryptocurrency Wallet address in the <span class="
        text-primary">Send to Address field.</span></p>
        <p><b>Step 3:</b> Enter the number of cryptos to send and click send.</p>
        <p><b>Note:</b> A percentage of blockchain fee is taken by the network to process the transaction.</p>
      </div>
    </div>
  </div>
</div>
<!--modal-2-->
<div class="modal fade" id="receivecrypto" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Receive Cryptos</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><b>Step 1:</b> Select BTC or LTC or ZEC from the drop down to display the addresses</p>
        <p><b>Step 2:</b> Copy the address displayed in the <span class="
        text-primary">Send to This Address</span> and share it with the sender</p>
        <p><b>Note:</b> If the QR code isn't getting displayed or appears broken, it means that you haven't created an address. You would have to create crypto addresses for each cryptocurrency separately.</p>
      </div>
    </div>
  </div>
</div>
        <?php include "includes/sonance_footer.php"; ?>
        <script type="text/javascript" src="js/ops.js?v=20160210"></script>
</html>