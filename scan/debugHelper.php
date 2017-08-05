<?php

function restartDebug()
{
    $_SESSION["debugCount"]=1;
}

function sendDbg($msg)
{
    $debug=false;
    if ($debug)
    {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        //Send the message to the server
        socket_sendto($sock, $msg , strlen($msg) , 0 , "127.0.0.1" , 9991);
        socket_close($sock);
    }
}

function ownIP() {
    $name = null;
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($sock, "8.8.8.8", 53);
    socket_getsockname($sock, $name); // $name passed by reference
    return $name;
}

?>