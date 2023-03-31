# hMAIL Log Reader

This is a very simple PHP script will read ALL of the hmail logs that contain TCPIP data
and then summarize the external IP addresses (first three octets) with the most TCPIP hits.
There is nothing fancy about the output.
(Log file names go by "hmailserver_2000-12-31.log")

## psuedo code:
* Setup variables, print basic header info, accept argument for number of entries to print)
* Call function that reads directory share to determine which files to read.
  This in turn calls a function that summarizes the data for each IP read in the logs.
  The rows analyzed and summarized are "TCPIP" entries
* Sort and print data up to the number of entries requested in the URL argument.

## Requirements/Infrastructure
This was created in a home lab and is not part of a corporate IT department.
* hMAIL is running on a Windows 2019 server.
  Logging must be enabled for TCPIP transactions.
* The PHP code is running on a Ubuntu 20.04.6 LTS that has Apache and PHP installed.

## Customization for this script needed on your part
* Create a share that the PHP code can read using FTP
  Since this PHP script is access internally, I created the share as "anonymous".
* The first line of the fct_readdir function contains the directory that houses the log files.
* The first line of the fct_readfile function contains your internal LAN IPs (three octets) that should be ignored.

## How to call the URL
Depending on how and where the web server is installed (IIS, Apache), and any specific ports used, the call to this program is:

http://127.0.0.1:8099/index.php?arg_entries=10

The "?arg_entries" argument will display the top most number of entries; in this case, 10.

![screen cap of top 10 external IP addresses summarized from logs](Example1.PNG)
