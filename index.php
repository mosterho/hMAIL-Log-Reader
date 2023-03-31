<?php
/*
###########################################################################
### hMAIL log reader
### This script will read ALL of the hmail logs that contain TCPIP data
### and summarize the external IP addresses with the most TCPIP hits
### (Log file names go by "hmailserver_2000-12-31.log")
###
### psuedo code:
### 1. Setup variables, print basic header info, accept argument (nbr of entries to print)
### 2. Call function that reads directory share to determine which files to read.
###    This in turn calls a function that summarizes the data for each IP read in the logs.
### 3. Sort and print data up to the numkber of entries requested in the URL argument.
*/

### 1. setup array for data, acept argument, print header info
$array_data = array();
$argentry = $_GET['arg_entries']; # Specify the number of IPs to print
echo '<p> ';
echo '<br>hMAIL log reader program...<br>';
echo '<br>Number of IPs to print?: '.$argentry;

### 2. call function that reads the directory entries. (The path is a predetermined Share)
### the function call will return the array of data that includes an IP address and the number of
### times it was found in the logs.
$array_data = fct_readdir();
### sort and print the array data, but only up to the number of entries requested by the URL argument.
arsort($array_data);
$idx = 0;
foreach($array_data as $IPdata => $counter){
  echo '<br>IP: '.$IPdata.' Counter: '.$counter;
  $idx++;
  if($idx >= $argentry){
    break;
  }
}

### Function to read the directory share to obtain the log files to read.
### As each log file is determined, call another function to summarize
### each IP address that is encountered.
function fct_readdir(){
  $systemname = 'ftp://10.126.26.43/';
  $dirlist = scandir($systemname,1);
  foreach($dirlist as $direntry){
    preg_match_all('/hmailserver_\d{4}-\d{2}-\d{2}.log/',$direntry, $regexresult);
    foreach($regexresult as $indfile){
      if($indfile[0] != ''){
        $parm_file = $systemname.$indfile[0];
        fct_readfile($parm_file, $array_data);  # Use argument by reference for the array
      }
    }
  }
  return $array_data;
}

### Function to update the "global" data array (argument is by reference)
function fct_readfile($arg_file_input, &$array_data){
  $LANips = '10.126.26.';
  $myfile = fopen($arg_file_input, "r") or die("Unable to open file!");
  while(!feof($myfile)){
    $thisline = fgets($myfile);
    if(substr($thisline,1,5) == 'TCPIP'){
      // Find the date and time (including microseconds)
      preg_match_all('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{3}/', $thisline, $arrayresult1);
      $datein = $arrayresult1[0][0];
      // Find the "key"; this section of the string contains "TCP - ###.###.###.### connected". do not use the other
      // strings/formats within other "TCPIP" rows
      preg_match_all('/TCP - \d{1,3}.\d{1,3}.\d{1,3}.\d{1,3} connected/', $thisline, $arrayresult2);
      $temp = $arrayresult2[0][0];
      // If the section of string is found, narrow it down to IP address three octets
      if($temp != '')
      preg_match_all('/\d{1,3}.\d{1,3}.\d{1,3}./', $temp, $arrayresult3);
      else {
        $arrayresult3[0][0] = '';
      }
      $IPin = $arrayresult3[0][0];
      // If there is a valid IP (not blank and not part of the LAN address space), include the row
      if($IPin != $LANips and $IPin != ''){
        $tmpcounter = $array_data[$IPin];
        $tmpcounter++;
        $array_data[$IPin] = $tmpcounter;
      }
    }
  }
  fclose($myfile);
}

?>
