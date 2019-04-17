<?php
	/*
		Filename: admin.php
		Usage: admin.php?
		
		Created by: Craig Sheppard
		Date Created: 12/24/2018
		Date Modified: 12/24/2018
		
		Log:
		> Pending functions. Interface in design mode.
		> Added "execInBackground($cmd)".
		> 
	*/
	
	//Get input.
	$action = $_GET['action'];
	
	//**Execute action.
	execAction($action);
	
	function execAction($submitted_action) {
		$run_status = 0;
		$actionComplete = false;
		$processMessage = "Nothing to report!";
		
		if (!ValidAction($submitted_action)) {
			//**Invalid action found!
			
			//Log action.
			logAction(null,$submitted_action,null);
			$processMessage = 'Invalid action found!';
		} else {
			$log_filename = "WakeMeOnLan.xml";
			$clearcache_filename = "del /Q /F WakeMeOnLan.xml";
			$scannetwork_filename = "WakeMeOnLan.exe /scan /sxml " . $log_filename;
			
			//**Valid action found! Execute submitted action.
			switch ($submitted_action) {
				case "ClearCache":
					//**Does the file exist?
					if (file_exists($log_filename)) {
						//**Run file "clearcache.bat".
						$run_status = execInBackground($clearcache_filename);
						
						//**Check status (1,2 = Complete; 0 = Failed)
						if ($run_status == 1 || $run_status == 2) {
							$actionComplete = true;
							$processMessage = 'Command complete! File "' . $log_filename . '" being removed!';
						} else if ($run_status == 0) {
							//**Error occured trying to run file.
							$processMessage = 'Unable to run file "' . $clearcache_filename . '"! Please consult your administrator.';
						} else {
							//**Unknown error occured trying to run file!
							$processMessage = 'Unknown error happened running file "' . $clearcache_filename . '"! Please consult your administrator.';
						}
					} else {
						$actionComplete = true;
						$processMessage = 'Command complete! File "' . $log_filename . '" was removed previously!';
					}
					
					echo $processMessage;
					break;
				case "ScanNetwork":
					//**Run file "clearcache.bat".
					$run_status = execInBackground($scannetwork_filename);
					
					//**Check status (1,2 = Complete; 0 = Failed)
					if ($run_status == 1 || $run_status == 2) {
						$processMessage = 'Command complete! System is now generating the file.';
					} else if ($run_status == 0) {
						//**Error occured trying to run file.
						$processMessage = 'Unable to run file "' . $clearcache_filename . '"! Please consult your administrator.';
					} else {
						//**Unknown error occured trying to run file!
						$processMessage = 'Unknown error happened running file "' . $clearcache_filename . '"! Please consult your administrator.';
					}
					
					echo $processMessage;
					break;
				case "LoadLogFile":
					try {
						echo implode("\n<br>", loadXMLData($log_filename));
					} catch (Exception $e) {
						$processMessage = 'File in use! Please retry in a few seconds. ('.$e.')';
					}
					break;
				case "Convert":
					
					echo $processMessage;
					break;
			}
		}
	}
	
	function loadXMLData($filename) {
		$status = false;
		
		if (file_exists($filename)) {
			/*
				Item Sample
				===========
				<ip_address>192.168.2.22</ip_address>
				<computer_name></computer_name>
				<mac_address>18-B4-30-CB-FA-22</mac_address>
				<network_adapter_company>Nest Labs Inc.</network_adapter_company>
				<user_text></user_text>
				<status>On</status>
				<workgroup></workgroup>
				<broadcast_address></broadcast_address>
				<port_number></port_number>
				<multiple_packets></multiple_packets>
				<index>3</index>
				=====================================
			*/
			
			$xml_import  = simplexml_load_file($filename);
			
			if ($xml_import instanceof SimpleXMLElement) {
				//**Link up to DOM object and setup.
				$xml_data = new DOMDocument ('1.0', 'utf-8');
				$xml_data->preserveWhiteSpace = false;
				$xml_data->formatOutput = true;
				
				//**Load XML data as version 1.0.
				$xml_data->loadXML( $xml_import->asXML() );
				
				//**Verify if valid.
				if (!$xml_data) {
					$status = '<b>Invalid XML file!</b>';
				} else {
					//**Import data.
					$dataset = simplexml_import_dom($xml_data);
					//**Generate entries for javascript.
					$js_entries = createJavascriptEntries($dataset);
					//**Validate.
					if (!$js_entries) {
						$status = "connection_WakeUpCls.addpc('" . $dataset->item[$i]->computer_name . "', '" . $dataset->item[$i]->mac_address . "', '" . $dataset->item[$i]->ip_address . "');";
					} else {
						//**Operation complete!
						$status = $js_entries;
					}
				}
			} else {
				$status = '<b>Invalid XML file!</b>';
			}
		} else {
			$status = '<b>XML file missing!</b>';
		}
		
		return $status;
	}
	
	function createJavascriptEntries($data) {
		$entries_status = false;
		
		$items_computername = "";
		$items_mac_address = "";
		$items_ip_address = "";
		
		$unknowndevice_count = 1;
		
		//NEW! Array, ensure empty.
		$js_mac_entries = array();
		
		for ( $i = count($data) - 1; $i >= 0; $i-- ) {
			//Sample: connection_WakeUpCls.addpc('DESKTOP-Marcus','70-85-C2-23-38-43','192.168.2.76');
			
			/*
				Dataset sample:
				<ip_address>192.168.2.22</ip_address>
				<computer_name></computer_name>
				<mac_address>18-B4-30-CB-FA-22</mac_address>
				
				$data->item[$i]->computer_name
				$data->item[$i]->mac_address
				$data->item[$i]->ip_address
			*/
			
			$items_computername = $data->item[$i]->computer_name;
			$items_mac_address = $data->item[$i]->mac_address;
			$items_ip_address = $data->item[$i]->ip_address;
			
			//**PC Name NULL Check.
			if (is_null($items_computername) || $items_computername == "") {
				if ($unknowndevice_count < 10) {
					//**Correction.. if between 0-9 need to add a 0 in front of it.
					$items_computername = "Unknown Device 0" . $unknowndevice_count;
				} else {
					$items_computername = "Unknown Device " . $unknowndevice_count;
				}
				
				//**Increase Count.
				$unknowndevice_count++;
			}
			
			$new = "connection_WakeUpCls.addpc('" . $items_computername . "', '" . $items_mac_address . "', '" . $items_ip_address . "');";
			array_push( $js_mac_entries, $new );
		}
		
		//**Validation.
		if (count($js_mac_entries) > 0) {
			//**Sort array alphabetically.
			sort($js_mac_entries);
			//**Return entries.
			return $js_mac_entries;
		} else {
			return $entries_status;
		}
	}
	
	function ValidAction($value) {
		$valid_action = null;
		
		if ($value == "ClearCache" || $value == "ScanNetwork" || $value == "LoadLogFile" || $value == "Convert") {
			$valid_action = true;
		} else {
			$valid_action = false;
		}
		
		return $valid_action;
	}
	
	function execInBackground($cmd) { 
		$status = 0;
		
		if (substr(php_uname(), 0, 7) == "Windows"){ 
			pclose(popen("start /B ". $cmd, "r"));
			$status = 1;
		} 
		else { 
			exec($cmd . " > /dev/null &");
			$status = 2;
		}
		
		return $status;
	}
	
	function isNull($value) {
		if (!$value == null) {
			return false;
		} else {
			return true;
		}
	}
?>