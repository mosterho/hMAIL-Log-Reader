# hMAIL Log Reader

This script will read ALL of the hmail logs that contain TCPIP data
and summarize the external IP addresses with the most TCPIP hits
(Log file names go by "hmailserver_2000-12-31.log")

## psuedo code:
* Setup variables, print basic header info, accept argument (nbr of entries to print)
* Call function that reads directory share to determine which files to read. This in turn calls a function that summarizes the data for each IP read in the logs.
*Sort and print data up to the numkber of entries requested in the URL argument.
