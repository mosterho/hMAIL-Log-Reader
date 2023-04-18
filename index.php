<?php

###########################################################################
### hMAIL log reader
### This script will read ALL of the hmail logs that contain TCPIP data
### and summarize the external IP addresses with the most TCPIP hits
### (Log file names go by "hmailserver_2000-12-31.log")
###
### psuedo code:
### 1. Accept arguments, Setup variables
### 2. Call function that reads the logs' directory to determine which files to read.
###    This in turn calls a function that summarizes the data for each IP read in the logs.
### 3. Sort and print data up to the numkber of entries requested in the URL arguments.


#######################################################################
### Define class object and functions
#######################################################################
class cls_logdata {
  public $array_data;
  public $app_path;
  public $wrk_whitelist ;
  public $wrk_blacklist ;
  public $wrk_nbr_of_files_read = 0;
  public $wrk_nbr_of_IPs_read = 0;

  ### __construct function of class to read the JSON file
  ### and setup application variables.
  function __construct() {
    $jsonstuff = file_get_contents("logreaderapp.json");
    $JSONdata = json_decode($jsonstuff, True);  #"True will generate an associative array from JSON data
    $this->app_path       = $JSONdata['path'];
    $this->wrk_whitelist  = $JSONdata['whitelist'];
    $this->wrk_blacklist  = $JSONdata['blacklist'];
  }


  ### Function to read the directory share to obtain the log files to read.
  ### As each log file is determined, call another function to summarize
  ### each IP address that is encountered.
  ### Allow for defaults for arguments if the class is instantiated from a separate program.
  function fct_readdir($argentryfiles = PHP_INT_MAX, $argentryIPs = PHP_INT_MAX){
    $systemname = $this->app_path;
    $dirlist = scandir($systemname,1); # scan directory of files in descending order
    # read each entry that contains a log file name.
    foreach($dirlist as $direntry){
      preg_match_all('/hmailserver_\d{4}-\d{2}-\d{2}.log/',$direntry, $regexresult);  #hmailserver_2022-12-31.log
      foreach($regexresult as $indfile){
        if($indfile[0] != ''){
          $parm_file = $systemname.$indfile[0]; #concatenate system name/path with filename
          # Call the function that reads the log file and accumulates the counts of each IP /24 octet.
          $this->fct_readfile($parm_file);
          # Increment counter to jump out of loops
          $this->wrk_nbr_of_files_read++;
        }
        # if the number of files read is equal to the argument,
        # break out of both foreach loops
        if($this->wrk_nbr_of_files_read >= $argentryfiles){
          break 2;  # Use break 2 to get out of both FOREACH loops
        }
      }
    }
    ## Get the total number of IPs in the array, then
    ## update the class's IP Counter
    $tmpipcount = count($this->array_data);
    if($argentryIPs == PHP_INT_MAX or $argentryIPs > $tmpipcount){
      $this->wrk_nbr_of_IPs_read = $tmpipcount;
    }
    else{
      $this->wrk_nbr_of_IPs_read = $argentryIPs;
    }
    ### sort the array data by count in descending order.
    arsort($this->array_data);
  }


  ### Function to read a log's data and update the class's data array
  function fct_readfile($arg_file_input){
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
        // If the section of string is found, narrow it down to obtain the IP address three octets
        if($temp != ''){
          preg_match_all('/\d{1,3}.\d{1,3}.\d{1,3}/', $temp, $arrayresult3);
        }
        else {
          $arrayresult3[0][0] = '';
        }
        ## For practical reasons, use the first 3 octets to get a /24 bit address for comparison (for now)
        if($arrayresult3[0][0] != ''){
          $IPin = $arrayresult3[0][0].'.0/24';
          // If there is a valid IP that is not in the whitelist array, add/update the data.
          if(!in_array($IPin, $this->wrk_whitelist)){
            ## Set default values for counter and date (in case of new IP entry)
            $tmpcounter = 1;
            $tmp_date = $datein;
            ## If the IP is already in the array, update the counter and the latest hit date.
            if(array_key_exists($IPin, $this->array_data)){
              ## Update counter.
              $tmpcounter = $this->array_data[$IPin][0];  # Get current count for an IP
              $tmpcounter++;
              ## Update most recent "hit" date.
              if($this->array_data[$IPin][1] > $datein){
                $tmp_date = $this->array_data[$IPin][1];
              }
            }
            $this->array_data[$IPin] = array($tmpcounter, $tmp_date);  # Update the new count for an IP
          }
        }
      }
    }
    fclose($myfile);
  }


  ### FUTURE enhancement!!!!
  function fct_output_cli(){
    ### This is a work-in-progress....
    ## report all errors, but ignore notifications
    error_reporting(E_ALL & ~E_NOTICE);
    ob_start();
    echo '\nhMail log reader program';
    echo '\nNumber of IPs to print?: '.$this->wrk_nbr_of_IPs_read;
    echo '<br>Number of most recent logs that were read?: '.$this->wrk_nbr_of_files_read;

    echo '<br>IPv4[tab]Counter[tab]Blacklisted?';
    $idx = 0;
    foreach($this->array_data as $IPdata=>$counter){
      echo '<br>'.$IPdata.' '.$counter;
      if(in_array($IPdata, $this->wrk_blacklist)){
        echo 'Blacklisted';
      }
      $idx++;
      if($idx >= $this->wrk_nbr_of_IPs_read){
        break;
      }
    }
    ob_end_flush();
  }


  ### Function to produce a wb page of the IPs and summary counts.
  function fct_output_web(){
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<link rel="stylesheet" href="mystyle.css">';
    echo '</head>';
    echo '<body>';
    echo '<h1>hMail log reader program</h1>';
    echo '<h3>Number of IPs to print?: '.$this->wrk_nbr_of_IPs_read.'</h3>';
    echo '<h3>Number of most recent logs that were read?: '.$this->wrk_nbr_of_files_read.'</h3>';
    ## of of header, start table output
    echo '<table>';
    echo '<tr>';
    echo '<th class="mydataleft">IPv4</th><th class="mydataright">Counter</th><th class="mydatacenter">Latest hit</th><th class="mydatacenter">Blacklisted?</th>';
    echo '</tr>';
    $idx = 0;
    foreach($this->array_data as $IPdata=>$wrk_array){
      echo '<tr>';
      if(is_array($wrk_array)){
        $counter = $wrk_array[0];
        $datein = $wrk_array[1];
      }
      echo '<td class="mydataleft">'.$IPdata.'</td><td class="mydataright">'.$counter.'</td><td class="mydatacenter">'.$datein.'</td>';
      if(in_array($IPdata, $this->wrk_blacklist)){
        echo '<td class="mydatacenter">Blacklisted</td>';
      }
      echo '</tr>';
      ## Check on the number of IPs to display on the webpage against the argument in the URL.
      $idx++;
      if($idx >= $this->wrk_nbr_of_IPs_read){
        break;
      }
    }
    echo '</table>
    </body>
    </html>';
    #var_dump($this->array_data);
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


  function testonly(){
    ### Debug, testing only...
    foreach($cls_logs->wrk_whitelist as $IPCIDR){
      echo '<br>Debug CIDR...'.$IPCIDR;
      $cls_logs->fct_IP_CIDR($IPCIDR);
    }
  }

}
#######################################################################
### End of class object
#######################################################################


#######################################################################
### Begin mainline
#######################################################################

### 1. accept arguments, determine number of logs to read and IPs to print.
if(isset($_GET['arg_entries'])){
  $argentryIPs = $_GET['arg_entries']; # Specify the number of IPs to print
}
else {
  $argentryIPs = PHP_INT_MAX;
}
if(isset( $_GET['arg_numberoflogs'])){
  $argentryfiles = $_GET['arg_numberoflogs'];   #Number of logs to read
}
else {
  $argentryfiles = PHP_INT_MAX;
}

### 2. Instantiate a new class. Call the function that reads the directory entries.
### The class will keep the array of data that includes an IP address and the number of
### times it was found in the logs.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($argentryfiles, $argentryIPs);

######
### Produce output, whether it's on a command prompt or web page
#####
if(php_sapi_name() == 'cli'){
  $cls_logs->fct_output_cli();
}
else {
  $cls_logs->fct_output_web();
}

?>
