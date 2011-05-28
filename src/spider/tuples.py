import sys
sys.path.append("..")

import sql
import nltk
import re
import random

class Process:

  s = None

  def __init__(self, city):
    self.s = sql.Sql("localhost", "mapster", "tra18wa", city)

  # makeVectors: list to n-tuples
  # http://code.activestate.com/lists/python-tutor/74382/
  def mv(self, length, listname):
    vectors = (listname[i:i+length] for i in range(len(listname)-length+1))
    return vectors

  # remove ascii art and most common words
  def CleanText(self, text):
    result = text.lower()
    regex = re.compile('\\xa0|\(|\)|\=')
    result = regex.sub('', result, count=0)
    regex = re.compile(' (a|as|at|in|is|of|on|or|to|and|for|the|from|very|well|with|de|et|pour|le|les|en|au|du|la|avec|un) ')
    l = 0
    while l != len(result):
      l = len(result)
      result = regex.sub(' ', result, count=0)
    del regex
    return result

  # break doc into tuples of tokens, store in db
  def AddTuples(self, id, desc, size):
    wds = nltk.word_tokenize(self.CleanText(desc))
    tuples = self.mv(size, wds)
    count = 0
    new = 0
    for t in tuples:
      tp = "'" + ' '.join(t) + "'"
      r = self.s.GetRow("SELECT id, cnt FROM Tuples WHERE text = " + tp + ";")
      if None == r:
        new += 1
        self.s.Exec("INSERT INTO Tuples VALUES (NULL, " + str(size) + ", 1, " + tp + ");")
        self.s.Exec("INSERT INTO Docs VALUES (" + str(id) + ", last_insert_id());")
      elif 0 == self.s.GetInt("SELECT count(*) FROM Docs WHERE postingId = " + str(id) + " AND tupleId = " + str(r[0]) + ";"):
        self.s.Exec("UPDATE Tuples SET cnt = " + str(r[1]+1) + " WHERE id = " + str(r[0]) + ";")
        self.s.Exec("INSERT INTO Docs VALUES (" + str(id) + ", " + str(r[0]) + ");")
      count += 1
    return (count, new)

  #adds new tuples for the not-yet-processed postings
  def Add(self, processed, size):
    result = self.s.GetTable("SELECT id, description FROM Postings;")
    i = 0
    cnt = str(len(result)-len(processed))
    for ad in result:
      if ad[0] not in processed:
        i += 1
        msg = self.AddTuples(ad[0], ad[1], size)
        ratio = 1
        if 0 < msg[0]:
          ratio = 100.0 * float(msg[1]) / float(msg[0])
          print "%.0f" % ratio + '% original ' + str(size) + '-tuples in ad ' + str(ad[0]) + ' [' + str(i) + '/' + cnt + ']';  
    print i, "new docs"

  # there's no reason not to move this to use IDs instead of URLs
  def GetDuplicates(self, id, n, th):
    dupes = []
    #get tuples for this posting
    current = self.s.GetList("SELECT tupleId FROM Tuples t INNER JOIN Docs d ON d.tupleId = t.id WHERE d.postingId = %d AND t.n = %d;" %(id, n))
    #get ids of all postings which share at least one tuple with current
    related = self.s.GetList("SELECT d1.postingId FROM Tuples t INNER JOIN Docs d ON d.tupleId = t.id INNER JOIN Docs d1 ON t.id = d1.tupleId WHERE d.postingId = %d AND d1.postingId != %d AND t.n = %d AND t.cnt > 1 GROUP BY d1.postingId;" % (id,id,n))
    for id in related:
      #get all tuples for current doc
      tuples = self.s.GetList("SELECT tupleId FROM Tuples t INNER JOIN Docs d ON d.tupleId = t.id WHERE d.postingId = %d AND t.n = %d;" % (id,n))
      cnt = 0
      #check how many tuples occur in both
      for t in current:
        if t in tuples:
          cnt += 1
      lt = len(current)
      #if above preset ratio, consider duplicate
      ratio = float(cnt)/float(lt)
      if ratio > th:
        dupes.append(str(id))
    return dupes

  #marks all similar-enough postings as inactive ("duplicate") and marks most recent as active
  def RemoveDuplicates(self,id,n,th):
    #get ids of postings deemed duplicate
    dupes = self.GetDuplicates(id,n,th)
    #along with current post
    dupes.append(str(id))
    ids = ','.join(dupes)
    #order by date posted
    dupes = self.s.GetList("SELECT id FROM Postings WHERE id IN (%s) ORDER BY date DESC" % ids)
    ids = []
    #leave most recent active, mark remaining as inactive (duplicate)
    if len(dupes) > 1:
      for d in dupes[1:]:
        ids.append(str(d))
      if len(ids) > 0:
        self.s.Exec("UPDATE Postings SET active = 0 WHERE id IN (%s);" % ','.join(ids))
        self.s.Exec("UPDATE Postings SET active = 1 WHERE id  = %d;" % dupes[0])
    #report count of removed
    return (id, len(dupes)-1)

  #not thread-safe!
  #does not work with current db schema
  #def MarkDuplicates(self,url,n,th):
  #  #get ids of postings deemed duplicate
  #  dupes = self.GetDuplicates(url,n,th)
  #  ringId = -1
  #  if len(dupes) > 0:
  #    #get postingId associated with requested url
  #    query = "SELECT id FROM Postings WHERE url LIKE '%" + url + "%';"
  #    dupes.append(str(self.s.GetInt(query)))
  #    query = "SELECT IFNULL(Max(ringId),0) from Duplicates;"
  #    ringId = str(self.s.GetInt(query) + 1)
  #    for d in dupes:
  #      query = "INSERT INTO Duplicates VALUES (" + str(d) + ", " + str(ringId) + ");"
  #      self.s.Exec(query)
  #  print ringId, len(dupes)

  #purges any active duplicates
  def Bump(self, n, th):
    posts = self.s.GetTable("SELECT id, url FROM Postings WHERE active = 1 ORDER BY date;")
    cnt = 0
    new = 0
    i = 0
    pCnt = len(posts)
    for p in posts:
      cnt += 1
      hits = self.RemoveDuplicates(p[0],n,th)
      if 0 < hits[1]:
        i += 1
        print hits[1], 'x', hits[0], "@", cnt, '/', len(posts)


  def ProcessPosting(self, url, n, th):
    post = self.s.GetRow("SELECT id, description FROM Postings WHERE url LIKE '%" + url + "%';")
    print url
    tp = self.AddTuples(post[0], post[1], n)
    dp = self.RemoveDuplicates(post[0], n, th)
    print tp[0], "tuples,", tp[1], "new,", dp[1], "duplicates"
    return dp[1]

#  def CheckOne(self, id, n, th):
#    processed = self.s.GetList("SELECT postingId FROM Docs d INNER JOIN Tuples t ON t.id = d.tupleId WHERE t.n = " + str(n) + " GROUP BY postingId;")
#    print len(processed), "docs present"
#    self.Add(processed, n)
#    p = self.s.GetRow("SELECT id, url FROM Postings WHERE id = " + str(id) + ";")
#    if p[0] not in processed:
#      hits = self.RemoveDuplicates(p[1],n,th)
#      if 0 < hits[1]:
#        print hits[1], 'x', hits[0]
#      else
#        print 'new'
