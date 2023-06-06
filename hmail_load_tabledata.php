<?php

class cls_main{
  public $wrk_sortby;
  public $wrk_array_data;
  public $wrk_blacklist;

  function __construct($arg_sortby, $arg_array, $arg_blacklist){
    $this->wrk_sortby = $arg_sortby;
    $this->wrk_array_data = $arg_array;
    if(isset($arg_blacklist) and $arg_blacklist != ''){
      $this->wrk_blacklist = $arg_blacklist;
    }
    else {
      $jsonstuff = file_get_contents("logreaderapp.json");
      $tmp_JSONdata = json_decode($jsonstuff, True);  #"True will generate an associative array from JSON data
      $this->wrk_blacklist  = $tmp_JSONdata['blacklist'];
    }
  }

  // fct_switch will switch/flip Counter and Latest Hit elements in the array
  //  since the arsort function will work on the 1st element.
  function fct_switch(){
     foreach($this->wrk_array_data as $x=>$xvalue){
      // if [0] contained Counter, it will now contian Latest Hit Date, and vice versa.
      $this->wrk_array_data[$x][0] = $xvalue[1];
      $this->wrk_array_data[$x][1] = $xvalue[0];
    }
  }

  function fct_loadtable(){
    if($this->sortby == 'latest_hit'){
      // flip Counter and Latest Hit Date positions within array.
      fct_switch();
      arsort($this->wrk_array_data);
      // now exchange Counter and Latest Hit Date back to their original postions within the array.
      fct_switch();
    }
    else{
      arsort($this->wrk_array_data);
    }

    $idx = 0;
    echo 'inside the load table function<br>';
    print_r('wrk_array_data: ', $this->wrk_array_data);
    foreach($this->wrk_array_data as $IPdata=>$wrk_array){
      #echo 'inside the load table function array proc<br>';
      echo '<tr>';
      if(is_array($wrk_array)){
        $idx++;
        $counter = $wrk_array[0];
        $datein = $wrk_array[1];
        $arg_IPid = 'IPID'.$IPdata;
        echo '<td class="mydataleft" id="'.$arg_IPid.'">'.$IPdata.'</td><td class="mydataright">'.$counter.'</td><td class="mydatacenter">'.$datein.'</td>';
        if(in_array($IPdata, $this->wrk_blacklist)){
          echo '<td class="mydatareversebutton">Blacklisted</td>';
        }
        else{
          $arg_blacklist = 'add_blacklist'.$IPdata;
          echo '<td><button id="'.$arg_blacklist.'" onclick="fct_add_blacklist(\''.$IPdata.'\')">Add IP to Blacklist?</button></td>';
        }
        echo '</tr>';
        ## Check on the number of IPs to display on the webpage against the argument in the URL.
        if($idx >= $this->wrk_nbr_of_IPs_read){
          break;
        }
      }
    }
  }

}


###########################################################################
### Begin mainline
###########################################################################

echo '<p>Within mainline of hmail_load_tabledata: <p>';
if(isset($_POST['sort_by'])){
  $tmp_sortby = $_POST['sort_by'];
}
else{
  $tmp_sortby = '$_POST for sortby did not work';
}

if(isset($_POST['data_array'])){
  $tmp_array = $_POST['data_array'];
}
else{
  $tmp_array =  '$_POST for data_array did not work';
}
if(isset($_POST['blacklist'])){
  $tmp_blacklist = $_POST['blacklist'];
}
else{
  $tmp_blacklist =  '$_POST for blacklist did not work';
}

$tmp_array2     = json_decode($tmp_array, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
#$tmp_array2 = json_decode('{ "84.54.50.0/24": [ 126, "2023-05-12 14:31:45.693" ], "162.142.125.0/24": [ 51, "2023-05-11 09:13:53.617" ]}', JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) ;
$tmp_blacklist2 = json_decode($tmp_blacklist, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
#$tmp_blacklist2 = json_decode('[ "141.98.11.0/24", "46.148.40.0/24", "185.36.81.0/24", "187.138.208.0/24", "80.94.95.0/24" ]', JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

var_dump("POST sort_by ", $_POST['sort_by']);
echo '<br>';
var_dump("Value of tmp_sortby ", $tmp_sortby);
echo '<br>';


var_dump($_POST['data_array']);
echo '<br>';
var_dump($tmp_array);
echo '<br>';
var_dump($tmp_blacklist);
echo '<br>';

var_dump($tmp_array2);
echo '<br>';
var_dump($tmp_blacklist2);
echo '<br>';

$cls_object = new cls_main($tmp_sortby, $tmp_array2, $tmp_blacklist2);
$cls_object->fct_loadtable();


?>
