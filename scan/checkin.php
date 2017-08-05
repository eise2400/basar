<?php

require 'debugHelper.php';
require_once 'db.php';

ini_set('default_charset','utf-8');
header('Content-Type: text/json; > charset=utf-8');

if (isset($_POST['usrNummer']))
{
	$usrNummer = $_POST['usrNummer'];	
	$sql = "UPDATE users SET ist_da=1 WHERE nummer={$usrNummer}";
	$result = mysqli_query($con,$sql);
	sendDbg($sql."\nRESULT: ".print_r($result,true));
	$ret = array("msg"=>"OK");
	echo json_encode($ret);
}

?>

