<?php
Class MyMTG{

	function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
	}

	function multi_array_search($array, $search){
		// Create the result array
		$result = array();
		// Iterate over each array element
		foreach ($array as $key => $value){
			// Iterate over each search condition
			foreach ($search as $k => $v){
				// If the array element does not meet the search condition then continue to the next element
				if (!isset($value[$k]) || $value[$k] != $v){
					continue 2;
				}
			}
			// Add the array element's key to the result array
			$result[] = $key;
		}
		// Return the result array
		return $result;
	}

	function listSets(){
		return file_get_contents('sets.json');
	}

	function getUser($config,$Username,$Authtoken){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$Username)."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$Authtoken)."';";
		$result = $conn->query($sql);
    if($result->num_rows > 0){
    	$user_row = $result->fetch_assoc();
			$data = array("ID" => $user_row['ID'],"Username"=>$user_row['Username']);
    	return json_encode($data);
    }else{
      return "Invalid Token!";
    }
		$conn->close();
	}

	function editProfile($config,$data){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
		$result = $conn->query($sql);
		if($result->num_rows > 0){
			$user_row = $result->fetch_assoc();
			$sql = "SELECT `Userdetails` FROM `UserDetails` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$sql = "UPDATE `UserDetails` SET `Userdetails`='".json_encode(array("DCI" => mysqli_real_escape_string($conn,$data['DCINumber']),"Name"=>mysqli_real_escape_string($conn,$data['realName'])))."' WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
				if($conn->query($sql)){
					return "{\"code\":200,\"Message\":\"Profile update Success!!\"}";
				}else{
					return "{\"code\":500,\"Message\":\"Couldn't update profile!\"}";
				}
			}else{
				return "Userdetails not found!";
			}
		}else{
			return "Invalid Token!";
		}
	}

	function addHave($config,$data){
		$ch = curl_init(); // create a new cURL resource

		// set URL and other appropriate options (In this case, we only care to see if the cardname exists)
		curl_setopt($ch, CURLOPT_URL, "http://gatherer.wizards.com/Pages/Card/Details.aspx?name=".urlencode($data['Card']));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
		curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// close cURL resource, and free up system resources
		curl_close($ch);

		if($httpcode == 200){
			$ch = curl_init(); // create a new cURL resource

			// set URL and other appropriate options
			curl_setopt($ch, CURLOPT_URL, "https://api.magicthegathering.io/v1/cards?set=".urlencode($data['Set'])."&name=%22".urlencode($data['Card'])."%22");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$mtgresult = json_decode(curl_exec($ch),1);
			// close cURL resource, and free up system resources
			curl_close($ch);

			$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
			$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
			$result = $conn->query($sql);
	    if($result->num_rows > 0){
	    	$user_row = $result->fetch_assoc();
				$sql = "SELECT `Inventory` FROM `Inventories` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
				$result = $conn->query($sql);
				if($result->num_rows > 0){
					$inventory = json_decode($result->fetch_assoc()['Inventory'],1);
					$key = $this->multi_array_search($inventory, array('Name' => $data['Card'], 'Set' => $data['Set']));

					if(!empty($key)){
						$inventory[$key[0]]['Foils'] = $inventory[$key[0]]['Foils'] + $data['Foils'];
						$inventory[$key[0]]['Non-Foils'] = $inventory[$key[0]]['Non-Foils'] + $data['Non-Foils'];
					}else{
						array_push($inventory,array("Name" => $data["Card"],"Set"=>$data['Set'],'Rarity' => $mtgresult['cards'][0]['rarity'],"Foils" => $data["Foils"],"Non-Foils" => $data["Non-Foils"]));
					}
					sort($inventory);
					$inventory = json_encode($inventory);
					$sql = "UPDATE `Inventories` SET `Inventory`='".mysqli_real_escape_string($conn,$inventory)."' WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
					if($conn->query($sql)){
						return "{\"code\":200,\"Message\":\"Card Added!\"}";
					}else{
						return "{\"code\":500,\"Message\":\"Couldn't add card!\"}";
					}
				}else{
					return "Inventory not found!";
				}
	    }else{
	      return "Invalid Token!";
	    }
			$conn->close();
		}else{
			return "{\"code\":404,\"Message\":\"Invalid Card!\"}";
		}
	}

	function updateHave($config,$data){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
		$result = $conn->query($sql);
	  if($result->num_rows > 0){
	  	$user_row = $result->fetch_assoc();
			$sql = "SELECT `Inventory` FROM `Inventories` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$inventory = json_decode($result->fetch_assoc()['Inventory'],1);
				$inventory[$data['cardID']]['Foils'] = $data['Foils'];
				$inventory[$data['cardID']]['Non-Foils'] = $data['Non-Foils'];
				sort($inventory);
				$inventory = json_encode($inventory);
				$sql = "UPDATE `Inventories` SET `Inventory`='".mysqli_real_escape_string($conn,$inventory)."' WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
				if($conn->query($sql)){
					return "{\"code\":200,\"Message\":\"Card Updated!\"}";
				}else{
					return "{\"code\":500,\"Message\":\"Couldn't update card!\"}";
				}
			}else{
				return "Inventory not found!";
			}
    }else{
      return "Invalid Token!";
    }
		$conn->close();
	}

	function addWant($config,$data){
		$ch = curl_init(); // create a new cURL resource

		// set URL and other appropriate options (In this case, we only care to see if the cardname exists)
		curl_setopt($ch, CURLOPT_URL, "http://gatherer.wizards.com/Pages/Card/Details.aspx?name=".urlencode($data['Card']));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
		curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// close cURL resource, and free up system resources
		curl_close($ch);

		if($httpcode == 200){
			$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
			$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$user_row = $result->fetch_assoc();
				$sql = "SELECT `Wants` FROM `Wants` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
				$result = $conn->query($sql);
				if($result->num_rows > 0){
					$wants = json_decode($result->fetch_assoc()['Wants'],1);
					$key = $this->multi_array_search($wants, array('Name' => $data['Card'], 'Set' => $data['Set']));

					if(!empty($key)){
						$wants[$key[0]]['Foils'] = $wants[$key[0]]['Foils'] + $data['Foils'];
						$wants[$key[0]]['Non-Foils'] = $wants[$key[0]]['Non-Foils'] + $data['Non-Foils'];
					}else{
						array_push($wants,array("Name" => $data["Card"],"Set"=>$data['Set'],"Foils" => $data["Foils"],"Non-Foils" => $data["Non-Foils"]));
					}
					sort($wants);
					$wants = json_encode($wants);
					$sql = "UPDATE `Wants` SET `Wants`='".mysqli_real_escape_string($conn,$wants)."' WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
					if($conn->query($sql)){
						return "{\"code\":200,\"Message\":\"Card Added!\"}";
					}else{
						return "{\"code\":500,\"Message\":\"Couldn't add card!\"}";
					}
				}else{
					return "Wants not found!";
				}
			}else{
				return "Invalid Token!";
			}
			$conn->close();
		}else{
			return "{\"code\":404,\"Message\":\"Invalid Card!\"}";
		}
	}

	function updateWant($config,$data){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
		$result = $conn->query($sql);
		if($result->num_rows > 0){
			$user_row = $result->fetch_assoc();
			$sql = "SELECT `Wants` FROM `Wants` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$wants = json_decode($result->fetch_assoc()['Wants'],1);
				$wants[$data['cardID']]['Foils'] = $data['Foils'];
				$wants[$data['cardID']]['Non-Foils'] = $data['Non-Foils'];
				sort($wants);
				$wants = json_encode($wants);
				$sql = "UPDATE `Wants` SET `Wants`='".mysqli_real_escape_string($conn,$wants)."' WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
				if($conn->query($sql)){
					return "{\"code\":200,\"Message\":\"Card Updated!\"}";
				}else{
					return "{\"code\":500,\"Message\":\"Couldn't update card!\"}";
				}
			}else{
				return "Wants not found!";
			}
		}else{
			return "Invalid Token!";
		}
		$conn->close();
	}

	function changePassword($config,$data){
		if($data['newPassword'] == $data['confnewPassword']){
			$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
			$sql = "SELECT `ID`,`Username`,`Password`,`Authtoken` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken`='".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$user_row = $result->fetch_assoc();
				if(password_verify($data['oldPassword'],$user_row['Password'])){
					$sql = "UPDATE `Users` SET `Password`='".password_hash($data['newPassword'],PASSWORD_DEFAULT)."' WHERE `Username`='".mysqli_real_escape_string($conn,$data['Username'])."' AND `Authtoken`='".mysqli_real_escape_string($conn,$data['Authtoken'])."';";
					if($conn->query($sql)){
						return "{\"code\":200,\"Message\":\"Password Updated!\"}";
					}else{
						return "{\"code\":500,\"Message\":\"Couldn't update password!\"}";
					}
				}else{
					return "{\"code\":403,\"Message\":\"Invalid Credentials!\"}";
				}
			}else{
				return "{\"code\":403,\"Message\":\"Invalid Credentials!\"}";
			}
		}else{
			return "{\"code\":404,\"Message\":\"Passwords Don't Match\"}";
		}
	}

	function Register($config,$data){
		if($data['Password'] == $data['confPassword']){
			$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
			$sql = "SELECT `ID` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data["Username"])."';";
			$result = $conn->query($sql);
	    if($result->num_rows > 0){
				return "{\"code\":403,\"message\":\"Username Taken\"}";
			}else{
				$sql = "SELECT `ID` FROM `Users` WHERE `Email`='".mysqli_real_escape_string($conn,$data["Email"])."';";
				$result = $conn->query($sql);
				if($result->num_rows > 0){
					return "{\"code\":403,\"message\":\"Email Taken\"}";
				}else{
					$sql = "INSERT INTO `Users` (`ID`, `Username`, `Password`, `Email`, `Authtoken`, `Activated`,`Activation_key`, `Reset_key`) VALUES (NULL, '".mysqli_real_escape_string($conn,$data['Username'])."', '".mysqli_real_escape_string($conn,password_hash($data['Password'],PASSWORD_DEFAULT))."', '".mysqli_real_escape_string($conn,$data['Email'])."', '', '0','', '');INSERT INTO `Inventories` (`ID`,`Inventory`) VALUES (NULL,'".json_encode(array())."');INSERT INTO `Wants` (`ID`, `Wants`) VALUES (NULL,'".json_encode(array())."');INSERT INTO `UserDetails` (`ID`, `UserDetails`) VALUES (NULL, '".json_encode(array("DCI" => 0))."');";
					if($conn->multi_query($sql)){
						return "{\"code\":200,\"message\":\"Registration success!\"}";
					}else{
						return "{\"code\":500,\"message\":\"Could not Register\"}";
					}
				}
			}
		}else{
			return "{\"code\":404,\"Message\":\"Passwords Don't Match\"}";
		}
	}

	function Authenticate($config,$Username,$Password){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username`,`Password`,`Authtoken` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$Username)."' OR `Email` = '".mysqli_real_escape_string($conn,$Username)."';";
		$result = $conn->query($sql);
    if($result->num_rows > 0){
    	$user_row = $result->fetch_assoc();
			if(password_verify($Password,$user_row['Password'])){
				if(empty($user_row['Authtoken'])){
					$authtoken = $this->generateRandomString(16);
					$sql = "UPDATE `Users` SET `Authtoken` = '".$authtoken."' WHERE `ID` = ".$user_row['ID'].";";
				}else{
					$authtoken = $user_row['Authtoken'];
				}
				if($conn->query($sql)){
					$data = array("ID" => $user_row['ID'],"Username"=>$user_row['Username'],"Authtoken"=>$authtoken);
					return "{\"code\":200,\"message\":".json_encode($data)."}";
				}else{
					return "{\"code\":500,\"message\":\"Internal Error\"}";
				}

			}else{
				return "{\"code\":403,\"message\":\"Invalid Credentials\"}";
			}
    }else{
    return "{\"code\":403,\"message\":\"Invalid Credentials\"}";
    }
		$conn->close();
	}

	function listInventory($config,$Username,$Authtoken){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$Username)."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$Authtoken)."';";
		$result = $conn->query($sql);
    if($result->num_rows > 0){
    	$user_row = $result->fetch_assoc();
			$sql = "SELECT `Inventory` FROM `Inventories` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$inventory = json_decode($result->fetch_assoc()['Inventory']);
				$data = array("ID" => $user_row['ID'],"Username"=>$user_row['Username'],"Inventory" => $inventory);
    		return json_encode($data);
			}else{
				return "Inventory not found!";
			}
    }else{
      return "Invalid Token!";
    }
		$conn->close();
	}

	function listWants($config,$Username,$Authtoken){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$Username)."' AND `Authtoken` = '".mysqli_real_escape_string($conn,$Authtoken)."';";
		$result = $conn->query($sql);
		if($result->num_rows > 0){
			$user_row = $result->fetch_assoc();
			$sql = "SELECT `Wants` FROM `Wants` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$wants = json_decode($result->fetch_assoc()['Wants']);
				$data = array("ID" => $user_row['ID'],"Username"=>$user_row['Username'],"Wants" => $wants);
				return json_encode($data);
			}else{
				return "Wants not found!";
			}
		}else{
			return "Invalid Token!";
		}
		$conn->close();
	}

	function listProfile($config,$data){
		$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
		$sql = "SELECT `ID`,`Username` FROM `Users` WHERE `Username`='".mysqli_real_escape_string($conn,$data['username'])."';";
		$result = $conn->query($sql);
    if($result->num_rows > 0){
    	$user_row = $result->fetch_assoc();
			$sql = "SELECT `Userdetails`, NULL as `Inventory`, NULL as `Wants` FROM `UserDetails` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."'
UNION ALL SELECT NULL as `UderDetails`, `Inventory`, NULL as `Wants` FROM `Inventories` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."'
UNION ALL SELECT NULL as `UderDetails`, NULL as `Inventory`, `Wants` From `Wants` WHERE `ID` = '".mysqli_real_escape_string($conn,$user_row['ID'])."';";
			$result = $conn->query($sql);
			if($result->num_rows > 0){
				$UserDetails = json_decode($result->fetch_assoc()['Userdetails']);
				$inventory = json_decode($result->fetch_assoc()['Inventory']);
				$wants = json_decode($result->fetch_assoc()['Wants']);
				$data = array("Username"=>$user_row['Username'],"UserDetails" =>$UserDetails, "Inventory" => $inventory,"Wants"=>$wants);
    		return json_encode($data);
			}else{
				return "Inventory not found!";
			}
    }else{
      return "Invalid User";
    }
		$conn->close();
	}

	function checkCaptcha($config,$data){
		if(!empty(($data['g-recaptcha-response']))){
			$captcha=$data['g-recaptcha-response'];
			$url = "https://www.google.com/recaptcha/api/siteverify?secret=".$config['captcha']['Secret']."&response=".$captcha;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			curl_close($ch);
			$responseKeys = json_decode($response,true);
			if($responseKeys["success"] != true) {
				return false;
			}else{
				return true;
			}
		}else{
			return false;
		}
		return true;
	}
}
