# Auswahl des Verfahrens zur Datenübertragung in einem spezifischen Projekt

## Einleitung
Das zu entwickelnde Projekt besteht aus den folgenden Bestandteilen, welche als unabhängige Prozesse agieren:
1. Konfiguration und Überwachung der System- u. Projektparameter in einer Web-GUI (NGINX-WebServer mit Hilfe sequenzieller PHP-FPM Prozesse) 
1. Einlesen der Sensordaten, Ermittlung der Betriebsparameter, Überwachung, Fehlererkennung und Logging (Python-Prozess)
1. Darstellung der Sensor-Betriebsdaten und Log-Files in der Web-GUI (NGINX WebServer mit Hilfe sequenziellen PHP-FPM Prozesse) 
1. Konfiguration der Sensor-Betriebsparameter in der Web-GUI (PHP-FPM Prozesse)
1. Steuerung spezifischer Betriebsparameter der Sensoren (Python Prozess)

## Aufgaben der Datenübertragung im Projekt
* Übertragung statischer Konfigurationsdaten aus der Web-GUI (PHP-FPM Prozesse) an die Sensor-Steuerung (Python-Prozess)
* Übertragung der Sensor-Betriebsdaten (Python-Prozess) an die Web-GUI (PHP-FPM Prozesse).
* Automatisierte Steuerung spezifischer Betriebsparameter als dynamische Konfiguration (bidirektionale Kommunikation aller beteiligter Prozesse)

## Randbedingungen
* Beim ersten Start der Web-GUI stehen (noch) keine Konfigurationsdaten zur Verfügung, der Prozess zum Einlesen der Sensor-Betriebsdaten läuft nicht. Dieser Prozess wird durch die Web-GUI als User-Prozess gestartet.
* Der (Python-) Prozess zum Einlesen der Sensor-Betriebsdaten läuft unabhängig aller anderen Prozesse als User-Prozess. Bei besonderen Konstellationen und des Nachts wird er angehalten, in diesen Fällen müssen die vorhandenen (veralteten) Betriebsdaten in der Web-GUI angezeigt werden.
* Der NGINX WebServer wartet auf eingehende Angragen durch den interaktiven Benutzer, die Web-GUI dieser Anwendung ist kein selbstständig laufender Prozess. Beim Aufruf der Web-GUI durch ein interaktiver Benutzer, werden PHP-FPM Prozesse durch den NGINX-WebServer gestart. Diese PHP-FPM Prozesse haben eine nur kurze Laufzeit (Anteile einer Sekunde), ein PHP-FPM Prozess kann keine Daten an den nächsten PHP-FPM-Prozess übergeben. Falls eine solche Datenübergabe erforderlich ist, müssen Methoden aus diesem Aufsatz verwendet werden.
* Es sind nur die letzten Sensor-Betriebsdaten von Interesse. Eine Warteschlange, welche alle (auch historische) Daten verarbeitet, ist nicht erwünscht.

## Überlegung
Es stellt sich die Frage, wie die Daten von einem Prozess zum anderen Prozess übertragen werden könnten.
Prinzipiell stehen folgende Mechanismen zur Interprozesskommunikation (IPC) zur Verfügung:
* Shared Memory (gemeinsamer Speicher)
* Pipes (Datenleitungen)
* Nachrichtenübermittlung über Message Queues oder Sockets
* Remote Procedure Calls (RPCs)
* Synchronisationsmechanismen wie Semaphoren und Mutexe

Weiterhin stehen folgende Arten der Kommunikation und zur Datenübertragung zur Diskussion:
* Speicherung der Daten in einer Datei und Lesen der Datei von einem zweiten Prozess.
* Speichern und Zurücklesen in einer Datenbank
* Kommunikation über MQTT, REST oder OPC-UA

## Lösungsansätze
Im ersten Lösungsansatz wurden die Betriebsdaten in einer Datei gespeichert und von den PHP-FPM Prozessen ausgelesen. Hierbei kam es immer wieder vor, dass eine "leere" Datei gelesen wurde. Diese Lösung funktioniert, wird aber nicht als "konsistente" Lösung angesehen.  
Im zweiten Lösungsansatz wurde MQTT mit einem "Retain-Mechanismus" implementiert. Der Aufwand war scheinbar gering, jedoch konnte keine zufriedenstellende Programmierung gefunden werden.  
Als dritter Lösungsansatz wurde die Datenübertragung über Shared-Memory untersucht. Hierbei wurde die Implementierung über System-V erfolgreich umgesetzt. Die Posix-Implementierung wurde nicht beendet.

## Shared-Memory
`Shared Memory` ist ein vom Kernel verwalteter Speicherbereich, der zwei oder mehreren unabhängigen Prozessen den Zugriff auf denselben logischen Speicher ermöglicht.
Es ist eine sehr effiziente Methode zur Datenübertragung zwischen laufenden Prozessen.
Schreibt ein Prozess in den Shared Memory, sind die Änderungen sofort für alle anderen Prozesse sichtbar, die Zugriff auf denselben Shared Memory haben.
Shared Memory bietet leider keine Synchronisierungsmöglichkeiten, sodass Sie in der Regel einen anderen Mechanismus zur Synchronisierung des Zugriffs
(z. B. Semaphoren) verwenden müssen. Es gibt keine automatischen Möglichkeiten, um zu verhindern,
dass ein zweiter Prozess mit dem Lesen des Shared Memory beginnt, bevor der erste Prozess mit dem Schreiben fertig ist.
Ein Shared-Memory Speicher-Segment existiert auch nach der Beendigung seines Erzeugers, sofern nicht das `Flag IPC_RMID` angegeben wurde.

## Shared-Memory über System-V
Zur Nutzung von `System-V Shared-Memory` in Python ist die folgende Bibliothek zu installieren:
```code
/home/AhoyDTU/ahoyenv/bin/python3 -m pip install sysv_ipc
```
[Python-Script zum Schreiben der Daten](./sysV_out.py)  
[Python-Script zum Lesen der Daten](./sysV_in.py)  
[PHP-Script zum Schreiben der Daten](./sysV_out.php)  
[PHP-Script zum Lesen der Daten](./sysV_in.php)  

Weitere Grundlagen (Quelle)
- Python: <https://semanchuk.com/philip/sysv_ipc/>
- PHP: <https://www.php.net/manual/de/book.sem.php>

## Prüfen der shared-memory Objekte mit “ipcs”
`ipcs` zeigt Informationen zu den Interprozesskommunikationsfunktionen von System-V Systemen an.
Standardmäßig werden Informationen zu allen drei Ressourcen angezeigt:
•	gemeinsam genutzte Speichersegmente
•	Nachrichtenwarteschlangen
•	Semaphor-Arrays.
```code
ipcs		# By default it shows information about all three resources
ipcs -m	# information about active shared memory segments
ipcs -q	# information about active message queues
ipcs -s	# information about active semaphore sets
```

