<?php

require 'debugHelper.php';
require_once 'db.php';

ini_set('default_charset','utf-8');
header('Content-Type: text/json; > charset=utf-8');

if(isset($_POST['barcode']))
{
    restartDebug();
    $barcode = $_POST["barcode"];

    // regular expression for barcode
    $reg = "^([0-9]{3})1([0-9]{2})(5|6)([0-9]{1})$";
    if (ereg($reg, $barcode))
    {
        //mysqli_query($con, 'SET NAMES utf8');
        $sql = "SELECT * FROM items WHERE barcode = $barcode";
        $result = mysqli_query($con,$sql);

        if ($result->num_rows >0)
        {
            $row = mysqli_fetch_array($result);
            if ($row['verkauft']=="0")
            {
                $item=array();
                $item['bc'] = $barcode;

                if (!is_null($row['id'])) {
                        $item['iid'] = $row['id'];
                }
                if (!is_null($row['bezeichnung'])) {
                        $item['txt'] = rawurlencode($row['bezeichnung']);
                }
                if (!is_null($row['groesse'])) {
                        $item['size'] = rawurlencode($row['groesse']);
                }
                if (!is_null($row['preis'])) {
                        $item['price'] = $row['preis'];
                }
                if (!is_null($row['verkauft'])) {
                        $item['sold'] = $row['verkauft'];
                }
                sendDbg(print_r($item,true));
                echo json_encode($item);
                return;
            } else {
                sendDbg("ERR: ALREADY SOLD "+$barcode);
                header("ALREADY SOLD",true,404);
                $err = array("msg"=>rawurlencode("Artikel  -  {$barcode} : {$row['bezeichnung']} - bereits verkauft"), "id"=>144, );
                echo json_encode($err);
                return;
            }
        } else {
            sendDbg("ERR: NOT IN DB "+$barcode);
            header("NOT IN DATABASE",true,404);
            $err = array("msg"=>rawurlencode("Artikel {$barcode} nicht in der Datenbank"), "id"=>142);
            echo json_encode($err);
            return;
        }
    } else {
        sendDbg("ERR: BARCODE INVALID "+$barcode);
        header("BARCODE INVALID",true,404);
        $err = array("msg"=>rawurlencode("Barcode {$barcode} ungültig"), "id"=>140);
        echo json_encode($err);
        return;
    }
}



?>
