#   Copyright 2010 Yasboti Inc
#
#    Retrieval of geocoding information
#

import sql
import yahoopf
import gmaps
import time
import unicodedata
from guppy import hpy
import os

class Geocoder:

  s = None
  cities = None
  ts = 0.0
  ts_max = 30.0
  ts_term = 95.0

  def __init__(self):
    self.s = sql.Sql("localhost", "mapster", "tra18wa", "main")
    self.GetCities()

  # check whether another geoloc process is alive
  def CanStart(self):
    age = self.s.GetRow("SELECT TIMESTAMPDIFF(SECOND,ts,now()) AS age, pid FROM Processes WHERE name LIKE 'geoloc';")
    if None == age:
      print "No geocoder running: go ahead!"
      return True
    # if stamp has not been touched in 90 seconds, assume process has stalled and kill it
    if age[0] > self.ts_term:
      try:
        os.kill(age[1], 9)
      except OSError, e:
        if 3 == e.errno:
          print "Found a geocoder fossil, long dead. Go ahead!"
          return True
        else:
          print "Found an old geocoder process, but couldn't terminate it!"
          return False
    else:
      print "Geocoder already running"
    return False

  def MarkStarted(self):
    # remove old process stamp
    self.s.Exec("DELETE FROM Processes WHERE name LIKE 'geoloc';")
    # mark this process as active
    self.s.Exec("INSERT INTO Processes VALUES (%d, 'geoloc', NOW())" % os.getpid())
    # mark start time
    self.ts = time.time()
    print "Marking process start"

  def TouchProcess(self):
    self.s.Exec("UPDATE Processes SET ts = NOW() WHERE name = 'geoloc' AND pid = %d" % os.getpid())
    self.ts = time.time()
    #print "Touch process"

  def Terminate(self):
    self.s.Exec("DELETE FROM Processes WHERE name LIKE 'geoloc';")
    print "Terminating process"
    
  # get all applicable cities
  def GetCities(self):
    query = "SELECT name FROM main.Cities;"
    self.cities = self.s.GetList(query)

  def Process(self):
    for city in self.cities:
      self.EncodeCity(city)
      
  def EncodeCity(self, city):
    addresses = self.s.GetTable("SELECT id, address FROM %s.Locations WHERE accuracy = -1;" % city)
    had = 0
    added = 0
    if len(addresses) > 0:
      print city, len(addresses), "addresses to encode"
    for addr in addresses:
      lId = 0
      if None != addr[1] and '' != addr[1].strip():
        lId = self.HaveAddress(city, addr)

      if None != lId:
        #we have it already -- delete the addr location, relate the Posting to old location
        self.s.Exec("DELETE FROM %s.Locations WHERE id = %d;" % (city, int(addr[0])))
        self.s.Exec("UPDATE %s.Places SET locationId = %d WHERE locationId = %d;" % (city, lId, addr[0]))
        #print "  Had", addr[1], "at", lId
        had += 1
      else:
        #need to look up the lat/lon pair (use google = 1 for now)
        coords = self.Coordinates(1, addr[1])
        #store the geo data
        if coords[2] != -1:
          self.s.Exec("UPDATE %s.Locations SET lat = %f, lon = %f, accuracy = %d WHERE id = %d" % (city, coords[0], coords[1], coords[2], addr[0]))
          print "Added", addr[1], "as", coords
          added += 1
        else:
          self.s.Exec("DELETE FROM %s.Locations WHERE id = %d;" % (city, int(addr[0])))
          self.s.Exec("UPDATE %s.Places SET locationId = 0 WHERE locationId = %d;" % (city, addr[0]))
        time.sleep(0.1)
      if time.time() - self.ts > self.ts_max:
        self.TouchProcess()

    if had+added > 0:
      print city, "had:", had
      print city, "added:", added
    count = len(addresses)
    del addresses
    return count
    
  #check if we already encoded this address
  def HaveAddress(self, city, addr):
    query = "SELECT id FROM %s.Locations WHERE address LIKE '%s' AND id != %d AND accuracy != -1;" % (city, addr[1].replace("'","''"), int(addr[0]))
    return self.s.GetInt(query)

  #retrieve (lat,long) pair
  def Coordinates(self, coder, address):
    accuracy = -1
    lat = 0
    lon = 0
    try:
      if(None != address and '' != address):
        address = self.PrepTextLine(address)
        if(1 == coder):
          coords = gmaps.location2latlong(address)
        elif(2 == coder):
          coords = yahoopf.location2latlong(address)
        if None != coords and None != coords[0] and None != coords[1] and None != coords[2] and '' != coords[0] and '' != coords[1] and '' != coords[2]:
          lat = float(coords[0])
          lon = float(coords[1])
          accuracy = int(coords[2])
    except:
      print "@@@@@@@@@@@@@@@@@@@@@@@@ Geocoder fail on ", address
    return (lat, lon, accuracy)

  def PrepTextLine(self, raw):
    raw = raw.decode("latin1", "ignore")
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'So'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Mn'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pf'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pd'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Po'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Cc'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Sk'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if ord(c) < 256))
    raw = ' '.join(raw.splitlines())
    return raw.replace("'","''")

g = Geocoder();

if g.CanStart():
  g.MarkStarted()
  while True:
    count = 0
    for city in g.cities:
      count += g.EncodeCity(city)
    if(0 == count):
      time.sleep(g.ts_max)
      g.TouchProcess()
    else:
      time.sleep(3)
    g.cities = None
    g.GetCities()

  g.Terminate()
