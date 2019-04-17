<?php
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
?>

<html>
	<head>
		<title>SubC Imaging - Remoting into the network</title>
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		<link rel="stylesheet" href="./bin/css/index.css">
		
		<script>
			//**Setup Global's
			/*
				Common values:
				> localhost
			*/
			var ServerIP_Address = "subc.dyndns.org";
			var ServerIP_Port = "9090";
			
			//**Create connection to class.
			var connection_WakeUpCls = new WakeUpCls("connection_WakeUpCls");
			
			//**Setup name of select element.
			connection_WakeUpCls.selectObject = "cboAvailableSystems";
			connection_WakeUpCls.iframeObject = "iframeSubmission";
			
			function Startup()
			{
				//Populate available system select.
				BuildListOfSystems();
			}
			
			function BuildListOfSystems()
			{
				<?php
					echo implode("\n", loadXMLData('WakeMeOnLan.xml'));
				?>
			}
			
			function WakeSystem()
			{
				//Debug.. 1=on; 0=off;
				var debug = 0;
				
				//*Build URL and test for errors.
				var WakeURL = BuildURL(document.getElementById(connection_WakeUpCls.selectObject).value,document.getElementById(connection_WakeUpCls.selectObject).text,debug);
				
				if (WakeURL != false) {
					//**Submit selected MAC address to the php script.
					document.getElementById(connection_WakeUpCls.iframeObject).src = WakeURL;
					//window.open(WakeURL);
				}
			}
			
			function BuildURL(mac,text,debug)
			{
				if (connection_WakeUpCls.isNull(mac)) {
					alert('Failed to wake up system. MAC address missing!');
					return false;
				} else {
					var baseURL = "http://" + ServerIP_Address;
					var portNum = ":" + ServerIP_Port;
					var filename = "/wakeme/wakeme.php?";
					var macInput = "macaddr=" + ValidateMacAddr(mac);
					//var ownerInput = "owner="+text;
					var GeneratedURL = baseURL + portNum + filename + macInput;
					//var GeneratedURL = baseURL + portNum + filename + macInput + ownerInput;
					
					if (!connection_WakeUpCls.isNull(debug)) alert(GeneratedURL);
					return GeneratedURL;
				}
			}
			
			function ValidateMacAddr(mac) {
				if (mac.length == 12) {
					//**Valid input.. no more work to be done.
				} else {
					//**Something is wrong! More checking needed.
					if (mac.length < 12) {
						//**Not a valid mac address, return value invalid.
						mac = "invalid";
					} else if (mac.length > 12) {
						var new_mac = null;
						
						/*
							Mac address could be including "-"'s or ":"'s.
							Need to remove them!
						*/
						
						//**Checking if they are "-"'s.
						var replacement_character = mac.substring(2,3);
						
						if (replacement_character == "-") {
							new_mac = mac.replace(/-/g, "");
							mac = new_mac;
						} else if (replacement_character == ":") {
							new_mac = mac.replace(/:/g, "");
							mac = new_mac;
						} else {
							//**Not a valid mac address, return value invalid.
							mac = "invalid";
						}
						
						//*Clean-up.
						replacement_character = null;
					}
				}
				
				return mac;
			}
			
			//Wake-up Class.
			function WakeUpCls (name)
			{
				//Form elements.
				this.selectObject = null;
				this.iframeSubmission = null;
				
				//**addpc usage variables.
				this.selectElement = null;
				this.selectElement_option = null;
				
				//Add pc.
				this.addpc = function(computername,mac,ip)
				{
					//if (this.isNull(mac) || this.isNull(ip)) {
					if (this.isNull(mac)) {
						alert('Unable to add entry! Missing IP and/or MAC address');
						return false;
					} else {
						//**Variable Reset
						this.selectElement = null;
						this.selectElement_option = null;
						
						//**Output to object.
						this.selectElement = document.getElementById(this.selectObject);
						this.selectElement_option = document.createElement("option");
						
						//**Optional if ip was needed.
						/*
						if (this.isNull(ip)) {
							this.selectElement_option.value = mac + '~na';
						} else {
							this.selectElement_option.value = mac + '~' + ip;
						}
						*/
						
						this.selectElement_option.value = mac;
						this.selectElement_option.text = computername + ' (' + mac + ')';
						
						this.selectElement.add(this.selectElement_option);
						return true;
					}
				}
				
				this.isNull = function(value)
				{
					if (value == null || value == "")
					{
						return true;
					} else {
						return false;
					}
				}
			}
		</script>
	</head>
	
	<body onLoad="Startup()" class="body">
		<table cellpadding="0" cellspacing="2" border="0" class="tables" width="100%">
			<tr>
				<td>
					<!-- Heading -->
					<div class="">
						<table cellpadding="0" cellspacing="0" border="0" class="tables" width="100%">
							<tr>
								<td><img src="./bin/images/subc_logo.jpg" alt="SubC Imaging Logo"></td>
							</tr>
							<tr class="title common-values">
								<td>Remoting into the network</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
			
			<tr>
				<td>
					<!-- Setup/Instructions -->
					<div class="divs rouunded_corners">
						<table cellpadding="1" cellspacing="0" border="0" class="tables" width="100%">
							<tr class="headings common-values">
								<td>Setting up your PC</td>
							</tr>
							<tr class="sub-headings common-values instructions-step">
								<td>Windows 10</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>With Wake-On-LAN (or WoL or WoWLAN for Wake-On-Wireless LAN), you can turn on your Windows 10 computer remotely as long as it connected to a local area network via cable or a wireless network. However, before you can turn on your PC over the network, WoL or WoWLAN must be enabled, and all the following prerequisites must be met:</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<ol>
										<li>The hardware of your PC must support Wake-On-LAN.</li>
										<li>Wake-On-LAN must be enabled in the BIOS.</li>
										<li>The PC must be configured to accept and respond to Magic Packets.</li>
									</ol>
								</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>Go to the <b>Power Management</b> tab, check the <b>Allow this device to wake the computer</b> and <b>Only allow a magic packet to wake the computer</b> boxes, and click <b>OK</b>. This configures your PC to wake up remotely through another computer via local area network (LAN).</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td align="center"><img src="./bin/images/nic-properties_0.jpg" alt="Win10-Advanced Tab"><img src="./bin/images/nic-properties_1.jpg" alt="Win10-Power Management Tab"><br/><img src="./bin/images/nic-properties_2.jpg" alt="Win10-Power Management Tab 2"><img src="./bin/images/nic-properties_3.jpg" alt="Win10-Power Management Tab 3"></td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>Note: In some instances, you may also need to turn off Fast Startup for Wake-On-LAN to work on your Windows 10 PC. To turn off Fast Startup, you can right-click Start, click Power Options, click Additional power settings from the Related settings section in the right, click Choose what the power buttons do from the left pane of the Power Options window, click Change settings that are current unavailable from the System Settings window, and uncheck the Turn on fast startup (recommended) box from the Shutdown settings section.</p></td>
							</tr>

							<tr class="sub-headings common-values instructions-step">
								<td><br>macOS Sierra</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>You can set your Mac to go to sleep after a specified duration of inactivity. You can also set your Mac to go to sleep and wake up at a specific time.</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>Specify sleep and wake settings for a desktop Mac</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<p>
										<ol>
											<li>Choose <b>Apple menu</b> > <b>System Preferences</b>, then <b>click Energy Saver</b>.</li>
											<li>
												Do any of the following:
												<ol>Set the amount of time your computer or display should wait before going to sleep: Drag the “Computer sleep” and “Display sleep” sliders, or the “Turn display off after” slider. (30 minutes)</ol>
												<ol>Keep your Mac from going to sleep automatically: Select “Prevent computer from sleeping automatically when the display is off.”</ol>
												<ol>Put hard disks to sleep: Select “Put hard disks to sleep when possible.”</ol>
												<ol>Keep your Mac turned on whenever power is available: Select “Start up automatically after a power failure.”</ol>
												<ol>Allow your Mac to wake briefly so users can access shared services (if applicable): Select any of the available “Wake for…” options, for example, “Wake for network access.”</ol>
											</li>
										</ol>
									</p>
								</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>More information can be found online @<a href="https://support.apple.com/kb/PH25222?locale=en_US" target="_blank">https://support.apple.com/kb/PH25222?locale=en_US</a>.</p></td>
							</tr>
							
							<!--
							<tr class="headings common-values">
								<td>Setting up your VPN</td>
							</tr>
							<tr class="sub-headings common-values instructions-step">
								<td>Step 1: Creating a connection to the work network using a VPN</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>Since we are trying to save power and in an effect to do some cost savings you will need to wake up you computer before you can get access to a from or remote location. Therefore, we need to create a tunnel to the office network to be able to wake up the required computer(s).</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td>&nbsp;</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p><b>*Please note once your computer is awake, you do not need the VPN to use a remote desktop solution but the VPN will offer options of getting direct connection to any network resources you may need from the office remotely.</b></p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td>&nbsp;</td>
							</tr>
							<tr class="sub-headings common-values">
								<td><p>Setting up a VPN can be done on several operating systems and devices. On this page we will be covering Microsoft Windows 7 and 10. For information on how to setup on a mac please click <a href="https://support.apple.com/en-ca/guide/mac-help/set-up-a-vpn-connection-on-mac-mchlp2963/10.14/mac/10.14" target="_blank">here</a>. The first step is to know your login information.</p></td>
							</tr>
							<tr class="sub-headings common-values">
								<td>&nbsp;</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<table cellpadding="0" cellspacing="0" border="0" class="instructions-table common-values" width="70%" align="center">
										<tr>
											<td align="right"><code>Internet address:</code></td>
											<td><code>subc.dyndns.org</code></td>
										</tr>
										<tr>
											<td align="right"><code>Username:</code></td>
											<td><code>SubC-(firstname) [example: SubC-Bob]</code></td>
										</tr>
										<tr>
											<td align="right"><code>Password:</code></td>
											<td><code>(Default is "Rayfin2018)</code></td>
										</tr>
										<tr>
											<td align="right"><code>Domain:</code></td>
											<td><code>blank</code></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr class="sub-headings common-values">
								<td colspan="2">&nbsp;</td>
							</tr>
							<tr class="common-values">
								<td colspan="2"><code>**If you are having login issues, please email me, <a href="mailto:craig.sheppard@subccontrol.com" target="blank">craig.sheppard@subccontrol.com</a>.</code></td>
							</tr>
							
							<tr class="sub-headings common-values">
								<td>&nbsp;</td>
							</tr>
							
							<tr class="sub-headings common-values instructions-step">
								<td>Microsoft Windows 7</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<ol>
										<li>Goto Start > Control Panel > Network and Internet > Network and Sharing Center</li>
										<li>Click on "Set up a new connection or network"</li>
										<li>Choose "Connect to a workplace" and click Next</li>
										<li>Choose "Use my Internet connection (VPN)</li>
										<li>Enter the "Internet address" (please refer to the above section for information)</li>
										<li>Does not matter what "Destination name" is but for example you can call it "SubC Connection"</li>
										<li>Click "Next"</li>
										<li>Type your "User name"</li>
										<li>Type your "Password"</li>
										<li>Click "Connect"</li>
									</ol>
								</td>
							</tr>
							<tr class="sub-headings common-values instructions-step">
								<td>Microsoft Windows 10</td>
							</tr>
							
							<tr class="sub-headings common-values">
								<td>
									<ol>
										<li>Goto Start > Type "Settings" and press enter</li>
										<li>Click on "Network & Internet"</li>
										<li>Click on "VPN"</li>
										<li>Click "Add a VPN Connection" and enter connection details.<br/><img src="./bin/images/win10_VPNCS.jpg" alt="Win10-Add a VPN Connection"></li>
										<li>Click "Save"</li>
										<li>After clicking save, click on the new connection you just made. If you followed the example, you will see something like the below.<br/><img src="./bin/images/win10_VPN_ConnShortcut.jpg" alt="Win10-VPN Connect Shortcut"></li>
										<li>Once the option is clicked you will see the below screen. Click the "Connect" button.<br/><img src="./bin/images/win10_VPN_ConnButton.jpg" alt="Win10-VPN Connect Button."></li>
										<li>The system will now connect and register you on the network.</li>
									</ol>
								</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<table cellpadding="2" cellspacing="0" border="0" class="tables" width="50%">
										<tr class="sub-headings common-values">
											<td><b>Note:</b> If you did not enter the user name or password on creating the VPN connection, windows will prompt you to enter it before completing the connection using a screen like the one below.</td>
										</tr>
										<tr>
											<td><img src="./bin/images/win10_VPN_LoginScreen.jpg" alt="Win10-Login Screen"></td>
										</tr>
									</table>
								</td>
							</tr>
							
							<tr class="sub-headings common-values">
								<td colspan="2">&nbsp;</td>
							</tr>
							!-->
							
							<!--
							<tr class="sub-headings common-values">
								<td>
									<ol>
										<li>Goto Start > Type "Control Panel" and press enter</li>
										<li>Click on "Network and Internet" > "Network and Sharing Center"</li>
										<li>Click on "Set up a new connection or network"</li>
										<li>Choose "Connect to a workplace" and click Next</li>
										<li>Choose "Use my Internet connection (VPN)</li>
										<li>Enter the "Internet address" (please refer to the above section for information)</li>
										<li>Does not matter what "Destination name" is but for example you can call it "SubC Connection"</li>
										<li>Click "Create"</li>
										<li>Your are now back to the "Network and Sharing Center" and need to click on the left side, "Change adapter settings"</li>
										<li>In here you are looking for your connection you just made. It will be formatted to show name of connection, status and type. The type we are looking for is "WAN Miniport (IKEv2)".</li>
										<li>Right click on it and goto "Properties"</li>
										<li>Click on the "Security" tab</li>
										<li>Change "Data encryption" to "Require encryption (disconnect if server declines)</li>
										<li>Check the box "Allow these protocols"</li>
										<li>Ensure boxes "Challenge Handshake Authentication Protocol (CHAP)" and "Microsoft CHAP Version 2 (MS-CHAPv2)" are checked</li>
										<li>Click "Ok"</li>
										<li>The system will provide a warning, click OK</li>
										<li>Close the Network Connecrions window</li>
										<li>Close the Network and Sharing Center window</li>
										<li>Goto Start > Type "Settings" and press enter</li>
										<li>Click on "Network & Internet"</li>
										<li>Click on "VPN"</li>
										<li>Click on the VPN connection you made</li>
										<li>Click "Connect"</li>
										<li>Now should be prompted for User name and Password</li>
										<li>Type your "User name"</li>
										<li>Type your "Password"</li>
										<li>Click "Ok"</li>
									</ol>
								</td>
							</tr>
							!-->
							
							<tr class="headings common-values">
								<td>Using this page</td>
							</tr>
							<tr class="sub-headings common-values instructions-step">
								<td>Step 1: Waking up your computer</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>In the below section "Waking up you computer", choose the computer matching your name and click "Wake Me"</td>
							</tr>
							
							<tr class="sub-headings common-values">
								<td>&nbsp;</td>
							</tr>
							
							<tr class="sub-headings common-values instructions-step">
								<td>Step 2: Remoting into your system</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>You can now use any software you would like, to remote into your computer. Some examples include:</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>
									<ul>
										<li><a href="https://www.teamviewer.com/en/download/old-versions.aspx#version8" target="blank">Teamviewer</a> (Teamviewer 8 is preferred)</li>
										<li><a href="https://chrome.google.com/webstore/detail/chrome-remote-desktop/gbchcmhmhahfdphkhkmpfmihenigjmpp?hl=en" target="blank">Chrome Remote Desktop</a></li>
										<li>
											<div class="tooltip">
												Windows Remote Desktop Connection
												<!-- <span class="tooltiptext">Remote Desktop does not have any built-in remote wake-up capability.</span> -->
											</div>
											(Click <a href="https://support.microsoft.com/en-ca/help/4028379/windows-10-how-to-use-remote-desktop" target="_blank">here</a> to instructions on usage.)
										</li>
									</ul>
								</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
			
			<tr>
				<td>
					<!-- Waking up you computer -->
					<div class="divs rouunded_corners">
						<table cellpadding="0" cellspacing="0" border="0" class="tables" width="100%">
							<tr class="headings common-values">
								<td>Waking up you computer</td>
							</tr>
							<tr class="sub-headings common-values">
								<td>Choose you name from the list to wake up your pc.</td>
							</tr>
							<tr class="sub-sections common-values">
								<td>
									<select id="cboAvailableSystems" name="cboAvailableSystems" class="selectObjectStyle" size="5"></select>
								</td>
							</tr>
							<tr class="sub-sections common-values">
								<td>
									<button id="" name="" type="button" onclick="WakeSystem();" class="buttonOjectStyle">Wake Me</button>
								</td>
							</tr>
							
							<tr class="sub-sections common-values">
								<td><iframe id="iframeSubmission" name="iframeSubmission" frameborder="0" src="" scrolling="no" class="iframeObjectStyle sub-headings"></iframe></td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
			
			<tr class="footer common-values">
				<td>&copy;2018 SubC Imaging<br/>Created by Craig Sheppard</td>
			</tr>
		</table>
	</body>
</html>