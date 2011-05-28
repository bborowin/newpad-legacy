## Copyright 2010 Yasboti Inc
#
#  Craigslist-specific ad parser
#

import sys
import re
import time
from lxml.html import parse
import cookielib

sys.path.append("..")
import parser
import address as ad

class Craigslist(parser.Parser):

  _charset_encoding = 'latin1'
  _identifying_selector = 'body.posting'
  _source_id = 2

  def __init__(self, city, mode, pages_persist):
    parser.Parser.__init__(self,city,mode,pages_persist)
    #self.cookie = CreateCookie(city)
    #self.cookie = "cl_b=1298049630187362743591954208; cl_def_hp=montreal; cl_def_lang=en"
    self.cookie = "cl_def_hp=%s; cl_def_lang=en" % city
    self.host = "%s.en.craigslist.ca" % city
    #self.host = "%s.craigslist.org" % city
    
  def CreateCookie(self,city):
    c = Cookie.SimpleCookie()
    exp = (datetime.datetime.now() + datetime.timedelta(365)).strftime('%a, %d %b %Y %H:%M:%S')
    # cl_b -- what is it?
    c["cl_b"]="1298049630187362743591954208"
    c["cl_b"]["path"]="/"
    c["cl_b"]["domain"]=".craigslist.org"
    c["cl_b"]["expires"]="Fri, 01 Jan 2038 00:00:00 GMT"

    # default home page
    c["cl_def_hp"] = city
    c["cl_def_hp"]["path"] = "/"
    c["cl_def_hp"]["domain"] = ".craigslist.org"
    c["cl_def_hp"]["expires"] = exp

    # default language
    c["cl_def_lang"] = "en" #todo:this might break montreal's defaulf french?
    c["cl_def_lang"]["path"] = "/"
    c["cl_def_lang"]["domain"] = ".craigslist.org"
    c["cl_def_lang"]["expires"] = exp

    self.cookie = c
    
  def ListPageUrl(self, num):
    url = None
    if "ap" == self.mode:
      url = "http://" + self._city_prefix + ".en.craigslist.ca/apa/"
      #url = "http://" + self._city_prefix + ".craigslist.org/aap/"
    elif "rm" == self.mode:
      url = "http://" + self._city_prefix + ".en.craigslist.ca/roo/"
      #url = "http://" + self._city_prefix + ".craigslist.org/roo/"
    elif "co" == self.mode:
      url = "http://" + self._city_prefix + ".en.craigslist.ca/off/"
    if(num > 1):
        url += "index" + str(num) + "00.html"
    return url

  #retrieves ad urls from listing page <num>
  def GetPage(self, url):
    ads = []
    root = None
    #identify individual ads pages
    try:
      root = parse(url).getroot()
    except IOError:
      sys.stderr.write('[ IOError : wait a little bit .. ]')
      time.sleep(30)
      root = parse(url).getroot()
    if None != root:
      links = root.cssselect('a')
      for link in links:
        href = link.xpath('@href')
        href = str(href)[2:][::-1][2:][::-1]
        if href.find(self._city_prefix) > -1 and href.find('.html') > -1 and href.find('http') > -1:
          ads.append(href)
    return ads

  def GetTitle(self, doc):
    title = doc.cssselect('h2')[0].text_content()
    return title

  def GetLocation(self, doc):
    res = []
    loc=[]
    for elem in doc.iter():
      if not isinstance(elem.tag, basestring):
          loc.append(elem.text)
    for line in loc:
      try:
        if (line.find('street') > -1) or (line.find('city') > -1) or (line.find('region') > -1):
          val = line[line.index("=")+1:][::-1][1:][::-1]
          res.append(val)
        elif (line.find('Geographic') > -1):
          val = line[line.index("=")+1:][::-1][1:][::-1]
          res.append(val)
      except ValueError:
        continue
    try:
      loc = ad.ParseLocation(res)
    except IndexError:
      if len(res) > 0:
        loc = res[0]
      else:
        loc = ''
    return parser.Parser.GetLocation(self, loc)

  def GetDate(self, doc):
    try:
      date = None
      txt = doc.cssselect('body')[0].text_content()
      idx = txt.find('Date: ')
      if idx > 0:
        idx += 6
        date = txt[idx:idx+20]
      if None != date:
        return time.strptime(date.strip(), '%Y-%m-%d,  %I:%M%p')
    except ValueError:
      print "GetDate failed on %s" % date
    return None

  def GetPrice(self, doc):
    price = 0
    txt = doc.cssselect('h2')[0].text_content()
    if txt.find("$") > -1:
      try:
        txt = ''.join([x for x in txt if ord(x) < 128])
        beg = txt.index('$')
        end = txt[beg:].index(' ')+beg
        price = txt[beg+1:end]
        price = float(price)
      except ValueError:
        try:
          end = beg
          beg = txt.index(' ')
          price = txt[beg:end]
          price = float(price)
        except ValueError:
          print '[price parse fail on: ' + str(txt) + ']'
        price = 0
    return price

  def GetDescription(self, doc):
    descr = ''
    doc = doc.cssselect('div#userbody')
    if None != doc and len(doc) > 0:
      doc = doc[0]
      for elem in doc.iter():
        if repr(elem.tag) == '<built-in function Comment>':
          break
        if elem.text != None:
          descr += elem.text
        if elem.tail != None:
          descr += elem.tail
    return descr.strip()
