<?php

require_once "debugHelper.php";
require_once 'db.php';

doSyncRequest();

// description:
// 1. get all registered cash registers from the database table
// 2. get all last reg item ids so we know what the frist record in our request should be
// 3. build an array with an entry for every (foreign) register and start value
// 4. request the values via http request from the remote machine
// 5. update the sold values of the received items in the original table
// 6. insert them into the sync table

function doSyncRequest()
{
    global $con;
    $ret = 0;
    $registers=array();
    $updateReg = array();
    $syncEn=True;
    $successfulUpdates=0;
    $syncErrors=0;
    $lastIdxArray=array();

    //print("-------------- ENTER: syncRequest.php".PHP_EOL);

    $sql = "SELECT * FROM registers";
    $result = mysqli_query($con,$sql);
    
    $myip = ownIP();

    if ($result->num_rows > 0)
    {
        while ($row = mysqli_fetch_array($result))
        {
            if ($row["local"] == 1 || $row['ip'] == $myip)			// is it myself?
            {
                if ($row["syncEn"] == 0)		// is sync enabled for me?
                {
                    $syncEn = false;
                }
        }
            else
            {
                if ($row["active"] == 1)		// is sync enabled for me?
                {
                    // 	remote register, add to array
                    array_push($registers, $row);		// and we put the register in the array
               }
            }
        }
    }

    if ($syncEn)
    {
        foreach ($registers as $register)			// run through the array of remote registers
        {
            $lastId = -1;

            // set each item in the cart to sold
            $syncArray=array();
            $sql = "SELECT MAX(reg_item_id) AS reg_item_id FROM sync WHERE register_id={$register["id"]}";
            $result = mysqli_query($con, $sql);
            $row = mysqli_fetch_array($result);
            if (!empty($row))
            {
                $lastId = $row["reg_item_id"];
            }
            $newLastId = syncRequest($register["syncaddr"], $register["id"], $lastId);
            $lastIdxArray['k'.$register["id"]] = $newLastId;
        }
    }

    if (!$syncEn)
    {
        $retArray = array("action" => "SYNC_DISABLED", "lastIdxArray" => $lastIdxArray);
        echo(json_encode($retArray, true));
    }
    else if (empty($lastIndexArray))
    {
        $retArray = array("action" => "NO_DEMAND", "lastIdxArray" => $lastIdxArray);
        echo json_encode($retArray, true);
    }
    else
    {
        $retArray = array("action" => "SYNC", "lastIdxArray" => $lastIdxArray);
        echo(json_encode($retArray, true));
    }
}

// description:
// 1. request the values via http request from the remote machine
// 2. update the sold values of the received items in the original table
// 3. insert them into the sync table
function syncRequest($addr, $regId, $lastRegItemId)
{
    global $con;
    $updateLog = array();
    $newLastId=$lastRegItemId;
    //addr: "http://localhost/syncRegister.php"
    //regId: 1 oder 2 ...
    //lastRegItemId: 1,2,3,...
    //print("----------------- START SYNC REQUEST");

    $field = array(
        'regId' => $regId,
        'lastId' => $lastRegItemId
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$addr);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$field);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno= curl_errno($ch);
    if ($http_status == 404)
    {
        return -404;
    }
    else
    {
        curl_close($ch);
        $syncItems = json_decode($response,true);

        if ($syncItems!=NULL)
        {
            foreach ($syncItems as $item)
            {
                // write the sold flag
                $iid = $item['item_id'];
                $sql = "UPDATE items SET verkauft=1 WHERE id={$item['item_id']}";
                $result = mysqli_query($con,$sql);
                print($sql."\nRESULT: ".print_r($result,true).PHP_EOL);

                // update the sync table
                $sql = "INSERT INTO sync (register_id,reg_item_id,item_id,barcode,created) VALUES ({$item['register_id']},{$item['reg_item_id']},{$item['item_id']},{$item['barcode']},\"{$item['created']}\")";
                $result = mysqli_query($con,$sql);
                print($sql."\nRESULT: ".print_r($result,true).PHP_EOL);

                // update register table sync timestamp
                $sql = "UPDATE registers SET lastSync=CURRENT_TIMESTAMP WHERE id={$regId}";
                $result = mysqli_query($con,$sql);
                print($sql."\nRESULT: ".print_r($result,true).PHP_EOL);

                $newLastId=$item['reg_item_id'];
            }
        }
    }
    //print("----------------- END SYNC REQUEST".PHP_EOL);
    return $newLastId;
    // take the data over to own database
}
?>