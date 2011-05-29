  var center = null;
  var map = null;
  var infowindow = null;
  var searcharea = null;

  var city = null;
  var pmin = null;
  var pmax = null;


  function initialize(city, lat, lon, count)
  {
    var myLatlng = new google.maps.LatLng(lat, lon);
    var myOptions = {
      zoom: 13,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.TERRAIN,
      streetViewControl: true
    }

    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    
    OverlayMarkers(city, count, myLatlng);
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


  function sprite(colour, size, flag)
  {
    var px = 11 - size;
    var trunk = '/static/img/';
    switch(flag)
    {
      case 0:
        trunk += 'blue/';
        break;
      case 1:
        trunk += 'gray/';
        break;
      case 2:
        trunk += 'purple/';
        break;
      case 3:
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

  function GetIcon(ts, rad, mode)
  {
    var icon = sprite(rad, ts, mode);

    return icon;
  }

  function OverlayMarkers(city, count, latlon)
  {
    var path = "faves.php?city=" + city + "&count=" + count + '&nocache=' + Math.random();

    downloadUrl(path,
    function(data) {
      var markers = data.getElementsByTagName("pt");
      for (var i = 0; i < markers.length; i++)
      {
        var latlng = new google.maps.LatLng(parseFloat(markers[i].getAttribute("lat")), parseFloat(markers[i].getAttribute("lng")));
        var cnt = parseInt(markers[i].getAttribute("ts"));
        var ts = 7 - Math.floor((cnt-count)/2);
        if(ts<0) ts = 0;
        var icon = GetIcon(ts, 9, 2+parseInt(markers[i].getAttribute("mode")));
        var title = (1 == count) ? " person has" : " people have";
        title = cnt.toString() + title + " seen this";
        var marker = new google.maps.Marker({position: latlng, map: map, icon: icon, title:title, flat:true});
        marker.url = markers[i].getAttribute("url");

        map.addMarker(marker);

        AddMarkerHandler(marker, city);
      }
    });
  }

  function AddMarkerHandler(marker, city)
  {
    google.maps.event.addListener(marker, "click", function()
    {
      window.open(this.url);
    });
  }
