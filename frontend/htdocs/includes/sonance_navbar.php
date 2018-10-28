<style type="text/css">
    .dropdown-menu
{
left : initial;
right:0;
}
</style>
<nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggler" aria-controls="navbarToggler" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="index">
                <img src="images/star.png" alt="img" class="logo-star">
                <img src="images/logo1.png" alt="img" class="main-logo" style="filter: invert(100%);" />
                <!-- <img class="logo" src="sonance/img/logo.png" alt=""> -->
            </a>
            <?php if (User::isLoggedIn()): ?>
            <div class="collapse navbar-collapse justify-content-md-center" id="navbarToggler">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="balances">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Funds</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="depositnew">
                                Deposits
                            </a>
                            <a class="dropdown-item" href="withdraw">
                                Withdrawals
                            </a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Orders</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="openorders">
                                Your Open Orders
                            </a>
                           
                            <a class="dropdown-item" href="tradehistory">
                                Trade History
                            </a>
                             <a class="dropdown-item" href="orderhistory">
                                Order Table
                            </a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="userbuy?trade=BTC-USD">Simple Trade</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cryptoaddress">Crypto Address</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cryptowalletnew">Crypto Wallet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="depositnew">Fiat Wallet</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user"></i></a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="myprofile">
                                <strong>Account</strong><br>
                                <span><?= User::$info['first_name']?></span>
                            </a>
                            <a class="dropdown-item" href="mysecurity">
                                Security
                            </a>
                            <a class="dropdown-item" href="logout.php?log_out=1&uniq=<?= $_SESSION["logout_uniq"] ?>">
                                Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <? else: ?>
            <div class="collapse navbar-collapse justify-content-md-center" id="navbarToggler">
                <ul class="navbar-nav ml-auto">  
                    <li class="nav-item">
                        <a class="nav-link" href="#">Support</a>
                    </li>              
                    <li class="nav-item">
                        <a class="nav-link" href="login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register">Register</a>
                    </li>
                </ul>
            </div>
            <? endif; ?>
        </div>
    </nav>

<script>
window.onload = function() {
    
    //if the current page is simple trade page 
    if(window.location.pathname.search("/userbuy") != -1) {
        
        document.querySelector('title').innerHTML = '<?=$CFG->exchange_name?> | Simple Trade'
    } 
    // if the current page any other page hading heading container
    else  {
        document.querySelector('title').innerHTML = '<?=$CFG->exchange_name?> | '+document.querySelector('.banner > .container > h1').innerText
    }
    
}
</script>