<?php
$result = array("statu" => 200);

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
	try{
		$db = new PDO(
			'mysql:host=localhost;dbname=dsb;charset=utf8',
			'root',
			''
		);
	}catch (Exception $e){
		$result["statu"] = 500;
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
			// chec if command is for a connection
			if($command[0] == "co"){
				if(isset($command[1])){
					if(isset($command[2])){
						if(srtsafe($command[1])){
							$recipesStatement = $db->prepare("SELECT password FROM users WHERE id='".$command[1]."'");
							$recipesStatement->execute();
							$tp = $recipesStatement->fetchAll();
							if($tp != false){
								if(isset($tp[0]["password"])){
									if($command[2] === $tp[0]["password"]){
										setcookie("User",$command[1]);
										$token = rdstring(20);
										$recipesStatement = $db->prepare("UPDATE users SET C_token = '".$token."' WHERE id = '".$command[1]."'");
										$recipesStatement->execute();
										$tp = $recipesStatement->fetchAll();
										setcookie("User_token",$token);
										$result["result"][$i] = "connection to '".$command[1]."' succes";
									}else{
										$result["statu"] = 400;
										$result["result"][$i] = "fatal error: unauthorized, authentification failed: the passworld is not valid";
										break;
									}
								}else{
									$result["statu"] = 400;
									$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
									break;
								}
							}else{
								$result["statu"] = 400;
								$result["result"][$i] = "fatal error: SQL request faild";
								break;
							}
						}else{
							$result["statu"] = 400;
							$result["result"][$i] = "fatal error: SQL request is not secure";
							break;
						}
					}else{
						$result["statu"] = 400;
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: the passworld is not defind";
						break;
					}
				}else{
					$result["statu"] = 400;
					$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not defind";
					break;
				}
			}else{
				// chec if the user is connect
				if(isset($_COOKIE["User_token"]) && isset($_COOKIE["User"])){
					if(srtsafe($_COOKIE["User"])){
						$recipesStatement = $db->prepare("SELECT c_token FROM users WHERE id = '".$_COOKIE["User"]."';");
						$recipesStatement->execute();
						$tp = $recipesStatement->fetchAll();
						if($tp != false){
							if(isset($tp[0]["c_token"])){
								if($_COOKIE["User_token"] === $tp[0]["c_token"]){
									$token = rdstring(20);
									$recipesStatement = $db->prepare("UPDATE users SET C_token = '".$token."' WHERE id = '".$_COOKIE["User"]."'");
									$recipesStatement->execute();
									setcookie("User_token",$token);

									// chec if the user is a super_user
									$recipesStatement = $db->prepare("SELECT su FROM users WHERE id = '".$_COOKIE["User"]."';");
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
											setcookie("User", "", time()-3600);
											setcookie("User_token", "", time()-3600);
											$result["result"][$i] = "deconnection succes";
											break;
										case "cuser":
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
																	}elseif ($command[3] == 'false') {
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
												}else {
													$result["result"][$i] = "fatal error: the name or the password is not defind";
													break;
												}
											}else {
												$result["result"][$i] = "fatal error: your need be a super user to use this command";
												break;
											}
											break;
										case 'duser':
											if($sudo == true){
												if (isset($command[1])) {
													if (srtsafe($command[1])) {
														if($command[1] != "root"){
															$recipesStatement = $db->prepare("SELECT id FROM users WHERE id='".$command[1]."';");
															$recipesStatement->execute();
															$tp = $recipesStatement->fetchAll();
															if($tp !== false){
																if(isset($tp[0]["id"])){
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
												}else {
													$result["result"][$i] = "fatal error: the name is not defind";
												}
											}else {
												$result["result"][$i] = "fatal error: your need be a super user to use this command";
											}
											break;
										default:
											// command not found
											$result["result"][$i] = "warn: command not found";
									}
								}else{
									$result["statu"] = 400;
									$result["result"][$i] = "fatal error: unauthorized, authentification failed: the User_token is not valid";
									break;
								}
							}else{
								$result["statu"] = 400;
								$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
								break;
							}
						}else{
							$result["statu"] = 400;
							$result["result"][$i] = "fatal error: SQL request faild";
							break;
						}
					}else{
						$result["statu"] = 400;
						$result["result"][$i] = "fatal error: SQL request is not secure";
						break;
					}
				}else{
					// show the error
					$result["statu"] = 401;
					$result["result"][$i] = "fatal error: unauthorized, authentification failed";
					break;
				}
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
	print_r(json_encode($result));
}else if(isset($_GET["a"])){
	// in case of test
}else{
	// basic html
	echo '<!DOCTYPE html><link rel="stylesheet" href="./style.css"><script src="./script.js"></script>';
}
?>
