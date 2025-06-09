#!/usr/bin/env python3
"""
delete_all_channels.py

21.01.2023 Knut Hallstein - initial

Zum Verbinden des VOLKSZÄHLER mit AHOY müssen in ahoy.yml:
! die benötigten AHOY-Messstellen (=Variablen-Namen aus dem AHOY-Logfile)
  mit einer UUID (aus der VOLKSZÄHLER-Datenbank) verknüpft werden.

Die AHOY-Messstellen sind als CHANNELs in der VOLKSZÄHLER-Datenbank zu 
konfigurieren, hierzu gibt es verschiedene Methoden. Die Rückmeldungen dieser 
Konfigurationsschritte sind UUIDs, welche in der ahoy.yml einzutragen sind. 
Dieser Schritt gilt es zu automatisieren!

Wir gehen davon aus, dass diese Schritte manuell durchgeführt werden. 
Da viele Anwender das hierzu notwendige Wissen erst aufbauen müssen, 
soll dieses Shell-Script unterstützen.

Natürlich passieren hierbei auch Fehler, manchmal entstehen die guten Ideen erst
beim 2. oder 3. Durchlauf. Um die Datenbank von den nicht mehr benötigten (falschen)
CHANNEL Konfigurationen wieder zu befreien, 
können mit diesem Script CHANNEL Konfigurationen wieder GELÖSCHT werden!

  ---  Also VORSICHT!  ---

Beim Aufruf des SCRIPT werden die vorhandenen VZ-Channels angezeigt!
Die Abfrage der VZ-DB erfolgt mit dem Tool "vzclient".
"""

import os, json

print(f"DELETE all VZ channels - START")

# check VZ-Installation-Path 
# VZC = f"{os.environ['HOME']}/volkszaehler.org/bin/vzclient"
VZC = f"/home/volkszaehler/bin/vzclient"
if not os.path.isfile(VZC):
   print(f"ERROR: volkszaehler/bin/vzclient not found - exit")
   exit(9)


# retrieve all channels from VZ-DB
channel_data = os.popen(f"{VZC} get channel")

# channel result is a python-list with one string element only
# this list-element has preceding char >b'< and attached char >'< 
# it looks like a bytearray, but it is a string
# so we have to remove the preceding char >b'< and the attachd char >'<
channel_list = [zeile.strip() for zeile in channel_data]

#convert channel-result to string
channel_str = channel_list[0].replace("b'","",1).replace("'","",1)
# print(channel_str)

# convert string to json object
channel_json = json.loads(channel_str)

# test existing key "channels"
if "channels" not in channel_json:
   print(f"No channels found in VZ-DB - exit")
   exit(8)

for element in channel_json["channels"]:
    print(f"{element['uuid']}   type:{element['type']:15} {element['title']}")

UUID_count = len(channel_json["channels"])
if UUID_count == 0:
   print(f"No channels found in VZ-DB - exit")
   exit(7)
print (f'{UUID_count} UUIDs found in VZ-DB')


x = input(f"Do you want to delete this {UUID_count} UUIDs really? (type 'yes')")
if x != "yes":
    print("Nothing to do - exit")
    exit(6)

print("OK - we delete all channels, now")

for element in channel_json["channels"]:
    print(f"delete: {element['uuid']}")
    delete_result = os.popen(f"{VZC} -u {element['uuid']} delete channel")
    #channel_list = [zeile.strip() for zeile in delete_result]
    print([zeile.strip() for zeile in delete_result])

exit(0)

