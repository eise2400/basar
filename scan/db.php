<?php

/* 
 * Stellt die Datenbankverbindung her und legt im Falle, dass die Basardatenbank
 * noch nicht existiert, diese an.
 */
    global $con;
    $con = mysqli_connect('localhost','root','');
    if (!$con) {
            die('Could not connect database!');
    }
    if (!mysqli_select_db($con, 'basar_local')) {
        $sql = "CREATE DATABASE basar_local";
        if ($con->query($sql) !== TRUE) {
            die('Datenbank konnte nicht angelegt werden!');
        }
    }
    mysqli_select_db($con, 'basar_local');
?>

