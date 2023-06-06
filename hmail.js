
// called from PHP and html "on_click" event for sort order.
// NOTE: array and blacklist are JSON data format.
function fct_button_click(arg_sortby, arg_array_data, arg_blacklist){
  console.log('First line of JS fct_button_click function...');
  var wrk_sortby = ''; // using var for scope
  let wrk_button = 'sort_button';
  let wrk_currenttext = document.getElementById(wrk_button).innerHTML;
  var wrk_button_text = 'Sort by ';  // using var for scope.
  // Build arguments and update button text.
  if(wrk_currenttext == "Sort by Latest hit"){
    wrk_sortby = 'sort_by=\'latest_hit\'';
    wrk_button_text += "Counter";
  }
  else{
    wrk_sortby = 'sort_by=\'Counter\'';
    wrk_button_text += "Latest hit";
  }
  wrk_array = '&data_array=' + arg_array_data;
  wrk_blacklist = '&blacklist=' + arg_blacklist;
  console.log("JUST BEFORE CALLING GET for hmail_load_tabledata.php");
  const xhttp = new XMLHttpRequest();
  xhttp.open("POST", "hmail_load_tabledata.php?" + wrk_sortby + wrk_array + wrk_blacklist, true);
  //xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  //wrk_sendstring = '"' + wrk_sortby + wrk_array + wrk_blacklist + '"';
  //wrk_sendstring = wrk_sortby + wrk_array + wrk_blacklist;
  //console.debug(wrk_sendstring);
  //xhttp.send(wrk_sendstring);
  xhttp.send();
  document.getElementById(wrk_button).innerHTML = wrk_button_text;
  console.log('At the very end of JS fct_button_click function, should have called hmail_load_tabledata.php at this point...');
}

// after clicking on PHP html "on_click" for an individual IP to add/remove blacklist entries.
function fct_blacklist(arg_IP, arg_addremove){
  var wrk_newbuttontext = "";
  const xhttp = new XMLHttpRequest();
  xhttp.open("GET", "hmail_JSONP.php?blacklist_ip=" + arg_IP, true); // PHP will determine if adding/removing.
  xhttp.send();
  let wrk_button = "blacklist" + arg_IP;
  if(arg_addremove == "add"){
    wrk_newbuttontext = "Remove IP from Blacklist?";
  }
  else{
    wrk_newbuttontext = "Add IP to Blacklist?";
  }
  document.getElementById(wrk_button).innerHTML = wrk_newbuttontext;
  document.getElementById(wrk_button).disabled = true;
}

wrk2_sortby = 'latest_hit';
//fct_button_click(wrk2_sortby, array(), array([]));
