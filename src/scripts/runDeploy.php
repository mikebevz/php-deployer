#!/usr/bin/php
<?php
$thisDir = dirname(__FILE__);
include_once $thisDir.DIRECTORY_SEPARATOR."../classes/Deployer.php";

try {
$dep = new Deployer($thisDir.DIRECTORY_SEPARATOR."config.xml");
} catch (Exception $e) {
    echo("\033[31mError: ".$e->getMessage()." \033[0m\n");
}