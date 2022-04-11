<?php
$result = array(
	"set" => array(),
	"del" => array(),
	"server" => true,
);

$serverStatu = "ready";
if (file_exists("./dsbconf.php")){
	include "./dsbconf.php";
} else {
	$serverStatu = "not configured";
	$result["result"][0] = "not configured";
}

if (isset($dsbconf["terminal.external.allow"])) {
	if ($dsbconf["terminal.external.allow"] == true) {
		header("Access-Control-Allow-Origin: *");
	}
}

function logadd($txt, $level){
	if (isset($txt)) {
		if (isset($dsbconf["log.level"])) {
			if ($dsbconf["log.level"] >= $level) {
				file_put_contents(date("Y_m_d").".log", date("[Y:m:d / H:i:s]: ")."".$txt,FILE_APPEND);
			}
		}
	}
}

function execute($txt, $User, $Password, &$result, &$dsbconf){
	$commands = explode("\n", $txt);
	try {
		$db = new PDO(
			'mysql:host=localhost;dbname=dsb;charset=utf8',
			$dsbconf['PDO.user'],
			$dsbconf["PDO.password"],
		);
	} catch (Exception $e){
		$result["result"][0] = "fatal error: SQL connection faild";
		return false;
	}

	function get_file($directory_id = ""){
		try {
			$db = new PDO(
				'mysql:host=localhost;dbname=dsb;charset=utf8',
				'root',
				'',
			);
		} catch (Exception $e){
			return false;
		}
		$statement = $db->prepare("SELECT * FROM files WHERE directory_id = ?;");
		$statement->execute(array("".$directory_id));
		$result = $statement->fetchAll();
		if (count($result) > 0) {
			return $result[0];
		} else {
			return false;
		}
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

	$connected = false;
	for($i = 0;$i < count($commands);$i++){
		$command = explode(" ", $commands[$i]);
		// chec if the user is super user
		if ($User != "" && $Password != "") {
			if (!str_contains($User, ",")) {
				$recipesStatement = $db->prepare("SELECT password FROM users WHERE id = ?");
				$recipesStatement->execute(array($User));
				$tp = $recipesStatement->fetchAll();
				if($tp != false){
					if(isset($tp[0]["password"])){
						if($Password == $tp[0]["password"]){
							$connected = true;
						}
					}
				}
			}
		}
		$sudo = false;
		if ($User != "") {
			if (!str_contains($User, ",")) {
				$recipesStatement = $db->prepare("SELECT su FROM users WHERE id = ?;");
				$recipesStatement->execute(array($User));
				$tp = $recipesStatement->fetchAll();
				if($tp != false){
					if(isset($tp[0]["su"])){
						if($tp[0]["su"]){
							$sudo = true;
						}else{
							$sudo = true;
						}
					}else{
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
					}
				}else{
					$result["result"][$i] = "fatal error: SQL request faild";
				}
			} else {
				$result["result"][$i] = "fatal error: the username contains ','";
			}
		}
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
				case '':
					$result["result"][$i] = "";
					break;
				case 'execute_mode':
					$result["result"][$i] = "";
					break;
				case 'co':
					// chec if command is for a connection
					if(isset($command[1])){
						if (!str_contains($User, ",")) {
							if(isset($command[2])){
								$recipesStatement = $db->prepare("SELECT password FROM users WHERE id = ?");
								$recipesStatement->execute(array($command[1]));
								$tp = $recipesStatement->fetchAll();
								if($tp != false){
									if(isset($tp[0]["password"])){
										if($command[2] === $tp[0]["password"]){
											$User = $command[1];
											$Password = $command[2];
											$result["result"][$i] = "connection to '".$command[1]."' succes";
											$result["set"]["user"] = $command[1];
											$result["set"]["password"] = $command[2];
										}else{
											$result["result"][$i] = "fatal error: unauthorized, authentification failed: the password is not valid";
										}
									}else{
										$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not found";
									}
								}else{
									$result["result"][$i] = "fatal error: SQL request faild";
								}
							}else{
								$result["result"][$i] = "fatal error: unauthorized, authentification failed: the password is not defind";
							}
						} else {
							$result["result"][$i] = "fatal error: the username contains ','";
						}
					}else{
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: the user is not defind";
					}
					break;
				case 'users':
					// show all user
					$recipesStatement = $db->prepare("SELECT id FROM users;");
					$recipesStatement->execute();
					$tp = $recipesStatement->fetchAll();
					if ($tp !== false) {
						for ($i1=0; $i1 < count($tp); $i1++) {
							$tp1[$i1] = $tp[$i1]["id"];
						}
						$result["result"][$i] = "users :/".implode("/",$tp1)."";
					} else {
						$result["result"][$i] = "fatal error: SQL request faild";
						break;
					}
					break;
				case "deco":
					$User = "";
					$Password = "";
					$sudo = false;
					$connected = false;
					array_push($result["del"], "user");
					array_push($result["del"], "password");
					$result["result"][$i] = "deconnection succes";
					break;
				case "cuser":
					// create a user: cuser <id> <password> <super user>
					if($connected == true && $sudo == true){
						if (isset($command[1]) && isset($command[2])) {
							if (!str_contains($command[1], ",")) {
								$recipesStatement = $db->prepare("SELECT id FROM users WHERE id = ?;");
								$recipesStatement->execute(array($command[1]));
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
										$recipesStatement = $db->prepare("INSERT INTO users (id,password,su) VALUES ( ? , ? , ?);");
										$recipesStatement->execute(array($new_user_conf['id'],$new_user_conf['password'],$new_user_conf['super_user']));
										$result["result"][$i] = "user '".$new_user_conf['id']."' create";
									}
								} else {
									$result["result"][$i] = "error: SQL request faild";
								}
							} else {
								$result["result"][$i] = "fatal error: the username contains ','";
							}
						} else {
							$result["result"][$i] = "fatal error: the name or the password is not defind";
						}
					} else {
						$result["result"][$i] = "fatal error: your need be a super user to use this command";
					}
					break;
				case 'duser':
					// delet an user !!only for super user : duser <name>
					if ($connected == true && $sudo == true){
						if (isset($command[1])) {
							if ($command[1] != "root"){
								if (!str_contains($command[1], ",")) {
									$recipesStatement = $db->prepare("SELECT id FROM users WHERE id = ?;");
									$recipesStatement->execute(array($command[1]));
									$tp = $recipesStatement->fetchAll();
									if ($tp !== false){
										if (isset($tp[0]["id"])){
											$recipesStatement = $db->prepare("DELETE FROM users WHERE id = ?;");
											$recipesStatement->execute(array($command[1]));
											$result["result"][$i] = "user '".$command[1]."' deleted";
										} else {
											$result["result"][$i] = "fatal error: user not defind";
										}
									} else {
										$result["result"][$i] = "fatal error: SQL request faild";
									}
								} else {
									$result["result"][$i] = "fatal error: the username contains ','";
								}
							} else {
								$result["result"][$i] = "fatal error: root can't be deleted";
							}
						} else {
							$result["result"][$i] = "fatal error: the name is not defind";
						}
					} else {
						$result["result"][$i] = "fatal error: your need be a super user to use this command";
					}
					break;
				// file système
				case 'cat':
					// cat <file> <content> <compress> <access> <more>
					if ($connected == true){
						if (isset($command[1])) {
							// $User
							$file = $command[1];
							$content = "";
							if (isset($command[2])) {
								$content = $command[2];
							}
							$r = get_file($file);
							if ($r != false) {
								$tomodify = array();
								if (isset($command[3])) {
									if ($command[3] == "true" || $command[3] == "1") {
										$tomodify["compress"] = "true";
									} else {
										$tomodify["compress"] = "false";
									}
								}
								if (isset($command[4])) {
									$access = explode(",",$command[4]);									
									if (count($access) > 0) {
										$tomodify["access"] = "".implode(",",$access);
									}
								}
								if (isset($command[5])) {
									$tomodify["more"] = "".$more;
								}
								if (count($tomodify) > 0) {
									$result = "";
									$arr = array_keys($tomodify);
									for ($i=0; $i < count($arr); $i++) { 
										$result .= $arr[$i]." = ?";
										if (isset($arr[$i+1])) {
											$result .= " , ";
										}
									}
									print_r("UPDATE files SET ".$result." WHERE directory_id = ?;");
									$recipesStatement = $db->prepare("UPDATE files SET WHERE directory_id = ?;");
									$recipesStatement->execute(array_values($tomodify), $r['id']);
									$tp = $recipesStatement->fetchAll();
								}
								if ($content != "") {
									file_put_contents("./files/".$r['id'], "".$content);
								}
								$result["result"][$i] = "file '".$file."' modified";
							} else {
								$compress = false;
								if (isset($command[3])) {
									if ($command[3] == "true" || $command[3] == "1") {
										$compress = true;
									} else {
										$compress = false;
									}
								}
								$access = "";
								if (isset($command[4])) {
									$access = explode(",",$command[4]);									
									if (count($access) > 0) {
										$access = "".implode(",",$access);
									}
								}
								$more = "";
								if (isset($command[5])) {
									$more = "".$command[5];
								}
								function randomstr($lenght = 10){
									$str = "";
									$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
									$size = strlen($chars);
									for ($i=0; $i < $lenght; $i++) { 
										$str .= $chars[rand(0,$size-1)];
									}
									return $str;
								}

								$fileUUID = randomstr()."".uniqid("",true);
								$recipesStatement = $db->prepare("INSERT INTO files (directory_id,`compress`,id,access,more) VALUES (?, ? , ? , ? , ?);");
								$recipesStatement->execute(array("".$file, $compress, "".$fileUUID, "".$access, "".$more));
								$tp = $recipesStatement->fetchAll();
								file_put_contents("./files/".$fileUUID, "".$content);
								$result["result"][$i] = "file '".$file."' created";
							}
						} else {
							$result["result"][$i] = "error: the file is not defind";
						}
					} else {
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: you need be connected";
					}
					break;
				case 'gedit':
					if ($connected == true){
						if (isset($command[1])) {
							$file = get_file($command[1]);
							if ($file != false) {
								if (array_key_exists($User,$file["access"]) || $sudo == true) {
//									file_get_contents("../files/".$file["id"])
									$result["result"][$i] = file_get_contents("../files/".$file["id"]);
								} else {
									$result["result"][$i] = "fatal error: unauthorized, you need an permission to access this file";
								}
							} else {
								$result["result"][$i] = "error: the file is not find";
							}
						} else {
							$result["result"][$i] = "error: the file is not defind";
						}
					} else {
						$result["result"][$i] = "fatal error: unauthorized, authentification failed: you need be connected";
					}
					break;
				default:
					// command not found
					$result["result"][$i] = "warn: command not found";
			}
		}
	}
	return $result;
}


$preset = array(
	"user" => "",
	"password" => "",
);
if (isset($_POST["preset"])) {
	$tp = json_decode($_POST["preset"], true);
	if (isset($tp["user"]) && isset($tp["password"])) {
		$preset["user"] = $tp["user"];
		$preset["password"] = $tp["password"];
	}
}


if (isset($_GET["f"])) {
	if ($preset["user"] != "" && $preset["password"] != "") {
		if (!str_contains($preset["user"], ",")) {
			$recipesStatement = $db->prepare("SELECT password FROM users WHERE id = ?");
			$recipesStatement->execute(array($preset["user"]));
			$tp = $recipesStatement->fetchAll();
			if($tp != false){
				if(isset($tp[0]["password"])){
					if($preset["password"] == $tp[0]["password"]){
						$connected = true;
					}
				}
			}
		}
	}
	$sudo = false;
	if ($preset["user"] != "") {
		if (!str_contains($preset["user"], ",")) {
			$recipesStatement = $db->prepare("SELECT su FROM users WHERE id = ?;");
			$recipesStatement->execute(array($preset["user"]));
			$tp = $recipesStatement->fetchAll();
			if($tp != false){
				if(isset($tp[0]["su"])){
					if($tp[0]["su"]){
						$sudo = true;
					}else{
						$sudo = true;
					}
				}
			}
		}
	}
	if ($connected == true || $sudo == true) {
		$file = get_file($_GET["f"]);
		if ($file != false) {
			if (array_key_exists($User,$file["access"]) || $sudo == true) {
				print_f(file_get_contents("../files/".$file["id"]));
			} else {
//				http_response_code(403);
				print_f("error: unauthorized, you need an permission to access this file");
			}
		} else {
			print_f("error: the file is not find");
		}
	} else {
		print_f("fatal error: unauthorized, authentification failed: you need be connected");
	}
} else if(isset($_GET["m"])){
	if ($serverStatu == "not configured") {
		exit(json_encode($result));
	}
	if($_GET["m"] == "terminal"){
		if(isset($_POST["t"])){
			execute($_POST["t"], $preset["user"], $preset["password"], $result, $dsbconf);
		}
	}
	if ($_GET["m"] == "basic") {
		if(isset($_POST["t"])){
			execute($_POST["t"], $preset["user"], $preset["password"], $result, $dsbconf);
			$result = implode("\n", $result["result"]);
		}
	}
	if($_GET["m"] == "info"){
		$result["sql"] = true;
		$result["database"] = "mysql";
		$result["allowsCommands"] = array(
			"co" => array(
				"description" => "connect to an user: <id> <password>",
				"parmType" => array(
					"id" => ":string",
					"password" => ":string",
				),
			),
			"deco" => array(
				"description" => "deconnect to the user",
				"parmType" => array(),
			),
			"users" => array(
				"description" => "show all the user",
				"parmType" => array(),
			),
			"cuser" => array(
				"description" => "!super_user, creat an user: <id> <password> <super user>",
				"parmType" => array(
					"id" => ":string",
					"password" => ":string",
					"super_user" => ":boolean",
				),
			),
			"duser" => array(
				"description" => "!super_user, delet an user: <id>",
				"parmType" => array(
					"id" => ":string",
				),
			),
			"cat" => array(
				"description" => "create / modify a file <file> <content> <compress> <access> <more>",
				"parmType" => array(
					"file" => ":string",
					"content" => ":string",
					"compress" => ":string",
					"access" => ":string",
					"more" => ":string",
				),
			),
		);
	}
	print_r(json_encode($result));
}else{
	// basic html
	echo '<!DOCTYPE html><script src="./script.js"></script><link rel="stylesheet" href="./style.css">';
}
?>
