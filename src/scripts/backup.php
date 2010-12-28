#!/usr/bin/php
<?php
include_once 'classes/Deployer.php';
include_once 'classes/BackupManager.php';

$backup = new BackupManager();
$backup->setBackupDir(realpath(dirname(__FILE__)."/backup"));
$backup->setRemoteBackupDir("/");
$backup->setRemoteBackup(true);
$backup->setDataDir(realpath(dirname(__FILE__)."/../public"));

$backup->init();
$backup->db();
$backup->data();