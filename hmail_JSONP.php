
<?php
#################################################################
### JSON called concurrent with javascript
#################################################################

class cls_main {
  public $JSONdata;

  function __construct(){
    $jsonstuff = file_get_contents("logreaderapp.json");
    $this->JSONdata = json_decode($jsonstuff, True);  #"True will generate an associative array from JSON data
  }

  function fct_validate($arg_IP_to_add, $arg_add_if_ok=False){
    if(in_array($arg_IP_to_add, $this->JSONdata['blacklist'])){
      return True;
    }
    else {
      ## NOTE: the following "if" is not to be paired as an ELSEIF with the above ELSE.
      // If the IP was originally not blacklisted and just now added, return True
      if($arg_add_if_ok){
        $this->fct_writeJSON($arg_IP_to_add);
        return True;
      }
      return False;
    }
  }

  function fct_writeJSON($arg_IP_to_add){
    array_push($this->JSONdata['blacklist'], $arg_IP_to_add);
    // When using json_encode, use pretty print and remove escape character '\'
    $wrk_JSON_towrite = json_encode($this->JSONdata, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    file_put_contents("logreaderapp.json", $wrk_JSON_towrite);
  }

  function fct_deleteJSON($arg_IP_to_delete){
    $wrk_return_idx = array_search($arg_IP_to_delete,$this->JSONdata['blacklist']);
    //echo '<script>console.log(\'Inside PHP fct_deleteJSON, after array search: \')</script>';
    unset($this->JSONdata['blacklist'][$wrk_return_idx]);
    //$this->JSONdata = array_values($this->JSONdata); // DO NOT USE THIS TO RERARRANGE/FIX INDEXES IN JSON!!!!!!!
    //unset($this->JSONdata['blacklist'][$arg_IP_to_delete]);  // This does not work, need to use index.
    // When using json_encode, use pretty print and remove escape character '\'
    $wrk_JSON_towrite = json_encode($this->JSONdata, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    file_put_contents("logreaderapp.json", $wrk_JSON_towrite);
  }

}

#################################################################
### Begin mainline
#################################################################
if(php_sapi_name() == 'cli'){
  $wrk_blacklist_toadd  = $argv[1];
}
else{
  $wrk_blacklist_toadd = $_GET['blacklist_ip'];
}

$cls_object = new cls_main();
// Validate if IP address isn't already in the Blacklist
$validate_return = $cls_object->fct_validate($wrk_blacklist_toadd);
## if return is False (meaning the entry is not in the blacklist), add it
if(!$validate_return){
  #var_dump($cls_object->JSONdata);
  $cls_object->fct_writeJSON($wrk_blacklist_toadd);
}
else{
  $cls_object->fct_deleteJSON($wrk_blacklist_toadd);
}

?>
