<? 
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// echo "string"; exit;
// error_reporting(E_ALL & ~E_NOTICE); // it shows all deprecated errors.
// ini_set('display_errors', 'On');
include 'lib/common.php';
ini_set("memory_limit","200M");

$con = mysqli_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass,$CFG->dbname);
$sql="SELECT * FROM currencies WHERE is_crypto='Y'";
$result=mysqli_query($con,$sql);
$currencies = mysqli_fetch_all($result,MYSQLI_ASSOC);

$CFG->currencies = $currencies;
//echo "<pre>"; print_r($CFG->currencies); exit;
$CFG->print = $_REQUEST['print'];
$CFG->url = ($_REQUEST['current_url'] != 'index.php') ? preg_replace("[^a-zA-Z_\-]", "",$_REQUEST['current_url']) : '';
$CFG->action = preg_replace("[^a-zA-Z_\-]", "",$_REQUEST['action']);

$CFG->inset_id = false;

$_SESSION['last_query'] = $_SESSION['this_query'];
$_SESSION['this_query'] = 'index.php?'.http_build_query((is_array($_POST)) ? $_POST : $_GET);
date_default_timezone_set($CFG->default_timezone);
Stringz::magicQuotesOff();

if ($CFG->locale) {
	setlocale(LC_ALL,$CFG->locale);
}

if (!$CFG->bypass || ($CFG->bypass && $CFG->print)) {
	$header = new Header();
	$header->metaAuthor();
	$header->metaDesc();
	$header->metaKeywords();
	$header->cssFile('css/colorpicker.css');
	$header->cssFile('css/reset.css');
	$header->cssFile('css/'.$CFG->skin.'/default.css','all');
	$header->cssFile('css/'.$CFG->skin.'/default_ie6.css','all','IE 6');
	$header->cssFile('css/'.$CFG->skin.'/default_ie7.css','all','IE 7');
	$header->cssFile('css/'.$CFG->skin.'/default_ie8.css','all','IE 8');
	$header->jsFile('js/jquery-1.4.2.min.js');
	$header->jsFile('js/jquery-ui-1.8.5.custom.min.js');
	$header->jsFile('js/ajax.js');
	$header->jsFile('js/calendar.js');
	$header->jsFile('js/colorpicker.js');
	$header->jsFile('js/comments.js');
	$header->jsFile('js/form.js');
	$header->jsFile('js/file_manager.js');
	$header->jsFile('js/flow_chart.js');
	$header->jsFile('js/gallery.js');
	$header->jsFile('js/grid.js');
	$header->jsFile('js/multi_list.js');
	$header->jsFile('js/popups.js');
	$header->jsFile('js/page_maker.js');
	$header->jsFile('js/permissions.js');
	$header->jsFile('js/swfupload.js');
	$header->jsFile('js/jquery.swfupload.js');
	$header->jsFile('ckeditor/ckeditor.js');
	$header->jsFile('js/Ops.js');
	$header->js('CKEDITOR.dtd.$removeEmpty[\'span\'] = false;');
	$header->display();
	$header->getJsGlobals();
}

if ($_REQUEST['authy_form']) {
	$token1 = preg_replace("/[^0-9]/", "",$_REQUEST['authy_form']['token']);
	
	if (!($token1 > 0))
		Errors::add('Invalid token.');

	if (!is_array(Errors::$errors)) {
		$response = Google2FA::verify_key(User::$info['authy_id'],$token1);
		if (!$response)
			Errors::add('Invalid token.');

		if (!is_array(Errors::$errors)) {
			$_SESSION['token_verified'] = 1;
			Errors::$errors = false;
		}
	}
}

if (User::isLoggedIn() && !(User::$info['verified_authy'] == 'Y' && !($_SESSION['token_verified'] > 0))) {
	$CFG->user_id = User::$info['id'];
	$CFG->group_id = User::$info['f_id'];
	if (!$CFG->bypass || ($CFG->url == 'edit_page' && !$_REQUEST['tab_bypass'])) {
		include_once 'includes/popups.php';

if (!empty($_REQUEST['bitcoins'])) {
	
	$btc_address1 = $_REQUEST['btc_address'] ;
	$btc_amount1 = $_REQUEST['btc_amount'] ;
	$c_currency = $_REQUEST['currency'] ;
	foreach($CFG->currencies as $row){
		if($row['id'] == $c_currency){
			$c_currency_info = $row ;
			break ;
		}
	}
	
	$walletSql = "SELECT * FROM wallets WHERE  c_currency = ".$c_currency." LIMIT 0,1" ;
	$walletResult =mysqli_query($con,$walletSql);
	$wallets = mysqli_fetch_all($walletResult,MYSQLI_ASSOC);
	$wallet = $wallets[0] ;
	if(empty($btc_address1)){
		Errors::add('Invalid Withdrawal Address');
	}
	if (($btc_amount1 - $wallet['bitcoin_sending_fee']) < 0.00000001){
		Errors::add('Withdrawal Amount is either 0 or very low.');
	}
	if ($btc_amount1 > $wallet['hot_wallet_btc']){
		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],'The withdrawal amount exceeds your available funds.'));
	}
	
	if (!BitcoinAddresses::validateAddress($c_currency, $btc_address1,$wallet)){
		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],'You have specified an invalid [c_currency] address.'));
	 	echo "3\n" ;		
	}
	
	if (!is_array(Errors::$errors)) {
		if($c_currency != 45){
			$bitcoin = new Bitcoin($wallet['bitcoin_username'],$wallet['bitcoin_passphrase'],$wallet['bitcoin_host'],$wallet['bitcoin_port'],$wallet['bitcoin_protocol']);
			$bitcoin->settxfee($wallet['bitcoin_sending_fee']);
			$bitcoin->walletpassphrase($wallet['bitcoin_passphrase'],3);
			$response = $bitcoin->sendfrom($wallet['bitcoin_accountname'],$btc_address1,(float)bcsub($btc_amount1,$wallet['bitcoin_sending_fee'],8));
			if (!empty($bitcoin->error)){
				echo $bitcoin->error.PHP_EOL;
				Errors::add($bitcoin->error.PHP_EOL);
				Errors::display();	
			}else{
				$hotWalletBalance = $wallet['hot_wallet_btc'] - $btc_amount1 ;
				db_update('wallets',$wallet['id'],array('hot_wallet_btc'=>$hotWalletBalance)) ;				
				Messages::add("Successfully Transferred to Cold Wallet ".$btc_address1." . Transaction Id : ".$response);
				Messages::display();
			}
		}else{
			//TODO ETHEREUM WALLET TRANSFER ;
		}
	}else{
		echo "IN ERRORS" ;
		Errors::display();
	}
	
}
?>
<div id="head">
	<?php
	$logos = DB::getFiles('settings_files',1,'logo',1);
	$logo_img = ($logos && file_exists('uploads/'.$logos[0]['name'].'_logo.png')) ? 'uploads/'.$logos[0]['name'].'_logo.png' : 'images/logo.png';
	?>
	<div class="logo"><img src="<?= $logo_img ?>" /></div>
	<div class="nav_buttons">
		<? if (User::$info['is_admin'] == 'Y') { ?>


		<div class="nav_button admin">
			<div class="c">
				<div class="icon"></div>
				<div class="label"><?= $CFG->admin_button ?></div>
				<div class="drop"></div>
				<div class="clear"></div>
			</div>
			<div class="options">
				<div class="contain">
					<?= Link::url('settings','<div class="icon settings"></div><div class="label1">'.$CFG->path_settings.'</div>') ?>
					<?= Link::url('users','<div class="icon users"></div><div class="label1">'.$CFG->path_users.'</div>',false,false,false,'content','alt') ?>
					<? if ($CFG->url != 'edit_page') { ?>
					<a href="#" onclick="pmOpenPage()"><div class="icon edit_this"></div><div class="label1"><?= $CFG->edit_tabs_this_button?></div></a>
					<?= Link::url('edit_tabs','<div class="icon edit_pages"></div><div class="label1">'.$CFG->edit_tabs_button.'</div>',false,false,false,'content','alt') ?>
					<? } ?>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="tl1_shadow"></div>
					<div class="tl2_shadow"></div>
					<div class="tr1_shadow"></div>
					<div class="tr2_shadow"></div>
					<div class="bl1_shadow"></div>
					<div class="bl2_shadow"></div>
					<div class="br1_shadow"></div>
					<div class="br2_shadow"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="r"></div>
			<div class="l"></div>
		</div>
		<? } ?>
		<div class="nav_button user">
			<div class="c">
				<div class="icon"></div>
				<div class="label"><?= User::$info['first_name'].' '.User::$info['last_name'] ?></div>
				<div class="drop"></div>
				<div class="clear"></div>
			</div>
			<div class="options">
				<div class="contain">
					<?= Link::url('my-account','<div class="icon my_account"></div><div class="label1">'.$CFG->my_account_button.'</div>') ?>
					<a class="alt" href="index.php?logout=1&current_url=<?= $CFG->current_url ?>"><div class="icon logout"></div><div class="label1"><?= $CFG->logout_button ?></div></a>
					<div class="t_shadow"></div>
					<div class="r_shadow"></div>
					<div class="b_shadow"></div>
					<div class="l_shadow"></div>
					<div class="tl1_shadow"></div>
					<div class="tl2_shadow"></div>
					<div class="tr1_shadow"></div>
					<div class="tr2_shadow"></div>
					<div class="bl1_shadow"></div>
					<div class="bl2_shadow"></div>
					<div class="br1_shadow"></div>
					<div class="br2_shadow"></div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="r"></div>
			<div class="l"></div>
		</div>
	</div>
	<div class="clear"></div>
	<div class="main_menu">
<?
		if ($CFG->url != 'edit_page') {
		?>
		<div class="menu_item">
			<?= Link::url('index.php','<div class="home_icon"></div>') ?>
		</div>
		<? 
			$tabs = Ops::getTabs();
			if ($tabs) {
				foreach ($tabs as $tab) {
					$pages = Ops::getPages($tab['id']);
					$c = count($pages);
					$split = false;
					$width_class = false;

		
					if ($c > 10) {
						$split = ceil($c/3);
						$width_class = 'triple';
					}
					elseif ($c > 5) {
						$split = ceil($c/2);
						$width_class = 'double';
					}		

					
					echo '
					<div class="menu_item">
						'.Link::url($tab['url'],$tab['name'].(($pages) ? '<div class="drop"></div>' : ''),false,array('is_tab'=>1));
					
					if ($pages) {
						$i = 0;
						
						echo '
						<div class="options '.$width_class.'">
							<div class="contain">
								<ul>';
						foreach ($pages as $page) {
							echo '<li>'.Link::url($page['url'],$page['name']).'</li>';
							$i++;
							
							if ($split && ($i % $split == 0))
								echo '<div class="clear"></div></ul><ul>';
						}
						echo '
							<div class="clear"></div>
								</ul>
								<div class="t_shadow"></div>
								<div class="r_shadow"></div>
								<div class="b_shadow"></div>
								<div class="l_shadow"></div>
								<div class="tl1_shadow"></div>
								<div class="tl2_shadow"></div>
								<div class="tr1_shadow"></div>
								<div class="tr2_shadow"></div>
								<div class="bl1_shadow"></div>
								<div class="bl2_shadow"></div>
								<div class="br1_shadow"></div>
								<div class="br2_shadow"></div>
								<div class="clear"></div>
							</div>
						</div>';
					}
					echo '
					</div>';
				}
			}
		}
		else {
			$page_info = DB::getRecord((($CFG->is_tab) ? 'admin_tabs' : 'admin_pages'),$CFG->id,0,1);
			if (!$page_info['is_ctrl_panel'] || $page_info['is_ctrl_panel'] == 'N') {
				echo '
				<div class="menu_item"><a class="'.((!$CFG->action) ? 'high' : false).'" href="#">'.$CFG->pm_list_tab.'</a></div>
				<div class="menu_item"><a class="'.(($CFG->action == 'form') ? 'high' : false).'" href="#">'.$CFG->pm_form_tab.'</a></div>
				<div class="menu_item"><a class="'.(($CFG->action == 'record') ? 'high' : false).'" href="#">'.$CFG->pm_record_tab.'</a></div>';
			}
			else {
				echo '
				<div class="menu_item"><a class="'.((!$CFG->action) ? 'high' : false).'" href="#">'.$CFG->pm_ctrl_tab.'</a></div>';
			}
			
			echo '
			<div class="pm_nav">';
			PageMaker::showTabsPages();
			echo '
				<div class="pm_exit"><div class="pm_exit_icon" onclick="pmExitEditor();"></div> <a href="index.php" onclick="pmExitEditor();return false;">'.$CFG->pm_exit.'</a></div>
			</div>';
		}
?>
	</div>
</div>
<?	
		if ($CFG->url != 'edit_page')
			echo '<div id="content">';
	}
	
	
} ?>
<style>
.form-otr{
	background-color: #fff;
	max-width: 800px;
	margin: 2em  auto ;
	border:1px solid #ddd;
	padding:2em;
	box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.calc.dotted {
    border-bottom: 1px dashed #0667D0;
    margin-bottom: 20px;
}
.form-control {
    border: 1px solid #DAE1E9;
    width: 100%;
    height: 50px;
    padding: 10px;
    outline: none;
    border-radius: 4px;
    background-color: #fff;
    font-size: 16px;
    font-weight: 400;
}
.bskbTZ {
    position: relative;
    margin-bottom: 25px;
}
.current-otr p,.calc.dotted p {
    margin: 10px 0;
    font-size: 15px;
}
.calc.dotted .pull-right, .current-otr .pull-right {
    float: right;
}
.buy-btc {
    position: relative;
    width: 100%;
    margin: 1em 0;
    border-radius: 4px;
    outline: none;
    font-weight: 600;
    cursor: pointer !important;
    color: #FFFFFF;
    cursor: default;
    padding: 20px 35px;
    font-size: 18px;
    border: 1px solid #2E7BC4;
    background-color: #3C90DF;
    border: 1px solid #2E7BC4;
    background-color: #3C90DF;
}
.form-otr h4{
	font-size: 18px;
	margin-bottom: 10px;
}
.form-otr h2{
	margin: 0 0 1em;
}
.form-otr .form-group input[type="text"]{
	 height: 30px;
    width: 97%;

}
.form-otr  .input-caption {
    bottom: 12px;
    position: absolute;
    right: 10px;
    color: grey;
}
</style>
<div class="form-otr">
	<h2>Send Cryptos</h2>
	<form id="buy_form" action="cryptowallet.php" method="POST">
	<!-- <div class="calc dotted">
	    <p>
	        Available BTC <span class="pull-right">0 BTC</span>
	    </p>
	</div> -->

	<div class="TradeSection__Wrapper-jIpuvx bskbTZ">
	<div class="TradeSection__Label-bicWvY CrFOg Flex__Flex-fVJVYW gsOGkq">
	    <h4 class="Heading__StyledHeading-sALAQ hwfHDH" style="font-size: 18px;">Select Currency</h4>
	</div>
	<div>
	    <div class="Flex__Flex-fVJVYW gkSoIH">
	        <div class="form-group">
	        	<select id="c_currency" name="currency" class="form-control">
				<?
				if ($currencies) {
					foreach ($currencies as $key => $currency) {
						if ($currency['is_crypto'] != 'Y')
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

	<div class="TradeSection__Wrapper-jIpuvx bskbTZ">
	<div class="TradeSection__Label-bicWvY CrFOg Flex__Flex-fVJVYW gsOGkq">
	    <h4 class="Heading__StyledHeading-sALAQ hwfHDH" style="font-size: 18px;">Send to Address</h4>
	</div>
	<div>
	    <div class="Flex__Flex-fVJVYW gkSoIH">
	        <div class="form-group">
	        	<input type="text" class="form-control " id="btc_address" name="btc_address" value="" placeholder="n1SQ34aEvDe2SG2kQ5CXV5o9VUGffrBc8f">
	        </div>
	    </div>
	</div>
	</div>

	<div class="TradeSection__Wrapper-jIpuvx bskbTZ">
	<div class="TradeSection__Label-bicWvY CrFOg Flex__Flex-fVJVYW gsOGkq">
	    <h4 class="Heading__StyledHeading-sALAQ hwfHDH" style="font-size: 18px;">Amount to Send</h4>
	</div>
	<div>
	    <div class="Flex__Flex-fVJVYW gkSoIH">
	        <div class="form-group">
	        	<input type="text" class="form-control" id="btc_amount" name="btc_amount" value="0">
	        <!-- <div class="input-caption">BTC</div> -->
	        </div>
	    </div>
	</div>
	</div>

	<!-- <div class="current-otr">
	    <p>
	        Blockchain Fee <span class="pull-right"><span id="withdraw_btc_network_fee">0.0001</span> BTC</span>
	    </p>
	</div>
	<div class="current-otr">
	    <p>
	       <span id="withdraw_btc_total_label">BTC to Receive </span>
	        <span class="pull-right"><span id="withdraw_btc_total">0</span></span>
	    </p>
	</div> -->
	<input type="hidden" name="bitcoins" value="1">
	<input type="submit" name="submit" value="Send Crypto" class="but_user buy-btc">
	</form>
</div>
	<?php echo '
	<div class="credits" id="credits"><div>&copy; 2018 <a href="https://bitexchange.live/">BitExchange Systems</a>.</div></div>
	</body></html>'; 
	// $header->jsFile('js/connect.js');
?>
