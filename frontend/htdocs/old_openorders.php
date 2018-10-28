<!DOCTYPE html>
<html lang="en">
    <?php 
        include '../lib/common.php';
        
        if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
            Link::redirect('settings.php');
        elseif (User::$awaiting_token)
            Link::redirect('verify-token.php');
        elseif (!User::isLoggedIn())
            Link::redirect('login.php');
        
        $delete_id1 = (!empty($_REQUEST['delete_id'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['delete_id']) : false;
        if ($delete_id1 > 0 && $_SESSION["openorders_uniq"] == $_REQUEST['uniq']) {
            API::add('Orders','getRecord',array($delete_id1));
            $query = API::send();
            $del_order = $query['Orders']['getRecord']['results'][0];
        
            if (!$del_order) {
                Link::redirect('openorders.php?message=order-doesnt-exist');
            }
            elseif ($del_order['site_user'] != $del_order['user_id'] || !($del_order['id'] > 0)) {
                Link::redirect('openorders.php?message=not-your-order');
            }
            else {
                API::add('Orders','delete',array($delete_id1));
                $query = API::send();
                
                Link::redirect('openorders.php?message=order-cancelled');
            }
        }
        
        $delete_all = (!empty($_REQUEST['delete_all']));
        if ($delete_all && $_SESSION["openorders_uniq"] == $_REQUEST['uniq']) {
            API::add('Orders','deleteAll');
            $query = API::send();
            $del_order = $query['Orders']['deleteAll']['results'][0];
        
            if (!$del_order)
                Link::redirect('openorders.php?message=deleteall-error');
            else
                Link::redirect('openorders.php?message=deleteall-success');
        }
        
        if ((!empty($_REQUEST['c_currency']) && array_key_exists(strtoupper($_REQUEST['c_currency']),$CFG->currencies)))
            $_SESSION['oo_c_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['c_currency']);
        else if (empty($_SESSION['oo_c_currency']) || $_REQUEST['c_currency'] == 'All')
            $_SESSION['oo_c_currency'] = false;
        
        if ((!empty($_REQUEST['currency']) && array_key_exists(strtoupper($_REQUEST['currency']),$CFG->currencies)))
            $_SESSION['oo_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['currency']);
        else if (empty($_SESSION['oo_currency']) || $_REQUEST['currency'] == 'All')
            $_SESSION['oo_currency'] = false;
        
        if ((!empty($_REQUEST['order_by'])))
            $_SESSION['oo_order_by'] = preg_replace("/[^a-z]/", "",$_REQUEST['order_by']);
        else if (empty($_SESSION['oo_order_by']))
            $_SESSION['oo_order_by'] = false;
        
        $c_currency1 = $_GET['c_currency'] ? : 28;
        $currency1 = $_GET['currency'] ? : 27;
        /* $currency1 = $_SESSION['oo_currency'];
        $c_currency1 = $_SESSION['oo_c_currency']; */
        $order_by1 = $_SESSION['oo_order_by'];
        $trans_realized1 = (!empty($_REQUEST['transactions'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['transactions']) : false;
        $id1 = (!empty($_REQUEST['id'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['id']) : false;
        $bypass = (!empty($_REQUEST['bypass']));
        
        API::add('Orders','get',array(false,false,false,$c_currency1,$currency1,1,false,1,$order_by1,false,1));
        API::add('Orders','get',array(false,false,false,$c_currency1,$currency1,1,false,false,$order_by1,1,1));
        $query = API::send();
        
        $bids = $query['Orders']['get']['results'][0];
        $asks = $query['Orders']['get']['results'][1];
        $currency_info = ($currency1) ? $CFG->currencies[strtoupper($currency1)] : false;
        
        if (!empty($_REQUEST['new_order']) && !$trans_realized1)
            Messages::add(Lang::string('transactions-orders-new-message'));
        if (!empty($_REQUEST['edit_order']) && !$trans_realized1)
            Messages::add(Lang::string('transactions-orders-edit-message'));
        elseif (!empty($_REQUEST['new_order']) && $trans_realized1 > 0)
            Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-orders-done-message')));
        elseif (!empty($_REQUEST['edit_order']) && $trans_realized1 > 0)
            Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-orders-done-edit-message')));
        elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'order-doesnt-exist')
            Errors::add(Lang::string('orders-order-doesnt-exist'));
        elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'not-your-order')
            Errors::add(Lang::string('orders-not-yours'));
        elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'order-cancelled')
            Messages::add(Lang::string('orders-order-cancelled'));
        elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'deleteall-error')
            Errors::add(Lang::string('orders-order-cancelled-error'));
        elseif (!empty($_REQUEST['message']) && $_REQUEST['message'] == 'deleteall-success')
            Messages::add(Lang::string('orders-order-cancelled-all'));
        
        $page_title = Lang::string('openorders');
        $_SESSION["openorders_uniq"] = md5(uniqid(mt_rand(),true));
        include "includes/sonance_header.php"; 
        ?>
    <style>
        .custom-select {
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 2px;
        height: 28px !important;
        }
        .messages
        {
            background: #0080002e;
            color: green;
            width: 100%;
            position: relative;
            margin: 1em auto 0 20px;
        }
        .errors
        {
            list-style-type: none;
            padding: 20px;
            background: #ff00003d;
            color: red;
            width: 100%;
            margin-top: 20px;
        }
    </style>
    <body id="wrapper">
        <?php include "includes/sonance_navbar.php"; ?>
        <header>
            <div class="banner row">
                <div class="container content">
                    <h1>Open Orders</h1>
                    <p class="text-white text-center">All your Open Buy / Sell Orders are shown below</p>
                </div>
            </div>
        </header>
        <div class="page-container">
            <div class="container">
                <div class="row">
                   
                    <div class="col-md-12">
                    <? Messages::display(); ?>
                    <? Errors::display(); ?>
                        <form action="" class="form-inline" style="padding: 20px;background: white;margin-top: 20px;">
                            <div class="form-group">
                                <label for="sel1" style="font-size: 12px;">Currency Pair &nbsp;</label>
                            </div>
                            <div class="form-group">
                                <select class="form-control custom-select" id="c_currency_select" style="width:100px;">
                                    <? if ($CFG->currencies): ?>
                                    <? foreach ($CFG->currencies as $key => $currency): ?>
                                    <? if (is_numeric($key) || $currency['is_crypto'] != 'Y') continue; ?>
                                    <option <?= $currency['id'] == $c_currency1 ? 'selected="selected"' : '' ?>  value="<?=$currency['id']?>">
                                        <?=$currency['currency'] ?>
                                    </option>
                                    <? endforeach; ?>
                                    <? endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select class="form-control custom-select" id="currency_select" style="margin-left:5px;width:100px;">
                                    <? if ($CFG->currencies): ?>
                                    <? foreach ($CFG->currencies as $key => $currency): ?>
                                    <? if (is_numeric($key) || $currency['id'] == $c_currency1) continue; ?>
                                    <option <?= $currency['id'] == $currency1 ? 'selected="selected"' : '' ?>  value="<?=$currency['id']?>">
                                        <?=$currency['currency'] ?>
                                    </option>
                                    <? endforeach; ?>
                                    <? endif; ?>
                                </select>
                            </div>
                        </form>
                        <span class="float-right">
                            <a href="#openorders" data-toggle="modal" style="    position: relative;top: -3em;right: 1em;">
                                <svg style="width:15px;height:15px;" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 50 50" xml:space="preserve">
                                <circle style="fill:#47a0dc" cx="25" cy="25" r="25"></circle>
                                <line style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" x1="25" y1="37" x2="25" y2="39"></line>
                                <path style="fill:none;stroke:#FFFFFF;stroke-width:4;stroke-linecap:round;stroke-miterlimit:10;" d="M18,16
                                    c0-3.899,3.188-7.054,7.1-6.999c3.717,0.052,6.848,3.182,6.9,6.9c0.035,2.511-1.252,4.723-3.21,5.986
                                    C26.355,23.457,25,26.261,25,29.158V32"></path><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                <g></g><g></g><g></g><g></g><g></g><g></g><g></g>
                                </svg>
                            </a>
                            </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <br>
                        <center>
                            <h5>Buy Orders</h5>
                        </center>
                        <div class="info-table-outer">
                            <table id="bids_list" class="table row-border info-data-table table-hover balance-table table-border trades" cellspacing="0 " width="100% ">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th><?= Lang::string('orders-price') ?></th>
                                        <th><?= Lang::string('orders-amount') ?></th>
                                        <th><?= Lang::string('orders-value') ?></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? 
                                        if ($bids) {
                                            foreach ($bids as $bid) {
                                                $blink = ($bid['id'] == $id1) ? 'blink' : '';
                                                $double = 0;
                                                if ($bid['market_price'] == 'Y')
                                                    $type = '<div class="identify market_order" style="background-color:#EFE62F;text-align:center;color:white;">M</div>';
                                                elseif ($bid['fiat_price'] > 0 && !($bid['stop_price'] > 0))
                                                    $type = '<div class="identify limit_order" style="background-color:#FF8282;text-align:center;color:white;">L</div>';
                                                elseif ($bid['stop_price'] > 0 && !($bid['fiat_price'] > 0))
                                                    $type = '<div class="identify stop_order" style="background-color:#DB82FF;text-align:center;color:white;">S</div>';
                                                elseif ($bid['stop_price'] > 0 && $bid['fiat_price'] > 0) {
                                                    $type = '<div class="identify limit_order" style="background-color:#FF8282;text-align:center;color:white;">L</div>';
                                                    $double = 1;
                                                }
                                                
                                                echo '
                                        <tr id="bid_'.$bid['id'].'" class="bid_tr '.$blink.'">
                                            <input type="hidden" class="usd_price" value="'.Stringz::currency(((empty($bid['usd_price'])) ? $bid['usd_price'] : $bid['btc_price']),($CFG->currencies[$bid['currency']]['is_crypto'] == 'Y')).'" />
                                            <input type="hidden" class="order_date" value="'.$bid['date'].'" />
                                            <input type="hidden" class="is_crypto" value="'.$bid['is_crypto'].'" />
                                            <td>'.$type.'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$bid['currency']]['fa_symbol'].'</span><span class="order_price">'.Stringz::currency(($bid['fiat_price'] > 0) ? $bid['fiat_price'] : $bid['stop_price'],($CFG->currencies[$bid['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="order_amount">'.Stringz::currency($bid['btc'],true).'</span> '.$CFG->currencies[$bid['c_currency']]['currency'].'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$bid['currency']]['fa_symbol'].'</span><span class="order_value">'.Stringz::currency($bid['btc'] * (($bid['fiat_price'] > 0) ? $bid['fiat_price'] : $bid['stop_price']),($CFG->currencies[$bid['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td>
                                            <!--<a href="edit-order.php?order_id='.$bid['id'].'" title="'.Lang::string('orders-edit').'"><i class="fa fa-edit"></i></a>-->
                                             <a href="openorders.php?delete_id='.$bid['id'].'&uniq='.$_SESSION["openorders_uniq"].'" title="'.Lang::string('orders-delete').'"><i class="fa fa-times"></i></a></td>
                                        </tr>';
                                                if ($double) {
                                                    echo '
                                        <tr id="bid_'.$bid['id'].'" class="bid_tr double">
                                            <input type="hidden" class="is_crypto" value="'.$bid['is_crypto'].'" />
                                            <td><div class="identify stop_order">S</div></td>
                                            <td><span class="currency_char">'.$CFG->currencies[$bid['currency']]['fa_symbol'].'</span><span class="order_price">'.Stringz::currency($bid['stop_price'],($CFG->currencies[$bid['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="order_amount">'.Stringz::currency($bid['btc'],true).'</span> '.$CFG->currencies[$bid['c_currency']]['currency'].'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$bid['currency']]['fa_symbol'].'</span><span class="order_value">'.Stringz::currency($bid['btc']*$bid['stop_price'],($CFG->currencies[$bid['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td>
                                        </tr>';
                                                }
                                            }
                                        }
                                        echo '<tr id="no_bids" style="'.(is_array($bids) && count($bids) > 0 ? 'display:none;' : '').'"><td colspan="5">'.Lang::string('orders-no-bid').'</td></tr>';
                                        ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <br>
                        <center>
                            <h5>Sell Orders</h5>
                        </center>
                        <div class="info-table-outer">
                            <table id="info-data-table " class="table row-border info-data-table table-hover balance-table table-order table-border" cellspacing="0 " width="100% ">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th><?= Lang::string('orders-price') ?></th>
                                        <th><?= Lang::string('orders-amount') ?></th>
                                        <th><?= Lang::string('orders-value') ?></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? 
                                        if ($asks) {
                                            foreach ($asks as $ask) {
                                                $blink = ($ask['id'] == $id1) ? 'blink' : '';
                                                $double = 0;
                                                if ($ask['market_price'] == 'Y')
                                                    $type = '<div class="identify market_order" style="background-color:#EFE62F;text-align:center;color:white;">M</div>';
                                                elseif ($ask['fiat_price'] > 0 && !($ask['stop_price'] > 0))
                                                    $type = '<div class="identify limit_order" style="background-color:#FF8282;text-align:center;color:white;">L</div>';
                                                elseif ($ask['stop_price'] > 0 && !($ask['fiat_price'] > 0))
                                                    $type = '<div class="identify stop_order" style="background-color:#DB82FF;text-align:center;color:white;">S</div>';
                                                elseif ($ask['stop_price'] > 0 && $ask['fiat_price'] > 0) {
                                                    $type = '<div class="identify limit_order" style="background-color:#FF8282;text-align:center;color:white;">L</div>';
                                                    $double = 1;
                                                }
                                                
                                                echo '
                                        <tr id="ask_'.$ask['id'].'" class="ask_tr '.$blink.'">
                                            <input type="hidden" class="usd_price" value="'.Stringz::currency(((empty($ask['usd_price'])) ? $ask['usd_price'] : $ask['btc_price']),($CFG->currencies[$ask['currency']]['is_crypto'] == 'Y')).'" />
                                            <input type="hidden" class="order_date" value="'.$ask['date'].'" />
                                            <input type="hidden" class="is_crypto" value="'.$ask['is_crypto'].'" />
                                            <td>'.$type.'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$ask['currency']]['fa_symbol'].'</span><span class="order_price">'.Stringz::currency(($ask['fiat_price'] > 0) ? $ask['fiat_price'] : $ask['stop_price'],($CFG->currencies[$ask['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="order_amount">'.Stringz::currency($ask['btc'],true).'</span> '.$CFG->currencies[$ask['c_currency']]['currency'].'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$ask['currency']]['fa_symbol'].'</span><span class="order_value">'.Stringz::currency($ask['btc'] * (($ask['fiat_price'] > 0) ? $ask['fiat_price'] : $ask['stop_price']),($CFG->currencies[$ask['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td> <!--<a href="edit-order.php?order_id='.$ask['id'].'" title="'.Lang::string('orders-edit').'"><i class="fa fa-edit"></i></a>-->
                                            <a href="openorders.php?delete_id='.$ask['id'].'&uniq='.$_SESSION["openorders_uniq"].'" title="'.Lang::string('orders-delete').'"><i class="fa fa-times"></i></a></td>
                                        </tr>';
                                                
                                                if ($double) {
                                                    echo '
                                        <tr id="ask_'.$ask['id'].'" class="ask_tr double">
                                            <input type="hidden" class="is_crypto" value="'.$ask['is_crypto'].'" />
                                            <td><div class="identify stop_order">S</div></td>
                                            <td><span class="currency_char">'.$CFG->currencies[$ask['currency']]['fa_symbol'].'</span><span class="order_price">'.Stringz::currency($ask['stop_price'],($CFG->currencies[$ask['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="order_amount">'.Stringz::currency($ask['btc'],true).'</span> '.$CFG->currencies[$ask['c_currency']]['currency'].'</td>
                                            <td><span class="currency_char">'.$CFG->currencies[$ask['currency']]['fa_symbol'].'</span><span class="order_value">'.Stringz::currency($ask['stop_price']*$ask['btc'],($CFG->currencies[$ask['currency']]['is_crypto'] == 'Y')).'</span></td>
                                            <td><span class="oco"><i class="fa fa-arrow-up"></i> OCO</span></td>
                                        </tr>';
                                                }
                                            }
                                        }
                                        echo '<tr id="no_asks" style="'.(is_array($asks) && count($asks) > 0 ? 'display:none;' : '').'"><td colspan="5">'.Lang::string('orders-no-ask').'</td></tr>';
                                        ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                      <!--modal-1-->
<div class="modal fade" id="openorders" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Open Orders</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Select the correct Currency pairs from the dropdown to display their successful transaction details.</p>
        
        <p><b>Amount:</b>The number of cryptocurrencies purchased.</p>
        <p><b>Value:</b> The cost of the cryptocurrency purchased. </p>
        <p><b>Price:</b> The per unit price of the Cryptocurrency purchased (Shown in USD)</p>
        <p><b>Fee:</b> The fee levied by the Exchange for each transaction.</p>
      </div>
    </div>
  </div>
</div>
        <?php include "includes/sonance_footer.php"; ?>
        <script>
            function redirectBasedOnCurrencies(c_currency, currency)
            {
                var url = window.location.origin+window.location.pathname+"?c_currency="+c_currency+"&currency="+currency;
                console.log(url);
                window.location.href = url;
            }
            
            $(document).ready(function(){
                $("#c_currency_select").on('change', function(){
                    redirectBasedOnCurrencies($(this).val(), $('#currency_select').find('option:selected').val());
                });
                $("#currency_select").on('change', function(){
                    redirectBasedOnCurrencies($("#c_currency_select").find('option:selected').val(), $(this).val());
                });
            });
            
        </script>
        <script type="text/javascript" src="js/ops.js?v=20160210"></script>
</html>