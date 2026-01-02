<?php
require_once'generic_json.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

function print2client($line){
	echo "event: serial\r\n";
	echo "retry: 1000\r\n";
	echo "data: " . trim($line) . "<rn>\n\n";
	// Den Puffer leeren, damit der Browser die Daten erhält
	ob_flush(); 		// Sendet den ersten Teil an den Browser
	flush();
	// Break the loop if the client aborted the connection (page closed)
	if (connection_aborted()) exit(99);
}

ob_start(); 			// Aktiviert die Ausgabepufferung
ob_end_flush(); 		// Sendet den letzten Teil und beendet die Pufferung

$ahoy_log = $ahoy_conf['WebServer']['filepath'] . '/AhoyDTU_' . $ahoy_conf['dtu']['serial'] . '.log';
$nginxlog = '/var/log/nginx/access.log';

$ahoy_fh = @fopen($ahoy_log, 'r');	// Öffnen im Read-Modus
$nginxfh = @fopen($nginxlog, 'r');	// Öffnen im Read-Modus

fseek($ahoy_fh, -121, SEEK_END);	// Zum Ende der Datei springen, um nur neue Einträge zu lesen
fseek($nginxfh, -121, SEEK_END);	// Zum Ende der Datei springen, um nur neue Einträge zu lesen

while (true) {
	// read line an print it to client
	if ($ahoy_fh) while (($ahoy_line = fgets($ahoy_fh)) !== false) print2client($ahoy_line);
	// reset file pos
	if (filesize($ahoy_log) > ftell($ahoy_fh)) fseek($ahoy_fh, ftell($ahoy_fh));

	// read line an print it to client
	if ($nginxfh) while (($nginxline = fgets($nginxfh)) !== false) print2client($nginxline);
	// reset file pos
	if (filesize($nginxlog) > ftell($nginxfh)) fseek($nginxfh, ftell($nginxfh));

	usleep(500000);		// no new line found - wait 0.5 sec, to save CPU
	clearstatcache();	// clear Cache
	if (connection_aborted()) exit(99);	// check connection
}
fclose($ahoy_fh);	// close filehandle
fclose($nginxfh);	// close filehandle
// ob_end_flush(); 	// Sendet den letzten Teil und beendet die Pufferung
?>

