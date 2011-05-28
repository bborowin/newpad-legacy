  google.maps.Map.prototype.markers = new Array();

  google.maps.Map.prototype.addMarker = function(marker)
  {
    this.markers[this.markers.length] = marker;
  };

  google.maps.Map.prototype.getMarkers = function()
  {
    return this.markers;
  };

  google.maps.Map.prototype.clearMarkers = function()
  {
    for(var i=0; i<this.markers.length; i++)
    {
      if(null == this.markers[i]) break;
      this.markers[i].setMap(null);
    }

    this.markers = new Array();
  };

  google.maps.Map.prototype.removeMarker = function(id)
  {
    for(var i=0; i<this.markers.length; i++)
    {
      if(null == this.markers[i]) break;
      if(id == this.markers[i].id)
      {
        this.markers[i].setMap(null);
        this.markers.splice(i,1);
        break;
      }
    }
  }

  google.maps.Map.prototype.getMarker = function(id)
  {
    for(var i=0; i<this.markers.length; i++)
    {
      if(null == this.markers[i]) break;
      if(id == this.markers[i].id)
      {
        return this.markers[i];
      }
    }
  }
  
  
  google.maps.Map.prototype.flagMarker = function(id, flag)
  {
    for(var i=0; i<cache.data.length; i++)
    {
      if(null == cache.data[i]) break;
      if(id == cache.data[i].id)
      {
        var mk = cache.data[i];
        mk.flag = flag;
        var marker = CreateMarker(mk);
        this.removeMarker(id);
        this.addMarker(marker);
      }
    }
  }

// create a map marker object from marker struct
function CreateMarker(mk)
{
  // ghetto hack to disperse stacked dots
  dx = 0;//.00005 * (10 - 5*Math.random());
  dy = 0;//.00005 * (10 - 5*Math.random());
  var latlng = new google.maps.LatLng(mk.lat+dx, mk.lng+dy);

  var tooltip = MakeTooltip(mk);

  var icon = ChooseIcon(mk);
  var marker = new google.maps.Marker({position: latlng, map: map, icon: icon, title:tooltip, flat:true});
  marker.price = mk.price;
  marker.url = mk.url;
  marker.id = Int(mk.id); //todo: long?
  marker.age = mk.age;
  marker.ts = mk.ts;
  marker.flag = mk.flag;
  marker.size = mk.size;
  marker.floor = mk.floor;
  marker.score = mk.score;
  
  AddMarkerHandler(marker);
  return marker;
}


function AddMarkerHandler(marker)
{
  google.maps.event.addListener(marker, "click", function()
  {
    if(2 > this.flag) FlagPosting(null, this.id, 'seen');
    //alert(this.url);
    //url = 'show.php?city=' + city + '&postingId=' + this.id.toString() + '&target=' + this.url + '&flag=' + marker.flag;
    window.open(this.url);
  });
}

