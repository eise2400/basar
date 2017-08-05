<?php

require "debugHelper.php";
require_once "db.php";

if (isset($_POST['regId']))
{
	sendDbg("----------------- START SYNC REMOTE REQUEST");
	$regId = $_POST['regId'];
	$lastId = $_POST['lastId'];
	
	sendDbg("SYNC REMOTE: CONNECT DB");
	
	// set each item in the cart to sold	
	$syncArray=array();
	
	$sql = "SELECT * FROM sync WHERE register_id={$regId} AND reg_item_id>{$lastId} ORDER BY reg_item_id LIMIT 20";
	sendDbg($sql);
	$result = mysqli_query($con,$sql);
	if ($result->num_rows >0)
	{
            while ($row=mysqli_fetch_array($result))
            {
                $line=array();
                $line["register_id"]=$row["register_id"];
                $line["reg_item_id"]=$row["reg_item_id"];
                $line["item_id"]	=$row["item_id"];			
                $line["barcode"]	=$row["barcode"];
                $line["created"]	=$row["created"];
                sendDbg("SYNC REMOTE: Add item:");
                sendDbg(print_r($line,true));
                array_push($syncArray, $line);				
            }
            $jSyncArray=json_encode($syncArray,true);
            sendDbg($jSyncArray);
            echo($jSyncArray);
            sendDbg("----------------- END SYNC REMOTE REQUEST - SUCCESS");
            return;
	}
	sendDbg("NO Rows");
	header("NO SYNC",true,404);
	$err = array("msg"=>"NO SYNC", "id"=>144);
	echo json_encode($err);
	
	sendDbg("----------------- END SYNC REMOTE REQUEST - FAILED");
	return;	
}

?>