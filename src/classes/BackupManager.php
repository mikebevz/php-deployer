<?php

class BackupManager {

    private $ftpHost = "local.nordsign.dk";
    private $ftpPort = "21";
    private $ftpUser = "";
    private $ftpPass = "";
    private $backupDir;
    private $remoteBackupDir;
    private $dbHost = "localhost";
    private $dbPort = "3306";
    private $dbUser = "";
    private $dbPass = "";
    private $dbName = "";
    private $dbSocket = "/tmp/mysqld.sock";
    private $remoteBackup = false;
    private $backupDbFile;
    private $backupDataFile;
    private $dataDir;
    private $backupArchiveName;
    /**
     *
     * Deployer
     * @var Deployer
     */
    private $deployer;
    private $items = array();

    public function __construct() {
        $this->deployer = new Deployer();

    }

    public function setBackupDir($dir) {
        $this->backupDir = $dir;
    }

    public function getBackupDir() {
        return $this->backupDir;
    }

    public function setDataDir($dir) {
        $this->dataDir = $dir;
    }

    public function getDataDir() {
        return $this->dataDir;
    }

    public function setRemoteBackupDir($dir) {
        $this->remoteBackupDir = $dir;

    }

    public function getRemoteBackupDir() {
        return $this->remoteBackupDir;
    }

    public function setRemoteBackup($status) {
        $this->remoteBackup = $status;

    }

    public function getRemoteBackup() {
        return $this->remoteBackup;
    }

    public function init() {
        $this->backupDataFile = "NsWebDataBackup-".date("Y-m-d-H-i-s").".zip";

        $this->deployer->setSourceDir($this->getDataDir());
        $this->deployer->setTargetDir($this->getBackupDir());
        $this->deployer->setDeploymentDir($this->getBackupDir());
        $this->deployer->setDeploymentName($this->backupDataFile);
        array_push($this->items, "/data");
        $this->deployer->setItems($this->items);

        $this->deployer->cleanTargetDirAction();
    }

    public function data() {
        $this->deployer->copyFilesAction();
        $this->deployer->buildAcrhive();
        $this->say("Packing backups for deployment");

        //$command = "gzip -9 ".$this->getBackupDir().DIRECTORY_SEPARATOR.$this->backupDataFile;
        //system($command, $status);
        $this->backupArchiveName = $this->getBackupDir().DIRECTORY_SEPARATOR.$this->backupDataFile.".gz";
        $this->say("Backups saved to ".$this->backupArchiveName);

        if ($this->remoteBackup === true) {
            $this->deployRemote();
        }
    }

    public function db() {
        // Connect to DB
        $this->backupDbFile = $this->backupDir.DIRECTORY_SEPARATOR.$this->dbName.'-'. date("Y-m-d-H-i-s") . '.gz';
        $command = "mysqldump -h ".$this->dbHost." -u ".$this->dbUser." --password=".$this->dbPass."  ".$this->dbName." | gzip -9 > ".$this->backupDbFile;
        $this->say("Create database dump for ".$this->dbName);
        $this->say("Command is ".$command);
        system($command, $status);
        $this->say("Status: ".$status);
        $this->say($this->dbName." successfully saved to ".$this->backupDbFile);

    }

    private function deployRemote() {
        // File to deploy$this->backupArchiveName
        if (!file_exists($this->backupArchiveName)) {
            $this->say("Backup file is not found: ".$this->backupArchiveName);
            return false;
        }

        $localData = file_get_contents($this->backupArchiveName);

        $handle = fopen("ftp://".$this->ftpUser.":".$this->ftpPass."@".$this->ftpHost."/".basename($this->backupArchiveName), "w");

        $parts = 20;
        $dataLength = strlen($localData);
        $chunksize = (int)($dataLength / $parts);
        $rest = ($dataLength % $parts);
        $this->say("Data length ".$dataLength);
        $this->say("Chunk size ".$chunksize);
        $this->say("Rest size ".$rest);
        $this->say("Total: ".($chunksize*$parts+$rest));

        $timeStart = time();
        $bytes = false;

        for ($written = 0; $written < strlen($localData); $written += $chunksize) {
             
            //$this->say("Start: ".$written. " Size: ".$chunksize. "Rest ".($dataLength-$written));
            $bytes += fwrite($handle, substr($localData, $written, $chunksize));
            if ($dataLength-$written ==$rest) {
                //$this->say("Write rest ".$rest);
                $bytes += fwrite($handle, substr($localData, $written+$chunksize, $rest));
            }
            $timeCurrent = time();
            $timeSpent = $timeCurrent-$timeStart;

             
            if ($timeSpent > 0){
                $speed = (int)(($bytes/$timeSpent)/1024);
                $timeLeft = (int) (($dataLength-$written)/($speed*1024));
                $this->say("Time spent: ".$timeSpent." seconds. Speed: ".$speed. "Kb/s. Finished in ".$timeLeft." seconds");
            }
            $this->say("Left: ".(($dataLength-$written))." bytes. ". ((int)(($written/$dataLength)*100))."%");
        }

        if ($bytes === false) {
            throw new Exception("Could not send data from file ".$localFilename);
        }

        if ($dataLength != $bytes) {
            $this->say("Wrong data count");
        }

        $this->say("Sent :".($bytes)." Kb");

        /*
         if (ftp_fput($ftpCon, $this->backupArchiveName, $fp, FTP_BINARY)) {
         $this->say("Files uploaded successfully");
         } else {
         $this->say("Problem uploading file ".$this->backupArchiveName);
         }*/

        //ftp_close($ftpCon);
        fclose($handle);

    }

    private function say($msg) {
        echo($msg."\n");
    }
}