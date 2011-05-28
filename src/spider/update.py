import time
import os
from multiprocessing import Process, Queue
import sql
import importer

class Update(Process):
  
  def __init__ (self, Q, R, delay):
    Process.__init__(self)
    self.pmin = 5
    self.pmax = 20
    self.tmin = 4
    self.Q = Q
    self.R = R
    self.delay = delay
    
  def run(self):
    pid = os.getpid()
    self.s = sql.Sql("localhost","mapster","tra18wa","main")

    while True:
      if not self.Q.empty():
        u = self.Q.get()
        #key = "%s-%s-%s" % (u[3], u[4], u[5])
        print " >>> >>  >   Starting", u
        #query = "INSERT INTO main.Processes VALUES (%d, '%s', NOW())" % (pid, key)
        #s.Exec(query)

        self.Execute(u)
        time.sleep(self.delay)
        
        #query = "DELETE FROM main.Processes WHERE name LIKE '%s'" % key
        #s.Exec(query)
      time.sleep(self.delay)

  def Execute(self, u):
    city = u[3]
    mode = u[4]
    source = u[5]

    started = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
    ts = time.strftime("%H:%M:00", time.localtime())
    
    pages_persist = 1
    i = importer.Importer(city, mode, pages_persist)
    count = i.Update(source, 1)
    
    query = "INSERT INTO main.Updates VALUES ('%s', '%s', '%s', '%s', NOW(), %d);" % (city, mode, source, started, count)
    self.s.Exec(query)

    #print "done", key, ":", count, "new urls found"
    self.R.put((city,mode,source,count))

