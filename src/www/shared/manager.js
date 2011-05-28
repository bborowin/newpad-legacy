
// retrieve ALL posting data for city-mode
function GetData(city, mode)
{
  if(!lock)
  {
    lock = true;
    var path = "getPostings.php?city=" + city + "&mode=" + mode + '&nocache=' + Math.random();

    $.get(path, function(data) {
      var get_count = 0;
      var prices = Array();
      var mk_xml = data.getElementsByTagName("pt");
      //alert(mk_xml.length + " xml snippets dld");
      if(mk_xml.length > 0)
      {
        //empty cache (for now, later implement merging)
        cache.data.length = 0;
        //fill cache with postings data
        for (var i = 0; i < mk_xml.length; i++)
        {
          mk = MakePosting(mk_xml[i]);
          cache.data.push(mk);
          if(mk.price > 0) prices.push(mk.price);
          get_count += 1;
        }
     
        Print(get_count + ' postings DLd');

        // set price range to cover 90% of returned postings
        prices.sort(function(a,b){return a>b?1:a<b?-1:0;});
        btm = Math.floor(prices.length * 0.05);
        ttp = Math.floor(prices.length * 0.95);
        min = prices[btm]
        min -= min % filters.slider_step;
        max = prices[ttp];
        max -= max % filters.slider_step - filters.slider_step;
        filters.abs_min = min - filters.slider_step;
        filters.abs_max = filters.slider_step + max;
        InitSlider(min,max);
      }
      
      lock = false;
      ShowMarkers();
    });
  }
}

//populates a posting object with data parsed out of xml
function MakePosting(xml)
{
  var mk = new Object();
  mk.id = xml.getAttribute("id");
  mk.lat = Float(xml.getAttribute("lat"));
  mk.lng = Float(xml.getAttribute("lng"));
  mk.price = Int(xml.getAttribute("price"));
  mk.url = xml.getAttribute("url");
  mk.ts = Int(xml.getAttribute('age'));
  mk.age = MakeAgeString(mk.ts);
  mk.flag = xml.getAttribute('flag');
  
  attr = xml.getAttribute('attr');
  atr = attr.split(', ');
  mk.size = Int(atr[0]);
  if(null == mk.size) mk.size = -1;
  mk.area = Int(atr[1]);
  if(null == mk.area) mk.area = -1;
  mk.floor = Int(atr[2]);
  if(null == mk.floor) mk.floor = -1;

  return mk;
}


// determine visibility of marker given current filter settings
function PassFilters(mk)
{
  var pass = true;
  
  if(filters.size != -1)
  {
    pass = filters.size == mk.size || mk.size == -1;
  }
  if(pass && filters.floor != -1)
  {
    pass = filters.floor == mk.floor || mk.floor == -1;
  }
  
  pass = pass ? mk.price >= filters.cur_min && mk.price <= filters.cur_max : pass;
  return pass;
}

// depending on filter settings, each marker is assigned a relative score
function Score(mk)
{
  var score = 0;

  if(-1 != filters.floor && filters.floor == mk.floor) score+=2;
  if(-1 != filters.size && filters.size == mk.size) score+=2;
  
  mk.score = score;
}

// sorting function for ordering marker lists by score
// (highest score first), then age (if same score)
function _marker_sort(a,b)
{
  var score = ((a.score > b.score) ? -1 : ((a.score < b.score) ? 1 : 0));
  if(0 == score)
  {
    score = ((a.ts < b.ts) ? -1 : ((a.ts > b.ts) ? 1 : 0));
  }
  return score;
}

function RecalculateVisible(center)
{
  var flag = false;
  
  if(null == params.prev_center || params.prev_center.lat() != center.lat() || params.prev_center.lng() != center.lng())
  {
    flag = true;
    
    var rsq = params.sa_radius * params.sa_radius;
    cache.visible.length = 0;
    for (var i = 0; i < cache.data.length; i++)
    {
      var mk = cache.data[i];
      var x = center.lat() - mk.lat;
      var y = center.lng() - mk.lng;
      if(Inside(x,y,rsq))
      {
        cache.visible.push(mk);
      }
    }
  }
  
  params.prev_center = center;
  return flag;
}

function FilterVisible()
{
  filters.size = $('#size').val();
  filters.floor = $('#floor').val();

  var candidates = Array();
  for (var i = 0; i < cache.visible.length; i++)
  {
    var mk = cache.visible[i];
    if(PassFilters(mk))
    {
      Score(mk);
      candidates.push(mk);
    }
  }

  candidates.sort(_marker_sort);
  return candidates;
}

function ShowMarkers()
{
  var center = sa.getCenter();
  RecalculateVisible(center);

  var candidates = FilterVisible();
  candidates.sort(_marker_sort);
  
  map.clearMarkers();
  cnt = 0;
  // add remaining candidates to map
  for(var i = 0; i<candidates.length; i++)
  {
    map.addMarker(CreateMarker(candidates[i]));
    if(++cnt > params.max_visible) break;
  }
}


function RemoveInvisible(center)
{
  var rsq = params.sa_radius * params.sa_radius;
  var center = sa.getCenter();

  //update markers which were on the map
  for(var i = 0; i < map.markers.length; i++)
  {
    mk = map.markers[i];
    if(null == mk) break;
    
    var x = center.lat() - mk.getPosition().lat();
    var y = center.lng() - mk.getPosition().lng();

    if(!Inside(x,y,rsq) || !PassFilters(mk))
    {
      //map.removeMarker(mk.id);
      mk.setMap(null);
      map.markers.splice(i,1);
      i--;
    }
  }
}

function DragSearchArea()
{
  //params.sa_radius = 0.576 / Math.pow(2, params.zoom-9);
  var rsq = params.sa_radius * params.sa_radius;
  var center = sa.getCenter();

  //NoteSearch(center);

  RemoveInvisible(center);
  
  RecalculateVisible(center);

  var potential = FilterVisible();

  var candidates = Array();
  for (var i = 0; i < potential.length; i++)
  {
    var have = false;
    for(var j=0; j<map.markers.length; j++)
    {
      if(null == map.markers[j]) break;
      if(potential[i].id == map.markers[j].id)
      {
        have = true;
        break;
      }
    }
    if(!have)
    {
      Score(potential[i]);
      candidates.push(potential[i]);
    }
  }

  // discard lowest ranking if we have more than max
  if(map.markers.length + candidates.length > params.max_visible)
  {
    // sort both arrays
    candidates.sort(_marker_sort);
    map.markers.sort(_marker_sort);

    var v = 0;
    var c = 0;
    
    while(c < candidates.length && v < params.max_visible)
    {
      if(v < map.markers.length && map.markers[v].score > candidates[c].score) v++;
      else if (v < map.markers.length && map.markers[v].score == candidates[c].score && map.markers[v].ts <= candidates[c].ts) v++;
      else if (v < map.markers.length && map.markers[v].score == candidates[c].score && map.markers[v].ts == candidates[c].ts && map.markers[v].price > candidates[c].price) v++
      else
      {
        if(map.markers.length == params.max_visible)
        {
          map.markers[map.markers.length-1].setMap(null);
          map.markers.length = map.markers.length-1;
        }
        map.markers.splice(v,0,CreateMarker(candidates[c]));
        c++;
      }
    }
  }
  else
  {
    for (var i = 0; i < candidates.length; i++)
    {
      if(map.markers.length >= params.max_visible) break;
      map.addMarker(CreateMarker(candidates[i]));
    }
  }
}


function FlagPosting(e, id, action)
{
  if('' != id && null != id)
  {
    $.get("tag.php", {target:id, action:action}, function(data)
    {
      r = $.parseJSON(data);
      if(1 == r.e)
      {
        map.removeMarker(r.id);
        for(i=0;i<cache.data.length;i++) if(cache.data[i].id == id)
        {
          cache.data.splice(i,1);
          break;
        }
        for(i=0;i<cache.visible.length;i++) if(cache.visible[i].id == id)
        {
          cache.visible.splice(i,1);
          break;
        }
      }
    }, 'text');
    
    switch(action)
    {
      case "expired":
        map.removeMarker(id);
        break;
      case "seen":
      case "unlike":
        map.flagMarker(id, 1);
        break;
      case "like":
        map.flagMarker(id, 2);
        break;
      default:
        break;
    }
  }
  
  if (e) e.stopPropagation();
  return false;
}
