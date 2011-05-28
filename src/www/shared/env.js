/*
 * (C) 2011- Yasboti Inc / Newpad.ca 
 * 
 * General environment set-up
 * 
 */

// set up the parameters object with default values
var params = Object();
params.city = null;
params.max_visible = 100;
params.coords = null;
params.zoom = 16;
params.drag = false;

// set up an object to hold filter values
var filters = Object();
filters.slider_step = 25;
filters.global_max = 3000;
filters.global_min = 0;
filters.area = -1;
filters.size = -1;
filters.floor = -1;


// search area gmaps object
var sa = null;
var sa_lock = false;

// global map object
var map = null;

// global DL lock
var lock = false;



function CenterMap(coord_string)
{
  coords = coord_string.split(" ");
  lat = parseFloat(coords[0]);
  lon = parseFloat(coords[1]);
  latlon = new google.maps.LatLng(lat, lon);
  map.panTo(latlon);
}

//sets up initial layout, ui, map etc
function Init(city, mode, lat, lon, zoom, max_count)
{
  // transcribe operating parameters
  params.city = city;
  params.mode = mode;
  params.coords = new google.maps.LatLng(lat, lon);
  params.zoom = zoom;
  params.max_count = max_count;
  
  //legacy UI, leave for now
  $("#location_container" ).draggable();

  $.postingMode = mode;
  
  
  // instantiate map
  map = CreateMap(params.coords, params.zoom);
  
  //should have price presets from city stats, omit for now
  //filters.abs_min = 250;
  //filters.abs_max = 1950;
  //InitSlider(filters.abs_min+filters.slider_step,filters.abs_max-filters.slider_step);

  //init search area and radius
  params.sa_radius = 0.576 / Math.pow(2, params.zoom-9);
  InitSearchArea(params.coords);

  // populate data cache
  GetData(params.city, params.mode);
}

// instantiate a gmaps map object
function CreateMap(coords, zoom)
{
  var myOptions = {
    zoom: zoom,
    center: coords,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    streetViewControl: true
  }

  return new google.maps.Map(document.getElementById("map_canvas"), myOptions);
}

function SearchRadius(zoom)
{
  var zoom_min = 9;
  var zoom_max = 17;
  if(zoom < zoom_min) zoom = zoom_min;
  if(zoom > zoom_max) zoom = zoom_max;
  var sr = 200 * Math.pow(2, 17-zoom);
  
  return sr;
}

function InitSearchArea(coords)
{
  if(null == sa)
  {
    var sr = SearchRadius(params.zoom);
    
    sa = new google.maps.Circle(
      { center: coords,
        radius:sr,
        fillColor:'white',
        fillOpacity:0.5, 
        strokeColor:'white',
        strokeOpacity:0.0, 
        map: map
      });

    // clicking on map or existing search area re-draws the search area
    google.maps.event.addListener(map, 'click',
    function(event) {
      SetSearchArea(event.latLng);
    });
    google.maps.event.addListener(sa, 'click',
    function(event) {
      SetSearchArea(event.latLng);
    });

    // enable search area drag
    if (params.drag) AddDragListener();
  }
  else
  {
    
  }
}

function NoteSearch(latLng)
{
  var qs = "searched.php?city=" + params.city + "&mode=" + params.mode + "&lat=" + latLng.lat() + "&lon=" + latLng.lng() + "&radius=" + params.sa_radius + "&min=" + filters.cur_min + "&max=" + filters.cur_max
  $.get(qs, null);
}

// re-center and reset search area radius at click / current zoom
function SetSearchArea(latLng)
{
  params.zoom = map.getZoom();
  params.sa_radius = 0.576 / Math.pow(2,params.zoom-9);
  sa.setRadius(SearchRadius(params.zoom));
  sa.setCenter(latLng);

  ShowMarkers();
  NoteSearch(latLng);
}

// combine mouse down/move/up events to implement dragging
function AddDragListener()
{
  google.maps.event.addListener(sa, 'mousedown',
  function(event) {
    if(!sa_lock)
    {
      map.setOptions({'draggable':false});
      sa_lock = true;
    }
  });
  google.maps.event.addListener(sa, 'mousemove',
  function(event) {
    if(sa_lock)
    {
      sa.setCenter(event.latLng);
      DragSearchArea();
    }
  });
  google.maps.event.addListener(sa, 'mouseup',
  function(event) {
    if(sa_lock)
    {
      map.setOptions({'draggable':true});
      sa_lock = false;
    }
  });
}
