<?php
 ini_set('default_charset','UTF-8');
 if($_POST['save'] === 'save'){
      
	$res = simplexml_load_file('config.xml');
        
	$res->verbose[0] =  $_POST['verbose'] ;                  
	$res->targetDir[0] =   $_POST['targetDir'];
	$res->sourceDir[0] = $_POST['sourceDir'];
	$res->localDeploymentDir[0] =  $_POST['localDeploymentDir'];
	$res->remoteDeploymentDir[0] =  $_POST['remoteDeploymentDir'];

        $res->transports->tranport['name'] = $_POST['tr_name_1'];
		$res->transports->tranport[0]->type[0] = $_POST['tr_type_1'];
		$res->transports->tranport[0]->login[0]  =  $_POST['login_1'];
		$res->transports->tranport[0]->password[0]  =  $_POST['pass_1'];
		$res->transports->tranport[0]->host[0]  =  $_POST['host_1'];
		$res->transports->tranport[0]->port[0]  = $_POST['port_1'];

				$res->transports->tranport[0]->fingerprints->fingerprint = $_POST['fingerprint'];

              $res->transports->tranport[0]->pubKeyPath = $_POST['pubKeyPath'];
			  $res->transports->tranport[0]->privKeyPath = $_POST['privKeyPath'];

        $res->transports->tranport[1]['name'] = $_POST['tr_name_2'];
		$res->transports->tranport[1]->type[0] = $_POST['tr_type_2'];
		$res->transports->tranport[1]->login[0]  = $_POST['login_2'];
        $res->transports->tranport[1]->password[0]  =  $_POST['pass_2'];
		$res->transports->tranport[1]->host[0]   = $_POST['host_2'];
		$res->transports->tranport[1]->port[0] = $_POST['port_2'];

 $res->items->item[0] = $_POST['item_1'];
 $res->items->item[1] = $_POST['item_2'];
 $res->items->item[2] = $_POST['item_3'];
 $res->items->item[3] = $_POST['item_4'];
 $res->items->item[4] = $_POST['item_5'];
 
 $res->saveXML('config.xml');
 
 }
 if($res->saveXML('config.xml') == true ){
 header( 'Location: http://' ) ;              // insert foms.php absolute path
 } 


?>