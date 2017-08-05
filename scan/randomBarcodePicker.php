<?php


// connect to database
$con = mysqli_connect('localhost','root','','basar');
if (!$con) {
	echo("Database Error");
	die('Could not connect: ' . mysqli_error($con));
}
mysqli_select_db($con,"basar");

$barcodes=array();



// get the index for the next entry into the sync database
$users = array();
$sql = "SELECT * FROM users ORDER BY RAND() LIMIT 40";
$result = mysqli_query($con,$sql);
if ($result->num_rows >0)
{
	while ($row=mysqli_fetch_array($result))
	{				
		array_push($users, $row);
	}
}

// set each item in the cart to sold
foreach ($users as $user )
{
	$sql = "SELECT * FROM items WHERE user_id={$user["id"]} ORDER BY RAND() LIMIT 2";
	$result = mysqli_query($con,$sql);
	print_r($result,true);
	$unsoldItems=array();

	if ($result->num_rows >0)
	{
		while ($row=mysqli_fetch_array($result))
		{			
			$itemId = sprintf("%02d", $row['nummer']);
			$nutz = $user['nummer'].'1'.$itemId.'6';
			print($nutz.PHP_EOL);
			$cs = getChecksum($nutz);
			$barcode = $nutz.$cs;
			//print($barcode.PHP_EOL);
			array_push($barcodes,$barcode);			
		}
	}
}
mysqli_close($con);

function getChecksum($barcode)
{
	$s=0;
	$w = [3,1,3,1,3,1,3];
	
	for($i=0; $i<7; $i++)
		$s += intval($barcode[$i])*$w[$i];
	//print($s.PHP_EOL);
	$pzn = $s % 10;
	//print($pzn.PHP_EOL);
	$pz = (10 - $pzn)%10;
	//print($pz.PHP_EOL);
	return $pz;
	
	
	
}



?>