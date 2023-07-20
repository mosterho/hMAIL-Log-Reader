<?php

###########################################################################
### hMAIL log reader
### This script will read ALL of the hmail logs that contain TCPIP data
### and summarize the external IP addresses with the most TCPIP hits
### (Log file names go by "hmailserver_2000-12-31.log")
###########################################################################

#######################################################################
### Define class object and functions
#######################################################################

class cls_logdata {
  public $array_data;
  public $JSONdata;
  public $app_path;
  public $wrk_whitelist ;
  public $wrk_blacklist ;
  public $wrk_nbr_of_files_read = 0;
  public $wrk_nbr_of_IPs_read = 0;
  public $sort_order = 'latest_hit';
  public $geolocate_available = False;

  ### __construct function of class to read the JSON file
  ### and setup application variables.
  function __construct() {
    $jsonstuff = file_get_contents("logreaderapp.json");
    $this->JSONdata = json_decode($jsonstuff, True);  #"True will generate an associative array from JSON data
    $this->app_path       = $this->JSONdata['path'];
    $this->wrk_whitelist  = $this->JSONdata['whitelist'];
    $this->wrk_blacklist  = $this->JSONdata['blacklist'];
    if(file_exists('/var/www/Geolocate/geolocate_API.php')){
      include '/var/www/Geolocate/geolocate_API.php';
      $this->geolocate_available = True;
    }
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
          ## For practical reasons, use the first 3 octets to get a /24 bit address for comparison (for now)
          if($arrayresult3[0][0] != ''){
            $IPin = $arrayresult3[0][0].'.0/24';
            //$IPin = $arrayresult3[0][0];
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


  ### Function to produce a web page.
  function fct_output_web(){
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    #echo '<head>';
    echo '<title>hMail Log Reader Summary of IPs and Hit Counts</title>';
    echo '<link rel="stylesheet" href="mystyle.css">';
    echo '<link rel="icon" type="image/png" href="icons8-email-network-64.png">';
    echo '<script src="hmail.js"></script>';
    #echo '</head>';
    echo '<body>';
    echo '<div class="w3-container w3-theme ">';
    echo '<h2>hMail log reader program</h2>';
    echo '<h4>Number of IPs to print?: '.$this->wrk_nbr_of_IPs_read.'</h4>';
    echo '<h4>Number of most recent logs that were read?: '.$this->wrk_nbr_of_files_read.'</h4>';
    $wrk_button_arg = '\''.$this->sort_order.'\', '.$this->array_data.', '.$this->wrk_blacklist;
    echo '<p>';
    // "Sort" button doesn't seem to work, comment out for now.
    //echo '<button type="button" id="sort_button" oncLick="fct_button_click('.$wrk_button_arg.')">Sort by Latest hit</button><p></p>';

    $this->fct_output_table();  // Function to output table rows part of web page.
    ## JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
    $tmparg_array_data = json_encode($this->array_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    $tmparg_blacklist =  json_encode($this->wrk_blacklist, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    #var_dump($tmparg_array_data);
    #var_dump($tmparg_blacklist);
    #fct_button_click('Counter', $tmparg_array_data, $tmparg_blacklist);
    echo '</div>';
    echo '</body>   </html>';
  }


  function fct_output_table(){
    echo '<table class="w3-table-all w3-third w3-small">';
    echo '<tr>';
    echo '<th class="w3-left-align">IPv4</th><th class="w3-left-align">Location info</th><th class="w3-right-align">Counter</th><th class="w3-center">Latest hit</th><th class="w3-left-align">Blacklisted?</th>';
    echo '</tr>';
    $idx = 0;
    foreach($this->array_data as $IPdata=>$wrk_array){
      echo '<tr>';
      if(is_array($wrk_array)){
        $idx++;
        $counter = $wrk_array[0];
        $datein = $wrk_array[1];
        $arg_IPid = 'IPID'.$IPdata;
        $wrk_addremove = '';
        $arg_blacklist = 'blacklist'.$IPdata;
        if(in_array($IPdata, $this->wrk_blacklist)){
          $wrk_addremove = 'Remove';
        }
        else{
          $wrk_addremove = 'Add';
        }

        // If the Geolocate module is available, setup Location variable for web page.
        $this_location = '';
        if($this->geolocate_available == True){
          //Remove the '0/24' from the string and replace the 4th octet with '1'.
          $IP_to_geolocate = substr_replace($IPdata,'1',-4);
          $wrk_cls_api = new cls_geolocateapi();
          $wrk_cls_api->fct_retrieve_IP_info($IP_to_geolocate);
          $tempobj = json_decode($wrk_cls_api->response);  // convert returned geolocate information from JSON to php object.

          // Piece together the location (e.g., city, region/state, country)
          if( $tempobj->{"city_name"} != ''){
            $this_location .= $tempobj->{"city_name"};
          }
          if($tempobj->{"region_name"} != ''){
            $this_location .= ', '.$tempobj->{"region_name"} ;
          }
          if($tempobj->{"country_name"} != ''){
            $this_location .= ', '.$tempobj->{"country_name"};
          }
        }
        else{
          $this_location = 'N/A';
        }

        echo '<td class="w3-left-align" id="'.$arg_IPid.'">'.$IPdata.'</td><td class="w3-left-align">'.$this_location.'</td><td class="w3-right-align">'.$counter.'</td><td class="w3-center">'.$datein.'</td>';
        echo '<td><button  type="button" id="'.$arg_blacklist.'" onclick="fct_blacklist(\''.$IPdata.'\', \''.$wrk_addremove.'\')">'.$wrk_addremove.' IP from/to Blacklist?</button></td>';
        echo '</tr>';
        ## Check on the number of IPs to display on the webpage against the argument in the URL.
        if($idx >= $this->wrk_nbr_of_IPs_read){
          break;
        }
      }
    }
    echo '</table>';
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
### The class will keep track of the array data.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($argentryfiles, $argentryIPs);

### 3. Produce output, whether it's on a command prompt or web page
if(php_sapi_name() == 'cli'){
  $cls_logs->fct_output_cli();
}
else {
  $cls_logs->fct_output_web();
}

?>
