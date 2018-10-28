<?php
class object {}
$CFG = new object();
if ($_SERVER['SERVER_NAME'] == 'localhost') {
	$base_url = "http://localhost/bit_live/";
	$CFG->api_url = $base_url.'api/htdocs/api.php';
	$CFG->auth_login_url = $base_url.'auth/htdocs/login.php';
	$CFG->auth_verify_token_url = $base_url.'auth/htdocs/verify_token.php';
} else {
	$base_url = "http://167.99.199.150/bit_live/";
$CFG->api_url = 'https://api.bitexchange.cash/api.php';
$CFG->auth_login_url = 'https://auth.bitexchange.cash/login.php';
$CFG->auth_verify_token_url = 'https://auth.bitexchange.cash/verify_token.php';
}


?>