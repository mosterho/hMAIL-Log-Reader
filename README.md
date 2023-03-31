# hMAIL Log Reader

This is a very simple PHP script will read ALL of the hmail logs that contain TCPIP data
and then summarize the external IP addresses (first three octets) with the most TCPIP hits.
There is nothing fancy about the output.
(Log file names go by "hmailserver_2000-12-31.log")

## psuedo code:
* Setup variables, print basic header info, accept argument (nbr of entries to print)
* Call function that reads directory share to determine which files to read. This in turn calls a function that summarizes the data for each IP read in the logs.
* Sort and print data up to the numkber of entries requested in the URL argument.

## Requirements
This was originally created in a home lab.
* hMAIL is running on a Windows 2019 server.
* The PHP code is running on a Ubuntu 20.04.6 LTS that has Apache and PHP installed.

## How to call the URL
Depending on how and where the web server is installed (IIS, Apache), and any specific ports used, the call to this program is:

http://127.0.0.1:8099/index.php?arg_entries=10

The "?arg_entries" argument will display the top most number of entries; in this case, 10.

![screen cap of top 10 external IP addresses summarized from logs](Example1.PNG)
