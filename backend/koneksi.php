<?php
    $host = "localhost";
    $username = "root";
    $password = "";
    $db = "db_project_praktikum";

    $connection = mysqli_connect($host, $username, $password, $db);
    if(!$connection){
        die("Koneksi database gagal: " . mysqli_connect_error());
    }
    
    // Set charset UTF-8
    mysqli_set_charset($connection, "utf8mb4");

    // Alias fleksibel agar kompatibel bila memanggil $connection atau $koneksi
    $koneksi = $connection;
?>