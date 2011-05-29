# yahoo placefinder -- alternate geocoder
# emergency implementation

# api key: JnjvbF3V34H6HtxY6aq2rHru2zu_3bcmLX4W5HZ6S2lWrJJ5NAarSm7zWL58Lg--

import sys
import urllib
from time import sleep
from xml.dom import minidom

key = "JnjvbF3V34H6HtxY6aq2rHru2zu_3bcmLX4W5HZ6S2lWrJJ5NAarSm7zWL58Lg--"

def location2latlong( query, key=key ):

  query = query.strip()
  
  if '' == query:
    return (0,0,0)
    
  params = { }
  params[ 'q' ] = query
  params[ 'appid' ] = key

  params = urllib.urlencode( params )

  try:
    f = urllib.urlopen("http://where.yahooapis.com/geocode?%s" % params)
  except IOError:
    sleep( 3 )
    try:
      f = urllib.urlopen("http://where.yahooapis.com/geocode?%s" % params)
    except IOError:
      # okay we give up
      return None
          
  dom = minidom.parse(f)
  err = dom.getElementsByTagName('Error')
  status = "0"
  if 1 == len(err):
    status = err[0].childNodes[0].nodeValue
  if "0" == status:
    found = dom.getElementsByTagName('Found')
    if 1 == len(found) and 0 < int(found[0].childNodes[0].nodeValue):
      for node in dom.getElementsByTagName('Result'):
        accuracy = int(node.getElementsByTagName('quality')[0].childNodes[0].nodeValue)
        latitude = float(node.getElementsByTagName('latitude')[0].childNodes[0].nodeValue)
        longitude = float(node.getElementsByTagName('longitude')[0].childNodes[0].nodeValue)
        break
    else:
      accuracy = 0
      latitude = 0
      longitude = 0

  if status != '0':
    print 'Geocoder error (!?!)'
    print status
    if status != '1012':
      sys.exit(0)
  else:
    sleep(0.1)
    ret = (latitude, longitude, accuracy)
    print ret
    if accuracy == 90:
      accuracy = 0
    elif accuracy > 79:
      accuracy = 8
    elif accuracy > 69:
      accuracy = 7
    elif accuracy > 50:
      accuracy = 5
    else:
      accuracy = 0
    ret = (latitude, longitude, accuracy)
    return ret
