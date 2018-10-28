<!doctype html>
<html>

<head>
<title>Authentication</title>

<meta property="viewport" name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="css/new-style.css" rel="stylesheet" />
<link href="css/dashboard.css" rel="stylesheet" />
<link href="css/security.css" rel="stylesheet" />
<!-- <link rel="stylesheet" href="css/style.css?v=20160204" type="text/css" /> -->
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script>
$("div").click(function() {
window.location = $(this).find("a").attr("href");
return false;
});
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0" data-react-helmet="true">
</head>

<body class="app signed-in static_application index" data-controller-name="static_application" data-action-name="index" data-view-name="Coinbase.Views.StaticApplication.Index" data-account-id="">
<?php
// error_reporting(E_ALL); 
// ini_set('display_errors', 'On');
include '../lib/common.php';

API::add('Content','getRecord',array('reset-2fa'));
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$page_title = $content['title'];

?>
<div id="root">
<div class="Flex__Flex-fVJVYW iJJJTg">
<div class="Flex__Flex-fVJVYW iJJJTg">
<div class="Toasts__Container-kTLjCb jeFCaz"></div>
<div class="Layout__Container-jkalbK gCVQUv Flex__Flex-fVJVYW bHipRv">
<div class="LayoutDesktop__AppWrapper-cPGAqn WhXLX Flex__Flex-fVJVYW bHipRv">
    
    <? include 'includes/topheader.php'; ?>

    <div class="LayoutDesktop__ContentContainer-cdKOaO cpwUZB Flex__Flex-fVJVYW bHipRv">
        
    <? include 'includes/menubar.php'; ?>

    <div class="LayoutDesktop__Wrapper-ksSvka fWIqmZ Flex__Flex-fVJVYW cpsCBW">
        <div class="LayoutDesktop__Content-flhQBc bRMwEm Flex__Flex-fVJVYW gkSoIH">
            <div class="Dashboard__FadeFlex-bFoDXs cYFmKg Flex__Flex-fVJVYW iDqRrV">
                <div class="Flex__Flex-fVJVYW bHipRv">
                    <div></div>
                    <div class="Dashboard__Panels-getBDx fJxaut Flex__Flex-fVJVYW iDqRrV">
                        <div class="Flex__Flex-fVJVYW bHipRv">
                            <div class="Flex__Flex-fVJVYW gsOGkq">

                               <div class="Dashboard__ChartContainer-bKDMTA kjRPPr Flex__Flex-fVJVYW iDqRrV" style="height: auto;">
                                    <div class="Flex__Flex-fVJVYW gsOGkq" style="border: 1px solid #DAE1E9;width: 100%;border-right: none;">
                                        <div id="page" class="jdmxYg" style="width: 100%;padding-bottom: 2em;">

                                        <div class="row" style="margin: 0 !important;">
                                            <ul id="account_tabs" class="nav nav-tabs">
                                                <li <? if ($CFG->self == 'userprofile.php') { ?> class="active" <?php } ?>>
                                                    <a href="userprofile.php">Profile</a>
                                                </li>
                                                <li <? if ($CFG->self == 'bank-accounts.php') { ?> class="active" <?php } ?>>
                                                    <a href="bank-accounts.php">Bank</a>
                                                </li>
                                                <li <? if ($CFG->self == 'usersecurity.php') { ?> class="active" <?php } ?>>
                                                    <a href="usersecurity.php">Security</a>
                                                </li>
                                                <li>
                                                    <a href="userapi.php" <? if ($CFG->self == 'userapi.php') { ?> class="active" <?php } ?>>API</a>
                                                </li>

                                            </ul>
                                           
    <div class="row fields">
        <? Errors::display(); ?>                        
        <div class="text"><?= $content['content'] ?></div>
    </div>

                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        
        <!-- Footer Section Starts Here -->
        <?php include "includes/footer.php"; ?>
        <!-- Footer Section Ends Here -->
        <div class="Backdrop__LayoutBackdrop-eRYGPr cdNVJh"></div>
    </div>
</div>
</div>
</div>
<div></div>
</div>
</div>

<script>
$(document).ready(function(){
$(".Header__DropdownButton-dItiAm").click(function(){
$(".DropdownMenu__Wrapper-ieiZya.kwMMmE").toggleClass("show-menu");
});
});
</script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script>
    document.getElementById('test').addEventListener('change', function () {
    var style = this.value == 1 || this.value == 2 ? 'block' : 'none';
    document.getElementById('hidden_div').style.display = style;

});
    </script>
    <script type="text/javascript" src="js/ops.js?v=20160210"></script>

</body>

</html>