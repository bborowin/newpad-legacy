//appends a newline'd message to console
function Print(msg)
{
  if($.debug)
  {
    $('#debug').html($('#debug').html() + msg + '</br>');
    var objDiv = document.getElementById("debug");
    objDiv.scrollTop = objDiv.scrollHeight;
  }
}

function Int(strdata)
{
  var result = parseInt(strdata);
  if(isNaN(result)) result = null;
  return result
}

function Float(strdata)
{
  var result = parseFloat(strdata);
  if(isNaN(result)) result = null;
  return result
}

function MakeAgeString(ts)
{
  suffix = ' ago'
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
    if(days > 1) 
    {
      age += days + ' days';
    }
    else
    {
      age = 'yesterday';
    }
  }
  if('yesterday' != age)
  {
    if('' != age)
    {
    age += suffix;
    }
    else
    {
      age = 'today';
    }
  }
  return age;
}

// format existing attribute values into a tooltip string
function MakeTooltip(mk)
{
  var floor = '';
  switch(mk.floor)
  {
    case 0:
      floor = "basement";
      break;
    case 1:
      floor = "first floor";
      break;
    case 2:
      floor = "second floor";
      break;
    case 3:
      floor = "third floor";
      break;
    case 999:
      floor = "top floor";
      break;
    default:
      floor = '';
      break;
  }
  
  var sqft = '';
  if(null != mk.area && -1 != mk.area) sqft = mk.area + ' sq.ft.';

  var size = '';
  if(null != mk.size)
  {
    if(0 == mk.size)
    {
      size = 'studio';
    }
    else if(-1 != mk.size)
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

function Inside(x,y,rsq)
{
  //return 1.7*x*x + 0.6*y*y < rsq;
  return 1.7*x*x + y*y < rsq;
}

function ChooseIcon(mk)
{
  var radius = 4; //[0..7]
  var shade = 6; //[0..18]
  
  age = Math.floor(mk.ts/ 2); //[0..6]
  if(age > 6) age = 6;
  else if(age < 0) age = 0;

  //lighter-darker
  if(mk.price > 0)
  {
    ratio = Math.floor(6 * (filters.cur_max - mk.price)/(filters.cur_max - filters.abs_min));
    shade += ratio;
  }

  radius -= mk.score;
  if(radius < 0) radius = 0;
  if(radius > 7) radius = 7;

  colour = mk.flag == 1 ? "gray" : "blue";
  var icon = sprite(radius, shade, age, colour);
  return icon;
}

function sprite(radius, shade, opacity, colour)
{
  // 8 dot radiuss (11 [0] to 3 px [8])
  if(radius > 8) radius = 8; else if(radius < 0) radius = 0;

  var trunk = '/static/img/' + colour + "/";

  //opacity = 2;
  var px = 11 - radius;
  var image = new google.maps.MarkerImage(trunk + '0' + radius + '_op.png',
  new google.maps.Size(px, px),
  new google.maps.Point(shade*px,opacity*px), //origin
  new google.maps.Point(px/2, px/2)); //anchor

  return image;
}
