<?php
# generic_json.php
# June 2025 - PaeserBastelstube - inital
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
date_default_timezone_set('Europe/Berlin');
header('Content-Type: application/json; charset=utf-8');

$AhoyHost = trim(shell_exec("hostname -A | awk '{print $1}'"));					# hostname of raspberry
$dtu_array = explode(".", shell_exec("hostname -I | awk '{print $1}'"));		# MAC of network interface
$dtu_serial = $dtu_array[0] * $dtu_array[1] * $dtu_array[2] * $dtu_array[3];	# def DTU-Serial from MAC

$_generic_rssi_str = @shell_exec('iwconfig wlan0 2>&1 | grep Quality');			# RSSI
if (empty($generic_rssi_str)) {$generic_rssi_str =  "LAN connected";}			# 

$generic_uptime_str   = @file_get_contents('/proc/uptime');						# tbd
$generic_uptime_array = explode(' ', $generic_uptime_str);						# 

$Environment = shell_exec("lsb_release -d 2>/dev/null | awk -F: '{print $2}'");
$Environment = trim($Environment); # Die Funktion entfernt Whitespaces am Anfang und Ende von string

# load ahoy config
$ahoy_config["filename"] = '../../ahoy/AhoyDTU.yml';
$ahoy_config["filetime"] = 0;
$ahoy_data = array();
if (file_exists($ahoy_config["filename"])) {
	$ahoy_config["filetime"] = filemtime($ahoy_config["filename"]);	# save timestamp
	$ahoy_data = @yaml_parse_file($ahoy_config["filename"]);		# read content data
	# echo 'Error: ', yaml_last_error_msg();
}

# check old ahoy configuration
$ahoy_config["old_filename"] = '../../ahoy/ahoy.yml';
if (file_exists($ahoy_config["old_filename"])) {
	# check timestamp, if newer --> read old config
	if (filemtime($ahoy_config["old_filename"]) > $ahoy_config["filetime"] or
      count($ahoy_data) == 0) {
		$ahoy_data += @yaml_parse_file($old_ahoy_config);
		# echo 'Error: ', yaml_last_error_msg();
	}
}

# check ahoy config: are no data loaded, define default values
if (count($ahoy_data) > 0) {
  $ahoy_data = $ahoy_data["ahoy"];
} else {
  if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
    echo ("No AhoyDTU-configuration found - prepare standard config!\n");
  }
  $ahoy_data["interval"] = 14;
  $ahoy_data["transmit_retries"] = 4;
  $ahoy_data["dtu"] = ["serial" => $dtu_serial, "name" => $AhoyHost];

  $ahoy_data["logging"] = ["filename" => "/tmp/AhoyDTU_" . strval($dtu_serial) . ".log", 
			 "level" => "INFO", "max_log_filesize" => 1000000, "max_log_files" => 1];

  # "nrfEnable":"on","pinCs":"5","pinCe":"4","pinIrq":"15","pinSclk":"18","pinMosi":"23","pinMiso":"19",

  $ahoy_data["WebServer"]["filepath"] = "/tmp";
  $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] = false;        # Reset values and YieldDay at midnight
  $ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] = false;      # Reset values when inverter status is 'not available'
  $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] = false;         # Reset values at sunrise
  $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] = false;          # Reset values at sunset
  $ahoy_data["WebServer"]["InverterReset"]["MaxValues"] = false;         # Include reset 'max' values
  $ahoy_data["WebServer"]["strtWthtTm"] = false;                         # Start without time sync
  $ahoy_data["WebServer"]["rdGrid"] = false;                             # Read Grid Profile

  $ahoy_data["sunset"]["disabled"] = true;
  $ahoy_data["mqtt"]["disabled"] = true;
  $ahoy_data["volkszaehler"]["disabled"] = true;
  $ahoy_data["influxdb"]["disabled"] = true;
}

if (! isset($ahoy_data["mqtt"]["host"])) {
	$ahoy_data["mqtt"]["host"] = "";
	$ahoy_data["mqtt"]["port"] = "";
	$ahoy_data["mqtt"]["user"] = "";
	$ahoy_data["mqtt"]["password"] = "";
	$ahoy_data["mqtt"]["topic"] = "";
	$ahoy_data["mqtt"]["Retain"] = "";
}

if (!isset($ahoy_data["generic"]["cst"]["lnk"])) {$ahoy_data["generic"]["cst"]["lnk"] = "";}
if (!isset($ahoy_data["generic"]["cst"]["txt"])) {$ahoy_data["generic"]["cst"]["txt"] = "";}

if (!isset($ahoy_data["sunset"]["latitude"])) {$ahoy_data["sunset"]["latitude"] = "";}
if (!isset($ahoy_data["sunset"]["longitude"])) {$ahoy_data["sunset"]["longitude"] = "";}

if (!isset($ahoy_data["generic"]["region"])) {$ahoy_data["generic"]["region"] = 0;}
if (!isset($ahoy_data["generic"]["timezone"])) {$ahoy_data["generic"]["timezone"] = 1;}

# create "ahoy-generic-data"
$generic_json = [
	"generic" => [
		"wifi_rssi"   => $generic_rssi_str,						# WIFI-RSSI or LAN
		"ts_uptime"   => intval($generic_uptime_array[0]),		# system uptime
		"ts_now"      => time(),								# current time
		"version"     => "0.8.155",
		"modules"     => trim(shell_exec("uname -m")),			# "MDH-de",
		"build"       => "5feb293",
		"env"         => $Environment,							# "esp32-wroom32-de",
		"host"        => $AhoyHost,								# hostname
		"menu_prot"   => false,
		"menu_mask"   => 61,
		"menu_protEn" => false,
		"cst_lnk"     => $ahoy_data["generic"]["cst"]["lnk"],	# custom 
		"cst_lnk_txt" => $ahoy_data["generic"]["cst"]["txt"],
		"region"      => $ahoy_data["generic"]["region"],		# wo wird das benötigt
		"timezone"    => $ahoy_data["generic"]["timezone"], 	# wo wird das benötigt
		"esp_type"    => "RASPI"
	]
];

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "generic_json.php") {
	print "/generic_json:\n" . json_encode($generic_json) . "\n";
	print "/ahoy_data:\n" . json_encode($ahoy_data) . "\n";
}
?>
