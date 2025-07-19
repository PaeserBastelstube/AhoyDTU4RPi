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

	if (isset($my_post["region"]))		$ahoy_data["WebServer"]["generic"]["region"]     = $my_post["region"];
	if (isset($my_post["timezone"]))	$ahoy_data["WebServer"]["generic"]["timezone"]   = $my_post["timezone"] -12;

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
	if (isset($ahoy_data["logging"]["serial"]) and count($ahoy_data["logging"]["serial"]) == 0) unset($ahoy_data["logging"]["serial"]);

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
	else unset($ahoy_data["WebServer"]["system"]["prot_mask"]);

	if (isset($ahoy_data["WebServer"]["system"]) and count($ahoy_data["WebServer"]["system"]) == 0) unset($ahoy_data["WebServer"]["system"]);

	# Inverter configuration # from web.h - line 512
	## [invInterval invRstMid invRstComStart invRstComStop invRstNotAvail invRstMaxMid strtWthtTm rdGrid

	# Interval [s]
	$ahoy_data["interval"] = $my_post["invInterval"] ?? 15;

	# Reset values and YieldDay at midnight
	if (isset($my_post["AtMidnight"])) $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] = $my_post["AtMidnight"];
	else unset($ahoy_data["WebServer"]["InverterReset"]["AtMidnight"]);

	# Reset values at sunrise
	if (isset($my_post["invRstComStart"])) $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] = $my_post["invRstComStart"];
	else unset($ahoy_data["WebServer"]["InverterReset"]["AtSunrise"]);

	# Reset values at sunset
	if (isset($my_post["invRstComStop"])) $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] = $my_post["invRstComStop"];
	else unset($ahoy_data["WebServer"]["InverterReset"]["AtSunset"]);

	# Reset values when inverter status is 'not available'
	if (isset($my_post["invRstNotAvail"]))	$ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] = $my_post["invRstNotAvail"];
	else unset($ahoy_data["WebServer"]["InverterReset"]["NotAvailable"]);

	# Include reset 'max' values
	if (isset($my_post["invRstMaxMid"]))	$ahoy_data["WebServer"]["InverterReset"]["MaxValues"] = $my_post["invRstMaxMid"];
	else unset($ahoy_data["WebServer"]["InverterReset"]["MaxValues"]);

	# Start without time sync (useful in AP-Only-Mode)
	if (isset($my_post["strtWthtTm"]))	$ahoy_data["WebServer"]["strtWthtTm"] = $my_post["strtWthtTm"];
	else unset($ahoy_data["WebServer"]["strtWthtTm"]);

	# Read Grid Profile
	if (isset($my_post["rdGrid"]))	$ahoy_data["WebServer"]["rdGrid"] = $my_post["rdGrid"];
	else unset($ahoy_data["WebServer"]["rdGrid"]);

	if (isset($ahoy_data["WebServer"]["InverterReset"]) and count($ahoy_data["WebServer"]["InverterReset"]) == 0) unset($ahoy_data["WebServer"]["InverterReset"]);

	# NTP Server configuration # from web.h - line 566
	## ntpAddr  => wird im Betriebssystem verwaltet - nicht änderbar
	## ntpPort  => 123
	## ntpIntvl => 720


	# Sunrise & Sunset configuration # from web.h - line 573
	## sunLat - sunLon - sunOffsSr - sunOffsSs
	if (isset($my_post["sunLat"]) and $my_post["sunLat"] != "")	$ahoy_data["sunset"]["latitude"] = $my_post["sunLat"];
	else unset($ahoy_data["sunset"]["latitude"]);
	if (isset($my_post["sunLon"]) and $my_post["sunLon"] != "")	$ahoy_data["sunset"]["longitude"] = $my_post["sunLon"];
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
		if (isset($my_post["sunOffsSr"])) unset($ahoy_data["sunset"]["sunOffsSr"]);
		if (isset($my_post["sunOffsSs"])) unset($ahoy_data["sunset"]["sunOffsSs"]);
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
	if (isset($my_post["pinLed0"]) and $my_post["pinLed0"] != "255") $ahoy_data["ledpin"]["pinLed0"] = $my_post["pinLed0"];
	else unset($ahoy_data["ledpin"]["pinLed0"]);
	if (isset($my_post["pinLed1"]) and $my_post["pinLed1"] != "255") $ahoy_data["ledpin"]["pinLed1"] = $my_post["pinLed1"];
	else unset($ahoy_data["ledpin"]["pinLed1"]);
	if (isset($my_post["pinLed2"]) and $my_post["pinLed2"] != "255") $ahoy_data["ledpin"]["pinLed2"] = $my_post["pinLed2"];
	else unset($ahoy_data["ledpin"]["pinLed2"]);
	if (isset($my_post["pinLedHighActive"]) and $my_post["pinLedHighActive"] != "0") $ahoy_data["ledpin"]["pinLedHighActive"] = $my_post["pinLedHighActive"];
	else unset($ahoy_data["ledpin"]["pinLedHighActive"]);
	if (isset($my_post["pinLedLum"]) and $my_post["pinLedLum"] != "0") $ahoy_data["ledpin"]["pinLedLum"] = $my_post["pinLedLum"];
	else unset($ahoy_data["ledpin"]["pinLedLum"]);
	if (isset($ahoy_data["ledpin"]) and count($ahoy_data["ledpin"]) == 0) unset($ahoy_data["ledpin"]);
	
	# "pinCs":"255","pinCe":"255","pinIrq":"255","pinSclk":"255","pinMosi":"255","pinMiso":"255"
	if (isset($my_post["nrfEnable"]) and $my_post["nrfEnable"] == "on") $ahoy_data["nrf"]["enabled"] = $my_post["nrfEnable"];
	else $ahoy_data["nrf"]["enabled"] = false;
	if (isset($my_post["spiCSN"])	and $ahoy_data["nrf"]["enabled"]) $ahoy_data["nrf"]["spiCSN"]  = $my_post["spiCSN"];
	else unset($ahoy_data["nrf"]["spiCSN"]);
	if (isset($my_post["spiSpeed"]) and $my_post["spiSpeed"]!= "255") $ahoy_data["nrf"]["spiSpeed"]= $my_post["spiSpeed"];
	else unset($ahoy_data["nrf"]["spiSpeed"]);
	if (isset($my_post["spiCe"])    and $my_post["spiCe"]   != "255") $ahoy_data["nrf"]["spiCe"]   = $my_post["spiCe"];
	else unset($ahoy_data["nrf"]["spiCe"]);
	if (isset($my_post["spiCs"])    and $my_post["spiCs"]   != "255") $ahoy_data["nrf"]["spiCs"]   = $my_post["spiCs"];
	else unset($ahoy_data["nrf"]["spiCs"]);
	if (isset($my_post["spiIrq"])   and $my_post["spiIrq"]  != "255") $ahoy_data["nrf"]["spiIrq"]  = $my_post["spiIrq"];
	else unset($ahoy_data["nrf"]["spiIrq"]);
	if (isset($my_post["spiSclk"])  and $my_post["spiSclk"] != "255") $ahoy_data["nrf"]["spiSclk"] = $my_post["spiSclk"];
	else unset($ahoy_data["nrf"]["spiSclk"]);
	if (isset($my_post["spiMosi"])  and $my_post["spiMosi"] != "255") $ahoy_data["nrf"]["spiMosi"] = $my_post["spiMosi"];
	else unset($ahoy_data["nrf"]["spiMosi"]);
	if (isset($my_post["spiMiso"])  and $my_post["spiMiso"] != "255") $ahoy_data["nrf"]["spiMiso"] = $my_post["spiMiso"];
	else unset($ahoy_data["nrf"]["spiMiso"]);

	# "cmtEnable":"on","pinCmtSclk":"255","pinSdio":"255","pinCsb":"255","pinFcsb":"255","pinGpio3":"255"
	if (isset($my_post["cmtEnable"]) and $my_post["cmtEnable"] == "on") $ahoy_data["cmt"]["enabled"] = $my_post["cmtEnable"];
	else $ahoy_data["cmt"]["enabled"] = false;
	if (isset($my_post["pinCmtSclk"])  and $my_post["pinCmtSclk"] != "255") $ahoy_data["cmt"]["pinCmtSclk"] = $my_post["pinCmtSclk"];
	else unset($ahoy_data["cmt"]["pinCmtSclk"]);
	if (isset($my_post["pinSdio"])     and $my_post["pinSdio"]    != "255") $ahoy_data["cmt"]["pinSdio"]    = $my_post["pinSdio"];
	else unset($ahoy_data["cmt"]["pinSdio"]);
	if (isset($my_post["pinCsb"])      and $my_post["pinCsb"]     != "255") $ahoy_data["cmt"]["pinCsb"]     = $my_post["pinCsb"];
	else unset($ahoy_data["cmt"]["pinCsb"]);
	if (isset($my_post["pinFcsb"])     and $my_post["pinFcsb"]    != "255") $ahoy_data["cmt"]["pinFcsb"]    = $my_post["pinFcsb"];
	else unset($ahoy_data["cmt"]["pinFcsb"]);
	if (isset($my_post["pinGpio3"])    and $my_post["pinGpio3"]   != "255") $ahoy_data["cmt"]["pinGpio3"]   = $my_post["pinGpio3"];
	else unset($ahoy_data["cmt"]["pinGpio3"]);

	# "ethEn":"on","ethCs":"255","ethSclk":"255","ethMiso":"255","ethMosi":"255","ethIrq":"255","ethRst":"255"
	if (isset($my_post["ethEn"])    and $my_post["ethEn"]   == "on")  $ahoy_data["eth"]["ethEn"]   = $my_post["ethEn"];
	else unset($ahoy_data["eth"]["ethEn"]);
	if (isset($my_post["ethCs"])    and $my_post["ethCs"]   != "255") $ahoy_data["eth"]["ethCs"]   = $my_post["ethCs"];
	else unset($ahoy_data["eth"]["ethCs"]);
	if (isset($my_post["ethSclk"])  and $my_post["ethSclk"] != "255") $ahoy_data["eth"]["ethSclk"] = $my_post["ethSclk"];
	else unset($ahoy_data["eth"]["ethSclk"]);
	if (isset($my_post["ethMiso"])  and $my_post["ethMiso"] != "255") $ahoy_data["eth"]["ethMiso"] = $my_post["ethMiso"];
	else unset($ahoy_data["eth"]["ethMiso"]);
	if (isset($my_post["ethMosi"])  and $my_post["ethMosi"] != "255") $ahoy_data["eth"]["ethMosi"] = $my_post["ethMosi"];
	else unset($ahoy_data["eth"]["ethMosi"]);
	if (isset($my_post["ethIrq"])   and $my_post["ethIrq"]  != "255") $ahoy_data["eth"]["ethIrq"]  = $my_post["ethIrq"];
	else unset($ahoy_data["eth"]["ethIrq"]);
	if (isset($my_post["ethRst"])   and $my_post["ethRst"]  != "255") $ahoy_data["eth"]["ethRst"]  = $my_post["ethRst"];
	else unset($ahoy_data["eth"]["ethRst"]);
	if (isset($ahoy_data["eth"])	and count($ahoy_data["eth"]) == 0) unset($ahoy_data["eth"]);
	

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

