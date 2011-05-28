## Copyright 2010 Yasboti Inc
#
#  Generic classified ad parser
#

import sys
import re
sys.path.append("..")
import loader as ld
import sql

def ireplace(string, old, new):
    pattern = re.compile(old, re.IGNORECASE)
    return pattern.sub(new, string)

class Parser:
  _identifying_selector = None    #used to identify downloaded html
  _charset_encoding = None        #encoding used by each parser
  _pages_persist = None           #how many pages of already-known ads to scan
  _city_prefix = None             #what to prepend to the site url
  mode = None                     #apartments, roommates, offices etc

  def __init__(self, city, mode, pages_persist):
    self._city_prefix = city
    self._pages_persist = pages_persist
    self.mode = mode
      
  def GetTitle(self, doc):
    return None

  def GetDate(self, doc):
    return None

  def GetPrice(self, doc):
    return None

  def GetLocation(self, address):
    if None != address and '' != address:
      address = ireplace(address, "quebec", "QC");
      address = ireplace(address, "mtl", "Montreal");
      address = ireplace(address, " pq", " QC");
      address = ireplace(address, "pq ", "QC ");
      address = ireplace(address, " que", " QC");
      address = ireplace(address, "&amp;", "and");
    return address

  def GetDescription(self, doc):
    return None

  def ListPageUrl(self, num):
    return None

  #retrieves ad urls from listing page <num>
  def GetPage(self, url):
    return None

  #parse ad html into categories
  def GetAd(self, doc):
    ad = None
    if None != doc:
      title = self.GetTitle(doc)
      date = self.GetDate(doc)
      price = self.GetPrice(doc)
      address = self.GetLocation(doc)
      desc = self.GetDescription(doc)
      #sys.stderr.write('\n' + str(price) + " (" + address + ") " + repr(title))
      ad = [title, date, price, address, desc, self._source_id, self.mode]
    return ad


  #retrieve ad information at given url
  def LoadAd(self, url):
    ad = None
    root = ld.GetDoc(url, self._charset_encoding)
    if None != root:
      elem = root.cssselect(self._identifying_selector)
      if None != elem and len(elem) > 0:
        ad = self.GetAd(root)
      else:
        print "parser failed to find identifying selector: " + url
    return ad


  def GetAdUrlsOnPage(self, num):
    ad_urls = None
    page_url = self.ListPageUrl(num)
    print page_url
    if None != page_url:
      ad_urls = self.GetPage(page_url)
    return ad_urls
