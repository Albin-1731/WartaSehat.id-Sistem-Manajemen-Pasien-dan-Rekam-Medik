<?php
    $host = "localhost";
    $username = "root";
    $password = "";
    $db = "db_project_praktikum";

    $connection = mysqli_connect($host,$username,$password,$db);
    if(!$connection){
        die("koneksi database gagal!" . mysqli_connect_error());
    }
?>