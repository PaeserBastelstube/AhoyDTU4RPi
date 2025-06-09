
Zum Verbinden von AHOY mit der VOLKSZÄHLER (VZ) Instanz, müssen die 
AHOY-Messstellen (Leistung, Temperatur, Ertrag, Effizienz, ...) als VZ-Channels
konfiguriert werden. Die konfigurierten Channels werden in der VZ-Datenbank 
(VZ-DB) als UUID (Universally Unique Identifier) representiert und müssen 
in der ahoy.yml eingetragen werden.

Die Konfiguration der Channels innerhalb der VOLKSZÄHLER Instanz kann 
interaktiv im Web-Bowser, automatisiert via REST oder auf der Kommandozeile 
mit dem Tool "vzclient" durchgeführt werden.
Die Rückmeldungen (result) dieser Konfigurationen werden als JSON-Strings 
(JSON = JavaScript Object Notation) dargestellt. Diese JSON-Strings enthalten 
die UUIDs, welche in der ahoy.yml einzutragen sind. 
Diese Schritte gilt es zu automatisieren!

Wir gehen davon aus, dass diese Schritte manuell durchgeführt werden. 
Da viele Anwender das hierzu notwendige Wissen erst aufbauen müssen, 
sollen diese Tools unterstützen.

In diesem kleinen Programm erfolgt die Kommunikation mit der VZ-DB mit 
dem Tool "vzclient". Dieses Tool wird mit der VZ-Installation im Verzeichnis 
"bin" bereitgestellt. Weitere Hinweise findet ihr unter:
https://wiki.volkszaehler.org/software/clients/vzclient

"vzclient" benötigt zur Kommunikation mit der VZ-DB eine repräsentierende URL 
(Uniform Resource Locator). Die folgenden beiden (2) Zeilen sind in der 
Konfigurationsdatei $HOME/.vzclient.conf einzutragen.

[default]
url:http://localhost/middleware.php

Bei der Konfiguration der CHANNELs in der VZ-DB passieren manchmal Fehler, die 
besten Ideen entstehen erst beim 2. oder 3. Durchlauf. Um die Datenbank von 
repräsentierendeden nicht mehr benötigten CHANNEL Konfigurationen wieder zu 
befreien, können mit diesem Script ALLE CHANNEL Konfigurationen wieder GELÖSCHT 
werden.  Also VORSICHT!

