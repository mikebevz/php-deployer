<?php
class Deployer {

    private $sourceDir;
    private $targetDir;
    private $zfBasePointer;
    private $pharName;
    private $stubName;
    private $stubPath;
    private $deployDir;
    private $deploymentName;
    private $remoteHost = "";
    private $remotePort = 22;
    private $remoteUsername = "";
    private $remotePassword = "";
    private $knownHost = "D59C12ADC382C3A12E519D05484690A2";
    private $version = "0.0.1";
    private $actionsCount = 0;
    private $quiet = false;
    /**
     *
     * Phar
     * @var Phar
     */
    private $phar;

    /**
     * Path to config file
     * @var string
     */
    private $configPath;
    /**
     *
     * Configuration
     * @var Object
     */
    private $config;
    private $verbose;

    private $configSchemaPath;

    private $items = array();

    private function say($msg) {
        if (!$this->quiet) {
            echo($msg."\n");
        }
    }

    private function sayYes($msg) {
        if (!$this->quiet) {
            echo("\033[32m".$msg."\033[0m\n");
        }
    }

    private function sayNo($msg) {
        if (!$this->quiet) {
            echo("\033[31m".$msg."\033[0m\n");
        }
    }

    /**
     *
     * Initialize deployer
     * @param string $config Path to config file
     */
    public function __construct($config = "deployer.xml") {
        if ($config == "deployer.xml") {
            $this->configPath = dirname(__FILE__).DIRECTORY_SEPARATOR.$config;
        } elseif (file_exists($config)) {
            $this->configPath = $config;
        } else {
            throw new Exception("Config file is not found in ".$config);
        }

        if (!is_readable($this->configPath)) {
            throw new Exception($this->configPath." is not readable");
        }

        // schema must be in the same directory with Deployer.php TODO figure out what's the best
        $this->configSchemaPath = dirname(__FILE__).DIRECTORY_SEPARATOR."Deployer_1.0.xsd";

        $this->parseConfig();


    }

    /**
     *
     * Parse XML configuration file and set up deployer
     * @return void
     * @throws Exception If targetDir is not found
     * @throws Exception If sourceDir is not found
     */
    private function parseConfig() {
        $this->say("PhpDeployer version ".$this->version);
        $this->say($this->getActionsCount().". Parsing config in ".$this->configPath);

        // Checking config against schema
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->load($this->configPath);
        $doc->schemaValidate($this->configSchemaPath);
        $errors = libxml_get_errors();
        if ($errors) {
            throw new Exception($errors[0]->message);
        }

        $this->config = simplexml_load_file($this->configPath, "SimpleXMLElement", LIBXML_COMPACT|LIBXML_NOCDATA);

        $config = (array)$this->config;

        if ($this->checkDir((string)$config['targetDir'])){
            $this->setTargetDir(realpath((string)$config['targetDir']));
        } else {
            throw new Exception("Target directory is not found in ".(string)$config['targetDir']);
        }
        if ($this->checkDir((string)$config['sourceDir'])) {
            $this->setSourceDir(realpath((string)$config['sourceDir']));
        } else {
            throw new Exception("Source directory is not found in ".(string)$config['sourceDir']);
        }

        $projectName = preg_replace("/ /", "_", $config['project']);//TODO include check for special symbols etc
        if ($config['verbose'] == 'true') {
            $this->verbose = true;
        } else {
            $this->verbose = false;
        }


        $this->verbose ? $this->say("Deploying ".$config['project']."...") : false;
        $this->setDeploymentName($projectName.".zip");


        $items = array();
        foreach($config['items'] as $item) {
            array_push($items, (string)$item);
        }
        $this->setItems($items);
        $this->sayYes("Done");
    }
    
    private function getActionsCount() {
        return $this->actionsCount++;
    }

    /**
     *
     * Delete everything in targetDir
     * @return void
     */
    public function cleanTargetDirAction() {

        if (!file_exists($this->targetDir)) {
            mkdir($this->targetDir);
        }

        if (!is_writeable($this->targetDir)) {
            throw new Exception($this->targetDir." must be writable");
        }
        $this->verbose ? $this->say($this->getActionsCount().". Deleting content of ".$this->targetDir) : $this->say($this->getActionsCount().". Cleaning target directory...");
        
        $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->targetDir), RecursiveIteratorIterator::CHILD_FIRST);
        foreach($dir as $cur) {
            if ($cur->getFilename() == '.' || $cur->getFilename() == '..') {
                continue;
            }
            $filePath = $cur->getPathname();
             
            if (is_dir($filePath)) {
                rmdir($filePath);
            }
            if (is_file($filePath) || is_link($filePath)) {
                unlink($filePath);
            }
        }
        $this->sayYes("Done");

    }
    
    /**
     * 
     * Copy specified items to targetDir
     * @return void
     * @throws Exception If sourceDir is not readable
     * @throws Exception If targetDir is not readable
     * @throws Exception If items are not specified
     */
    public function copyFilesAction() {
        if (!is_readable($this->sourceDir)) {
            throw new Exception($this->sourceDir." must be readable");
        }

        if (!is_writeable($this->targetDir)) {
            throw new Exception($this->targetDir." must be writable");
        }

        $this->say($this->getActionsCount().". Copying files...");

        if (empty($this->items)) {
            throw new Exception("Please specify files for deployment in your ".$this->configPath);
        }

        foreach($this->items as $file) {

            $srcItemName = $this->sourceDir.DIRECTORY_SEPARATOR.$file;
            $dstItemName = $this->targetDir.DIRECTORY_SEPARATOR.$file;
            $this->verbose ? $this->say($srcItemName." to ".$dstItemName) : false;
            if (!file_exists($srcItemName)) {
                $this->say("File not found:". $srcItemName);
            }
            if (is_dir($srcItemName)) {
                $this->recDirCopy($srcItemName, $dstItemName);
            }

            if (is_file($srcItemName)) {
                $this->recFileCopy($srcItemName, $dstItemName);

            }
        }

        $this->verbose ? $this->say("Files successfully copied from ".$this->sourceDir." to ".$this->targetDir) : $this->sayYes("Done");


    }

    /**
     *
     * Check existance of the directory
     * @param string $dir
     * @return boolean
     */
    private function checkDir($dir) {
        if (file_exists($dir)) {
            // check if directory exists in case it's absolute path
            return true;
        } elseif (file_exists(basename($this->configPath).DIRECTORY_SEPARATOR.$dir)) {
            // It might be relative path - check if directory exists where config located
            return true;
        } else {
            return false;
        }


    }

    /**
     * Build ZIP archive and gzip it
     *
     * @return void
     */
    public function buildAcrhiveAction() {

        $zip = new ZipArchive();
        $archive = $this->targetDir.DIRECTORY_SEPARATOR.$this->deploymentName;
        $this->verbose ? $this->say($this->getActionsCount().". Building new ZIP acrhive in ".$archive) : $this->say($this->getActionsCount().". Archiving files...");
        if (file_exists($archive)) {
            unlink($archive);
            $this->verbose ? $this->say("Deleting old archive: ".$archive) : false;
        }

        if ($zip->open($archive, ZIPARCHIVE::CREATE) !== true) {
            $this->say("Cannot create new archive in".$archive);
            return;
        }

        $this->addDir($this->targetDir, '.', $zip);

        $zip->close();

        if (!file_exists($archive)) {
            $this->say(__METHOD__.': Backup was not created for unknown reason');
            throw new RuntimeException(__METHOD__.': Backup was not created for unknown reason');
        }

        $command = "gzip -9 ".$archive;
        system($command, $status);
        $this->deploymentName = $this->deploymentName.".gz";
        $this->verbose ? $this->say("New archive created and ready for deployment: ".$archive.".gz") : $this->sayYes("Done");

    }

    /**
     * Adds file to archive recursivelly
     *
     * @param string     $filename
     * @param string     $localname
     * @param ZipArchive $zip
     *
     * @return void
     */
    private function addDir($filename, $localname, ZipArchive $zip) {

        $zip->addEmptyDir($localname);

        $iter = new RecursiveDirectoryIterator($filename);

        foreach ($iter as $fileinfo) {
            if (!$fileinfo->isFile() && !$fileinfo->isDir()) {
                $this->say('Skippping file '. $fileinfo->getFilename());
                continue;
            }

            if ($fileinfo->getFilename() == '.' || $fileinfo->getFilename() == '..' || $fileinfo->getFilename() == $this->targetDir.DIRECTORY_SEPARATOR.$this->deploymentName) {
                $this->say('Skippping file '. $fileinfo->getFilename());
                continue;
            }
            if ($fileinfo->isFile()
            //&& preg_match('/(.*).(png|xml)/', $fileinfo->getFilename())
            ) {
                $zip->addFile($fileinfo->getPathname(), $localname . DIRECTORY_SEPARATOR .
                $fileinfo->getFilename());
                // $this->say(__METHOD__.': added file'.$fileinfo->getPathname());
            }

            if ($fileinfo->isDir()){
                $this->addDir($fileinfo->getPathname(), $localname .DIRECTORY_SEPARATOR .
                $fileinfo->getFilename(), $zip);
                //$this->say(__METHOD__.': added dir'.$fileinfo->getPathname());
            }

        }
    }

    /**
     * Problem with PHAR is that it doesn't support realpath for now. There is some bug
     * Enter description here ...
     */
    public function buildPhar() {

        $filePath = $this->targetDir.DIRECTORY_SEPARATOR.$this->pharName;
        $this->say("Creating new phar archive: ".$filePath);
        if (file_exists($filePath)) {
            Phar::unlinkArchive($filePath);
            $this->say("Deleting old build: ".$filePath);
        }

        $this->phar = new Phar($filePath, 0, $this->pharName);
        //$this->phar->compress(Phar::GZ);
        $this->phar->setSignatureAlgorithm(Phar::SHA1);

        $files = array();
        //$files[$this->stubName] = $this->targetDir.DIRECTORY_SEPARATOR.$this->stubName;
        $this->copyStub();

        $rd = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->targetDir));
        foreach($rd as $file) {
            if ((strpos($file->getPath(), '.svn') ===false) &&
            $file->getFilename() != '..' &&
            $file->getFilename() != '.'){
                $files[substr($file->getPath().DIRECTORY_SEPARATOR.$file->getFilename(), strlen($this->targetDir))] = $file->getPath().DIRECTORY_SEPARATOR.$file->getFilename();

            }

        }

        $this->phar->startBuffering();
        $this->phar->buildFromIterator(new ArrayIterator($files));
        $this->phar->stopBuffering();
        $this->phar->setStub($this->phar->createDefaultStub($this->stubName, "public/index.php"));
        $this->phar = null;
        //print_r($files);
        $this->say("Phar successfully created ".$filePath);
    }

    private function copyStub() {
        //TODO add checks
        copy($this->stubPath.DIRECTORY_SEPARATOR.$this->stubName, $this->targetDir.DIRECTORY_SEPARATOR.$this->stubName);
        $this->say("Stub copied to ".$this->targetDir.DIRECTORY_SEPARATOR.$this->stubName);
    }

    public function deploy() {
        $deployFilename = $this->deployDir.DIRECTORY_SEPARATOR.$this->deploymentName;
        if (file_exists($deployFilename)) {
            unlink($deployFilename);
            $this->say("Deleted previous version in deployment dir");
        }

        copy($this->targetDir.DIRECTORY_SEPARATOR.$this->deploymentName, $deployFilename);
        $this->say("File deployed to ".$this->deployDir.DIRECTORY_SEPARATOR.$this->deploymentName);

    }



    public function getSshConnection() {

        $methods = array(
              'kex' => 'diffie-hellman-group1-sha1',
              'client_to_server' => array(
                'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
                'comp' => 'none',
                'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
                ),
              'server_to_client' => array(
                'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
                'comp' => 'none',
                'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
                ));
                $connection = ssh2_connect($this->remoteHost, $this->remotePort, $methods);
                $fingerprint = ssh2_fingerprint($connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);

                if ($fingerprint != $this->knownHost) {
                    die ("HOSTKEY MISMATCH! Possible Man-In-The-Middle attack?");
                }
                $this->say("Fingerprint: ".$fingerprint);

                if (!ssh2_auth_password($connection, $this->remoteUsername, $this->remotePassword)) {
                    //if (!ssh2_auth_pubkey_file($connection, $this->remoteUsername, $this->pubKey, $this->privKey)) {
                    $this->say("Unable to establish connection to ".$this->remoteHost.":".$this->remotePort);
                    return false;
                }

                return $connection;
    }

    public function deployRemote() {

        $connection = $this->getSshConnection();
        if (!$connection) {
            $this->say("Problem with SSH connection");
            return false;
        }

        //ssh2_scp_send($connection, $this->deployDir.DIRECTORY_SEPARATOR.$this->deploymentName,"/var/www/sites/".$this->deploymentName);
        //ssh2_exec($objConnection, 'exit');
        $localFilename = $this->deployDir.DIRECTORY_SEPARATOR.$this->deploymentName;
        $remoteFilename = "/var/www/sites/deploy/".$this->deploymentName;
        $this->say("Upload ".$localFilename." to ".$this->remoteHost.":".$this->remotePort."".$remoteFilename);

        $sftp = ssh2_sftp($connection);

        $sftpStream = fopen('ssh2.sftp://'.$sftp.$remoteFilename, 'w+');

        try {
            if (!$sftpStream) {
                throw new Exception("Could not open remote file: ".$remoteFilename);
            }

            $localData = @file_get_contents($localFilename);

            if ($localData === false) {
                throw new Exception("Could not open local file: ".$localFilename);
            }
            $parts = 40;
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
                $bytes += fwrite($sftpStream, substr($localData, $written, $chunksize));
                if ($dataLength-$written ==$rest) {
                    //$this->say("Write rest ".$rest);
                    $bytes += fwrite($sftpStream, substr($localData, $written+$chunksize, $rest));
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

            fclose($sftpStream);
            $this->say("File successfuly uploaded to ".$remoteFilename);
        } catch (Exception $e) {
            error_log('Exception: '.$e->getMessage());
            fclose($sftpStream);
        }

        //$this->unzipRemote($connection);
    }

    public function unzipRemote($connection = false) {
        $this->remoteCommand('/var/www/sites/deploy/unzip.php');


    }

    private function remoteCommand($command, $connection = false) {
        if ($connection === false) {
            $connection = $this->getSshConnection();
        }
        // Run unzip.php through console
        $this->say("Executing ".$command);

        $stream = ssh2_exec($connection, $command, true);
        stream_set_blocking($stream, true);

        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($errorStream, true);

        while($line = fgets($stream)) {
            flush();
            $line .= $line;
        }

        if ($line != '') {
            $this->say("Result: ".$line);
        }


        $this->say("Result: ".stream_get_contents($stream));
        $this->say("Error: ".stream_get_contents($errorStream));

        fclose($stream);
        fclose($errorStream);

    }


    public function backupData() {
        //Backup data on remote host
        //Backup database on remote host
    }





    private function recFileCopy ($src, $dst) {
        $srcDir = dirname($src);
        $dstDir = dirname($dst);
        $this->checkDirOrCreateRec($dstDir);
        copy($src, $dst);

    }
    
    /**
     * 
     * Copy directory recursivery
     * @param string $src Source path
     * @param string $dst Target path
     * @return void
     */
    private function recDirCopy($src, $dst) {

        $this->checkDirOrCreateRec($dst);

        $dir = opendir($src);

        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $srcFilename = $src . DIRECTORY_SEPARATOR . $file;
                $dstFilename = $dst . DIRECTORY_SEPARATOR . $file;

                if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
                    $this->recDirCopy($srcFilename, $dstFilename);
                } else {

                    if (!file_exists($srcFilename)) {
                        $this->say($srcFilename." not exist");
                        continue;
                    }

                    if (!file_exists(dirname($dstFilename))){
                        $this->say(dirname($dstFilename)." not exist");
                        $this->checkDirOrCreateRec(dirname($dstFilename));
                    }

                    copy($srcFilename, $dstFilename);


                }
            }
        }
        closedir($dir);
    }

    public function extractArchive($archive, $dest) {
        if (!is_writable($dest)) {
            throw new Exception("Dest dir ".$dest." is no writable");
        }
        $zip = new ZipArchive();
        if ($zip->open($archive) == true) {
            $zip->extractTo($dest);
            $zip->close();
        } else {
            $this->say("Archive extraction failed: ".$zip->getStatusString());
        }
    }

    private function checkDirOrCreateRec($dst) {
        $arr = explode(DIRECTORY_SEPARATOR, $dst);
        $length = count($arr);
        $prev = '';
        for ($i=0;$i<$length;$i++) {
            $path = $prev.$arr[$i];
            if (!file_exists($path)) {
                @mkdir($path);
            }
            $prev .= $arr[$i].DIRECTORY_SEPARATOR;
        }
    }

    private function checkDirOrCreate($src, $dst) {

        $relative    = $this->mb_string_intersect($src, $dst);
        $common = str_replace($relative, '', $dst);
        $arr = explode(DIRECTORY_SEPARATOR, $relative);
        $length = count($arr);
        $prev = '';
        for ($i=0; $i<$length;$i++) {

            $path = $common.$prev.$arr[$i];
            if (!file_exists($path)) {
                mkdir($path);
            }
            $prev .= $arr[$i].DIRECTORY_SEPARATOR;
            //$this->say($path);
        }
        //$this->say($common.$relative." length:".$length);

    }



    public function compileJsAction() {
        // Compile JS files
    }


    private function mb_string_intersect($string1, $string2, $minChars = 5)
    {
        assert('$minChars > 1');

        $string1 = trim($string1);
        $string2 = trim($string2);

        $length1 = mb_strlen($string1);
        $length2 = mb_strlen($string2);

        if ($length1 > $length2) {
            // swap variables, shortest first

            $string3 = $string1;
            $string1 = $string2;
            $string2 = $string3;

            $length3 = $length1;
            $length1 = $length2;
            $length2 = $length3;

            unset($string3, $length3);
        }

        if ($length2 > 255) {
            return null; // to much calculation required
        }

        for ($l = $length1; $l >= $minChars; --$l) { // length
            for ($i = 0, $ix = $length1 - $l; $i <= $ix; ++$i) { // index
                $substring1 = mb_substr($string1, $i, $l);
                $found = mb_strpos($string2, $substring1);
                if ($found !== false) {
                    return trim(mb_substr($string2, $found, mb_strlen($substring1)));
                }
            }
        }

        return null;
    }

    public function getTargetDir() {
        return $this->targetDir;
    }

    public function setTargetDir($dir) {
        $this->targetDir = $dir;
    }

    public function getSourceDir() {
        return $this->sourceDir;
    }

    public function setSourceDir($dir) {
        $this->sourceDir = $dir;
    }

    public function setDeploymentDir($dir) {
        $this->deployDir = $dir;
    }

    public function getDeploymentDir() {
        return $this->deployDir;
    }

    public function setDeploymentName($name) {
        $this->deploymentName = $name;
    }

    public function getDeploymentName() {
        return $this->deploymentName;
    }

    public function setItems($items) {

        $this->items = $items;
    }

    public function getItems() {
        return $this->items;
    }

    public function remoteBackupAction() {
        $this->remoteCommand("/var/www/sites/deploy/backup.php");

    }


}