<?php
header("Access-Control-Allow-Origin: *");
$result = array(
	"set" => array(),
);

function srtsafe($srt){
	$a = array("\\","/",":","*","?","<",">","|","\"","'","`");
	for($i = 0;$i < count($a);$i++){
		if(strpos($srt,$a[$i])){
			return false;
		}elseif (str_starts_with($srt,$a[$i])) {
			return false;
		}
	}
	return true;
}

function execute($txt, &$result){
	$commands = explode("\n", $txt);
	try {
		$db = new PDO(
			'mysql:host=localhost;dbname=dsb;charset=utf8',
			'root',
			'your_password'
		);
	} catch (Exception $e){
		$result["result"][0] = "fatal error: SQL connection faild";
		return false;
	}
	/*
	cmd parm
	cmd2 parm2
	
	array(
		[0] => "cmd parm",
		[1] => "cmd2 parm2"
	)
	*/
	function rdstring($max = 10){
		$char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$lenght = strlen($char);
		$result = '';
		for ($i = 0; $i < $max; $i++){
			$result .= $char[rand(0, $lenght - 1)];
		}
		return $result;
	}

	$connection_mode = 0;

	$command = explode(" ", $commands[0]);
	if(isset($command[0])){
		if ($command[0] == "executable_mode") {
			if(isset($command[1])){
				if ($command[1] == "0") {
					$connection_mode = 0;
				} elseif ($command[1] == "1") {
					$connection_mode = 1;
				}
			}
		}
	}

	if ($connection_mode == 0) {
		if(isset($_COOKIE["User_token"])){
			$User_token = $_COOKIE["User_token"];
		}
		if(isset($_COOKIE["User"])){
			$User = $_COOKIE["User"];
		}
	}

	for($i = 0;$i < count($commands);$i++){
		$command = explode(" ", $commands[$i]);
		/*
		cmd parm
		
		array(
			[0] => "cmd"
			[1] => "parm"
		)
		*/
		// chec if their is a command
		if(isset($command[0])){
			switch ($command[0]) {
				case 'execute_mode':
					break;
				case 'co':
					// chec if command is for a connection
					if(isset($command[1])){
						if(isset($command[2])){
							if(srtsafe($command[1])){
								$recipesStatement = $db->prepare("SELECT password FROM users WHERE id='".$command[1]."'");
								$recipesStatement->execute();
								$tp = $recipesStatement->fetchAll();
								if($tp != false){
									if(isset($tp[0]["password"])){
										if($command[2] === $tp[0]["password"]){
											if ($connection_mode == 0) {
												setcookie("User",$command[1]);
											} else {
												$User = $command[1];
											}
											$token = rdstring(20);
											$recipesStatement = $db->prepare("UPDATE users SET C_token = '".$token."' WHERE id = '".$command[1]."'");
											$recipesStatement->execute();
											$tp = $recipesStatement->fetchAll();
											if ($connection_mode == 0) {
												setcookie("User_token",$token);
											}
											$result["result"][$i] = "connection to '".$command[1]."' succes";
											$result["set"]["user"] = $command[1];
										}else{
											$result["result"][$i] = "fatal error: unauthorized, authentification failed: the passworld is not valid";
											break;
										}
									}else{
										$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
										break;
									}
								}else{
									$result["result"][$i] = "fatal error: SQL request faild";
									break;
								}
							}else{
								$result["result"][$i] = "fatal error: SQL request is not secure";
								break;
							}
						}else{
							$result["result"][$i] = "fatal error: unauthorized, authentification failed: the passworld is not defind";
							break;
						}
					}else{
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not defind";
					}
					break;
				case 'users':
					//show all user
					$recipesStatement = $db->prepare("SELECT id FROM users;");
					$recipesStatement->execute();
					$tp = $recipesStatement->fetchAll();
					if ($tp !== false) {
						for ($i1=0; $i1 < count($tp); $i1++) {
							$tp1[$i] = $tp[$i]["id"];
						}
						$result["result"][$i] = "users :/".implode("/",$tp1)."";
					} else {
						$result["result"][$i] = "fatal error: SQL request faild";
						break;
					}
					break;
				default:
					// chec if the user is connect
					if(isset($User_token) && isset($User)){
						if(srtsafe($User)){
							$recipesStatement = $db->prepare("SELECT c_token FROM users WHERE id = '".$User."';");
							$recipesStatement->execute();
							$tp = $recipesStatement->fetchAll();
							if($tp != false){
								if(isset($tp[0]["c_token"])){
									if($User_token === $tp[0]["c_token"]){
										$token = rdstring(20);
										$recipesStatement = $db->prepare("UPDATE users SET C_token = '".$token."' WHERE id = '".$User."'");
										$recipesStatement->execute();
										if ($connection_mode == 0) {
											setcookie("User_token",$token);
										}

										// chec if the user is a super_user
										$recipesStatement = $db->prepare("SELECT su FROM users WHERE id = '".$User."';");
										$recipesStatement->execute();
										$tp = $recipesStatement->fetchAll();
										if($tp != false){
											if(isset($tp[0]["su"])){
												if ($tp[0]["su"] == 1) {
													$sudo = true;
												} else {
													$sudo = false;
												}
											}else{
												$sudo = false;
											}
										}else{
											$sudo = false;
										}

										// execute the command
										switch($command[0]){
											case "deco":
												// deconnect the user
												if ($connection_mode == 0) {
													setcookie("User", "", time()-3600);
													setcookie("User_token", "", time()-3600);
												} elseif ($connection_mode == 1) {
													$User = $command[1];
												}
												$result["result"][$i] = "deconnection succes";
												break;
											case "cuser":
												//reat a user: cuser <id> <password>
												if($sudo == true){
													if (isset($command[1]) && isset($command[2])) {
														if (srtsafe($command[1]) && srtsafe($command[2])) {
															$recipesStatement = $db->prepare("SELECT id FROM users WHERE id='".$command[1]."';");
															$recipesStatement->execute();
															$tp = $recipesStatement->fetchAll();
															if($tp !== false){
																if(isset($tp[0]["id"])){
																	$result["result"][$i] = "fatal error: user alredy exist";
																	break;
																} else {
																	$new_user_conf = array(
																		"id" => $command[1],
																		"password" => $command[2],
																		"super_user" => false,
																	);
																	if(isset($command[3])){
																		if($command[3] == 'true'){
																			$new_user_conf['super_user'] = "true";
																		} elseif ($command[3] == 'false') {
																			$new_user_conf['super_user'] = "false";
																		}
																	} else {
																		$new_user_conf['super_user'] = "false";
																	}
																	$recipesStatement = $db->prepare("INSERT INTO users (id,password,su) VALUES ('".$new_user_conf['id']."','".$new_user_conf['password']."',".$new_user_conf['super_user'].");");
																	$recipesStatement->execute();
																	$result["result"][$i] = "user '".$new_user_conf['id']."' create";
																}
															} else {
																$result["result"][$i] = "error: SQL request faild";
																break;
															}
														} else {
															$result["result"][$i] = "fatal error: SQL request is not secure";
															break;
														}
													} else {
														$result["result"][$i] = "fatal error: the name or the password is not defind";
														break;
													}
												} else {
													$result["result"][$i] = "fatal error: your need be a super user to use this command";
													break;
												}
												break;
											case 'duser':
												//delet an user !!only for super user : duser <name>
												if ($sudo == true){
													if (isset($command[1])) {
														if (srtsafe($command[1])) {
															if ($command[1] != "root"){
																$recipesStatement = $db->prepare("SELECT id FROM users WHERE id='".$command[1]."';");
																$recipesStatement->execute();
																$tp = $recipesStatement->fetchAll();
																if ($tp !== false){
																	if (isset($tp[0]["id"])){
																		$recipesStatement = $db->prepare("DELETE FROM users WHERE id = '".$command[1]."';");
																		$recipesStatement->execute();
																		$result["result"][$i] = "user '".$command[1]."' deleted";
																	} else {
																		$result["result"][$i] = "fatal error: user not defind";
																	}
																} else {
																	$result["result"][$i] = "fatal error: SQL request faild";
																}
															} else {
																$result["result"][$i] = "fatal error: root can't be deleted";
															}
														} else {
															$result["result"][$i] = "fatal error: SQL request is not secure";
														}
													} else {
														$result["result"][$i] = "fatal error: the name is not defind";
													}
												} else {
													$result["result"][$i] = "fatal error: your need be a super user to use this command";
												}
												break;
											default:
												// command not found
												$result["result"][$i] = "warn: command not found";
										}
									} else {
										$result["result"][$i] = "fatal error: unauthorized, authentification failed: the User_token is not valid";
										break;
									}
								} else {
									$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
									break;
								}
							} else {
								$result["result"][$i] = "fatal error: SQL request faild";
								break;
							}
						} else {
							$result["result"][$i] = "fatal error: SQL request is not secure";
							break;
						}
					} else {
						$result["result"][$i] = "fatal error: unauthorized, authentification failed";
						break;
					}
					break;
			}
		}
	}
	return $result;
}

if(isset($_GET["m"])){
	if($_GET["m"] == "terminal"){
		if(isset($_POST["t"])){
			execute($_POST["t"], $result);
		}
	}
	if($_GET["m"] == "info"){
		$result["server"] = true;
		$result["sql"] = true;
		$result["database"] = "mysql";
		$result["allowsCommands"] = array(
			"co" => "connect to an user: <id> <password>",
			"deco" => "deconnect to the user",
			"cuser" => "!super_user, creat an user: <id> <password>",
			"duser" => "!super_user, delet an user: <id>",
			"users" => "show all the user"
		);
	}
	print_r(json_encode($result));
}else{
	// basic html
	echo '<!DOCTYPE html><script src="./script.js"></script><link rel="stylesheet" href="./style.css">';
}
?>
