  var center = null;
  var map = null;
  var infowindow = null;
  var searcharea = null;

  var city = null;
  var pmin = null;
  var pmax = null;

  google.maps.Map.prototype.markers = new Array();

  google.maps.Map.prototype.addMarker = function(marker)
  {
    this.markers[this.markers.length] = marker;
  };

  google.maps.Map.prototype.getMarkers = function()
  {
    return this.markers
  };

  google.maps.Map.prototype.clearMarkers = function()
  {
    for(var i=0; i<this.markers.length; i++)
    {
      this.markers[i].setMap(null);
    }

    this.markers = new Array();
  };

  function initialize(city, lat, lon, zoom, userId)
  {
    var myLatlng = new google.maps.LatLng(lat, lon);
    var myOptions = {
      zoom: zoom,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.TERRAIN,
      streetViewControl: true
    }

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    OverlayMarkers(city, 10000, myLatlng, userId);
  }

  function GetIconColour(price)
  {
    var number = 1;
    
    var highest = $("#slider").slider("values", 1); //max allowed price
    var lowest = $("#slider").slider("values", 0); //min allowed price

    var step = (highest - lowest)/19.0;

    var diff = price - lowest;
    number +=  Math.floor(diff/step);
    number = number > 19 ? 19 : number;

    return 20 - number;
  }

  //establish icon size according to age
  function GetIconSize(days)
  {
    var size;

    var adj = Math.floor(days/2)
    size = adj > 8 ? 8 : adj;

    return size;
  }

  function sprite(colour, size, flag)
  {
    var px = 11 - size;
    var trunk = '/static/img/';
    switch(flag)
    {
      case "0":
        trunk += 'blue/';
        break;
      case "1":
        trunk += 'gray/';
        break;
      case "2":
        trunk += 'green/';
        break;
      case "3":
        trunk += 'red/';
        break;
      default:
        trunk += 'blue/';
        break;
    }

    var image = new google.maps.MarkerImage(trunk + '0' + size + '.png',
    new google.maps.Size(px, px),
    new google.maps.Point(colour*px,0), //origin
    new google.maps.Point(px/2, px/2)); //anchor

    return image;
  }

  function ChooseIcon(price, ts, flag)
  {
    var days = Math.floor(ts);

    var size = GetIconSize(days)
    var colour = GetIconColour(price)

    var icon = sprite(colour, size, flag);

    return icon;
  }

  function GetIcon(ts, rad)
  {
    var days = Math.floor(ts);
    var size = GetIconSize(days)
    var icon = sprite(rad, size, "3");

    return icon;
  }

  function GetAge(ts, suffix)
  {
  ts = Math.floor(ts);

  var years = (ts - ts % 365) / 365;
  var months = ((ts % 365) - (ts % 365) % 28) / 28;
  var weeks = ((ts % 28) - (ts % 28) % 7) / 7;
  var days = (ts % 7);

  var age = '';
  var separator = ', ';
  if(years > 0) age += years + ' year';
  if(years > 1) age += 's';
  if(months > 0)
  {
      if('' != age) age += separator;
    age += months + ' month';
    if(months > 1) age += 's';
  }
  if(weeks > 0)
  {
      if('' != age) age += separator;
    age += weeks + ' week';
    if(weeks > 1) age += 's';
  }
  if(days > 0)
  {
      if('' != age) age += separator;
    age += days + ' day';
    if(days > 1) age += 's';
  }
  if('' != age)
  {
    age += suffix;
  }
  else
  {
    age = 'today';
  }
    return age;
  }

  function OverlayMarkers(city, count, latlon, userId)
  {
    map.clearMarkers();

    zoom = map.getZoom()
    var path = "hits.php?city=" + city + "&count=" + count + '&lat=' + latlon.lat() + '&lon=' + latlon.lng() + '&userId=' + userId + '&nocache=' + Math.random();

    downloadUrl(path,
    function(data) {
      var markers = data.getElementsByTagName("pt");
      for (var i = 0; i < markers.length; i++)
      {
        var latlng = new google.maps.LatLng(parseFloat(markers[i].getAttribute("lat")), parseFloat(markers[i].getAttribute("lng")));
        var ts = markers[i].getAttribute("ts")
        var icon = GetIcon(ts, markers[i].getAttribute("r"));

        var marker = new google.maps.Marker({position: latlng, map: map, icon: icon, title:GetAge(ts, ' ago'), flat:true});
        map.addMarker(marker);
      }
    });
  }

