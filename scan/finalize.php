<?php

// connect to database
$con = mysqli_connect('localhost','root','','basar');
if (!$con) {
	echo("Database Error");
	die('Could not connect: ' . mysqli_error($con));
}
mysqli_select_db($con,"basar");


// get the index for the next entry into the sync database
$users = array();
$sql = "SELECT * FROM users ORDER BY name ASC";
$result = mysqli_query($con,$sql);
$ucnt =0;
if ($result->num_rows >0)
{
	while ($row=mysqli_fetch_array($result))
	{
		$ucnt += 1;
		print ($ucnt.": ADD USER: ".$row["name"].", ".$row["vorname"].", ".$row["email"]."\r\n");
		array_push($users, $row);
	}
}
$fsum = fopen("I:\Basar\summary.csv","w");
fwrite($fsum,"Verkäufer ID; Nachnname; Vorname; email; Anzahl Artikel Gesamt; Anzahl Artikel Verkauft; Einnahmen; 15% Einbehalten; Gebühren komplett; Ausbezahlung");
fwrite($fsum,PHP_EOL);
		
// set each item in the cart to sold
foreach ($users as $user )
{	
	$sql = "SELECT * FROM items WHERE user_id={$user["id"]} ORDER BY nummer";
	$result = mysqli_query($con,$sql);
	
	$soldItems=array();
	$unsoldItems=array();
	$completeCnt=0;
	$soldCnt=0;
	$soldSum=0.00;
	$completeSum=0.00;
	$listCharged=0.00;
	$percentCharged =0.0;
	$valueCharged=0.0;
	
	
	if ($result->num_rows >0)
	{
		$fn= "{$user['name']}_{$user['vorname']}_{$user['id']}.txt";
		
		while ($row=mysqli_fetch_array($result))
		{			
			if ($row["verkauft"]=="1")
			{
				print ("SOLD ITEM: ".$row["id"].", ". $row["barcode"].", ". $row["bezeichnung"]."\r\n");
				array_push($soldItems, $row);
				$soldSum += $row["preis"];
				$soldCnt += 1;			
			} else {
				array_push($unsoldItems,$row);
			}
			$completeSum += $row["preis"];
			$completeCnt+=1;
		}
		// set percentage
		print("------------------\n");
		$valExact = 0.00;
		$valExact = (0.85 * $soldSum);
		/*print("EX:".$valExact.'\n');
		$dblval=($valExact*2);
		print("DBL:".$dblval.'\n');
		$ceilVal = floor($dblval);
		print ("Floor:".$ceilVal.'\n');
		$hv = $ceilVal/2;
		print ("HALF:".$hv.'\n');
		*/
		$valuePercReturn = (floor($valExact*2))/2;
		print($valuePercReturn);
		$percentCharged = $soldSum - $valuePercReturn;
		
		// set lists
		$listCharged = (ceil($completeCnt/30))*2;
		$valueCharged = $listCharged + $percentCharged;
		$valueReturn = $valuePercReturn - $listCharged;
		
		$uid = $user["id"];
		$name = $user["name"];
		$vorname = $user["vorname"];
		
				
		fwrite($fsum,"{$uid}; {$name}; {$vorname}; {$user["email"]}; {$completeCnt}; {$soldCnt}; {$listCharged}; {$percentCharged}; {$valueCharged}; {$valueReturn}");
		fwrite($fsum,PHP_EOL);
		
		if ($soldCnt>0)
		{
			$fur = fopen("I:\\Basar\\Single\\".$fn,"w");
			fwrite($fur,"Verkäufer: ".$user['name'].", ".$user['vorname']." (ID: ".$user['id'].")");
			fwrite($fur,PHP_EOL);
			fwrite($fur,"-------------------------------------------");
			fwrite($fur,PHP_EOL);
			fwrite($fur,"Anzahl Artikel (Gesamt): ".$completeCnt);
			fwrite($fur,PHP_EOL);
			fwrite($fur,PHP_EOL);
			
			fwrite($fur,"Verkaufte Artikel (".$soldCnt."):".PHP_EOL);
			foreach($soldItems as $soldItem)
			{
				$artName = $soldItem["bezeichnung"];
				if (strlen($artName) > 40)
				{
					$artName = substr($artName, 0, 36).'...';
				}
				$line = str_pad ( $soldItem["nummer"], 6, ' ' );
				$line .= str_pad ( $artName, 40, ' ' );
				$line .= str_pad ( $soldItem["groesse"], 12, ' ' );
				$line .= str_pad ( " ", 5, '.' );
				$line .= str_pad ( $soldItem["preis"].'€', 10, ' ' );
				$line .=PHP_EOL;
				fwrite($fur,$line);
			}
			
			fwrite($fur,PHP_EOL);
			fwrite($fur,"SUMME: ".number_format($soldSum,2).'€');
			fwrite($fur,PHP_EOL);
			fwrite($fur,"Listenkosten: ".number_format($listCharged,2)."€");
			fwrite($fur,PHP_EOL);
			fwrite($fur,"15% auf verkaufte Artikel: ".number_format($percentCharged,2)."€ =>".number_format($soldSum-$valExact,2));
			fwrite($fur,PHP_EOL);
			fwrite($fur,PHP_EOL);
			fwrite($fur,"Rückgabe: ".number_format($valueReturn,2).'€');
			fwrite($fur,PHP_EOL);
			fwrite($fur,"-------------------------------------------");
			fwrite($fur,PHP_EOL);
			fwrite($fur,"Unverkaufte Artikel:".PHP_EOL);
			foreach($unsoldItems as $unsoldItem)
			{
				$artName = $unsoldItem["bezeichnung"];
				if (strlen($artName) > 40)
				{
					$artName = substr($artName, 0, 36).'...';
				}
				$line = str_pad ( $unsoldItem["nummer"], 6, ' ' );
				$line .= str_pad ( $artName, 40, ' ' );
				$line .= str_pad ( $unsoldItem["groesse"], 12, ' ' );
				$line .= str_pad ( " ", 5, '.' );
				$line .= str_pad ( $unsoldItem["preis"].'€', 10, ' ' );
				$line .=PHP_EOL;
				fwrite($fur,$line);
			}
			fclose($fur);
		}
	}
	else 
	{
		// set percentage
		$valueReturn = 0;
		$percentCharged = 0;
		
		// set lists
		$listCharged = 0;
		$valueCharged = 0;
		
		$uid = $user["id"];
		$name = $user["name"];
		$vorname = $user["vorname"];
		
		fwrite($fsum,"{$uid}; {$name}; {$vorname}; {$user["email"]}; {$completeCnt}; {$soldCnt}; {$listCharged}; {$percentCharged}; {$valueCharged}; {$valueReturn}");
		fwrite($fsum,PHP_EOL);
		
	}
	
	
	
}
fclose($fsum);


?>