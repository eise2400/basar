<?php

require 'debugHelper.php';
require_once 'db.php';

function defaultExceptionHandler($exception) {
	sendDbg("EXCEPTION:".$exception->getMessage());
}

if (isset($_POST['cart']))
{
	set_exception_handler('defaultExceptionHandler');
	restartDebug();
	sendDbg("----------------- START CHECKOUT");
	
	$jcart = $_POST['cart'];	
	
	$cart = json_decode($jcart,true);
	sendDbg("Cart:\n".print_r($cart,true));

	$soldItems=array();


	// set each item in the cart to sold
	foreach ($cart as $item )
	{	
		$sql = "UPDATE items SET verkauft=1 WHERE id={$item['iid']}";
		$result = mysqli_query($con,$sql);
		sendDbg($sql."\nRESULT: ".print_r($result,true));
		$item["sold"]="1";
		array_push($soldItems ,$item);
	}
	
	sendDbg("SoldItems:\n".print_r($soldItems,true));
	
	writeToSync($soldItems);
	
	echo(json_encode($soldItems,true));
	sendDbg("----------------- END CHECKOUT");

}

function writeToSync($soldItems)
{
	global $con;
	set_exception_handler('defaultExceptionHandler');
	$reg_item_id=0;
	$regId=0;
	
	$sql = "SELECT * FROM registers WHERE local=1";
	$result = mysqli_query($con,$sql);
	sendDbg($sql."\nRESULT: ".print_r($result,true));

	if ($result->num_rows >0)
	{
		$row=mysqli_fetch_array($result);
		$regId = $row["id"];		
	}
	
	if ($regId>0)
	{
		//get the index for the next entry into the sync database
		$sql = "SELECT reg_item_id FROM sync WHERE register_id={$regId} ORDER BY reg_item_id DESC LIMIT 1";
		
		$result = mysqli_query($con,$sql);
		sendDbg($sql."\nRESULT: ".print_r($result,true));
		if ($result->num_rows >0)
		{
			$row=mysqli_fetch_array($result);
			$reg_item_id=$row["reg_item_id"]+1;
		}	
		foreach ($soldItems as $item )
		{
			// do the entry into the sync database
			$sql = "INSERT INTO sync (register_id,reg_item_id,item_id,barcode) VALUES ({$regId},{$reg_item_id},{$item['iid']},{$item['bc']})";
			$result = mysqli_query($con,$sql);
			sendDbg($sql."\nRESULT: ".print_r($result,true));
			$reg_item_id +=1;
		}
		return $reg_item_id-1;
	}
	return -1;
}


?>
