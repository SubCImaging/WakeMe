<?php
	/*
		Filename: wakeme.php
		Usage: wakeme.php?macaddr=<mac>
		
		Created by: Craig Sheppard
		Date Created: 12/18/2018
		Date Modified: 12/24/2018
		
		Log:
		> Created a tested main functions for waking up systems
		on network.
		> Testing complete.. status working!
		> Added function ValidateMAC ($mac) to ensure only
		command is run on a proper mac address.
	*/
	
	//Get input.
	$macaddr = $_GET['macaddr'];
	
	//Send command.
	if (!ValidateMAC($macaddr)) $macaddr = "invalid";
	WakeMe($macaddr);
	
	function ValidateMAC ($mac) {
		$validEntry = false;
		
		if (strlen($mac) == 12) {
			$validEntry = true;
		} else if (strlen($mac) > 12 || strlen($mac) < 12) {
			$validEntry = false;
		}
		
		return $validEntry;
	}
	
	function WakeMe($macaddr) {
		if ($macaddr == "invalid") {
			$processMessage = 'Unknown error occured! Please consult your administrator.';
		} else {
			/*
				Usage Examples:
				WakeMeOnLan.exe /wakeup 192.168.1.25 
				WakeMeOnLan.exe /wakeup Comp01 
				WakeMeOnLan.exe /wakeup Comp02 
				WakeMeOnLan.exe /wakeup 40-65-81-A7-16-23 
				WakeMeOnLan.exe /wakeup 406581A71623 
				WakeMeOnLan.exe /wakeup Comp02 30000 192.168.0.255 
				WakeMeOnLan.exe /wakeup 192.168.1.25 20000 192.168.1.255 
			*/
			
			$processStatus = execInBackground('WakeMeOnLan.exe /wakeup ' . $macaddr);
			
			if ($processStatus == 1 || $processStatus == 2) {
				$processMessage = 'Command complete! System is turning on machine with MAC address ' . $macaddr .'.';
			} else if ($processStatus == 0) {
				$processMessage = 'Command failed! Please consult your administrator.';
			} else {
				$processMessage = 'Unknown error occured!';
			}
		}
		
		echo '<h2>' . $processMessage . '</h2>';
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
?>