<?php

require_once "debugHelper.php";
require_once 'db.php';

// description:
// 1. get all registered cash registers from the database table
// 2. get all last reg item ids so we know what the frist record in our request should be
// 3. build an array with an entry for every (foreign) register and start value
// 4. request the values via http request from the remote machine
// 5. update the sold values of the received items in the original table
// 6. insert them into the sync table

$ret = 0;
$registers=array();
$updateReg = array();
$syncEn=True;
$successfulUpdates=0;
$syncErrors=0;

sendDbg("-------------- ENTER: syncRequest.php");

$sql = "SELECT * FROM registers";
$result = mysqli_query($con,$sql);
//sendDbg($sql."\n=syncRequest= RESULT: ".print_r($result,true));

$myip = ownIP();

if ($result->num_rows > 0)
{
    while ($row = mysqli_fetch_array($result))
    {
//        if ($row["local"] == 1)			// is it myself?
        if ($row["local"] == 1 || $row['ip'] == $myip)			// is it myself?
        {
            if ($row["syncEn"]==0)		// is sync enabled for me?
            {
                sendDbg("NO SYNC ENABLED");
                $syncEn=False;
            }			
        }
        else 
        {
            if ($row["active"] == 1)		// is sync enabled for me?
            {
                //remote register, add to array
                //sendDbg(print_r($row,true));
                array_push($registers, $row);		// and we put the register in the array
            }
        }
    }	
}

if ($syncEn == true)
{
    foreach ($registers as $register)			// run through the array of remote registers
    {
        $lastId = -1;
        sendDbg("Iterate through the registers and get the last entries");

        // set each item in the cart to sold
        $syncArray = array();
        $sql = "SELECT MAX(reg_item_id) AS reg_item_id FROM sync WHERE register_id = {$register["id"]}";
        $result = mysqli_query($con,$sql);
        $row = mysqli_fetch_array($result);
        if (!empty($row))
        {
            $lastId = $row["reg_item_id"];
        }
        $syret = syncRequest($register["syncaddr"], $register["id"], $lastId);
        if ($syret > 0)
        {
            $successfulUpdates += 1;
            array_push($updateReg, $ret);
        }
        else if ($syret < 0)
        {
            $syncErrors += 1;			
        }

    }
}

if ($syncEn==False)
{
    header("NO SYNC EN", true, 404);
    echo json_encode(array("errMsg" => "SYNC DISABLED", "errId" => "1"));
}
else if ($successfulUpdates == 0)
{
    if ($syncErrors > 0)
    {
        header("SYNC REQUEST ERROR RESPONSE",true,404);
        echo json_encode(array("errMsg" => "SYNC REQUEST ERROR RESPONSE", "errId" => "2"));
    }
    else
    {
        //header("NO SYNC DEMAND",true,404);
        //echo json_encode(array("errMsg"=>"NO SYNC DEMAND", "errId"=>"3"));
        echo json_encode(array());
    }
}
else
{
    echo json_encode($updateReg);
}
	
// description:
// 1. request the values via http request from the remote machine
// 2. update the sold values of the received items in the original table
// 3. insert them into the sync table
function syncRequest($addr, $regId, $lastRegItemId)
{
    global $con;
    $updateLog = array();
    $ret=-1;
    $newLastId=$lastRegItemId;
    //addr: "http://localhost/syncRegister.php"
    //regId: 1 oder 2 ...
    //lastRegItemId: 1,2,3,...
    sendDbg("----------------- START SYNC REQUEST");

    $field = array(
        'regId' => $regId,
        'lastId' => $lastRegItemId
    );

    sendDbg(print_r($field,true));
    $ch = curl_init();		
    curl_setopt($ch, CURLOPT_URL,$addr);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$field);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec ($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno= curl_errno($ch);
    if ($http_status==404)
    {
        sendDbg("----------------- END SYNC REQUEST - No Sync demand");
        $ret=0;
        return $ret;
    }
    else
    {				
        sendDbg(print_r($response,true));
        curl_close($ch);
        $syncItems = json_decode($response,true);
        //sendDbg("RECEIVED ITEMS:");
        sendDbg(print_r($syncItems,true));

        sendDbg("Connect to database");
        $ret=0;

        if ($syncItems!=NULL)
        {			
            // iterate through syncItems
            foreach ($syncItems as $item)
            {
                // write the sold flag
                $iid = $item['item_id'];
                $sql = "UPDATE items SET verkauft=1 WHERE id={$item['item_id']}";			
                $result = mysqli_query($con,$sql);
                sendDbg($sql."\nRESULT: ".print_r($result,true));			

                // update the sync table
                $sql = "INSERT INTO sync (register_id,reg_item_id,item_id,barcode,created) VALUES ({$item['register_id']},{$item['reg_item_id']},{$item['item_id']},{$item['barcode']},\"{$item['created']}\")";
                $result = mysqli_query($con,$sql);
                sendDbg($sql."\nRESULT: ".print_r($result,true));
                $ret = $ret+1;

                // update register table sync timestamp
                $sql = "UPDATE registers SET lastSync=CURRENT_TIMESTAMP WHERE id={$regId}";
                $result = mysqli_query($con,$sql);
                sendDbg($sql."\nRESULT: ".print_r($result,true));

                $newLastId=$item['reg_item_id'];
            }
        }
    }
    sendDbg("----------------- END SYNC REQUEST");
    return $ret;
    // take the data over to own database
}
?>