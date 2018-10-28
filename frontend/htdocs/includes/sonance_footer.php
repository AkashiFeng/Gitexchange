<footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-xs-12 links">
                    <ul>
                        <li>
                        <a href="aboutus.php" rel="nofollow" target="_blank">About</a>
                        </li>
                        <li>
                        <a href="apidoc.php" rel="nofollow" target="_blank">API</a>
                        </li>
                        <li>
                        <a href="howitworks.php" target="_blank">How it works</a>
                        </li>
                        <li>
                        <a href="terms-conditions.php" target="_blank">Terms</a>
                        </li>                 
                         <!--  <li><a href="https://bitexchange.live/fee-schedule.php" rel="nofollow" target="_blank">Fees</a></li>
                        <li><a href="https://bitexchange.live/contact.php" target="_blank" rel="nofollow">Contact</a></li> -->
                    </ul>
                </div>
                <div class="col-md-6 col-xs-12 social">
                    <ul>
                        <li><a href="https://www.facebook.com/bitcoin.exchange.script" rel="nofollow" target="_blank"><i class=""><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="https://twitter.com/ExchangeScript" rel="nofollow" target="_blank"><i class="fab fa-twitter"></i></a></li>
                        <li><a href=" https://bitcoinscript.bitexchange.systems/" rel="nofollow" target="_blank"><i class="fab fa-medium-m"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="row copy-bar">
                <div class="col-md-6 col-xs-12 copy">
                    <p>&copy; 2018 <?= $CFG->exchange_name; ?> All Rights Reserved</p>
                </div>
                <div class="col-md-6 col-xs-12 statistics">
                    <a title="Realtime application protection" href="https://www.sqreen.io/?utm_source=badge"><img style="width:109px;height:36px;float:right;" src="https://s3-eu-west-1.amazonaws.com/sqreen-assets/badges/20171107/sqreen-light-badge.svg" alt="Sqreen | Runtime Application Protection" /></a>
                   <!--  <p><span class="gray-color">24h Volume：</span> 1,211,621.18 <span class="gray-color">LTC/</span> 81,420.07 <span class="gray-color">BTC/</span> 238,606.22 <span class="gray-color">ETH/674,419,885.28 <span class="gray-color">USDT</span> </p> -->
                </div>
            </div>
        </div>
    </footer>

<!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js "></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js " integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q " crossorigin="anonymous "></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js " integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl " crossorigin="anonymous "></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tour/0.11.0/js/bootstrap-tour.min.js"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js "></script>
    <script type="text/javascript " language="javascript " src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js ">
    </script>
    <script type="text/javascript " language="javascript " src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js ">
    </script>
    <!-- Color Switcher -->
    <script type="text/javascript" src="sonance/js/jquery.colorpanel.js"></script>
    <!-- Custom Scripts -->
    <script type="text/javascript " src="sonance/js/script.js "></script>
    
</body>

<script type="text/javascript ">
$(document).ready(function() {
    $('.info-data-table').DataTable();
});
</script>

</script>
<script type="text/javascript ">
jQuery(document).ready(function($) {
    $(".clickable-row ").click(function() {
        window.location = $(this).data("href ");
    });
});
</script>
