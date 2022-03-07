<?php
$db = new PDO(
	'mysql:host=localhost;dbname=dsb;charset=utf8',
	'root',
	'your_password'
);
$recipesStatement = $db->prepare("SELECT c_token FROM users WHERE id = 'root';");
$recipesStatement->execute();
$tp = $recipesStatement->fetchAll();
print_r($tp);
?>