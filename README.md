## SubC WakeMe
WakeMe is a platform to that uses the utility WakeMeOnLan.exe to generarte a local copy of machine on the network and allow remote users to wake one or more of those personal computers. You can wake your Microosoft Windows, Mac or any device on the network that allows for the magic packet to be send to it's network card.

## WakeMeOnLan.exe (https://www.nirsoft.net/utils/wake_on_lan.html)
This utility allows you to easily turn on one or more computers remotely by sending Wake-on-LAN (WOL) packet to the remote computers. 
When your computers are turned on, WakeMeOnLan allows you to scan your network, and collect the MAC addresses of all your computers, and save the computers list into a file. Later, when your computers are turned off or in standby mode, you can use the stored computers list to easily choose the computer you want to turn on, and then turn on all these computers with a single click. 
WakeMeOnLan also allows you to turn on a computer from command-line, by specifying the computer name, IP address, or the MAC address of the remote network card.

## Getting started
With Wake-On-LAN (or WoL or WoWLAN for Wake-On-Wireless LAN), you can turn on your Windows 10 computer remotely as long as it connected to a local area network via cable or a wireless network. However, before you can turn on your PC over the network, WoL or WoWLAN must be enabled, and all the following prerequisites must be met:
1. The hardware of your PC must support Wake-On-LAN.
2. Wake-On-LAN must be enabled in the BIOS.
3. The PC must be configured to accept and respond to Magic Packets.

Once you know the target pc is ready, you can choose the pc from the list and click the "Wake Me" button. The system will send the command and display a message weather the command could be completed. Please note if you run this from within the same network using the website it will only work if you are able to get reverse loopback working. It needs in the configuration of the Javascript a source ip and port number to use when it builds the url to execute the command.

Please visit subc.dyndns.org:9090/wakeme for more instructions.

##
Programmed by: Craig Sheppard
2018 Subc Imaging