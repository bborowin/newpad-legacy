import sql
import time

class Buckets:
  s = None

  def __init__(self):
    self.s = sql.Sql("localhost", "mapster", "tra18wa", "main")

  def Compute(self, city, mode, source):
    key = "%s-%s-%s" % (city,mode,source)
    mc = 1440
    query = "SELECT new, HOUR(started), MINUTE(started), TO_DAYS(started) FROM main.Updates WHERE city = '%s' AND mode = '%s' AND source = '%s'" % (city,mode,source)
    table = self.s.GetTable(query)
    pMin = None
    pNew = None
    pDays = None
    mult = self.s.GetInt("SELECT TIMESTAMPDIFF(DAY, min(started), max(started)) FROM main.Updates WHERE city = '%s' AND mode = '%s' AND source = '%s'" % (city,mode,source))
    print key,'updates:',len(table),'days:',mult
    if 0 == mult:
      mult = 1
    c = None
    sums = []
    for i in range(0, mc):
      sums.append(0.0)
    for row in table:
      #timestamp in minutes since midnight
      cMin = row[1]*60 + row[2]
      #new urls found
      cNew = row[0]
      #days spanned by update
      cDays = row[3]
      if None != pMin:
        offset = cDays - pDays
        #calculate duration in minutes
        span = cMin - pMin + mc * offset
        #calculate average density per bucket for this update
        if 0 < span:
          pb = (cNew / float(span)) / float(mult)
          #accumulate in appropriate buckets
          if cMin < pMin:
            offset -= 1
            for i in range(0, cMin):
              sums[i] += pb
            for i in range(pMin, mc):
              sums[i] += pb
          elif pMin < cMin:
            for i in range(pMin,cMin):
              sums[i] += pb
          #adjust if multiple days spanned
          if offset > 0:
            inc = pb*offset
            for i in range(0,mc):
              sums[i] += inc
            
      #mark as parameters of preceeding update for next loop iteration
      pMin = cMin
      pNew = cNew
      pDays = cDays
    return sums

  def Go(self):
    cities = self.s.GetList("select name from main.Cities")

    for city in cities:
      for mode in ("ap","rm","co"):
        for source in ("kij","cl"):
          key = "%s-%s-%s" % (city,mode,source)
          sums = self.Compute(city,mode,source)
          self.s.Exec("DELETE FROM main.Buckets WHERE uId LIKE '%s'" % key)
          i = 0
          for s in sums:
            query = "INSERT INTO main.Buckets VALUES (%d,%f,'%s')" % (i,s,key)
            self.s.Exec(query)
            i+=1

#b = Buckets()
#b.Go()
