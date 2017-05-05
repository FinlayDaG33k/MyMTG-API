<?php
	if (isset($_SERVER['HTTP_ORIGIN'])) {
		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		header('Access-Control-Allow-Credentials: true');
	}
	require('config.inc.php');
	require('class.php');
	$MyMTG = new MyMTG();
	if($_SERVER['REQUEST_METHOD'] == "POST"){
		switch($_POST['action']){
			case "Authenticate":
				if($MyMTG->checkCaptcha($config,$_POST)){
					echo $MyMTG->Authenticate($config,$_POST['Username'],$_POST['Password']);
				}else{
					echo "{\"code\":400,\"Message\":\"Invalid Captcha\"}";
				}
				break;
			case "addHave":
				echo $MyMTG->addHave($config,$_POST);
				break;
			case "updateHave":
				echo $MyMTG->updateHave($config,$_POST);
				break;
			case "addWant":
				echo $MyMTG->addWant($config,$_POST);
				break;
			case "updateWant":
				echo $MyMTG->updateWant($config,$_POST);
				break;
			case "addTrade":
				echo $MyMTG->addTrade($config,$_POST);
				break;
			case "updateTrade":
				echo $MyMTG->updateTrade($config,$_POST);
				break;
			case "changePassword":
				if($MyMTG->checkCaptcha($config,$_POST)){
					echo $MyMTG->changePassword($config,$_POST);
				}else{
					echo "{\"code\":400,\"Message\":\"Invalid Captcha\"}";
				}
				break;
			case "Register":
				if($MyMTG->checkCaptcha($config,$_POST)){
					echo $MyMTG->Register($config,$_POST);
				}else{
					echo "{\"code\":400,\"Message\":\"Invalid Captcha\"}";
				}
				break;
			case "editProfile":
				if($MyMTG->checkCaptcha($config,$_POST)){
					echo $MyMTG->editProfile($config,$_POST);
				}else{
					echo "{\"code\":400,\"Message\":\"Invalid Captcha\"}";
				}
				break;
		}
	}else{
		if(!empty($_GET['authtoken'])){
			switch($_GET['action']){
				case "listInventory":
					echo $MyMTG->listInventory($config,$_GET['username'],$_GET['authtoken']);
					break;
				case "listWants":
					echo $MyMTG->listWants($config,$_GET['username'],$_GET['authtoken']);
					break;
				case "listTrades":
					echo $MyMTG->listTrades($config,$_GET);
					break;
				Default:
					echo $MyMTG->getUser($config,$_GET['username'],$_GET['authtoken']);
					break;
			}

		}else{
			switch($_GET['action']){
				case "listSets":
					echo $MyMTG->listSets($config,$_GET);
					break;
				case "listProfile":
					echo $MyMTG->listProfile($config,$_GET);
					break;
				default:
					echo "Authtoken required!";
			}
		}
	}
