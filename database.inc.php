<?php
$servername = "localhost";   
$username   = "u954949747_ananya";    
$password   = "Ananya@2026";     
$database   = "u954949747_ananya";


$con = mysqli_connect($servername, $username, $password, $database);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
?>