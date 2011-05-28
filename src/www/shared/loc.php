<html>
  <head>
    <title>
      v3 Geocoder
    </title>
    <style type="text/css">
      suggest_list { background-color:black; }
    </style>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script> 
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false">
</script>
  </head>
  <body>

<input type="text" size="60" id="search" onkeyup="geocode()" onfocus="this.select()" autocomplete="off" />
<select id="suggest_list"><option value=''>enter search term</option></select>

<script type="text/javascript">



/**
* geocoder
*/
var geocoder = new google.maps.Geocoder();
geocoder.firstItem = {};

$("#suggest_list").change(function() { alert();});
 
function geocode() {
  var query = document.getElementById("search").value;
  if(query && query.trim) query = query.trim(); // trim space if browser supports
  if(query != geocoder.resultAddress && query.length > 1) { // no useless request
    clearTimeout(geocoder.waitingDelay);
    geocoder.waitingDelay = setTimeout(function(){
      geocoder.geocode({address: query}, geocodeResult);
    }, 300);
  }else{
    document.getElementById("suggest_list").innerHTML =  "";
    geocoder.resultAddress = "";
    geocoder.resultBounds = null;
  }
  // callback function
  /// -> if only one item in list, go directly there (don't populate dpdn)
  function geocodeResult(response, status) {
    if (status == google.maps.GeocoderStatus.OK && response[0]) {
      geocoder.firstItem = response[0];
      clearListItems();
      var len = response.length;
      for(var i=0; i<len; i++){
        addListItem(response[i]);
      }
    } else if(status == google.maps.GeocoderStatus.ZERO_RESULTS) {
      document.getElementById("suggest_list").innerHTML =  "?";
      geocoder.resultAddress = "";
      geocoder.resultBounds = null;
    } else {
      document.getElementById("suggest_list").innerHTML =  status;
      geocoder.resultAddress = "";
      geocoder.resultBounds = null;
    }
  }
}


/**
 * Selectable dropdown list
 */
var listContainer = document.getElementById("suggest_list");
function addListItem(resp){
  var loc = resp || {};
  var row = document.createElement("option");
  row.innerHTML = loc.formatted_address;
  row.className = "list_item";
  listContainer.appendChild(row);
}
// clear list
function clearListItems(){
  while(listContainer.firstChild){
    listContainer.removeChild(listContainer.firstChild);
  }
}


    </script>
  </body>
</html>


