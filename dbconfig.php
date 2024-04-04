<?php

// Replace with the actual host, dbusername, dbpassword, and database name for your MariaDB server
$host = 'localhost';
$dbusername = 'root';
$dbpassword = ''; //Mycloud@456
$database = 'openshiftdb';

// Connect to MariaDB
$cnx = new mysqli($host, $dbusername, $dbpassword, $database);

// Check connection
if ($cnx->connect_error) {
    die("Connection failed: " . $cnx->connect_error);
}

?>