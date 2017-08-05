<?php

require 'debugHelper.php';
require_once 'db.php';

ini_set('default_charset','utf-8');
header('Content-Type: text/json; > charset=utf-8');

if(isset($_GET['barcode']))
{
    restartDebug();
    $barcode = $_GET["barcode"];

    // regular expression for barcode
    $reg = "^([0-9]{3})1([0-9]{2})(5|6)([0-9]{1})$";
    if (ereg($reg, $barcode))
    {
    	$userNummer = substr($barcode,0,3);
        //mysqli_query($con, 'SET NAMES utf8');
        $sql = "SELECT * FROM items join users on items.user_id = users.id WHERE users.nummer={$userNummer} ORDER BY items.preis ASC";
        $result = mysqli_query($con,$sql);

        $cnt = $result->num_rows;
        if ($cnt==0) {
        	sendDbg("ERR: NOT IN DB "+$barcode);
        	header("NOT IN DATABASE",true,404);
        	$err = array("msg"=>rawurlencode("Keine Artikel oder kein Benutzer {$userNummer} in der Datenbank"), "id"=>151);
        	echo json_encode($err);
        	return;
        }


		$usr = "";
        $itemlist = array();
        while ($row = mysqli_fetch_array($result))
        {
        	if ($usr=="")
        	{
        		$usr = $row["name"]." ".$row["vorname"]." (".$userNummer.")";
        	}
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

            array_push($itemlist,$item);


        }
        $retArray=array("usr"=>rawurlencode($usr),"cnt"=>$cnt, "cart"=>$itemlist);
        //$retArray=array("usr"=>$usr,"cnt"=>$cnt);
        echo json_encode($retArray);
        print_r($retArray);
        return;

    } else {
        sendDbg("ERR: BARCODE INVALID "+$barcode);
        header("BARCODE INVALID",true,404);
        $err = array("msg"=>rawurlencode("Barcode {$barcode} ungültig"), "id"=>140);
        echo json_encode($err);
        return;
    }
}



?>
