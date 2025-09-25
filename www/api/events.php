<?php
require_once 'generic_json.php';
ob_start(); 								#Aktiviert die Ausgabepufferung

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');


// Beispielhafte Endlosschleife
for ($ii = 0; $ii < 6; $ii++) {
	// Daten abrufen oder generieren
	$data = date('l jS \of F Y h:i:s A');
	// Daten im Event-Stream-Format senden
	echo "data: " . json_encode($data) . "\n\n";

	// Den Puffer leeren, damit der Browser die Daten erhält
	ob_flush(); 						# Sendet den ersten Teil an den Browser
	flush();

	// Kurze Pause, um die CPU-Last zu reduzieren
	sleep(1); 
}

##$fn = $ahoy_conf["WebServer"]["filepath"] . "/AhoyDTU_" . $ahoy_conf["dtu"]["serial"] . ".log";
##$fsize = filesize($fn);	
##$fsize = filesize($fn) - 1024;	
##$finode = fileinode($fn);
$a = false;

while ($a) {
	clearstatcache();						# Clears file status cache
	if (filesize($fn) == $fsize) {			# look for changed file size
		sleep(2);							# wait 2 sec, if file-size not changed
		#print(".");
		#print("_fsize=" . filesize($fn) . "\n");
		$a = false;
	} else {
		$fh_fn = fopen($fn, "r");			# create File Handle
		if ($finode == fileinode($fn))		# check for new inode
			fseek($fh_fn, $fsize);          # set file pointer to unread position
			##fseek($fh_fn, 0, SEEK_END);	# SEEK_END - Setzt die Position ans Ende der Datei plus offset(0) Bytes.
		else {
			$finode = fileinode($fn);
			$fsize = filesize($fn);
		}
		$result = fgets($fh_fn, 4096);		# read next line from file
		fclose($fh_fn);						# close file-handle

		if ($fsize > strlen($result))		# old log file
			$fsize += strlen($result);		# increase file-size variable
		else $fsize = strlen($result);		# set file-size variable

		#echo htmlspecialchars($result);
		echo $result;
		ob_flush(); 						# Sendet den ersten Teil an den Browser
	}
}


if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	#header('Content-Type: application/json; charset=utf-8');
	# print json_encode($generic_json["generic"]);
print("
retry: 1000
id: 394727593
data: hello!

id: 394730067
event: serial
data: 
	13:38:49.084 I: (#1) RX  78ms | 27 -65dBm | 95 02<rn>
	13:38:49.085 I: (#1) RX 118ms | 27 -65dBm | 95 03<rn>
	13:38:49.086 I: (#1) RX 177ms | 27 -65dBm | 95 04<rn>
	13:38:49.086 I: (#1) RX 217ms | 15 -65dBm | 95 85<rn>
	13:38:49.223 I: (#1) RX  53ms | 27 -65dBm | 95 02<rn>
	13:38:49.223 I: (#1) RX 100ms | 27 -65dBm | 95 03<rn>
	13:38:49.284 I: (#1) RX  57ms | 15 -65dBm | 95 85<rn>
	13:38:49.498 I: (#1) RX  78ms | 27 -65dBm | 95 01<rn>
	13:38:49.499 W: (#1) CRC Error -> Fail<rn>
	13:38:49.499 -----<rn>
	13:38:49.499 I: com loop duration: 499ms<rn>
	13:38:49.500 -----<rn>
");
}
ob_end_flush(); 		# Sendet den letzten Teil und beendet die Pufferung
?>

