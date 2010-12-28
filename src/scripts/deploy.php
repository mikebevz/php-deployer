#!/usr/bin/php
<?php

include_once "classes/Deployer.php";
include_once "classes/BackupManager.php";

$dep = new Deployer();
// Configuration
$dep->setSourceDir(realpath("../"));
$dep->setTargetDir(realpath("../../build/"));
$dep->setDeploymentDir(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."webphar"));
$dep->setDeploymentName("nsweb.zip");

//$dep->remoteBackupAction();
$dep->cleanTargetDirAction();
//$dep->compileJavaScript(); // TODO Implement it
$dep->copyFilesAction();
$dep->buildAcrhive();
$dep->deploy();
$dep->deployRemote();
$dep->unzipRemote();
$dep->cleanTargetDirAction();
