<?php

$ip = "255.255.255.255";
$port = 8888;
$str = "DEVICE_DISCOVERY";

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
socket_sendto($sock, $str, strlen($str), 0, $ip, $port);

while(true) {
  $ret = @socket_recvfrom($sock, $buf, 20, 0, $ip, $port);
  if($ret === false) break;
  echo "Messagge : < $buf > , $ip : $port <br>";
}

socket_close($sock);

?>