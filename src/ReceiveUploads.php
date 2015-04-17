<?php

session_start();


$_SESSION["Authenticated"] =1;

require 'Messages.php';


DebugLog("Application started.");



if($_SERVER['REQUEST_METHOD'] == 'GET')
{

	$commandName = $_GET['Command'];
	DebugLog("Got command $commandName");
	$userID = $_GET['UserID'];	
	ReceiveMessage($commandName,$userID);

}
else
{
	DebugLog("Received POST request from " . $_SERVER['REMOTE_ADDR'] . " " . $_SERVER['HTTP_USER_AGENT']);
	DebugLog(Print_r($_POST, true));
	DebugLog(Print_r($_FILES, true));
	$commandName = $_POST['Command'];
	DebugLog("Got command $commandName");
	$userID = $_POST['UserID'];	
	ReceiveMessage($commandName,$userID);
}


?>
