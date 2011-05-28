## Copyright 2010-2011 Yasboti Inc
#
#  sql.py
#
#  Generic class encapsulating MySql interaction methods
#

import MySQLdb
import sys
from datetime import datetime
import time

class Sql:

  c = None

  def __init__(self, hostname, username, password, database):
    self.c = MySQLdb.Connect(host = hostname, user=username, passwd=password, db=database)

  #execute a result-less query
  def Exec(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)

  #execute an insert query
  def Insert(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)
    return self.c.insert_id()

  #query for integer value
  def GetInt(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)
    result = cursor.fetchone()
    if None != result:
      return result[0]
    return None

  #query for integer value
  def GetScalar(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)
    result = cursor.fetchone()
    if None != result:
      return result[0]
    return None

  #query for a list of scalar values
  def GetList(self, query):
    result = []
    cursor = self.c.cursor()
    cursor.execute(query)
    table = cursor.fetchall()
    for row in table:
      result.append(row[0])
    return result
    
  #query for a single row of values
  def GetRow(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)
    return cursor.fetchone()

  #query for a table of values
  def GetTable(self, query):
    cursor = self.c.cursor()
    cursor.execute(query)
    return cursor.fetchall()

  def GetPosting(self, url):
    query = "SELECT date, price, address, description FROM Postings WHERE url = '" + url + "'"
    cursor = self.c.cursor()
    cursor.execute(query)
    result = cursor.fetchone()
    return result

