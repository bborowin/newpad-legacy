  var center = null;
  var map = null;
  var infowindow = null;
  var searcharea = null;
  var wait = null;
  var lock = false;
  
  var city = null;
  var pmin = null;
  var pmax = null;
  var focus = 0;
  
  var mapcenter;
  var maxhits;
  var current;
  
  var sa = [];
  var cache = Array();
  
  //populates a posting object with data parsed out of xml
  function MakePosting(xml)
  {
    var mk = new Object();
    mk.id = xml.getAttribute("id");
    mk.lat = parseFloat(xml.getAttribute("lat"));
    mk.lng = parseFloat(xml.getAttribute("lng"));
    mk.price = xml.getAttribute("price");
    mk.url = xml.getAttribute("url");
    mk.title = xml.getAttribute("title");
    mk.ts = xml.getAttribute('age');
    mk.age = GetAge(mk.ts, ' ago');
    mk.flag = xml.getAttribute('flag');
    mk.attr = xml.getAttribute('attr');

    atr = mk.attr.split(', ')[0]
    val = parseInt(atr);
    if(!isNaN(val))
    {
      mk.size = atr;
    }

    return mk;
  }

  //appends debug message to console
  function Print(msg)
  {
    /*
    if($('#debug'))
    {
      $('#debug').html($('#debug').html() + msg);
      var objDiv = document.getElementById("debug");
      objDiv.scrollTop = objDiv.scrollHeight;
    }*/
  }
  
  function CenterMap(coord_string)
  {
    coords = coord_string.split(" ");
    lat = parseFloat(coords[0]);
    lon = parseFloat(coords[1]);
    latlon = new google.maps.LatLng(lat, lon);
    map.panTo(latlon);
  }
  
  //sets up initial layout, ui, map etc
  function initialize(city, mode, lat, lon, zoom, min, max, count, run)
  {
    //$('#debug').hide();
    //$("#debug_container" ).draggable();
    //$("#debug_container" ).dblclick(function(){if($('#debug').is(":visible")) $('#debug').hide();else $('#debug').show();});
    $("#location_container" ).draggable();
    $("#location_container" ).dblclick(function(){if($('#location').is(":visible")) $('#location').hide();else $('#location').show();});
    $("#filters_container" ).draggable();
    $("#filters_container" ).dblclick(function(){if($('#filters').is(":visible")) $('#filters').hide();else $('#filters').show();});

    $.postingMode = mode;
    $.current_min_price = min;
    $.current_max_price = max;

    var center_coords = new google.maps.LatLng(lat, lon);
    maxhits = count;
    var myOptions = {
      zoom: zoom,
      center: center_coords,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      streetViewControl: true
    }

    infowindow = new google.maps.InfoWindow();
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    
    InitSlider($.min_price, $.max_price, min, max);

    google.maps.event.addListener(map, 'click',
    function(event) {
      var ads = true;
      if(window.parent.frames.length > 1)
      {
        var ad = $('.adsense', window.parent.frames[0].document);
        if (null == ad || 0 == ad.height() || !ad.is(":visible"))
        {
          ads = false;
        }
      }
      
      if(!lock)
      {
        map.clearMarkers();
        if(null != wait) wait.setMap();
        if(undefined != sa) for(i in sa) sa[i].setMap(null);
        OverlayMarkers(city, count, event.latLng);
      }
    });

    if(run)
    {
      run = false;
      OverlayMarkers(city, count, center_coords);
    }
  }

  function OverlayMarkers(city, count, latlon)
  {
    zoom = map.getZoom();
    lock = true;
    map.clearMarkers();
    infowindow.close();

    var zoom_min = 9;
    var zoom_max = 17;
    if(zoom < zoom_min) zoom = zoom_min;
    if(zoom > zoom_max) zoom = zoom_max;
    var radius = 0.576 / Math.pow(2,zoom-9);
    var searchradius = 200 * Math.pow(2, 17-zoom);
    var min_price = $.current_min_price;
    var max_price = $.current_max_price;
    var path = "rents.php?city=" + city + "&mode=" + $.postingMode + "&count=" + count + '&lat=' + latlon.lat() + '&lon=' + latlon.lng() + '&radius=' + radius + '&min=' + min_price + '&max=' + max_price + '&nocache=' + Math.random();

    //generic search-area implementation (for making concentric rings)
    sa = [];
    for(i=0; i<=0; i++)
    {
      r = 80.0;
      o = 0.5;
      f = 1 + (i / r);
      sa.push(new google.maps.Circle({center: latlon, radius:searchradius*f, fillColor:'white', strokeColor:'black', fillOpacity:o, strokeOpacity:0.1, strokeWeight:2, map: null}));
    }

    var waitImg = new google.maps.MarkerImage('img/processing_gears.gif', new google.maps.Size(141, 141), new google.maps.Point(0,0), new google.maps.Point(75, 75));
    wait = new google.maps.Marker({position: latlon, map: map, icon: waitImg, title:'please wait', flat:true});

    for(i in sa)
    {
      google.maps.event.addListener(sa[i], 'click',
      function(event) {
        google.maps.event.trigger(map, 'click', event);
      });
    }

    downloadUrl(path,
    function(data) {
      if(null != wait)
      {
        for(i in sa) sa[i].setMap(wait.getMap());
      }
      var mk_xml = data.getElementsByTagName("pt");
      
      var markers = Array();
      for (var i = 0; i < mk_xml.length; i++)
      {
        mk = MakePosting(mk_xml[i]);
        markers.push(mk);
      }
   
      Print('</br>' + markers.length + ' postings DLd');
  
      var cnt = 0;
      for (var i = 0; i < markers.length; i++)
      {
        var mk = markers[i];
        var latlng = new google.maps.LatLng(mk.lat, mk.lng);

        var tooltip = MakeTooltip(mk);

        var icon = ChooseIcon(mk);
        var marker = new google.maps.Marker({position: latlng, map: map, icon: icon, title:tooltip, flat:true});
        marker.price = mk.price;
        marker.url = mk.url;
        marker.id = mk.id;
        marker.age = mk.age;
        marker.ts = mk.ts;
        marker.flag = mk.flag;
        marker.ttl = mk.title;
        marker.attr = mk.attr;
        marker.size = mk.size;
        
        //decide if marker hidden/visible
        size = $("#size").val();
        floor = $("#floor").val();
        
        map.addMarker(marker);
        if((mk.price < min_price || mk.price > max_price) ||
         (size != '-1' && ('-1' != marker.size) && (size != marker.size) ) ||
         (floor != 'ignore' && (mk.attr.indexOf(floor) == -1) ))
        {
          marker.setVisible(false);
        }
        else
        {
          cnt++;
        }
        
        AddMarkerHandler(marker, city);
      }

      Print('</br>' + cnt + ' postings shown');
      
      //reset the slider min/max to correspond to returned range
      stats = data.getElementsByTagName("stats");
      btm_cutoff = stats[0].getAttribute("bottom_ten");
      top_cutoff = stats[0].getAttribute("top_ten");
      //InitSlider(btm_cutoff, top_cutoff, btm_cutoff, top_cutoff);

      
      if(null != wait) wait.setMap(null);
      lock = false;
    });
  }
  
  function MakeTooltip(mk)
  {
    floor = '';
    if(mk.attr.indexOf("top floor") != -1) floor = 'top floor';
    else if(mk.attr.indexOf("third floor") != -1) floor = 'third floor';
    else if(mk.attr.indexOf("second floor") != -1) floor = 'second floor';
    else if(mk.attr.indexOf("first floor") != -1) floor = 'ground floor';
    else if(mk.attr.indexOf("basement") != -1) floor = 'basement';
    
    sqft = '';
    attr = mk.attr.split(', ');
    for(var i in attr)
    {
      var sf = attr[i].indexOf("sqft");
      if(-1 != sf)
      {
        sqft = attr[i];
        break;
      }
    }

    size = '-1'
    if(mk.size)
    {
      if('0' == mk.size)
      {
        size = 'studio';
      }
      else
      {
        size = mk.size + ' bedroom';
      }
    }
    
    tooltip = '$' + mk.price;
    if('' != floor) tooltip += ' ' + floor;
    if('' != size && '-1' != size) tooltip += ' ' + size;
    if('' != sqft) tooltip += ' (' + sqft + ')';
    
    tooltip += ' posted ' + mk.age
    return tooltip;
  }

  function PriceMarkers(min, max)
  {
    for (i in map.markers)
    {
      m = map.markers[i];
      if(m)
      {
        if(min > -1 && m.price < min)
        {
          m.setVisible(false);
        }
        else if(max > -1 && m.price > max)
        {
          m.setVisible(false);
        }
        else
        {
          m.setVisible(true);
        }
      }
    }
  }
  
  function AddMarkerHandler(marker, city)
  {
    google.maps.event.addListener(marker, "click", function()
    {
      if(0 == this.flag) FlagPosting(null, city, this.id, 'seen');

      if(current) current.setMap(null);
      var image = new google.maps.MarkerImage("img/target_16x16.png",
      new google.maps.Size(16, 16),
      new google.maps.Point(0,0), //origin
      new google.maps.Point(8, 8)); //anchor
      current = new google.maps.Marker({position: marker.position, map: map, icon: image, title:"last selection (click to clear)", flat:true});
      google.maps.event.addListener(current, "click", function(e)
      {
        current.setMap(null);
        current = null;
      });

      map.addMarker(current);

      url = 'show.php?city=' + city + '&postingId=' + this.id.toString() + '&target=' + this.url + '&flag=' + marker.flag;
      window.open(url);
    });
  }
  
  function FlagPosting(e, city, id, action)
  {
    if('' != id && null != id)
    {
      $.get("tag.php", { city:city, target:id, action:action});
      infowindow.close();

      switch(action)
      {
        case "expired":
          map.removeMarker(id);
          break;
        case "seen":
          map.flagMarker(id, 1, city);
          break;
        case "like":
          map.flagMarker(id, 2, city);
          break;
        case "unlike":
          map.flagMarker(id, 1, city);
          break;
        default:
          break;
      }
    }
    
    if (e) e.stopPropagation();
    return false;
  }
