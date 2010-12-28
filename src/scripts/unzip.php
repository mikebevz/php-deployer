#!/usr/bin/php
<?php

class Unzip {

    public function extractArchive($archive, $dest) {
        if (!is_writable($dest)) {
            throw new Exception("Dest dir ".$dest." is no writable");
        }
        if (file_exists($archive)) {
            $command = "gzip -f -d ".$archive;
            system($command, $status);
        } else {
            $this->say("No files to extract");
            return false;
        }
        $archive = str_replace(".gz", "", $archive);

        $zip = new ZipArchive();
        if ($zip->open($archive) == true) {
            $zip->extractTo($dest);
            $zip->close();
        } else {
            $this->say("Archive extraction failed: ".$zip->getStatusString());
        }
    }
}

$unzip = new Unzip();

if ($unzip->extractArchive(realpath(dirname(__FILE__).'/nsweb.zip.gz'), realpath(dirname(__FILE__).'/../prod.nordsign.dk.test'))) {
    print("nsweb.zip was successfully extracted to ".realpath("../prod.nordsign.dk.test")."\n");

    copy(realpath(dirname(__FILE__).'/../prod.nordsign.dk.test/deploy/unzip.php'), realpath(dirname(__FILE__))."/unzip.php");
    copy(realpath(dirname(__FILE__).'/../prod.nordsign.dk.test/deploy/backup.php'), realpath(dirname(__FILE__))."/backup.php");
    if (!file_exists(realpath(dirname(__FILE__))."/classes/")) {
        mkdir(realpath(dirname(__FILE__))."/classes/");
    }
    copy(realpath(dirname(__FILE__).'/../prod.nordsign.dk.test/deploy/classes/Deployer.php'), realpath(dirname(__FILE__))."/classes/Deployer.php");
    copy(realpath(dirname(__FILE__).'/../prod.nordsign.dk.test/deploy/classes/BackupManager.php'), realpath(dirname(__FILE__))."/classes/BackupManager.php");
}
