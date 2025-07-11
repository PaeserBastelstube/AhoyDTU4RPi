<?PHP
header('Content-Type: application/json; charset=utf-8');

function showSave($my_post){
	include 'generic_json.php';    # to load AhoyDTU configuration

	file_put_contents("/tmp/AhoyDTU_asdf", "_my_in : " . json_encode($my_post)     . "\n", LOCK_EX);
	file_put_contents("/tmp/AhoyDTU_asdf", "_get :"    . json_encode($_GET)        . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/AhoyDTU_asdf", "_post :"   . json_encode($_POST)       . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/AhoyDTU_asdf", "_files :"  . json_encode($_FILES)      . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/AhoyDTU_asdf", "_server :" . json_encode($_SERVER)     . "\n", FILE_APPEND | LOCK_EX);
	file_put_contents("/tmp/AhoyDTU_asdf", "_data_s :" . json_encode($ahoy_data)   . "\n", FILE_APPEND | LOCK_EX);

	## System Config # from web.h - line 465
	## [device] =>  $(hostname)	# wird im Betriebssystem verwaltet - nicht änderbar
	## [schedReboot] => on		# Neustart um Mitternacht - Web-Servers / Ahoy # sudo systemctl restart nginx ...
	## [darkMode] => on			# unlink / link CSS file
	## [cstLnk] => ""			# custom link addr
	## [cstLnkTxt] => ""		# custom link text
	## [region] => 0			# wofür wird das benötigt
	## [timezone] => 13			# wofür wird das benötigt

	# Reboot Ahoy at midnight
	if (isset($my_post["schedReboot"]))	$ahoy_data["WebServer"]["system"]["sched_reboot"] = $my_post["schedReboot"];
	else unset($ahoy_data["WebServer"]["system"]["sched_reboot"]);

	# check and switch for Dark or Bright color
	if (isset ($my_post["darkMode"]) and $my_post["darkMode"] == "on") {
		unlink ('../html/colors.css');
		symlink ('../html/colorDark.css', '../html/colors.css');
	} else {
		unlink ('../html/colors.css');
		symlink ('../html/colorBright.css', '../html/colors.css');
	}
	if (isset($my_post["region"]))		$ahoy_data["WebServer"]["generic"]["region"]      = $my_post["region"];
	if (isset($my_post["timezone"]))	$ahoy_data["WebServer"]["generic"]["timezone"]    = $my_post["timezone"] -12;

	# custom link
	if (isset($my_post["cstLnk"]))		$ahoy_data["WebServer"]["generic"]["cst"]["lnk"] = $my_post["cstLnk"];
	if (isset($my_post["cstLnkTxt"]))	$ahoy_data["WebServer"]["generic"]["cst"]["txt"] = $my_post["cstLnkTxt"];


	## System configuration / Serial console # from web.h - line 603
	# "serEn":"on","serDbg":"on","priv":"on","wholeTrace":"on","log2mqtt":"on",
	if (isset($my_post["serEn"]))		$ahoy_data["logging"]["serial"]["serEn"] = $my_post["serEn"];
	else unset($ahoy_data["logging"]["serial"]["serEn"]);
	if (isset($my_post["serDbg"]))		$ahoy_data["logging"]["serial"]["serDbg"] = $my_post["serDbg"];
	else unset($ahoy_data["logging"]["serial"]["serDbg"]);
	if (isset($my_post["priv"]))		$ahoy_data["logging"]["serial"]["priv"] = $my_post["priv"];
	else unset($ahoy_data["logging"]["serial"]["priv"]);
	if (isset($my_post["wholeTrace"]))	$ahoy_data["logging"]["serial"]["wholeTrace"] = $my_post["wholeTrace"];
	else unset($ahoy_data["logging"]["serial"]["wholeTrace"]);
	if (isset($my_post["log2mqtt"]))	$ahoy_data["logging"]["serial"]["log2mqtt"] = $my_post["log2mqtt"];
	else unset($ahoy_data["logging"]["serial"]["log2mqtt"]);

	# Network configuration # from web.h - line 500
	## [ap_pwd] => esp_8266          #Standard in AhoyDTU
	## [ssid] => wifi-ssid
	## [hidd] => off
	## [pwd] => {PWD}
	## [ipAddr] => 1.1.1.1
	## [ipMask] => 2.2.2.2
	## [ipDns1] => 3.3.3.3
	## [ipDns2] => 4.4.4.4
	## [ipGateway] => 5.5.5.5
	### on RASPBERRY: system managed - not by AhoyDTU


	# Protection configuration # from web.h - line 489
	## [adminpwd] => {PWD}
	if (isset($my_post["adminpwd"])) {
 		if ($my_post["adminpwd"] == "") unset($ahoy_data["WebServer"]["system"]["pwd_pwd"]);
 		else $ahoy_data["WebServer"]["system"]["pwd_pwd"] = $my_post["adminpwd"];
	}
	if (isset($my_post["login"]) and $my_post["login"] == "login") {
		if (isset($my_post["pwd"]) and $my_post["pwd"] == $ahoy_data["WebServer"]["system"]["pwd_pwd"])
			unset($ahoy_data["WebServer"]["system"]["pwd_pwd"]);
	}

	## [protMask0] => on   # Index
	## [protMask1] => on   # Live
	## [protMask2] => on   # Webserial
	## [protMask3] => on   # Settings
	## [protMask4] => on   # Update
	## [protMask5] => on   # System
	## [protMask6] => on   # History
	$prot_mask = 0;
	if (isset($my_post["protMask0"]) and $my_post["protMask0"] == "on") $prot_mask += 2**0;
	if (isset($my_post["protMask1"]) and $my_post["protMask1"] == "on") $prot_mask += 2**1;
	if (isset($my_post["protMask2"]) and $my_post["protMask2"] == "on") $prot_mask += 2**2;
	if (isset($my_post["protMask3"]) and $my_post["protMask3"] == "on") $prot_mask += 2**3;
	if (isset($my_post["protMask4"]) and $my_post["protMask4"] == "on") $prot_mask += 2**4;
	if (isset($my_post["protMask5"]) and $my_post["protMask5"] == "on") $prot_mask += 2**5;
	if (isset($my_post["protMask6"]) and $my_post["protMask6"] == "on") $prot_mask += 2**6;
	if (isset($my_post["protMask7"]) and $my_post["protMask7"] == "on") $prot_mask += 2**7;
	if ($prot_mask > 0) $ahoy_data["WebServer"]["system"]["prot_mask"] = $prot_mask;


	# Inverter configuration # from web.h - line 512
	## [invInterval invRstMid invRstComStart invRstComStop invRstNotAvail invRstMaxMid strtWthtTm rdGrid

	if (isset($my_post["invInterval"]) and $ahoy_data["interval"] != $my_post["invInterval"])
		$ahoy_data["interval"]  = $my_post["invInterval"];

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

	# NTP Server configuration # from web.h - line 566
	## ntpAddr  => wird im Betriebssystem verwaltet - nicht änderbar
	## ntpPort  => 123
	## ntpIntvl => 720


	# Sunrise & Sunset configuration # from web.h - line 573
	## sunLat - sunLon - sunOffsSr - sunOffsSs
	if (isset($my_post["sunLat"]))	$ahoy_data["sunset"]["latitude"] = $my_post["sunLat"];
	else unset($ahoy_data["sunset"]["latitude"]);
	if (isset($my_post["sunLon"]))	$ahoy_data["sunset"]["longitude"] = $my_post["sunLon"];
	else unset($ahoy_data["sunset"]["longitude"]);
	if (isset($my_post["sunOffsSr"]))	$ahoy_data["sunset"]["sunOffsSr"] = 60 * $my_post["sunOffsSr"];
	else unset($ahoy_data["sunset"]["sunOffsSr"]);
	if (isset($my_post["sunOffsSs"]))	$ahoy_data["sunset"]["sunOffsSs"] = 60 * $my_post["sunOffsSs"];
	else unset($ahoy_data["sunset"]["sunOffsSs"]);
	if (isset($ahoy_data["sunset"]["latitude"]) and $ahoy_data["sunset"]["latitude"] != "" and
		isset($ahoy_data["sunset"]["longitude"]) and $ahoy_data["sunset"]["longitude"] != "") {
		$ahoy_data["sunset"]["enabled"] = true;
	} else {
		$ahoy_data["sunset"]["enabled"] = false;
	}

	# MQTT configuration # from web.h - line 586
	## mqttAddr - mqttPort - mqttClientId - mqttUser - mqttPwd - mqttTopic - mqttJson - mqttInterval - retain
    if (isset($my_post["mqttAddr"]) and $my_post["mqttAddr"] != "")	 $ahoy_data["mqtt"]["host"]     = $my_post["mqttAddr"];
	else unset($ahoy_data["mqtt"]["host"]);
    if (isset($my_post["mqttPort"]) and $my_post["mqttPort"] != "")	 $ahoy_data["mqtt"]["port"]     = $my_post["mqttPort"];
	else unset($ahoy_data["mqtt"]["port"]);
    if (isset($my_post["mqttClientId"]) and $my_post["mqttClientId"] != "") $ahoy_data["mqtt"]["clientId"] = $my_post["mqttClientId"];
	else unset($ahoy_data["mqtt"]["clientId"]);
    if (isset($my_post["mqttUser"]) and $my_post["mqttUser"] != "")	 $ahoy_data["mqtt"]["user"]     = $my_post["mqttUser"];
	else unset($ahoy_data["mqtt"]["user"]);
    if (isset($my_post["mqttPwd"]) and $my_post["mqttPwd"] != "")	 $ahoy_data["mqtt"]["password"] = $my_post["mqttPwd"];
	else unset($ahoy_data["mqtt"]["password"]);
    if (isset($my_post["mqttTopic"]) and $my_post["mqttTopic"] != "")$ahoy_data["mqtt"]["topic"]    = $my_post["mqttTopic"];
	else unset($ahoy_data["mqtt"]["topic"]);
    if (isset($my_post["mqttJson"]) and $my_post["mqttJson"] != "")	 $ahoy_data["mqtt"]["asJson"]   = $my_post["mqttJson"];
	else unset($ahoy_data["mqtt"]["asJson"]);
    if (isset($my_post["mqttInterval"]) and $my_post["mqttInterval"] != 0) $ahoy_data["mqtt"]["Interval"] = $my_post["mqttInterval"];
	else unset($ahoy_data["mqtt"]["Interval"]);
    if (isset($my_post["retain"]) and $my_post["retain"] != "")		 $ahoy_data["mqtt"]["Retain"]   = $my_post["retain"];
	else unset($ahoy_data["mqtt"]["Retain"]);
	$ahoy_data["mqtt"]["enabled"] = (isset($my_post["mqttAddr"]) and $my_post["mqttAddr"] != "")  ? true : false;


	# Pinout Configuration
	# "pinLed0":"255","pinLed1":"255","pinLed2":"255","pinLedHighActive":"0","pinLedLum":"255",
	if (isset($my_post["pinLed0"]) and $my_post["pinLed0"] == "255") unset($ahoy_data["ledpin"]["pinLed0"]);
	else $ahoy_data["ledpin"]["pinLed0"] = $my_post["pinLed0"];
	if (isset($my_post["pinLed1"]) and $my_post["pinLed1"] == "255") unset($ahoy_data["ledpin"]["pinLed1"]);
	else $ahoy_data["ledpin"]["pinLed1"] = $my_post["pinLed1"];
	if (isset($my_post["pinLed2"]) and $my_post["pinLed2"] == "255") unset($ahoy_data["ledpin"]["pinLed2"]);
	else $ahoy_data["ledpin"]["pinLed2"] = $my_post["pinLed2"];
	if (isset($my_post["pinLedHighActive"]) and $my_post["pinLedHighActive"] == "0") unset($ahoy_data["ledpin"]["pinLedHighActive"]);
	else $ahoy_data["ledpin"]["pinLedHighActive"] = $my_post["pinLedHighActive"];
	if (isset($my_post["pinLedLum"]) and $my_post["pinLedLum"] == "0") unset($ahoy_data["ledpin"]["pinLedLum"]);
	else $ahoy_data["ledpin"]["pinLedLum"] = $my_post["pinLedLum"];
	if (isset($ahoy_data["ledpin"]) and count($ahoy_data["ledpin"]) == 0) unset($ahoy_data["ledpin"]);
	
	# "pinCs":"255","pinCe":"255","pinIrq":"255","pinSclk":"255","pinMosi":"255","pinMiso":"255"
	if (isset($my_post["nrfEnable"]) and $my_post["nrfEnable"] == "on") $ahoy_data["nrf"]["enabled"] = $my_post["nrfEnable"];
	else $ahoy_data["nrf"]["enabled"] = false;
	if (isset($my_post["pinCs"])    and $my_post["pinCs"]   == "255") unset($ahoy_data["nrf"]["pinCs"]);
	else $ahoy_data["nrf"]["pinCs"]   = $my_post["pinCs"];
	if (isset($my_post["pinCe"])    and $my_post["pinCe"]   == "255") unset($ahoy_data["nrf"]["pinCe"]);
	else $ahoy_data["nrf"]["pinCe"]   = $my_post["pinCe"];
	if (isset($my_post["pinIrq"])   and $my_post["pinIrq"]  == "255") unset($ahoy_data["nrf"]["pinIrq"]);
	else $ahoy_data["nrf"]["pinIrq"]  = $my_post["pinIrq"];
	if (isset($my_post["pinSclk"])  and $my_post["pinSclk"] == "255") unset($ahoy_data["nrf"]["pinSclk"]);
	else $ahoy_data["nrf"]["pinSclk"] = $my_post["pinSclk"];
	if (isset($my_post["pinMosi"])  and $my_post["pinMosi"] == "255") unset($ahoy_data["nrf"]["pinMosi"]);
	else $ahoy_data["nrf"]["pinMosi"] = $my_post["pinMosi"];
	if (isset($my_post["pinMiso"])  and $my_post["pinMiso"] == "255") unset($ahoy_data["nrf"]["pinMiso"]);
	else $ahoy_data["nrf"]["pinMiso"] = $my_post["pinMiso"];

	# "cmtEnable":"on","pinCmtSclk":"255","pinSdio":"255","pinCsb":"255","pinFcsb":"255","pinGpio3":"255"
	if (isset($my_post["cmtEnable"]) and $my_post["cmtEnable"] == "on") $ahoy_data["cmt"]["enabled"] = $my_post["cmtEnable"];
	else $ahoy_data["cmt"]["enabled"] = false;
	if (isset($my_post["pinCmtSclk"])  and $my_post["pinCmtSclk"] == "255") unset($ahoy_data["cmt"]["pinCmtSclk"]);
	else $ahoy_data["cmt"]["pinCmtSclk"] = $my_post["pinCmtSclk"];
	if (isset($my_post["pinSdio"])     and $my_post["pinSdio"]    == "255") unset($ahoy_data["cmt"]["pinSdio"]);
	else $ahoy_data["cmt"]["pinSdio"]    = $my_post["pinSdio"];
	if (isset($my_post["pinCsb"])      and $my_post["pinCsb"]     == "255") unset($ahoy_data["cmt"]["pinCsb"]);
	else $ahoy_data["cmt"]["pinCsb"]     = $my_post["pinCsb"];
	if (isset($my_post["pinFcsb"])     and $my_post["pinFcsb"]    == "255") unset($ahoy_data["cmt"]["pinFcsb"]);
	else $ahoy_data["cmt"]["pinFcsb"]    = $my_post["pinFcsb"];
	if (isset($my_post["pinGpio3"])    and $my_post["pinGpio3"]   == "255") unset($ahoy_data["cmt"]["pinGpio3"]);
	else $ahoy_data["cmt"]["pinGpio3"]   = $my_post["pinGpio3"];

	# "ethEn":"on","ethCs":"255","ethSclk":"255","ethMiso":"255","ethMosi":"255","ethIrq":"255","ethRst":"255"
	if (isset($my_post["ethEn"])    and $my_post["ethEn"]   != "on") unset($ahoy_data["eth"]["ethEn"]);
	else $ahoy_data["eth"]["ethEn"]   = $my_post["ethEn"];
	if (isset($my_post["ethCs"])    and $my_post["ethCs"]   == "255") unset($ahoy_data["eth"]["ethCs"]);
	else $ahoy_data["eth"]["ethCs"]   = $my_post["ethCs"];
	if (isset($my_post["ethSclk"])  and $my_post["ethSclk"] == "255") unset($ahoy_data["eth"]["ethSclk"]);
	else $ahoy_data["eth"]["ethSclk"] = $my_post["ethSclk"];
	if (isset($my_post["ethMiso"])  and $my_post["ethMiso"] == "255") unset($ahoy_data["eth"]["ethMiso"]);
	else $ahoy_data["eth"]["ethMiso"] = $my_post["ethMiso"];
	if (isset($my_post["ethMosi"])  and $my_post["ethMosi"] == "255") unset($ahoy_data["eth"]["ethMosi"]);
	else $ahoy_data["eth"]["ethMosi"] = $my_post["ethMosi"];
	if (isset($my_post["ethIrq"])   and $my_post["ethIrq"]  == "255") unset($ahoy_data["eth"]["ethIrq"]);
	else $ahoy_data["eth"]["ethIrq"]  = $my_post["ethIrq"];
	if (isset($my_post["ethRst"])   and $my_post["ethRst"]  == "255") unset($ahoy_data["eth"]["ethRst"]);
	else $ahoy_data["eth"]["ethRst"]  = $my_post["ethRst"];
	

	# aus Display Config
	## [disp_pwr] => on
	## [disp_cont] => 88
	## [disp_graph_ratio] => 1

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



	if (isset($_SERVER["QUERY_STRING"]) and $_SERVER["QUERY_STRING"] == "upload") {
		# file content
		$fileContentString = file_get_contents(htmlspecialchars($_FILES["upload"]["tmp_name"]));
		$fileContentArray  = json_decode($fileContentString, true);  ## WICHTIG: ",true" - sonst JSON und kein ARRAY
		$ahoy_data = $fileContentArray["ahoy"];
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
				"name"		=> $my_post["name"],
				"enabled"	=> $my_post["en"],
				"serial"	=> base_convert($my_post["ser"], 10, 16),  #!! Kodierung dec2hex
				"txpower"	=> $my_post["pa"],
				"strings"	=> [],
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
  			#file_put_contents("/tmp/AhoyDTU_asdf", "\n" . json_encode($inverter), FILE_APPEND | LOCK_EX);
			if (! isset($ahoy_data["inverters"])) $ahoy_data["inverters"] = [];
			$ahoy_data["inverters"][$my_post["id"]] = $inverter;
		}	
	}

	file_put_contents("/tmp/AhoyDTU_asdf", "\n_data_e: " . json_encode($ahoy_data) . "\n", FILE_APPEND | LOCK_EX);

	# Save changed data to AhoyDTU config file
	$RC = yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_data]);

	# print ($RC ? "alles OK\n" : "Fehler\n");
	if ($RC == true) {
		print json_encode(["success" => true]);

		##  [reboot] => on - from SAVE button
		# if (isset($my_post["reboot"])) { reboot in 1 sec.}
	} else {
		print_r ("\n\n" . "one ore more Settings changed, Error while saving config to: " . $myFilename . "\n");
		print ("Length: " . count($ahoy_data) . ": " . json_encode($ahoy_data) . "\n");
	}
}

