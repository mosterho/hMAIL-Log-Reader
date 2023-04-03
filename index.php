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

#######################################################################
### Define class object and functions
#######################################################################
class cls_logdata {
  public $array_data;
  public $app_path = '';
  public $wrk_whitelist ;
  public $wrk_blacklist ;
  public int $wrk_nbr_of_files_read = 0;

  function __construct() {
    ### Read the JSON file for application variables
    ### file_get_contents loads the entire file into a string variable
    $jsonstuff = file_get_contents("logreaderapp.json");
    $JSONdata = json_decode($jsonstuff, True);  #"True will generate an associative array from JSON data
    $this->app_path       = $JSONdata['path'];
    $this->wrk_whitelist  = $JSONdata['whitelist'];
    $this->wrk_blacklist  = $JSONdata['blacklist'];
  }

  ### Function to read the directory share to obtain the log files to read.
  ### As each log file is determined, call another function to summarize
  ### each IP address that is encountered.
  function fct_readdir($argentryfiles){
    #$systemname = 'ftp://10.126.26.43/';  MOD: remove hard coded path
    $systemname = $this->app_path;
    $dirlist = scandir($systemname,1);
    $idx_files = 0;
    foreach($dirlist as $direntry){
      preg_match_all('/hmailserver_\d{4}-\d{2}-\d{2}.log/',$direntry, $regexresult);
      foreach($regexresult as $indfile){
        if($indfile[0] != ''){
          $parm_file = $systemname.$indfile[0];
          $this->fct_readfile($parm_file);
          $idx_files++;
          $this->wrk_nbr_of_files_read++;
        }
        if($idx_files >= $argentryfiles){
          break 2;  # Use break 2 to get out of both FOREACH loops
        }
      }
    }
    #return this->$array_data;
  }

  ### Function to update the "global" data array (argument is by reference)
  function fct_readfile($arg_file_input){
    # Users must change this hard coding to accomdate NOT reading their local LAN IPs.
    $LANips = '10.126.26.';
    $myfile = fopen($arg_file_input, "r") or die("Unable to open file!");
    while(!feof($myfile)){
      $thisline = fgets($myfile);
      if(substr($thisline,1,5) == 'TCPIP'){
        // Find the date and time (including microseconds) CURRENTLY NOT USED!!!!!
        preg_match_all('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{3}/', $thisline, $arrayresult1);
        $datein = $arrayresult1[0][0];
        // Find the "key"; this section of the string contains "TCP - ###.###.###.### connected". do not use the other
        // strings/formats within other "TCPIP" rows
        preg_match_all('/TCP - \d{1,3}.\d{1,3}.\d{1,3}.\d{1,3} connected/', $thisline, $arrayresult2);
        $temp = $arrayresult2[0][0];
        // If the section of string is found, narrow it down to IP address three octets
        if($temp != ''){
          preg_match_all('/\d{1,3}.\d{1,3}.\d{1,3}./', $temp, $arrayresult3);
        }
        else {
          $arrayresult3[0][0] = '';
        }
        $IPin = $arrayresult3[0][0];
        // If there is a valid IP (not blank and not part of the LAN address space), include the row
        if($IPin != $LANips and $IPin != ''){
          $tmpcounter = $this->array_data[$IPin];
          $tmpcounter++;
          $this->array_data[$IPin] = $tmpcounter;
        }
      }
    }
    fclose($myfile);
  }
}
#######################################################################
### End of class object
#######################################################################

#######################################################################
### Begin mainline
#######################################################################

### 1. setup array for data, acept argument, print header info
$argentryIPs = $_GET['arg_entries']; # Specify the number of IPs to print
$argentryfiles = $_GET['arg_numberoflogs'];   #Number of logs to read

if(isset($argentryfiles)){
}
else {
  $argentryfiles = PHP_INT_MAX;
}

### 2. call function that reads the directory entries. (The path is a predetermined Share)
### the function call will return the array of data that includes an IP address and the number of
### times it was found in the logs.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($argentryfiles);
### sort and print the array data, but only up to the number of entries requested by the URL argument.
arsort($cls_logs->array_data);

echo '<p> ';
echo '<br>hMAIL log reader program...';
echo '<br>Number of IPs to print?: '.$argentryIPs;
echo '<br>Number of most recent logs that were read?: '.$cls_logs->wrk_nbr_of_files_read;
echo '<br>';

$idx = 0;
foreach($cls_logs->array_data as $IPdata=>$counter){
  echo '<br>IP: '.$IPdata.' Counter: '.$counter;
  $idx++;
  if($idx >= $argentryIPs){
    break;
  }
}

?>
