<?php
    require(DIRNAME(__FILE__).'/config.inc.php');
    
    if(!empty($_GET['username']) && !empty($_GET['password']) && !empty($_GET['email'])){
       	$conn = new mysqli($config['db']['Host'], $config['db']['Username'], $config['db']['Password'], $config['db']['Dbname']);
        $sql = "SELECT * FROM `Users` WHERE `Username` = '".mysqli_real_escape_string($conn,$_GET['username'])."' OR `Email`= '".mysqli_real_escape_string($conn,$_GET['email'])."';";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            echo "User exists!";
        }else{
            $sql = "INSERT INTO `Users` (`ID`, `Username`, `Password`, `Email`, `Authtoken`, `Activation_key`, `Reset_key`) VALUES (NULL, '".mysqli_real_escape_string($conn,$_GET['username'])."', '".password_hash($_GET['password'],PASSWORD_DEFAULT)."', '".mysqli_real_escape_string($conn,$_GET['email'])."', '', '', '');";
            if($query->query($sql)){
                echo "success!";
            }else{
                echo "failure: ";
            }
        }
    }else{
        echo "Invalid credentials";
    }