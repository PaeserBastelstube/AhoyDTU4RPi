<?PHP
header('Content-Type: application/json; charset=utf-8');

function showSave($my_post){
	include 'generic_json.php';    # to load AhoyDTU configuration

	file_put_contents("/tmp/asdf", "_my_in : " . json_encode($my_post)     . "\n", LOCK_EX);
	file_put_contents("/tmp/asdf", "_get :"    . json_encode($_GET)        . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/asdf", "_post :"   . json_encode($_POST)       . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/asdf", "_files :"  . json_encode($_FILES)      . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/asdf", "_server :" . json_encode($_SERVER)     . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/asdf", "_data_s :" . json_encode($ahoy_data)   . "\n", FILE_APPEND | LOCK_EX);

	## System Config # from web.h - line 465
	## [device] =>  $(hostname)	# wird im Betriebssystem verwaltet - nicht änderbar
	## [schedReboot] => on		# Neustart um Mitternacht - Web-Servers / Ahoy # sudo systemctl restart nginx ...
	## [darkMode] => on			# unlink / link CSS file
	## [cstLnk] => ""			# custom link addr
	## [cstLnkTxt] => ""		# custom link text
	## [region] => 0			# wofür wird das benötigt
	## [timezone] => 13			# wofür wird das benötigt

	if (isset($my_post["schedReboot"]))	$ahoy_data["WebServer"]["system"]["sched_reboot"] = $my_post["schedReboot"];
	else unset($ahoy_data["WebServer"]["system"]["sched_reboot"]);
	if (isset($my_post["region"]))		$ahoy_data["WebServer"]["generic"]["region"]      = $my_post["region"];
	if (isset($my_post["timezone"]))	$ahoy_data["WebServer"]["generic"]["timezone"]    = $my_post["timezone"] -12;

	# check and switch for Dark or Bright color
	if (isset ($my_post["darkMode"]) and $my_post["darkMode"] == "on") {
		unlink ('../html/colors.css');
		symlink ('../html/colorDark.css', '../html/colors.css');
	} else {
		unlink ('../html/colors.css');
		symlink ('../html/colorBright.css', '../html/colors.css');
	}

	# Check custom link
	if (isset($my_post["cstLnk"]))		$ahoy_data["WebServer"]["generic"]["cst"]["lnk"] = $my_post["cstLnk"];
	if (isset($my_post["cstLnkTxt"]))	$ahoy_data["WebServer"]["generic"]["cst"]["txt"] = $my_post["cstLnkTxt"];


	## Serial console # from web.h - line 603
	# "serEn":"on","serDbg":"on","priv":"on","wholeTrace":"on","log2mqtt":"on",
	if (isset($my_post["serEn"]))		$ahoy_data["WebServer"]["serial"]["serEn"] = $my_post["serEn"];
	else unset($ahoy_data["WebServer"]["serial"]["serEn"]);
	if (isset($my_post["serDbg"]))		$ahoy_data["WebServer"]["serial"]["serDbg"] = $my_post["serDbg"];
	else unset($ahoy_data["WebServer"]["serial"]["serDbg"]);
	if (isset($my_post["priv"]))		$ahoy_data["WebServer"]["serial"]["priv"] = $my_post["priv"];
	else unset($ahoy_data["WebServer"]["serial"]["priv"]);
	if (isset($my_post["wholeTrace"]))	$ahoy_data["WebServer"]["serial"]["wholeTrace"] = $my_post["wholeTrace"];
	else unset($ahoy_data["WebServer"]["serial"]["wholeTrace"]);
	if (isset($my_post["log2mqtt"]))	$ahoy_data["WebServer"]["serial"]["log2mqtt"] = $my_post["log2mqtt"];
	else unset($ahoy_data["WebServer"]["serial"]["log2mqtt"]);

	# aus Network # from web.h - line 500
	##    [ap_pwd] => esp_8266          #Standard in AhoyDTU
	##    [ssid] => wifi-ssid
	##    [hidd] => off
	##    [pwd] => {PWD}
	##    [ipAddr] => 1.1.1.1
	##    [ipMask] => 2.2.2.2
	##    [ipDns1] => 3.3.3.3
	##    [ipDns2] => 4.4.4.4
	##    [ipGateway] => 5.5.5.5
	### on RASPBERRY: system managed - not by AhoyDTU


	# aus Protection # from web.h - line 489
	##    [adminpwd] => {PWD}
	##    [protMask0] => on   # Index
	##    [protMask1] => on   # Live
	##    [protMask2] => on   # Webserial
	##    [protMask3] => on   # Settings
	##    [protMask4] => on   # Update
	##    [protMask5] => on   # System
	##    [protMask6] => on   # History
	if (isset($my_post["adminpwd"])  and $my_post["adminpwd"]  != "")   $ahoy_data["WebServer"]["system"]["pwd_set"] = true;
	$ahoy_data["WebServer"]["system"]["prot_mask"] = 0;
	if (isset($my_post["protMask0"]) and $my_post["protMask0"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**0;
	if (isset($my_post["protMask1"]) and $my_post["protMask1"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**1;
	if (isset($my_post["protMask2"]) and $my_post["protMask2"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**2;
	if (isset($my_post["protMask3"]) and $my_post["protMask3"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**3;
	if (isset($my_post["protMask4"]) and $my_post["protMask4"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**4;
	if (isset($my_post["protMask5"]) and $my_post["protMask5"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**5;
	if (isset($my_post["protMask6"]) and $my_post["protMask6"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**6;
	if (isset($my_post["protMask7"]) and $my_post["protMask7"] == "on") $ahoy_data["WebServer"]["system"]["prot_mask"] += 2**7;

# aus Inverter
##    [invInterval] => 99
##    [invRstMid] => on
##    [invRstComStart] => on
##    [invRstComStop] => on
##    [invRstNotAvail] => on
##    [invRstMaxMid] => on
##    [strtWthtTm] => on
##    [rdGrid] => on

# aus NTP Server
##    [ntpAddr] => wird im Betriebssystem verwaltet - nicht änderbar
##    [ntpPort] => 999
##    [ntpIntvl] => 100

# aus Sunrise & Sunset
##    [sunLat] => 53                   ## In ahoy.yml speichern
##    [sunLon] => 10                   ## In ahoy.yml speichern
##    [sunOffsSr] => 0
##    [sunOffsSs] => 0

# aus MQTT
##    [mqttAddr] => MQTT Broker        ## In ahoy.yml speichern
##    [mqttPort] => 9998               ## In ahoy.yml speichern
##    [mqttClientId] => das            ## In ahoy.yml speichern
##    [mqttUser] => ich                ## In ahoy.yml speichern
##    [mqttPwd] => du                  ## In ahoy.yml speichern
##    [mqttTopic] => erSieEs           ## In ahoy.yml speichern
##    [mqttJson] => on                 ## In ahoy.yml speichern
##    [mqttInterval] => 22             ## In ahoy.yml speichern
##    [retain] => on                   ## In ahoy.yml speichern

# aus Pinout Configuration
##    [pinLed0] => 255
##    [pinLed1] => 255
##    [pinLed2] => 255
##    [pinLedHighActive] => 0
##    [pinLedLum] => 255

##    [nrfEnable] => on
##    [pinCs] => 5
##    [pinCe] => 4
##    [pinIrq] => 15
##    [pinSclk] => 18
##    [pinMosi] => 23
##    [pinMiso] => 19

##    [cmtEnable] => on
##    [pinCmtSclk] => 14
##    [pinSdio] => 12
##    [pinCsb] => 15
##    [pinFcsb] => 26
##    [pinGpio3] => 23

##    [ethEn] => on
##    [ethCs] => 15
##    [ethSclk] => 14
##    [ethMiso] => 12
##    [ethMosi] => 13
##    [ethIrq] => 4
##   [ethRst] => 255

# aus Display Config
##    [disp_pwr] => on
##    [disp_cont] => 88
##    [disp_graph_ratio] => 1

# aus SAVE Button
##    [reboot] => on

## bool saveSettings() { # .../src/config/settings.h - Zeile 323
##       jsonNetwork(root[F("wifi")].to<JsonObject>(), true);
##           jsonNrf(root[F("nrf")].to<JsonObject>(), true);
##           jsonCmt(root[F("cmt")].to<JsonObject>(), true);
##           jsonNtp(root[F("ntp")].to<JsonObject>(), true);
##           jsonSun(root[F("sun")].to<JsonObject>(), true);
##        jsonSerial(root[F("serial")].to<JsonObject>(), true);
##          jsonMqtt(root[F("mqtt")].to<JsonObject>(), true);
##           jsonLed(root[F("led")].to<JsonObject>(), true);
##        jsonPlugin(root[F("plugin")].to<JsonObject>(), true);
##          jsonInst(root[F("inst")].to<JsonObject>(), true);

## bool saveSettings() { # .../src/config/settings.h - Zeile 382
##        void loadDefaults(bool keepWifi = false) {



    # check invInterval 
	if (isset($my_post["invInterval"]) and $ahoy_data["interval"] != $my_post["invInterval"]) {
		$ahoy_data["interval"]  = $my_post["invInterval"];
	}

    # check WebServer Reset values
	# Reset values and YieldDay at midnight
	if (isset($my_post["invRstMid"])) $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] = $my_post["invRstMid"];
	else                              $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] = false;

	# Reset values at sunrise
	if (isset($my_post["invRstComStart"])) $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] = $my_post["invRstComStart"];
	else                                   $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] = false;

	# Reset values at sunset
	if (isset($my_post["invRstComStop"])) $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] = $my_post["invRstComStop"];
	else								  $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] = false;

	# Reset values when inverter status is 'not available'
	if (isset($my_post["invRstNotAvail"]))	$ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] = $my_post["invRstNotAvail"];
	else									$ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] = false;

	# Include reset 'max' values
	if (isset($my_post["invRstMaxMid"]))	$ahoy_data["WebServer"]["InverterReset"]["MaxValues"] = $my_post["invRstMaxMid"];
	else									$ahoy_data["WebServer"]["InverterReset"]["MaxValues"] = false;

	# Start without time sync (useful in AP-Only-Mode)
	if (isset($my_post["strtWthtTm"]))	$ahoy_data["WebServer"]["strtWthtTm"] = $my_post["strtWthtTm"];
	else								$ahoy_data["WebServer"]["strtWthtTm"] = false;

	# Read Grid Profile
	if (isset($my_post["rdGrid"]))	$ahoy_data["WebServer"]["rdGrid"] = $my_post["rdGrid"];
	else							$ahoy_data["WebServer"]["rdGrid"] = false;

	if (isset($_SERVER["QUERY_STRING"]) and $_SERVER["QUERY_STRING"] == "upload") {
		# file content
		$fileContentString = file_get_contents(htmlspecialchars($_FILES["upload"]["tmp_name"]));
		$fileContentArray  = json_decode($fileContentString, true);  ## WICHTIG: ",true" - sonst JSON und kein ARRAY
		$ahoy_data = $fileContentArray["ahoy"];
	}


	# check for sunrise and sunset data
    if (isset($my_post["sunLat"]) and isset($my_post["sunLon"])) {
		if (!isset($ahoy_data["sunset"]["latitude"]) or
			!isset($ahoy_data["sunset"]["longitude"]) or
			$my_post["sunLat"] != $ahoy_data["sunset"]["latitude"] or
			$my_post["sunLon"] != $ahoy_data["sunset"]["longitude"]) {
			$ahoy_data["sunset"]["latitude"] = $my_post["sunLat"];
			$ahoy_data["sunset"]["longitude"] = $my_post["sunLon"];
			if ($my_post["sunLat"] == "" or $my_post["sunLon"] == "") {
				$ahoy_data["sunset"]["disabled"] = true;
			} else {
				$ahoy_data["sunset"]["disabled"] = false;
			}
		}
    }

# {"cmd":"save_iv","token":"*","id":1,"ser":142929835590,"name":"qwert","en":true,
# "ch":[{"pwr":"","name":"","yld":"0"},{"pwr":"","name":"","yld":"0"},
#       {"pwr":"","name":"","yld":""},{"pwr":"","name":"","yld":""},
#       {"pwr":"","name":"","yld":""},{"pwr":"","name":"","yld":""}],
# "pa":"0","freq":"12","disnightcom":false}

	# add new / delete inverter ## see setup.html:736
	if (isset($my_post["cmd"]) and $my_post["cmd"] == "save_iv") {  ## detect inverter commands

		if ($my_post["ser"] == 0) {                                   ## delete inverter
			# unset($ahoy_data["inverters"][$my_post["id"]]);
			array_splice($ahoy_data["inverters"], $my_post["id"], 1);

		} else {                                                      ## add / change inverter
			$txpower = "max";
			if (isset($my_post["pa"])) {                              ## see setup.html:756
				if     ($my_post["pa"] == 0) $txpower = "min";
				elseif ($my_post["pa"] == 1) $txpower = "low";
				elseif ($my_post["pa"] == 2) $txpower = "high";
			}

			$inverter = [                                             ## def var for inverter
				"name"     => $my_post["name"],
				"disabled" => ! $my_post["en"],
				"serial"   => base_convert($my_post["ser"], 10, 16),  #!! Kodierung dec2hex
				"txpower"  => $my_post["pa"],
				"strings"  => [],
				"disnightcom" => $my_post["disnightcom"]
			];
			foreach ($my_post["ch"] as $ii => $channel) {             ## add channel to inv var
				if (isset($channel["name"]) and $channel["name"] != "") 
				$inverter["strings"][$ii] = array(
					"s_name"     => $channel["name"],
					"s_maxpower" => $channel["pwr"],
					"s_yield"    => $channel["yld"]
				);
	 		}
  			#file_put_contents("/tmp/asdf", "\n" . json_encode($inverter), FILE_APPEND | LOCK_EX);
			if (! isset($ahoy_data["inverters"])) $ahoy_data["inverters"] = [];
			$ahoy_data["inverters"][$my_post["id"]] = $inverter;
		}	
	}

	file_put_contents("/tmp/asdf", "\n_data_e: " . json_encode($ahoy_data) . "\n", FILE_APPEND | LOCK_EX);

	# Save changed data to AhoyDTU config file
	$RC = yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_data]);

	# print ($RC ? "alles OK\n" : "Fehler\n");
	if ($RC == true) {
		print json_encode(["success" => true]);
	} else {
		print_r ("\n\n" . "one ore more Settings changed, Error while saving config to: " . $myFilename . "\n");
		print ("Length: " . count($ahoy_data) . ": " . json_encode($ahoy_data) . "\n");
	}
}

