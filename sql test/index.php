<?php
$db = new PDO(
	'mysql:host=localhost;dbname=dsb;charset=utf8',
	'root',
	''
);
$recipesStatement = $db->prepare("SELECT password FROM users WHERE id='root'");
$recipesStatement->execute();
$tp = $recipesStatement->fetchAll();
print_r($tp);
?>