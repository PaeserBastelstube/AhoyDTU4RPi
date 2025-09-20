<?php
# generic_json.php
# June 2025 - PaeserBastelstube - inital
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
date_default_timezone_set('Europe/Berlin');
if (! isset($_SERVER["TERM"])) header('Content-Type: application/json; charset=utf-8');

# Name of DTU
$AhoyHost = trim(shell_exec("hostname -A | awk '{print $1}'"));	# hostname of raspberry ==> Name of DTU

# load new ahoy config
$ahoy_config["filename"] = '../../ahoy/AhoyDTU.yml';	# /home/AhoyDTU/ahoy/AhoyDTU.yml
$ahoy_config["filetime"] = 0;
$ahoy_conf = array();
if (file_exists($ahoy_config["filename"])) {
	$ahoy_config["filetime"] = filemtime($ahoy_config["filename"]);	# save timestamp
	$ahoy_conf = @yaml_parse_file($ahoy_config["filename"]);		# read content data
	# echo 'Error: ', yaml_last_error_msg();
}

# check old ahoy configuration
$ahoy_config["old_filename"] = '../../ahoy/ahoy.yml';
if (file_exists($ahoy_config["old_filename"])) {
	# check timestamp, if newer --> read old config
	if (filemtime($ahoy_config["old_filename"]) > $ahoy_config["filetime"] or
      count($ahoy_conf) == 0) {
		$ahoy_conf += @yaml_parse_file($old_ahoy_config);
		# echo 'Error: ', yaml_last_error_msg();
	}
}

# check ahoy config: when no data loaded, define default values
if (count($ahoy_conf) > 0) {
	$ahoy_conf = $ahoy_conf["ahoy"];
} else {
	if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
		echo ("No AhoyDTU-configuration found - prepare standard config!" . PHP_EOL);
	}
	$ahoy_conf["interval"] = 14;
	$ahoy_conf["transmit_retries"] = 4;

	# from "src/hm/Radio.h:133"
	## // the first digit is an 8 for DTU production year 2022, the rest is filled with the ESP chipID in decimal
	# mDtuSn |= 0x80000000; 
	$IP_array = explode(".", shell_exec("hostname -I | awk '{print $1}'"));			# get IP of network interface
	$dtu_serial = intval(0x80000000 + $IP_array[1] * $IP_array[2] * $IP_array[3]);	# def DTU-Serial from IP-Address
	$ahoy_conf["dtu"] = ["serial" => dechex($dtu_serial), "name" => $AhoyHost];

	$ahoy_conf["WebServer"]["filepath"] = "/tmp";
	$ahoy_conf["logging"] = ["filename" => "/tmp/AhoyDTU_" . strval(dechex($dtu_serial)) . ".log", 
			 "level" => "INFO", "max_log_filesize" => 1000000, "max_log_files" => 1];

	$ahoy_conf["sunset"]["enabled"] = false;
	$ahoy_conf["nrf"]["enabled"] = false;
	$ahoy_conf["cmt"]["enabled"] = false;
	$ahoy_conf["mqtt"]["enabled"] = false;
	$ahoy_conf["volkszaehler"]["enabled"] = false;
	$ahoy_conf["influxdb"]["enabled"] = false;
}

# network configuration
preg_match_all('/default via (.+) dev (.+) proto (.+) src (.+) metric/m', trim(shell_exec('ip route')), $ahoy_conf["iface"]);
# $ahoy_conf["iface"][0][0] = default via 192.168.254.253 dev wlan0 proto dhcp src 192.168.254.55 metric 600
#                                         |xxxxx 1 xxxxx|     | 2 |       | 3|     |xxxxxx 4 xxx| 
# $ahoy_conf["iface"][1][0] = gateway IP address
# $ahoy_conf["iface"][2][0] = name of network interface
# $ahoy_conf["iface"][3][0] = dhcp
# $ahoy_conf["iface"][4][0] = system IP address (my Raspis IP)

$ahoy_conf["iface"]["rssi"] = 0;
$iface = $ahoy_conf["iface"][2][0];
if (str_starts_with($iface, "wlan")) {
	preg_match_all('/ESSID:"(.+)"|Signal level=(.+) dBm/m', trim(shell_exec('iwconfig $iface 2>&1')), $wifi_rssi_array);
	$ahoy_conf["iface"]["essid"] = trim($wifi_rssi_array[1][0]);
	$ahoy_conf["iface"]["rssi"]  = trim($wifi_rssi_array[2][1]);
	$ahoy_conf["iface"]["wired"] = false;
} else {
	$ahoy_conf["iface"]["essid"] = "";
	$ahoy_conf["iface"]["rssi"]  = "LAN connected";
	$ahoy_conf["iface"]["wired"] = true;
}

# System Uptime
$uptime_array = explode(' ', @file_get_contents('/proc/uptime'));		# 

# System Release Information
$Environment = shell_exec("lsb_release -d 2>/dev/null | awk -F: '{print $2}'");
$Environment = trim($Environment); # "trim" entfernt Whitespaces am Anfang und Ende von string

# DTU Protection
$menu_mask   = $ahoy_conf["WebServer"]["system"]["prot_mask"] ?? 0;
$menu_protEn = isset($ahoy_conf["WebServer"]["system"]["pwd_pwd"]) ? true : false;
$menu_prot   = $menu_protEn and $menu_mask > 0 ? true : false;

# Define "ahoy-generic-data" Variable
$generic_json = [
	"generic" => [
		"wifi_rssi"   => $ahoy_conf["iface"]["rssi"],	# WIFI-RSSI or LAN
		"ts_uptime"   => intval($uptime_array[0]),		# system uptime
		"ts_now"      => time(),						# current time
		"version"     => "0.8.0",
		"modules"     => trim(shell_exec("uname -m")),	# "MDH-de",
		"build"       => "5feb293",
		"env"         => $Environment,					# "esp32-wroom32-de",
		"host"        => $AhoyHost,						# hostname
		"menu_prot"   => $menu_prot,					# Switch, if prot=set - true=locked - false=unlocked
		"menu_mask"   => $menu_mask,					# exp-sum of 7 switches
		"menu_protEn" => $menu_protEn,					# check, if prot-PW != "\0"
		"cst_lnk"     => $ahoy_conf["WebServer"]["generic"]["cst"]["lnk"] ?? "",	# custom 
		"cst_lnk_txt" => $ahoy_conf["WebServer"]["generic"]["cst"]["txt"] ?? "",
		"region"      => $ahoy_conf["WebServer"]["generic"]["region"] ?? 0,			# wo wird das benötigt
		"timezone"    => $ahoy_conf["WebServer"]["generic"]["timezone"] ?? 1,	 	# wo wird das benötigt
		"esp_type"    => "RASPI"
	]
];

#############################################################################
# System-V IPC for PHP - Semaphores, Shared Memory and Message Queues
# https://www.php.net/manual/en/book.shmop.php
# https://www.php.net/manual/en/book.sem.php
################################################################################
function readOperatingData($filePathName, $print_OK = False) {
	# def a file to generate the specific FTOK key
	# generate the specific FTOK key from file-path-name
	#  ftok(string $filename, string $project_id): int
	# "id" must be in chr(string) - hex(0x30) = dec(48) = chr("0")
	$ftokKey = ftok($filePathName, "0");
	if ($print_OK) termPrint("ftokKey=" . $ftokKey . " hex=0x" . dechex($ftokKey));

	$shm_string = "{'ERROR' : 'No Data found'}";
	$max_acquire = 1;		# number of processes that can acquire the semaphore simultaneously
	$auto_release = true;	# Specifies if the semaphore should be automatically released on shutdown
	do {
		$sem_id = @sem_get($ftokKey, $max_acquire, 0o666, $auto_release);	# creating a semaphore
		if (False == $sem_id) {
			if ($print_OK) termPrint("can't create or open the Semaphore with KEY: 0x" . dechex($ftokKey));
	    	break;
		}
		if (sem_acquire($sem_id)) {			# acquiring the semaphore
    		$shdMemObj = @shmop_open($ftokKey, "a", 0644, 0);
			if (!$shdMemObj) {
				if ($print_OK) termPrint("Couldn't connect to shared memory segment");
				sem_remove($sem_id);			# delete a semaphore
				break;
			}
			$shm_size = shmop_size($shdMemObj); # Get shared memory block's size
			$offsetFromStart = 0;
			$shm_string = shmop_read($shdMemObj, $offsetFromStart, $shm_size);
			shmop_close($shdMemObj);            # close the shared memory segment
			sem_release($sem_id);               # release the semaphore

			if ($print_OK)
			if (!$shm_string) {
				termPrint("Couldn't read from shared memory block");
			} else {
				termPrint("SHM Block Size: " . $shm_size . " bytes found");
				termPrint("The data inside shared memory was an " .
						json_encode(json_decode($shm_string, true), JSON_PRETTY_PRINT));
			}
			sem_remove($sem_id);                # remove the semaphore
		}
	} while (0);

	if ($print_OK) termPrint(PHP_EOL . 
		"To control shared-memory and semaphore, please call 'ipcs' or remove with 'ipcrm -a'" .
		PHP_EOL);

	return json_decode($shm_string, true);
}

function termPrint($textToPrint) {
	if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm")
		print $textToPrint . PHP_EOL;
}



if ($argv[0] == "generic_json.php"){
	# termPrint(readOperatingData(realpath($ahoy_config["filename"]), True));
	$ahoy_data = readOperatingData(realpath($ahoy_config["filename"]));

	termPrint("/ahoy_data:"    . PHP_EOL . json_encode($ahoy_data) . PHP_EOL .
			  "/generic_json:" . PHP_EOL . json_encode($generic_json) . PHP_EOL . 
			  "/ahoy_conf:"    . PHP_EOL . json_encode($ahoy_conf));
}
?>
