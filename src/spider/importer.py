#!/usr/bin/python
# -*- coding: latin-1 -*-

#   Copyright 2010 Yasboti Inc
#
#    Retrieval of housing rental ads
#

import sys
from datetime import datetime
import unicodedata
import time
import re
sys.path.append("..")
import sql
import kijiji
import craigslist
sys.path.append("../process")
import tuples
from multiprocessing import Process, Queue

class Importer:

  s = None
  city = None
  pages_persist = None
  mode = None
  site = None
  p = None

  def __init__(self, city, mode, pages_persist):
    self.s = sql.Sql("localhost", "mapster", "tra18wa", city)
    self.city = city
    self.mode = mode
    self.pages_persist = pages_persist
    self.p = tuples.Process(city)

  def GetParser(self, site):
    self.site = site
    if "kij" == site:
        return kijiji.Kijiji(self.city, self.mode, self.pages_persist)
    elif "cl" == site:
        return craigslist.Craigslist(self.city, self.mode, self.pages_persist)
    return None

  def PrepTextLine(self, raw):
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'So'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Mn'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pf'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pd'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Po'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Cc'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if ord(c) < 256))
    raw = ' '.join(raw.splitlines())
    return raw.replace("'","''")
      
  def HasPosting(self, url):
    query = "SELECT id FROM Postings WHERE url LIKE '" + url + "'"
    id = self.s.GetScalar(query)
    if None == id:
      return -1
    return id;

  def AddPosting(self, url, ad):
    result = -1
    if None == ad:
      query = "UPDATE Postings SET active = 0, ts = NOW() WHERE url = '" + url + "';"
      self.s.Exec(query)
      cursor.execute(query, url)
      query = "SELECT id FROM Postings WHERE url = %s;"
      result = self.s.GetInt(query)
    else:
      title = self.PrepTextLine(ad[0])
      date = datetime.fromtimestamp(time.mktime(ad[1]))
      price = int(ad[2])
      if None == ad[3]:
        address = ''
      else:
        address = self.PrepTextLine(ad[3])
      desc = self.PrepTextLine(ad[4])
      source = ad[5]
      lang = -1   #posting language unknown at this point
      mode = -1
      if "rm" == ad[6]:
        mode = 0
      elif "ap" == ad[6]:
        mode = 1
      elif "co" == ad[6]:
        mode = 2
      query = "INSERT IGNORE INTO Postings VALUES (NULL, '%s', '%s', %d, '%s', '%s', 1, '%s', DEFAULT, %d, %d, %d);" % (url, date, price, address, desc, title, source, lang, mode)
      result = self.s.Insert(query)
    #return resulting postings.id
    if None == result:
      return -1
    return result

  def AddPlace(self, postingId, locationId):
    query = "INSERT IGNORE INTO Places VALUES (%d, %d);" % (locationId, postingId)
    self.s.Exec(query)

  def ImportAds(self, parser, page):
    ads = parser.GetAdUrlsOnPage(page)
    count = 0
    net_wait = 0
    cpu_wait = 0
    if None != ads:
      for url in ads:       
        postingId = self.HasPosting(url)
        if -1 == postingId:
          res = self.GetAd(parser, url)
          if None != res:
            count += 1
            net_wait += res[0]
            cpu_wait += res[1]
          else:
            print '--------------------------------------- bollocks'
    key = "%s-%s-%s" % (self.city, self.mode, self.site)
    if count > 0:
      msg = '%s: avg net wait: %f; avg cpu wait: %f' % (key, net_wait/count, cpu_wait/count)
      query = "INSERT INTO main.Processes VALUES (%d, '%s', NOW())" % (6, msg)
      self.s.Exec(query)
      print msg    
    return (count, net_wait, cpu_wait)
    
  def GetAd(self, parser, url):
    net_wait = 0
    cpu_wait = 0
    net_started = time.time()
    ad = parser.LoadAd(url)
    net_wait += time.time() - net_started
    if None != ad:
      cpu_started = time.time()
      dateStr = None
      try:
        dateStr = datetime.fromtimestamp(time.mktime(ad[1]))
      except:
        ad[1] = (1999, 12, 31, 23, 59, 59, 0, 0, 0)
        dateStr = datetime.fromtimestamp(time.mktime(ad[1]))
      print repr(ad[2]), repr(ad[3]), repr(ad[0]), dateStr
      postingId = self.AddPosting(url, ad)
      if None != ad[3]:
        query = "INSERT INTO Locations VALUES (NULL, '%s', 0.0, 0.0, -1, NULL, -1);"
        query = query % ad[3].replace("'","''")
        locationId = self.s.Insert(query)
        self.AddPlace(postingId, locationId)

      #compute tuples & docs to determine duplicates
      self.p.ProcessPosting(url, 4, 0.075)
      #extract knowable attributes
      attr = self.GetAttributes(url, ad)
      if None != attr and '' != attr:
        query = "INSERT INTO Attributes VALUES (%d, '%s');" % (postingId, attr)
        self.s.Insert(query)
        print attr
        
      cpu_wait += time.time() - cpu_started

      print net_wait, cpu_wait
      return(net_wait, cpu_wait)

  def MaxPage(self, site):
      return {'kij': 1000, 'cl': 250}.get(site, 10)

  def Update(self, site, start):
    maxp = self.MaxPage(site)
    blanks = 0
    total = 0
    parser = self.GetParser(site)
    for page in range(start, maxp):
      print "-------------------- --- -- --  ", site, self.city, self.mode, datetime.now(), " (pg", page, ")  - -- --- --------------------"
      result = self.ImportAds(parser, page)
      total += result[0]
      if result[0] < 1:
        blanks += 1
      else:
        blanks = 0
      if not (blanks < parser._pages_persist):
        break
    return total
  
  def GetSize(self,url,ad): #this doesn't belong in this class.. put this in Parser(?)
    size = None
    url = url.lower()

    if 'kijiji' in url:
      key = 'c-immobilier-appartements-a-louer-'
      if key in url:
        idx = url.find(key)+len(key)
        size = url[idx:idx+6] # find the n-1-2 format (eg, 2-1-2 for a deux-et-demi)
        if None != size:
          bits = size.split('-')
          lead = bits[0]
          if '1' == lead:
            size = '0'
          elif lead in ['2','3']:
            size = '1'
          else:
            size = str(int(lead) - 2)
        return size

      key = 'c-housing-apartments-for-rent-'
      if key in url:
        idx = url.find(key)+len(key)
        size = url[idx:idx+8] # find the n-1-2 format (eg, 2-1-2 for a deux-et-demi)
        if None != size:
          bits = size.split('-')
          if 'bachelor' == bits[0]:
            size = '0'
          else:
            size = bits[0]
        return size

    elif 'craigslist' in url:
      title = ad[0].lower()
      #p = re.compile('(\$[0-9]+ )+ +[0-9]+br +') ## todo ## take this outside the loop
      p = re.compile('.*[0-9]+ *br *') ## todo ## take this outside the loop
      if p.match(title):
        idx = title.find('br ')-1
        size = title[idx:idx+1]
      else:
        p = re.compile('.*[0-9]+ +1[/]*2 +') ## todo ## take this outside the loop
        if p.match(title):
          idx = title.find(' 12 ')
          if idx < 0:
            idx = title.find(' 1/2 ')
          size = title[title[:idx].rfind(' '):idx].strip()
          if size.isdigit():
            lead = size
            if '1' == lead:
              size = '0'
            elif lead in ['2','3']:
              size = '1'
            else:
              size = str(int(lead) - 2)
        elif title.find('bachelor') > -1 or title.find('studio') > -1:
          size = '0'

      return size
      
  def GetSqFt(self,url,ad):
    #try to match square footage in english and french
    #en = re.compile('[0-9]+ *s[quared\.]* *f[eot\.]')
    en = re.compile('[0-9]+ +s(q)*(uare)d* *f(ee|oo)t* +')
    #fr = re.compile('[0-9]+ *p[ieds\.]* *c[ares\.]')
    fr = re.compile('[0-9]+ +p((ie)*d)*s* +c(a(r+e+s*)*)* +')
    #exceptions:
    # 4 pièces
    # terrain de 10000 pied carré

    m = None
    for l in [en,fr]:
      for t in [ad[0], ad[4]]:
        m = l.search(t)
        if None != m:
          sqft = m.string[m.start(0):m.end(0)]
          sf = re.compile('[0-9]+')
          m = sf.search(sqft)
          if None != m:
            result = m.string[m.start(0):m.end(0)]
            return result
    return None
    
  def GetFloor(self, ad):
    #ad: title,date,price,address,desc
    attributes = []
    #simple attributes
    attributes.append(("0", ['semi-sous-sol', 'basement bachelor', 'basement one bedroom', 'clean basement', 'lower level', 'basement one bedroom apartment', 'furnished basement', 'semi-basement', 'basemant', 'half basement', 'half-basement', 'bedroom basement', 'basement suite', 'basement suit', 'basement ste', 'basement apartment', 'basement apt', 'basment apartment', 'basment apt', 'basment suite', 'basment ste', 'basemt apartment', 'basemt apt', 'basemt suite', 'basemt ste', 'bsmnt apartment', 'bsmnt apt', 'bsmnt suite', 'bsmnt ste', 'bsmt apartment', 'bsmt apt', 'bsmt suite', 'bsmt ste', 'semi sous-sol', 'semi sous sol', 'semi soussol', 'demi sousol', 'demi soussol', 'demi sous sol', 'demi sous-sol', 'demisousol', 'demisoussol', 'demisous sol', 'demisous-sol', 'semi basement', 'semi bsmt']))
    attributes.append(("1", ['ground floor suite', 'au r-d-c d\'un', 'r-d-c d un duplex', '1er plancher', '1er etage', 'premier etage', '1e etage', '1er etage', 'premier etage', 'rez-de-chaussee', 'rez-du-chaussee', 'rez-de-chausse', 'rez de chaussee', '1st floor', 'first floor', 'ground floor', 'main floor']))
    attributes.append(("2", ['2 iem', '2ieme', '2eme', '2 eme', '2ieme', '2iem', '2em', '2 em', '2e', 'second floor', '2nd floor']))
    attributes.append(("999", ['deuxieme etage d\'un duplex', '2ieme etage d\'un duplex', 'penthouse', 'haut de', 'derniere etage', 'dernier etage', 'top floor', 'last floor', 'upper level', 'upper duplex', 'attic']))

    #attributes.append(("first floor", ['1er étage', 'premier étage', '1e etage', '1er etage', 'premier etage', 'rez-de-chaussée', 'rez-de-chaussee', 'rez-de-chausse', '1st floor', 'first floor', 'ground floor']))
    #attributes.append(("second floor", ['2 iem', '2ième', '2ème', '2 ème', '2ieme', '2eme', '2 eme', '2iem', '2em', '2 em', '2e', 'second floor', '2nd floor']))
    #attributes.append(("top floor", ['penthouse', 'haut de', 'derniere étage', 'dernier etage', 'top floor', 'last floor', 'upper level', 'upper duplex', 'attic']))

    #example of compound attribute
    left = ["troisieme", "troixieme", "3 iem", "3ieme", "3 ieme", "3eme", "3 eme", "3iem", "3em", "3 em", "3e", "3rd", "3ird", "3 rd", "third"]
    #left = ["troisième", "troisieme", "troixième", "troixieme", "3 iem", "3ième", "3ème", "3 ème", "3ieme", "3eme", "3 eme", "3iem", "3em", "3 em", "3e", "3rd", "3ird", "3 rd", "third"]
    right = ["etage", "floor", "level", "story", "storey", "niveau"]
    #right = ["etage", "étage", "floor", "level", "story", "storey"]
    both = []
    for l in left:
      for r in right:
        both.append('%s %s' % (l,r))
    attributes.append(("3", both))
    #exceptions:
    # laundry room on 3rd floor
    # Grands 4 1/2 à louer, 1er étage et 3e étage d'un six logements,
    # au 2ième d un duplex.
    # the 4th Floor
    
    # also could expand to things like
    # On the 3rd floor of a 3 story building.
    
    # also this bullshit:
    # looking for a female
    # NOTE: The price of each room is $675   #price was given $650 for 2 bedroom BUT it's actually two rooms for rent @ $650
    
    #get level/floor if priovided (in description / title)
    level = None
    for a in attributes:
      for a_val in a[1]:
        if ad[4].find(a_val) > -1 or ad[0].find(a_val) > -1:
          level = a[0]
          break
      if None != level:
        break
    if None == level:
      level = "-1"
      
    return level


  def GetAttributes(self,url, ad):
    matches = []
    
    #get size / bedroom count
    size = self.GetSize(url,ad)
    if None != size:
      matches.append(size)
    else:
      matches.append("-1")
      
    #get area
    sqft = self.GetSqFt(url,ad)
    if None != sqft:
      matches.append(sqft)
    else:
      matches.append("-1")
    
    #get floor/level
    matches.append(self.GetFloor(ad))
    
    #return all attributes contcatenated into one string
    result = ', '.join(matches);
    return result

  def SetAllAttributes(self,city):
    #ad: title,date,price,address,desc
    query = "SELECT id, url, description, title FROM %s.Posts" % city
    ids = self.s.GetTable(query)
    print len(ids)
    idx = 0
    for i in ids:
      attr = self.GetAttributes(i[1],[i[3],None,None,None,i[2]])
      if None != attr and '' != attr:
        query = "INSERT INTO %s.Attributes VALUES (%s, '%s')" % (city, i[0],attr)
        self.s.Insert(query)
        idx = (idx + 1) % 1000
        if idx == 0:
          print '.'
          
