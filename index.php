<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="mystyle.css">
</head>
<body>
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
  public int $wrk_nbr_of_IPs_read = 0;

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
  }

  ### Function to update the "global" data array (argument is by reference)
  function fct_readfile($arg_file_input){
    # Users must change this hard coding to accomdate NOT reading their local LAN IPs.
    #$LANips = '10.126.26.';
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
          preg_match_all('/\d{1,3}.\d{1,3}.\d{1,3}/', $temp, $arrayresult3);
        }
        else {
          $arrayresult3[0][0] = '';
        }
        ## For practical reason, will use the first 3 octets for comparison (for now)
        if($arrayresult3[0][0] != ''){
          $IPin = $arrayresult3[0][0].'.0/24';
          // If there is a valid IP (not blank and not part of the whitelist LAN address space), include the row
          if(!in_array($IPin, $this->wrk_whitelist)){
            #var_dump('<br>var_dump if key exists<br>',$IPin,'<br>',$this->wrk_whitelist);
            $tmpcounter = $this->array_data[$IPin];
            $tmpcounter++;
            $this->array_data[$IPin] = $tmpcounter;
          }
        }
      }
    }
    fclose($myfile);
  }

  ### FUTURE enhancement!!!!
  ### Function to narrow down CIDR notation to its "level" of  octet notation
  ### I'm sure there is a module I could download, but it was fun to figure out.
  function fct_IP_CIDR($arg_IP_with_CIDR){
    ### Look for the "/" and determine the prefix bits (i.e., /8, /16, /24, /32)
    ### NOTE: using ## as the delimiter for REGEX rather than the usual //
    ### but still need \ as an escape character for the /
    $result32 = array();
    $result24 = array();
    $result16 = array();
    $result8 =  array();
    ##$IP_POS = str_pos($arg_IP_with_CIDR, '/');
    if(preg_match('#\/\d{1,2}#', $arg_IP_with_CIDR, $wrk_matched)){
      var_dump($arg_IP_with_CIDR, $wrk_matched, '***** GOOD!');
    }
    else {
      var_dump($arg_IP_with_CIDR, '*** NO GOOD');
    }
    // After determining the prefix bits, assign various octets to test.
    switch($wrk_matched[0]){
      case '/32':
      preg_match('/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}/', $arg_IP_with_CIDR, $result32);
      preg_match('/\d{1,3}.\d{1,3}.\d{1,3}./', $arg_IP_with_CIDR, $result24);
      preg_match('/\d{1,3}.\d{1,3}./', $arg_IP_with_CIDR, $result16);
      preg_match('/\d{1,3}./', $arg_IP_with_CIDR, $result8);
      break;
      case '/24':
      preg_match('/\d{1,3}.\d{1,3}.\d{1,3}./', $arg_IP_with_CIDR, $result24);
      preg_match('/\d{1,3}.\d{1,3}./', $arg_IP_with_CIDR, $result16);
      preg_match('/\d{1,3}./', $arg_IP_with_CIDR, $result8);
      break;
      case '/16':
      preg_match('/\d{1,3}.\d{1,3}./', $arg_IP_with_CIDR, $result16);
      preg_match('/\d{1,3}./', $arg_IP_with_CIDR, $result8);
      break;
      case '/8':
      preg_match('/\d{1,3}./', $arg_IP_with_CIDR, $result8);
      break;
      default:
      echo '<br>Got something other than /8, /16, /24, /32: '.$arg_IP_with_CIDR;
    }
    echo '<br>Within fct_IP_CIDR... '.$arg_IP_with_CIDR.' and <br>';
    var_dump($result32, '<br>', $result24, '<br>', $result16, '<br>' ,$result8 );
  }

}
#######################################################################
### End of class object
#######################################################################

#######################################################################
### Begin mainline
#######################################################################

### When running from a command prompt/terminal, turn off notifications,
### but allow errors to appear.
if(php_sapi_name() == 'cli'){
  error_reporting(E_ALL & ~E_NOTICE);
}

### 1. setup array for data, acept argument, print header info
$argentryIPs = $_GET['arg_entries']; # Specify the number of IPs to print
$argentryfiles = $_GET['arg_numberoflogs'];   #Number of logs to read
if(isset($argentryIPs)){
}
else {
  $argentryIPs = PHP_INT_MAX;
}
if(isset($argentryfiles)){
}
else {
  $argentryfiles = PHP_INT_MAX;
}

### 2. call function that reads the directory entries. (The path is a predetermined Share)
### the class function will keep the array of data that includes an IP address and the number of
### times it was found in the logs.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($argentryfiles);

### sort and print the array data.
arsort($cls_logs->array_data);
### if the arg_entries URL arugment was not specified, determine the number of IPs in the array.
if($argentryIPs == PHP_INT_MAX){
  $argentryIPs = count($cls_logs->array_data);
}

### Debug only...
if(1==2){
  foreach($cls_logs->wrk_whitelist as $IPCIDR){
    print '<br>Debug CIDR...'.$IPCIDR;
    $cls_logs->fct_IP_CIDR($IPCIDR);
  }
}

echo '<h1>hMail log reader program</h1>';
echo '<h3>Number of IPs to print?: '.$argentryIPs.'</h3>';
echo '<h3>Number of most recent logs that were read?: '.$cls_logs->wrk_nbr_of_files_read.'</h3>';

### print the array data, but only the number of entries requested in the URL argument.
echo '<table>';
echo '<tr>';
echo '<th class="IP">IPv4</th><th class="counter">Counter</th><th class="blacklist">Blacklisted?</th>';
echo '</tr>';
$idx = 0;
foreach($cls_logs->array_data as $IPdata=>$counter){
  echo '<tr>';
  echo '<td class="IP">'.$IPdata.'</td><td class="counter">'.$counter.'</td>';
  if(in_array($IPdata, $cls_logs->wrk_blacklist)){
    echo '<td class="blacklist">Blacklisted</td>';
  }
  echo '</tr>';
  $idx++;
  if($idx >= $argentryIPs){
    break;
  }
}
echo '</table>';
?>
</body>
</html>
