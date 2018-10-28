<!DOCTYPE html>
<html lang="en">
    <?php include '../lib/common.php';
        include "includes/sonance_header.php"; 
        
        if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
            Link::redirect('settings.php');
        elseif (User::$awaiting_token)
            Link::redirect('verify-token.php');
        elseif (!User::isLoggedIn())
            Link::redirect('login.php');
        
        $currencies = Settings::sessionCurrency();
        // $page_title = Lang::string('order-book');
       

        $c_currency1 = $_GET['c_currency'] ? : 28;
        $currency1 = $_GET['currency'] ? : 27;


        //commented out because of not required to take currency and c_currency from session
        /* $currency1 = $currencies['currency'];
        $c_currency1 = $currencies['c_currency']; */
       /*  if(!$currency1 || empty($currency1)){
            $currency1 = 27 ;
        }
        if(!$c_currency1 || empty($c_currency1)){
            $c_currency1 = 28 ;
        } */
        $currency_info = $CFG->currencies[$currency1];
        $c_currency_info = $CFG->currencies[$c_currency1];
        
        API::add('Orders', 'get', array(false, false, false, $c_currency1, $currency1, false, false, 1));
        API::add('Orders', 'get', array(false, false, false, $c_currency1, $currency1, false, false, false, false, 1));
        API::add('Transactions', 'get', array(false, false, 1, $c_currency1, $currency1));
        $query = API::send();
        
        $bids = $query['Orders']['get']['results'][0];
        $asks = $query['Orders']['get']['results'][1];
        $last_transaction = $query['Transactions']['get']['results'][0][0];
        $last_trans_currency = ($last_transaction['currency'] == $currency_info['id']) ? false : (($last_transaction['currency1'] == $currency_info['id']) ? false : ' (' . $CFG->currencies[$last_transaction['currency1']]['currency'] . ')');
        $last_trans_symbol = $currency_info['fa_symbol'];
        $last_trans_color = ($last_transaction['maker_type'] == 'sell') ? 'price-green' : 'price-red';
        
        if ((!empty($_REQUEST['c_currency']) && array_key_exists(strtoupper($_REQUEST['c_currency']), $CFG->currencies)))
            $_SESSION['oo_c_currency'] = preg_replace("/[^0-9]/", "", $_REQUEST['c_currency']);
        else if (empty($_SESSION['oo_c_currency']) || $_REQUEST['c_currency'] == 'All')
            $_SESSION['oo_c_currency'] = false;
        
        if ((!empty($_REQUEST['currency']) && array_key_exists(strtoupper($_REQUEST['currency']), $CFG->currencies)))
            $_SESSION['oo_currency'] = preg_replace("/[^0-9]/", "", $_REQUEST['currency']);
        else if (empty($_SESSION['oo_currency']) || $_REQUEST['currency'] == 'All')
            $_SESSION['oo_currency'] = false;
        
        if ((!empty($_REQUEST['order_by'])))
            $_SESSION['oo_order_by'] = preg_replace("/[^a-z]/", "", $_REQUEST['order_by']);
        else if (empty($_SESSION['oo_order_by']))
            $_SESSION['oo_order_by'] = false;
        
        $open_currency1 = $_SESSION['oo_currency'];
        $open_c_currency1 = $_SESSION['oo_c_currency'];
        $order_by1 = $_SESSION['oo_order_by'];
        $trans_realized1 = (!empty($_REQUEST['transactions'])) ? preg_replace("/[^0-9]/", "", $_REQUEST['transactions']) : false;
        $id1 = (!empty($_REQUEST['id'])) ? preg_replace("/[^0-9]/", "", $_REQUEST['id']) : false;
        $bypass = (!empty($_REQUEST['bypass']));
        
        API::add('Orders', 'get', array(false, false, false, $c_currency1, $currency1, 1, false, 1, $order_by1, false, 1));
        API::add('Orders', 'get', array(false, false, false, $c_currency1, $currency1, 1, false, false, $order_by1, 1, 1));
        $query = API::send();
        
        $open_bids = $query['Orders']['get']['results'][0];
        $open_asks = $query['Orders']['get']['results'][1];
        $open_currency_info = ($open_currency1) ? $CFG->currencies[strtoupper($open_currency1)] : false;
        
        if (!empty($_REQUEST['new_order']) && !$trans_realized1)
            Messages::add(Lang::string('transactions-orders-new-message'));
        if (!empty($_REQUEST['edit_order']) && !$trans_realized1)
            Messages::add(Lang::string('transactions-orders-edit-message'));
        elseif (!empty($_REQUEST['new_order']) && $trans_realized1 > 0)
            Messages::add(str_replace('[transactions]', $trans_realized1, Lang::string('transactions-orders-done-message')));
        elseif (!empty($_REQUEST['edit_order']) && $trans_realized1 > 0)
            Messages::add(str_replace('[transactions]', $trans_realized1, Lang::string('transactions-orders-done-edit-message')));
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
        
        $_SESSION["openorders_uniq"] = md5(uniqid(mt_rand(), true));
        
        //transaction
        
        API::add('Transactions', 'get', array(1, $page1, 30, $c_currency1, $currency1, 1, $start_date1, $type1, $order_by1));
        $query = API::send();
        $total = $query['Transactions']['get']['results'][0];
        
        API::add('Transactions', 'get', array(false, $page1, 30, $c_currency1, $currency1, 1, $start_date1, $type1, $order_by1));
        API::add('Transactions', 'getTypes');
        $query = API::send();
        
        $transactions = $query['Transactions']['get']['results'][0];
        $transaction_types = $query['Transactions']['getTypes']['results'][0];
        $pagination = Content::pagination('transactions.php', $page1, $total, 30, 5, false);
        
        $currency_info = ($currency1) ? $CFG->currencies[strtoupper($currency1)] : array();
        
        if ($trans_realized1 > 0)
            Messages::add(str_replace('[transactions]', $trans_realized1, Lang::string('transactions-done-message')));
        ?>
    <style>
        .custom-select {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 2px;
            height: 28px !important;
        }
    </style>
    <body id="wrapper">
        <?php include "includes/sonance_navbar.php"; ?>
        <header>
            <div class="banner row">
                <div class="container content">
                    <h1>Trade History</h1>
                     <p class="text-white text-center">All your Successful completed Buy / Sell transactions are shown here</p>
                </div>
            </div>
        </header>
        <div class="page-container">
            <div class="container">
                <div class="row">
                    <? Messages::display(); ?>
                    <? Errors::display(); ?>
                    <div class="col-md-12">
                        <form action="" class="form-inline" style="padding: 20px;background: white;margin-top: 20px;">
                            <div class="form-group">
                                <label for="sel1" style="font-size: 12px;">Currency Pair &nbsp;
                                </label>

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
                            <div class="form-group" style="margin-left:10px">
                            <a class="download" href="transactions_downloaded.php?c_currency=<?php echo $c_currency1; ?>&currency=<?php echo $currency1; ?>" ><i class="fa fa-download"></i> <?= Lang::string('transactions-download') ?></a>
                            </div>
                        </form>
                        <span class="float-right">
                            <a href="#tradehistory" data-toggle="modal" style="    position: relative;top: -3em;right: 1em;">
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
                        <div class="info-table-outer">
                            <input type="hidden" id="refresh_transactions" value="1" />
                            <input type="hidden" id="page" value="<?= $page1 ?>" />
                            <table id="info-data-table " class="table row-border info-data-table table-hover balance-table table-border" cellspacing="0 " width="100% ">
                                <thead>
                                    <tr>
                                    <th><?= Lang::string('transactions-type') ?></th>
                                    <th><?= Lang::string('transactions-time') ?></th>
                                    <th><?= Lang::string('orders-amount') ?></th>
                                    <th><?= Lang::string('transactions-fiat') ?></th>
                                    <th><?= Lang::string('orders-price') ?></th>
                                    <th><?= Lang::string('transactions-fee') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?
                                        if ($transactions) {
                                            foreach ($transactions as $transaction) {
                                                $trans_symbol = $CFG->currencies[$transaction['currency']]['fa_symbol'];
                                                echo '
                                                <tr id="transaction_' . $transaction['id'] . '">
                                                    <input type="hidden" class="is_crypto" value="' . $transaction['is_crypto'] . '" />
                                                    <td>' . $transaction['type'] . '</td>
                                                    <td><input type="hidden" class="localdate" value="' . (strtotime($transaction['date'])) . '" /></td>
                                                    <td>' . Stringz::currency($transaction['btc'], true) . ' ' . $CFG->currencies[$transaction['c_currency']]['fa_symbol'] . '</td>
                                                    <td><span class="currency_char">' . $trans_symbol . '</span><span>' . Stringz::currency($transaction['btc_net'] * $transaction['fiat_price'], ($transaction['is_crypto'] == 'Y')) . '</span></td>
                                                    <td><span class="currency_char">' . $trans_symbol . '</span><span>' . Stringz::currency($transaction['fiat_price'], ($transaction['is_crypto'] == 'Y')) . '</span></td>
                                                    <td><span class="currency_char">' . $trans_symbol . '</span><span>' . Stringz::currency($transaction['fee'] * $transaction['fiat_price'], ($transaction['is_crypto'] == 'Y')) . '</span></td>
                                                </tr>';
                                            }
                                        }
                                        echo '<tr id="no_transactions" style="' . (is_array($transactions) ? 'display:none;' : '') . '"><td colspan="6" style="padding: 0;"><div class="" style="background: #f4f6f8; text-align:  center;
                                                "><img src="images/no-results.gif" style="width: 300px;height: auto;    float: none;" ></div></td></tr>';
                                        ?>
                                </tbody>
                            </table>
                            <?= $pagination ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
               <!--modal-1-->
<div class="modal fade" id="tradehistory" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Trade History</h5>
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