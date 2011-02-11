<?php

    ini_set('default_charset','UTF-8');
	$res = simplexml_load_file('config.xml');   
   
  echo "

<center>
         <table border = '0'><form method ='post' action ='print_conf.php'>
         <tr>
			<td colspan = '2' valign='top' align='middle'> Config </td>
		</tr>
	     <tr>
			<td  width='50%'>verbose</td>
			<td><input name = 'verbose' type = 'text' value ='{$res->verbose[0]}'></td>
		</tr>	
		<tr>
			<td>targetDir</td>
			<td><input name = 'targetDir' type = 'text' value ='{$res->targetDir[0]}'></td>
		</tr>
		<tr>
			<td>sourceDir</td>
			<td><input name = 'sourceDir' type = 'text' value ='{$res->sourceDir[0]}'></td>
		</tr>
         <tr>
			<td>localDeploymentDir</td>
			<td><input name = 'localDeploymentDir' type = 'text' value ='{$res->localDeploymentDir[0]}'></td>
		</tr>
         <tr>
			<td>remoteDeploymentDir</td>
			<td><input name = 'remoteDeploymentDir' type = 'text' value ='{$res->remoteDeploymentDir[0]}'></td>
        </tr>
</table>
            <hr width='50%' align='center'/>
<table border = '0'>
					<tr>
						<td colspan = '2'><center> transport </center></td>
		           </tr>
                    <tr>
						<td>tranport name</td>
						<td><input name = 'tr_name_1' type = 'text' value = '{$res->transports->tranport['name']}'></td>	
					</tr>
				    <tr>
						<td width = '50%'>transport type </td>
						<td><input name = 'tr_type_1' type = 'text' value = '{$res->transports->tranport[0]->type[0]}'></td>	
					</tr>
					 <tr>
						<td>login</td>
						<td><input name = 'login_1' type = 'text' value = '{$res->transports->tranport[0]->login}'></td>
                    </tr>
					 <tr>
						<td>password</td>
						<td><input name = 'pass_1' type = 'password' value = '{$res->transports->tranport[0]->password}'></td>
                    </tr>
                     <tr> 
					    <td>host</td>
						<td><input name = 'host_1' type = 'text' value = '{$res->transports->tranport[0]->host}'></td>
					</tr>
                    <tr>
					    <td>port</td>
						<td><input name = 'port_1' type = 'text' value = '{$res->transports->tranport[0]->port}'></td>
					</tr>
                     <tr>
						<td colspan = '2'>&nbsp;</td>	
                    <tr/> 
					 <tr>
					    <td>Fingerprints</td>
						<td><input name = 'fingerprint' type = 'text' value = '{$res->transports->tranport[0]->fingerprints->fingerprint}'></td>
					</td>
					<tr>
					    <td>PubKeyPath</td>
						<td><input name = 'pubKeyPath' type = 'text' value = '{$res->transports->tranport[0]->pubKeyPath}'></td>
					</td>
                     <tr>
					    <td>PrivKeyPath</td>
						<td><input name = 'privKeyPath' type = 'text' value = '{$res->transports->tranport[0]->privKeyPath}'></td>
					</td>
					 <tr height = '25%'>
						<td colspan = '2'></td>	
                    <tr/>
        </table>
				<hr width='50%' align='center'/>
	<table border = '0'>
					<tr>
						<td colspan = '2'><center> transport 2 </center></td>
		            </tr>
                    <tr>
					    <td width = '50%'>transport name</td>
                        <td><input type name = 'tr_name_2' type = 'text' value = '{$res->transports->tranport[1]['name']}'></td>
					</tr>
					 <tr>
						<td>tranport type_2</td>
						<td><input name = 'tr_type_2' type = 'text' value = '{$res->transports->tranport[1]->type[0]}'></td>	
					</tr>
                     <tr>
						<td>login_2</td>
						<td><input name = 'login_2' type = 'text' value = '{$res->transports->tranport[1]->login[0]}'></td>
                    </tr>
                     <tr>
						<td>password_2</td>
						<td><input name = 'pass_2' type = 'password' value = '{$res->transports->tranport[1]->password[0]}'></td>
                    </tr>
                     <tr> 
					    <td>host_2</td>
						<td><input name = 'host_2' type = 'text' value = '{$res->transports->tranport[1]->host[0]}'></td>
					</tr>
                     <tr>
					    <td>port_2</td>
						<td><input name = 'port_2' type = 'text' value = '{$res->transports->tranport[1]->port[0]}'></td>
					</tr>   
	</table>
			<hr width='50%' align='center'/>
    <table border = '0' align = 'center'>
                    <tr height = '25%'>
						<td colspan = '2' align='middle'>Items</td>	
                    <tr/>
					<tr>
						<td width = '50%'>item 1</td>
						<td><input name ='item_1' type = 'text' value ={$res->items->item[0]}></td>
					</tr>
                     <tr>
                        <td>item 2</td>
						<td><input name ='item_2' type = 'text' value ={$res->items->item[1]}></td>
					</tr>
					 <tr>
						<td>item 3</td>
						<td><input name ='item_3' type = 'text' value ={$res->items->item[2]}></td>
					</tr>
					 <tr>
						<td>item 4</td>
						<td><input name ='item_4' type = 'text' value ={$res->items->item[3]}></td>
					</tr>
					 <tr>
						<td>item 5</td>
						<td><input name ='item_5' type = 'text' value ={$res->items->item[4]}></td>
					</tr>
                    <tr>
						<td colspan='2'><center><input name ='save' type='submit'  value ='save'><center></form>
						</td>
					</tr>  
            </center>
        </form>  
</table> 
<hr width='50%' align='center'/>";
 
?>
















