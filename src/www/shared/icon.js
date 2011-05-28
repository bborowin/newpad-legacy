// functionality facilitating marker icons

function GetIconColour(price)
{
  var number = 1;
  var highest = $("#slider").slider("values", 1); //max allowed price
  var lowest = $("#slider").slider("values", 0); //min allowed price

  var step = (highest - lowest)/19.0;

  var diff = price - lowest;
  number +=  Math.floor(diff/step);
  number = number > 19 ? 19 : number;

  return 19 - number;
}

//establish icon size according to age
function GetIconSize(days)
{
  var size;

  var adj = Math.floor(days/2)
  size = adj > 8 ? 8 : adj;

  return size;
}

function sprite(colour, size, mk)
{
  var px = 11 - size;
  var trunk = 'img/';

  switch(mk.flag)
  {
    case "0":
      if(mk.attr.indexOf("top floor") != -1 || mk.attr.indexOf("third floor") != -1)
      {
        trunk += 'red/';
      }
      else if(mk.attr.indexOf("second floor") != -1)
      {
        trunk += 'orange/';
      }
      else if(mk.attr.indexOf("first floor") != -1)
      {
        trunk += 'yellow/';
      }
      else if(mk.attr.indexOf("basement") != -1)
      {
        trunk += 'purple/';
      }
      else
      {
        trunk += 'blue/';
      }
      break;
    case "1":
      trunk += 'gray/';
      break;
    case "2":
      trunk += 'green/';
      break;
    default:
      trunk += 'blue/';
      break;
  }

  //size = 1;
  var image = new google.maps.MarkerImage(trunk + '0' + size + '.png',
  new google.maps.Size(px, px),
  new google.maps.Point(colour*px,0), //origin
  new google.maps.Point(px/2, px/2)); //anchor

  return image;
}

function ChooseIcon(mk)
{
  var days = Math.floor(mk.ts);
  var size = GetIconSize(days)
  
  var colour = GetIconColour(mk.price)

  alert("price:" + mk.price);
  var icon = sprite(colour, size, mk);

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

