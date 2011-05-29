import random
import math
import sql
import time
import datetime
import sys
import os
import update
import buckets
from multiprocessing import Queue

class Schedule:
  max_active = 5    # max concurrently active update processes
  min_pending = 5   # minimum pending urls required before an update can be enqueued
  queue_size = 5    # how many updates to enqueue at a time from pending
  min_respite = 600 # wait at least 15 minutes between updates of the same key
  min_ratio = 0.008  # min starvation ratio before update can be enqueued

  s = None    # sql api
  current = None # currently active updates
  Q = None    # supply queue
  R = None    # result queue
  b = None    # buckets object
  
  def __init__(self):
    self.s = sql.Sql("localhost", "mapster", "tra18wa", "main")
    self.Q = Queue()
    self.R = Queue()
    self.current = list()
    self.B = buckets.Buckets()
    self.recent = dict()

  #retrieves bucket list of historical densities for given update
  def Compute(self, city, mode, source):
    # find the minute duration of the interval since last update was started
    query = "SELECT timestampdiff(minute, started,now()) FROM main.Updates WHERE city = '%s' AND mode = '%s' AND source = '%s' ORDER BY started DESC LIMIT 1" % (city,mode,source)
    interval = self.s.GetScalar(query)
    
    #find minute offset on the 24-hour cycle to match when the last update was started
    query = "SELECT hour(started)*60 + minute(started) FROM main.Updates WHERE city = '%s' AND mode = '%s' AND source = '%s' ORDER BY started DESC LIMIT 1" % (city,mode,source)
    offset = self.s.GetScalar(query)

    query = "SELECT idx, cnt FROM main.Buckets WHERE uId LIKE '%s-%s-%s' ORDER BY idx;" % (city,mode,source)
    table = self.s.GetTable(query)
    total = 0
    if len(table) > 0:
      while interval > 0:
        total += table[offset][1]
        offset = (offset + 1) % 1440
        interval -= 1
    return total
  
  # get list of updates pending, in order of "urgency"
  def RefreshPending(self):
    avgs = {}
    averages = s.s.GetTable("select a.uid, avg(a.cnt) as av from (select concat(city,'-',mode,'-',source) as uid, sum(new) as cnt, date(started) from main.Updates where new > 0 group by city,mode,source, date(started) order by city,mode,source,date(started)) a group by a.uid order by av desc")
    for row in averages:
      avgs[row[0]] = row[1]

    pending = {}
    #cities = self.s.GetList("select name from main.Cities where name in ('montreal', 'hamilton','halifax','guelph', 'kitchener', 'kingston');")
    cities = self.s.GetList("select name from main.Cities where name in ('montreal');")
    for city in cities:
      for mode in ("ap","rm","co"):
        for source in ("kij","cl"):
          waiting = self.Compute(city,mode,source)
          key = "%s-%s-%s" % (city,mode,source)
          if key in avgs:
            ratio = waiting/float(avgs[key])
            if ratio >= self.min_ratio:
              #bprint key,ratio,waiting, float(avgs[key])
              pending[key] = (ratio, int(waiting), int(avgs[key]), city,mode,source)

    #city, days behind, expected postings, avg daily
    self.pending = list()
    for key, value in sorted(pending.iteritems(), key=lambda (k,v): (v,k), reverse=True):
      #return value #return first value (most urgent update)
      #print "add to pending >  %s: %s" % (key, value)
      #u = (value[3],value[4],value[5])
      if value[1] >= self.min_pending:
        self.pending.append(value)

  def Go(self):
    #create worker processes
    updates = list()
    delays = [2.11,2.12,2.13,2.14]
    delays = [2.11,2.12]
    for d in delays:
      u = update.Update(self.Q, self.R, d)
      updates.append(u)
      time.sleep(0.25)
      u.start()
      print u,d

    while True:
      #put a few most urgent updates on the queue, if the previous batch is done
      if self.Q.empty():
        self.RefreshPending()
        for i in range(0,self.queue_size):
          if len(self.pending) > i:
            q = self.pending[i]
            key = '%s-%s-%s' % (q[3],q[4],q[5])
            if key not in self.current:
              enqueue = True
              #check if update ran recently
              if key in self.recent:
                age = time.time() - self.recent[key]
                #if too recently, refuse update
                if age < self.min_respite:
                  enqueue = False
              if enqueue:
                print ' ))) ))  )   ',q
                self.Q.put(q)
                self.current.append(key)

      while not self.R.empty():
        r = self.R.get()
        key = '%s-%s-%s' % (r[0],r[1],r[2])
        if key in self.current:
          self.current.remove(key)
          self.recent[key] = time.time()
          sums = self.B.Compute(r[0],r[1],r[2])
          self.s.Exec("DELETE FROM main.Buckets WHERE uId LIKE '%s'" % key)
          i = 0
          for s in sums:
            query = "INSERT INTO main.Buckets VALUES (%d,%f,'%s')" % (i,s,key)
            self.s.Exec(query)
            i+=1
          print ' <<< <<  <   buckets updated:', r, datetime.datetime.now()

      time.sleep(1)
      

  def InitialImport(self, city_names):
    #create worker processes
    updates = list()
    delays = [2.11,2.12]
    for d in delays:
      u = update.Update(self.Q, self.R, d)
      updates.append(u)
      time.sleep(2)
      u.start()
      print u,d

    cities = self.s.GetList("select name from main.Cities where name in (%s);" % city_names)
    for city in cities:
      for mode in ['ap','rm','co']:
        for source in ['cl','kij']:
          q = (0,0,0,city,mode,source)
          print q
          self.Q.put(q)
    
s = Schedule()
s.Go()
#s.InitialImport("'guelph'")
