#   parses address strings into structured information

import re
#1230 Docteur Penfield and Drummond Street

def ParseLocation(items):
    address = None
    if None != items:
        rue1 = items[0]
        rue2 = items[1]
        city = items[2]
        prov = items[3]

        ex = re.compile('[0-9]+.*')
        if None != ex.search(rue1):
            address = rue1 + ", " + city + ", " + prov
        elif None == rue2 or len(rue2.strip()) == 0:
            address = rue1 + ", " + city + ", " + prov
        else:
            rues = None
            if rue2.find(' and ') > 0:
                rues = rue2.split(' and ')
            if rue2.find(' et ') > 0:
                rues = rue2.split(' et ')
            if rue2.find(' & ') > 0:
                rues = rue2.split(' & ')
            if rue2.find(' at ') > 0:
                rues = rue2.split(' at ')
            if None == rues:
                address = rue1 + " and " + rue2 + ", " + city + ", " + prov
            else:
                address = rue1 + " and " + rues[0] + ", " + city + ", " + prov
    return address
