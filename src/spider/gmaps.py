#
# based on : http://www.megasolutions.net/python/google-maps-api-for-py_-78670.aspx
#
#  used to retrieve latitude/longitude associated with an address (via google maps api)

import sys
import urllib
from time import sleep

key = "BQIAAAA6hOLU1bkvOi-fZadsQJG8hQ9LXX-QqSpcWug2XD7MA_Eoc-UmBQcum01W8I0uUvYAUTkAX_k-AQ-XA"

def location2latlong( query, key=key ):

    output = 'csv'

    params = { }
    params[ 'key' ] = key
    params[ 'output' ] = output
    params[ 'q' ] = query

    params = urllib.urlencode( params )

    try:
        f = urllib.urlopen( "http://maps.google.com/maps/geo?%s" % params )
    except IOError:
        # maybe some local problem at google? let's try again
        sleep( 3 )
        try:
            f = urllib.urlopen( "http://maps.google.com/maps/geo?%s" % params )
        except IOError:
            # okay we give up
            return None

    response = f.read( ).split(',')
    f.close( )

    try:
        status = response[0]
        accuracy = response[1]
        latitude = response[2]
        longitude = response[3]
    except:
        return None

    if status != '200':
        if '620' == status:
            print 'Geocoder query limit reached (!!!)'
            sys.exit(0)
        return status
    else:
        sleep(0.2)
        return (latitude, longitude, accuracy)
