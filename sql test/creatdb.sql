CREATE DATABASE `dsb`;
CREATE TABLE `dsb`.`users` (
	`id` TEXT NOT NULL,
	`password` TEXT NOT NULL,
	`su` BOOLEAN NOT NULL,
	`C_token` TEXT
)ENGINE=MyISAM;

CREATE TABLE `dsb`.`files` (
	`directory_id` TEXT NOT NULL,
	`compress` BOOLEAN NOT NULL,
	`id` TEXT NOT NULL,
	`access` TEXT NOT NULL,
	`more` TEXT
)ENGINE=MyISAM;

INSERT INTO users (id,password,su) VALUES ('root','root',true);