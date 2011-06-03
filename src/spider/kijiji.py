## Copyright 2010 Yasboti Inc
#
#  Kijiji-specific ad parser
#

import sys
import urllib2
from lxml.html import parse
import time
import unicodedata

sys.path.append("..")
import parser
import sql

class Kijiji(parser.Parser):

  _charset_encoding = 'utf8'
  _identifying_selector = 'h1#preview-local-title'
  _source_id = 1
  lang = 'en'
  #urls = Dict()

  def __init__(self, city, mode, pages_persist):
    parser.Parser.__init__(self,city,mode,pages_persist)
    self.host = "%s.kijiji.ca" % city
    if city in ('montreal','quebec'):
      self.lang = 'fr'
    
    #urls["rmen"] = "http://" + self._city_prefix + ".kijiji.ca/f-housing-room-rental-roommates-W0QQAdTypeZ2QQCatIdZ36"
    #urls["apen"] = "http://" + self._city_prefix + ".kijiji.ca/f-housing-apartments-for-rent-W0QQAdTypeZ2QQCatIdZ37QQmaxPriceZ9000QQminPriceZ0"
    #urls["coen"] = "http://" + self._city_prefix + ".kijiji.ca/kijiji.ca/f-housing-commercial-W0QQCatIdZ40"

    #urls["rmfr"] = "http://" + self._city_prefix + ".kijiji.ca/f-housing-room-rental-roommates-W0QQAdTypeZ2QQCatIdZ36"
    #urls["apfr"] = "http://" + self._city_prefix + ".kijiji.ca/f-housing-apartments-for-rent-W0QQAdTypeZ2QQCatIdZ37QQmaxPriceZ9000QQminPriceZ0"
    #urls["cofr"] = "http://" + self._city_prefix + ".kijiji.ca/kijiji.ca/f-housing-commercial-W0QQCatIdZ40"
    
  def DeUnicode(self, raw):
    #raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'So'))
    #raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Mn'))
    #raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pf'))
    #raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Pd'))
    #raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if unicodedata.category(c) != 'Po'))
    raw = ''.join((c for c in unicodedata.normalize('NFD', unicode(raw)) if ord(c) < 256))
    raw = ' '.join(raw.splitlines())
    return raw.replace("'","''")

  def ListPageUrl(self, num):
    url = None
    if "ap" == self.mode:
      url = "http://" + self._city_prefix + ".kijiji.ca/f-real-estate-apartments-condos-W0QQAdTypeZ2QQCatIdZ37QQmaxPriceZ9000QQminPriceZ0"
    elif "rm" == self.mode:
      url = "http://" + self._city_prefix + ".kijiji.ca/f-real-estate-room-rental-roommates-W0QQAdTypeZ2QQCatIdZ36"
    elif "co" == self.mode:
      url = "http://" + self._city_prefix + ".kijiji.ca/f-housing-commercial-W0QQCatIdZ40"
    if(num > 1):
        url = url + "QQPageZ" + str(num - 1)
    return url

  #retrieves ad urls from listing page <num>
  def GetPage(self, url):
    root = None
    try:
      root = parse(url).getroot()
    except IOError:
      print ">>>>>>>>>>>>>> KIJIJI failed to load listing page: " + url
    #find all the housing ads in list
    if self._city_prefix in ('montreal','quebec'):
      if "rm" == self.mode:
        adUrl = 'immobilier-chambres-a-louer-colocs'
      elif "ap" == self.mode:
        adUrl = 'immobilier-appartements-condos'
      elif "co" == self.mode:
        adUrl = 'immobilier-espaces-commerciaux'
    else:
      if "rm" == self.mode:
        adUrl = 'real-estate-room-rental-roommates'
      if "ap" == self.mode:
        adUrl = 'real-estate-apartments-condos'
      if "co" == self.mode:
        adUrl = 'housing-commercial'

    ads = []
    if None != root:
      for a in root.cssselect('a'):
        href = a.get('href')
        if None != href:
          if -1 != href.find(adUrl):
            if -1 != href.find("AdIdZ"):
              if href not in ads:
                ads.append(href)
    return ads

  #standardize date string
  def FixDate(self, ts):
    ts_ = ts
    if None != ts and 'none' != ts:
      ts = self.DeUnicode(ts)
      ts = ts.lower().replace('.', '')
      #replace french month names with numbers
      ts = ts.replace('janv', '01')
      ts = ts.replace('janv.', '01')
      ts = ts.replace('fevr', '02')
      ts = ts.replace('fevr.', '02')
      ts = ts.replace('mars', '03')
      ts = ts.replace('avril', '04')
      ts = ts.replace('avr', '04')
      ts = ts.replace('avr.', '04')
      ts = ts.replace('mai', '05')
      ts = ts.replace('mai', '05')
      ts = ts.replace('juin', '06')
      ts = ts.replace('juin.', '06')
      ts = ts.replace('juil', '07')
      ts = ts.replace('juil.', '07')
      ts = ts.replace('aout', '08')
      ts = ts.replace('aout.', '08')
      ts = ts.replace('sept', '09')
      ts = ts.replace('sept.', '09')
      ts = ts.replace('oct', '10')
      ts = ts.replace('oct.', '10')
      ts = ts.replace('nov', '11')
      ts = ts.replace('nov.', '11')
      ts = ts.replace('dec', '12')
      ts = ts.replace('dec.', '12')

      #replace english month names with numbers
      ts = ts.replace('jan', '01')
      ts = ts.replace('feb', '02')
      ts = ts.replace('mar', '03')
      ts = ts.replace('apr', '04')
      ts = ts.replace('may', '05')
      ts = ts.replace('jun', '06')
      ts = ts.replace('jul', '07')
      ts = ts.replace('aug', '08')
      ts = ts.replace('sep', '09')
      ts = ts.replace('oct', '10')
      ts = ts.replace('nov', '11')
      ts = ts.replace('dec', '12')

      ts = ts.split('-')
      ts = '20' + ts[2] + '-' + ts[1] + '-' + ts[0]
    else:
      #sys.stderr.write('[date parsing error: "' + ts_ + '" - stored as NULL]')
      ts = None
    if None != ts:
      ts = time.strptime(ts.strip(), '%Y-%m-%d')
    return ts
  
  def GetItems(self, doc):
    table = doc.cssselect('table#attributeTable')[0]
    return table

  def GetTitle(self, doc):
    return doc.cssselect('h1#preview-local-title')[0].text_content()

  def GetDate(self, doc):
    table = self.GetItems(doc)
    ts = table.cssselect('td.first_row')[1].text_content().strip().lower()
    return self.FixDate(ts)

  def GetPrice(self, doc):
    price = 0
    table = self.GetItems(doc)
    item = table.cssselect('strong')
    if len(item) > 0:
      item = item[0].text_content()            
      price = int((item.strip()[1:][::-1][3:][::-1]).replace(',','').replace(' ',''))
    return price

  def GetLocation(self, doc):
    address = None
    table = self.GetItems(doc)
    tds = table.cssselect('td')
    for td in tds:
      if len(td.cssselect('a.viewmap-link')) > 0:
        address = td.text_content().split('\n')[0].strip()
    return parser.Parser.GetLocation(self, address)

  def GetDescription(self, doc):
    return doc.cssselect('span#preview-local-desc')[0].text_content().strip()
