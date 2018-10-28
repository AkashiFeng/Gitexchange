<?php
class object {}
$CFG = new object();
if ($_SERVER['SERVER_NAME'] == 'localhost') {
	$CFG->dbhost = "localhost";
	$CFG->dbname = "bitexchange_cash";
	$CFG->dbuser = "root";
	$CFG->dbpass = "";
} else {
	$CFG->dbhost = "localhost";
$CFG->dbname = "bitexchange_cash";
$CFG->dbuser = "root";
$CFG->dbpass = "xchange@123";
}


?>