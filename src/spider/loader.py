## Copyright 2010 Yasboti Inc
#
#  Handles obtaining and decoding of classifieds' pages

import sys
import time
import socket
import unicodedata
import urllib2
from lxml.html import parse
from lxml.html import fromstring
from urllib2 import URLError

#remove accents from text
def strip_accents(s):
    return ''.join((c for c in unicodedata.normalize('NFD', s) if unicodedata.category(c) != 'Mn'))

#retrieve page html from server
def GetDoc(url, encoding):
    root = None
    attempts = 1
    while None == root and attempts < 3:
        try:
            #time.sleep(0.95) we might not need this delay
            raw = urllib2.urlopen(url).read()
            clean = unicode(raw, encoding)
            root = fromstring(clean)
            if None != root:
                return root
            sys.stderr.write("[hiccup : go nap]\n")
        except URLError:
                sys.stderr.write("[UrlError : we best lay off a bit .. ]\n")
        except socket.error:
                sys.stderr.write("[socket.error : we best chill a bit .. ]\n")
        except:
                sys.stderr.write("[unknown error : discarding this post .. ]\n")
                return None

        time.sleep(5)
        attempts += 1

    time.sleep(1.5)
    return root
