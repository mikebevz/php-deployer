#!/usr/bin/php
<?php
$thisDir = dirname(__FILE__);
include_once $thisDir.DIRECTORY_SEPARATOR."../classes/Deployer.php";

$configPath = $_SERVER['PWD'].DIRECTORY_SEPARATOR."deployer.xml";

if (count($argv) == 2) {
    $configPath = $_SERVER['PWD'].DIRECTORY_SEPARATOR.$argv[1];
    if (!file_exists($configPath)) {
        echo("\033[31mCannot find ".$configPath." \033[0m\n");
        exit();
    }
} else {
    if (!file_exists($configPath)) {
        echo("\033[31mCannot find deployer.xml or specify path to config file \033[0m\n");
        exit();
    }
}

try {
    $dep = new Deployer($configPath);
} catch (Exception $e) {
    echo("\033[31mError: ".$e->getMessage()." \033[0m\n");
}