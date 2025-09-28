<?PHP
header('Content-Type: application/json; charset=utf-8');


$debug_fn = "/tmp/AhoyDTU_debug.log";
function saveDebug($my_post, $ahoy_conf = ""){
	global $debug_fn;
	file_put_contents($debug_fn, "//_my_post: ". json_encode($my_post)	. PHP_EOL, LOCK_EX);
	file_put_contents($debug_fn, "//_get :"    . json_encode($_GET)		. PHP_EOL, FILE_APPEND | LOCK_EX);
	file_put_contents($debug_fn, "//_post :"   . json_encode($_POST)	. PHP_EOL, FILE_APPEND | LOCK_EX);
	file_put_contents($debug_fn, "//_files :"  . json_encode($_FILES)	. PHP_EOL, FILE_APPEND | LOCK_EX);
	file_put_contents($debug_fn, "//_server :" . json_encode($_SERVER)	. PHP_EOL, FILE_APPEND | LOCK_EX);
	file_put_contents($debug_fn, "//_conf_s :" . json_encode($ahoy_conf). PHP_EOL, FILE_APPEND | LOCK_EX);
}

function saveSettings($my_post){
	global $ahoy_conf, $ahoy_config;
	saveDebug($my_post, $ahoy_conf);

	## System Config # from web.h - line 465
	## [device] =>  $(hostname)	# wird im Betriebssystem verwaltet - nicht änderbar
	## [schedReboot] => on		# Neustart um Mitternacht - Web-Servers / Ahoy # sudo systemctl restart nginx ...
	## [darkMode] => on			# unlink / link CSS file
	## [cstLnk] => ""			# custom link addr
	## [cstLnkTxt] => ""		# custom link text
	## [region] => 0			# wofür wird das benötigt
	## [timezone] => 13			# wofür wird das benötigt

	# Reboot Ahoy at midnight
	if (isset($my_post["schedReboot"]))	$ahoy_conf["WebServer"]["system"]["sched_reboot"] = $my_post["schedReboot"];
	else unset($ahoy_conf["WebServer"]["system"]["sched_reboot"]);

	# check and switch for Dark or Bright color
	if (isset ($my_post["darkMode"]) and $my_post["darkMode"] == "on") {
		unlink ('../html/colors.css');
		symlink ('../html/colorDark.css', '../html/colors.css');
	} else {
		unlink ('../html/colors.css');
		symlink ('../html/colorBright.css', '../html/colors.css');
	}

	if (isset($my_post["region"]))		$ahoy_conf["WebServer"]["generic"]["region"]     = $my_post["region"];
	if (isset($my_post["timezone"]))	$ahoy_conf["WebServer"]["generic"]["timezone"]   = $my_post["timezone"] -12;

	# custom link
	if (isset($my_post["cstLnk"]))		$ahoy_conf["WebServer"]["generic"]["cst"]["lnk"] = $my_post["cstLnk"];
	if (isset($my_post["cstLnkTxt"]))	$ahoy_conf["WebServer"]["generic"]["cst"]["txt"] = $my_post["cstLnkTxt"];


	## System configuration / Serial console # from web.h - line 603
	# "serEn":"on","serDbg":"on","priv":"on","wholeTrace":"on","log2mqtt":"on",
	if (isset($my_post["serEn"]))		$ahoy_conf["logging"]["serial"]["serEn"] = $my_post["serEn"];
	else unset($ahoy_conf["logging"]["serial"]["serEn"]);
	if (isset($my_post["serDbg"]))		$ahoy_conf["logging"]["serial"]["serDbg"] = $my_post["serDbg"];
	else unset($ahoy_conf["logging"]["serial"]["serDbg"]);
	if (isset($my_post["priv"]))		$ahoy_conf["logging"]["serial"]["priv"] = $my_post["priv"];
	else unset($ahoy_conf["logging"]["serial"]["priv"]);
	if (isset($my_post["wholeTrace"]))	$ahoy_conf["logging"]["serial"]["wholeTrace"] = $my_post["wholeTrace"];
	else unset($ahoy_conf["logging"]["serial"]["wholeTrace"]);
	if (isset($my_post["log2mqtt"]))	$ahoy_conf["logging"]["serial"]["log2mqtt"] = $my_post["log2mqtt"];
	else unset($ahoy_conf["logging"]["serial"]["log2mqtt"]);
	if (isset($ahoy_conf["logging"]["serial"]) and count($ahoy_conf["logging"]["serial"]) == 0) unset($ahoy_conf["logging"]["serial"]);

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
 		if ($my_post["adminpwd"] == "") unset($ahoy_conf["WebServer"]["system"]["pwd_pwd"]);
 		else $ahoy_conf["WebServer"]["system"]["pwd_pwd"] = $my_post["adminpwd"];
	}
	if (isset($my_post["login"]) and $my_post["login"] == "login") {
		if (isset($my_post["pwd"]) and $my_post["pwd"] == $ahoy_conf["WebServer"]["system"]["pwd_pwd"])
			unset($ahoy_conf["WebServer"]["system"]["pwd_pwd"]);
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
	if ($prot_mask > 0) $ahoy_conf["WebServer"]["system"]["prot_mask"] = $prot_mask;
	else unset($ahoy_conf["WebServer"]["system"]["prot_mask"]);

	if (isset($ahoy_conf["WebServer"]["system"]) and count($ahoy_conf["WebServer"]["system"]) == 0) unset($ahoy_conf["WebServer"]["system"]);

	# Inverter configuration # from web.h - line 512
	## [invInterval invRstMid invRstComStart invRstComStop invRstNotAvail invRstMaxMid strtWthtTm rdGrid

	# Interval [s]
	$ahoy_conf["interval"] = intval($my_post["invInterval"] ?? 15);
    if ($ahoy_conf["interval"] < 5) $ahoy_conf["interval"] = 15;

	# Reset values and YieldDay at midnight
	if (isset($my_post["AtMidnight"])) $ahoy_conf["WebServer"]["InverterReset"]["AtMidnight"] = $my_post["AtMidnight"];
	else unset($ahoy_conf["WebServer"]["InverterReset"]["AtMidnight"]);

	# Reset values at sunrise
	if (isset($my_post["invRstComStart"])) $ahoy_conf["WebServer"]["InverterReset"]["AtSunrise"] = $my_post["invRstComStart"];
	else unset($ahoy_conf["WebServer"]["InverterReset"]["AtSunrise"]);

	# Reset values at sunset
	if (isset($my_post["invRstComStop"])) $ahoy_conf["WebServer"]["InverterReset"]["AtSunset"] = $my_post["invRstComStop"];
	else unset($ahoy_conf["WebServer"]["InverterReset"]["AtSunset"]);

	# Reset values when inverter status is 'not available'
	if (isset($my_post["invRstNotAvail"]))	$ahoy_conf["WebServer"]["InverterReset"]["NotAvailable"] = $my_post["invRstNotAvail"];
	else unset($ahoy_conf["WebServer"]["InverterReset"]["NotAvailable"]);

	# Include reset 'max' values
	if (isset($my_post["invRstMaxMid"]))	$ahoy_conf["WebServer"]["InverterReset"]["MaxValues"] = $my_post["invRstMaxMid"];
	else unset($ahoy_conf["WebServer"]["InverterReset"]["MaxValues"]);

	# Start without time sync (useful in AP-Only-Mode)
	if (isset($my_post["strtWthtTm"]))	$ahoy_conf["WebServer"]["strtWthtTm"] = $my_post["strtWthtTm"];
	else unset($ahoy_conf["WebServer"]["strtWthtTm"]);

	# Read Grid Profile
	if (isset($my_post["rdGrid"]))	$ahoy_conf["WebServer"]["rdGrid"] = $my_post["rdGrid"];
	else unset($ahoy_conf["WebServer"]["rdGrid"]);

	if (isset($ahoy_conf["WebServer"]["InverterReset"]) and count($ahoy_conf["WebServer"]["InverterReset"]) == 0) unset($ahoy_conf["WebServer"]["InverterReset"]);

	# NTP Server configuration # from web.h - line 566
	## ntpAddr  => wird im Betriebssystem verwaltet - nicht änderbar
	## ntpPort  => 123
	## ntpIntvl => 720


	# Sunrise & Sunset configuration # from web.h - line 573
	## sunLat - sunLon - sunOffsSr - sunOffsSs
	if (isset($my_post["sunLat"]) and $my_post["sunLat"] != "")	$ahoy_conf["sunset"]["latitude"] = floatval($my_post["sunLat"]);
	else unset($ahoy_conf["sunset"]["latitude"]);
	if (isset($my_post["sunLon"]) and $my_post["sunLon"] != "")	$ahoy_conf["sunset"]["longitude"] = floatval($my_post["sunLon"]);
	else unset($ahoy_conf["sunset"]["longitude"]);
	if (isset($my_post["sunOffsSr"]))	$ahoy_conf["sunset"]["sunOffsSr"] = 60 * $my_post["sunOffsSr"];
	else unset($ahoy_conf["sunset"]["sunOffsSr"]);
	if (isset($my_post["sunOffsSs"]))	$ahoy_conf["sunset"]["sunOffsSs"] = 60 * $my_post["sunOffsSs"];
	else unset($ahoy_conf["sunset"]["sunOffsSs"]);

	if (isset($ahoy_conf["sunset"]["latitude"]) and $ahoy_conf["sunset"]["latitude"] != "" and
		isset($ahoy_conf["sunset"]["longitude"]) and $ahoy_conf["sunset"]["longitude"] != "") {
		$ahoy_conf["sunset"]["enabled"] = true;
	} else {
		$ahoy_conf["sunset"]["enabled"] = false;
		if (isset($my_post["sunOffsSr"])) unset($ahoy_conf["sunset"]["sunOffsSr"]);
		if (isset($my_post["sunOffsSs"])) unset($ahoy_conf["sunset"]["sunOffsSs"]);
	}

	# MQTT configuration # from web.h - line 586
	## mqttAddr - mqttPort - mqttClientId - mqttUser - mqttPwd - mqttTopic - mqttJson - mqttInterval - retain
    if (isset($my_post["mqttAddr"]) and $my_post["mqttAddr"]		!= "")	$ahoy_conf["mqtt"]["host"]		= $my_post["mqttAddr"];
	else unset($ahoy_conf["mqtt"]["host"]);
    if (isset($my_post["mqttPort"]) and $my_post["mqttPort"]		!= "")	$ahoy_conf["mqtt"]["port"]		= intval($my_post["mqttPort"]);
	else unset($ahoy_conf["mqtt"]["port"]);
    if (isset($my_post["mqttClientId"]) and $my_post["mqttClientId"]!= "")	$ahoy_conf["mqtt"]["clientId"]	= $my_post["mqttClientId"];
	else unset($ahoy_conf["mqtt"]["clientId"]);
    if (isset($my_post["mqttUser"]) and $my_post["mqttUser"]		!= "")	$ahoy_conf["mqtt"]["user"]		= $my_post["mqttUser"];
	else unset($ahoy_conf["mqtt"]["user"]);
    if (isset($my_post["mqttPwd"]) and $my_post["mqttPwd"]			!= "")	$ahoy_conf["mqtt"]["password"]	= $my_post["mqttPwd"];
	else unset($ahoy_conf["mqtt"]["password"]);
    if (isset($my_post["mqttTopic"]) and $my_post["mqttTopic"]		!= "")	$ahoy_conf["mqtt"]["sub_topic"]	= $my_post["mqttTopic"];
	else unset($ahoy_conf["mqtt"]["topic"]);
    if (isset($my_post["mqttJson"]) and $my_post["mqttJson"]		!= "")	$ahoy_conf["mqtt"]["asJson"]	= true;
	else unset($ahoy_conf["mqtt"]["asJson"]);
    if (isset($my_post["mqttInterval"]) and $my_post["mqttInterval"]!= 0)	$ahoy_conf["mqtt"]["Interval"]	= intval($my_post["mqttInterval"]);
	else unset($ahoy_conf["mqtt"]["Interval"]);
    if (isset($my_post["retain"]) and $my_post["retain"]			!= "")	$ahoy_conf["mqtt"]["Retain"]	= true;
	else unset($ahoy_conf["mqtt"]["Retain"]);
	$ahoy_conf["mqtt"]["enabled"] = (isset($ahoy_conf["mqtt"]["host"]) and isset($ahoy_conf["mqtt"]["port"]))  ? true : false;


	# Pinout Configuration
	# "pinLed0":"255","pinLed1":"255","pinLed2":"255","pinLedHighActive":"0","pinLedLum":"255",
	if (isset($my_post["pinLed0"]) and $my_post["pinLed0"] != "255") $ahoy_conf["ledpin"]["pinLed0"] = $my_post["pinLed0"];
	else unset($ahoy_conf["ledpin"]["pinLed0"]);
	if (isset($my_post["pinLed1"]) and $my_post["pinLed1"] != "255") $ahoy_conf["ledpin"]["pinLed1"] = $my_post["pinLed1"];
	else unset($ahoy_conf["ledpin"]["pinLed1"]);
	if (isset($my_post["pinLed2"]) and $my_post["pinLed2"] != "255") $ahoy_conf["ledpin"]["pinLed2"] = $my_post["pinLed2"];
	else unset($ahoy_conf["ledpin"]["pinLed2"]);
	if (isset($my_post["pinLedHighActive"]) and $my_post["pinLedHighActive"] != "0") $ahoy_conf["ledpin"]["pinLedHighActive"] = $my_post["pinLedHighActive"];
	else unset($ahoy_conf["ledpin"]["pinLedHighActive"]);
	if (isset($my_post["pinLedLum"]) and $my_post["pinLedLum"] != "0") $ahoy_conf["ledpin"]["pinLedLum"] = $my_post["pinLedLum"];
	else unset($ahoy_conf["ledpin"]["pinLedLum"]);
	if (isset($ahoy_conf["ledpin"]) and count($ahoy_conf["ledpin"]) == 0) unset($ahoy_conf["ledpin"]);
	
	# "pinCs":"255","pinCe":"255","pinIrq":"255","pinSclk":"255","pinMosi":"255","pinMiso":"255"
	if (isset($my_post["nrfEnable"]) and $my_post["nrfEnable"] == "on") $ahoy_conf["nrf"]["enabled"] = $my_post["nrfEnable"];
	else $ahoy_conf["nrf"]["enabled"] = false;
	if (isset($my_post["spiCSN"])	and $ahoy_conf["nrf"]["enabled"]) $ahoy_conf["nrf"]["spiCSN"]  = intval($my_post["spiCSN"]);
	else unset($ahoy_conf["nrf"]["spiCSN"]);
	if (isset($my_post["spiSpeed"]) and $my_post["spiSpeed"]!= "255") $ahoy_conf["nrf"]["spiSpeed"]= intval($my_post["spiSpeed"]);
	else unset($ahoy_conf["nrf"]["spiSpeed"]);
	if (isset($my_post["spiCe"])    and $my_post["spiCe"]   != "255") $ahoy_conf["nrf"]["spiCe"]   = intval($my_post["spiCe"]);
	else unset($ahoy_conf["nrf"]["spiCe"]);
	if (isset($my_post["spiCs"])    and $my_post["spiCs"]   != "255") $ahoy_conf["nrf"]["spiCs"]   = intval($my_post["spiCs"]);
	else unset($ahoy_conf["nrf"]["spiCs"]);
	if (isset($my_post["spiIrq"])   and $my_post["spiIrq"]  != "255") $ahoy_conf["nrf"]["spiIrq"]  = intval($my_post["spiIrq"]);
	else unset($ahoy_conf["nrf"]["spiIrq"]);
	if (isset($my_post["spiSclk"])  and $my_post["spiSclk"] != "255") $ahoy_conf["nrf"]["spiSclk"] = intval($my_post["spiSclk"]);
	else unset($ahoy_conf["nrf"]["spiSclk"]);
	if (isset($my_post["spiMosi"])  and $my_post["spiMosi"] != "255") $ahoy_conf["nrf"]["spiMosi"] = intval($my_post["spiMosi"]);
	else unset($ahoy_conf["nrf"]["spiMosi"]);
	if (isset($my_post["spiMiso"])  and $my_post["spiMiso"] != "255") $ahoy_conf["nrf"]["spiMiso"] = intval($my_post["spiMiso"]);
	else unset($ahoy_conf["nrf"]["spiMiso"]);

	# "cmtEnable":"on","pinCmtSclk":"255","pinSdio":"255","pinCsb":"255","pinFcsb":"255","pinGpio3":"255"
	if (isset($my_post["cmtEnable"]) and $my_post["cmtEnable"] == "on") $ahoy_conf["cmt"]["enabled"] = $my_post["cmtEnable"];
	else $ahoy_conf["cmt"]["enabled"] = false;
	if (isset($my_post["pinCmtSclk"])  and $my_post["pinCmtSclk"] != "255") $ahoy_conf["cmt"]["pinCmtSclk"] = $my_post["pinCmtSclk"];
	else unset($ahoy_conf["cmt"]["pinCmtSclk"]);
	if (isset($my_post["pinSdio"])     and $my_post["pinSdio"]    != "255") $ahoy_conf["cmt"]["pinSdio"]    = $my_post["pinSdio"];
	else unset($ahoy_conf["cmt"]["pinSdio"]);
	if (isset($my_post["pinCsb"])      and $my_post["pinCsb"]     != "255") $ahoy_conf["cmt"]["pinCsb"]     = $my_post["pinCsb"];
	else unset($ahoy_conf["cmt"]["pinCsb"]);
	if (isset($my_post["pinFcsb"])     and $my_post["pinFcsb"]    != "255") $ahoy_conf["cmt"]["pinFcsb"]    = $my_post["pinFcsb"];
	else unset($ahoy_conf["cmt"]["pinFcsb"]);
	if (isset($my_post["pinGpio3"])    and $my_post["pinGpio3"]   != "255") $ahoy_conf["cmt"]["pinGpio3"]   = $my_post["pinGpio3"];
	else unset($ahoy_conf["cmt"]["pinGpio3"]);

	# "ethEn":"on","ethCs":"255","ethSclk":"255","ethMiso":"255","ethMosi":"255","ethIrq":"255","ethRst":"255"
	if (isset($my_post["ethEn"])    and $my_post["ethEn"]   == "on")  $ahoy_conf["eth"]["ethEn"]   = $my_post["ethEn"];
	else unset($ahoy_conf["eth"]["ethEn"]);
	if (isset($my_post["ethCs"])    and $my_post["ethCs"]   != "255") $ahoy_conf["eth"]["ethCs"]   = $my_post["ethCs"];
	else unset($ahoy_conf["eth"]["ethCs"]);
	if (isset($my_post["ethSclk"])  and $my_post["ethSclk"] != "255") $ahoy_conf["eth"]["ethSclk"] = $my_post["ethSclk"];
	else unset($ahoy_conf["eth"]["ethSclk"]);
	if (isset($my_post["ethMiso"])  and $my_post["ethMiso"] != "255") $ahoy_conf["eth"]["ethMiso"] = $my_post["ethMiso"];
	else unset($ahoy_conf["eth"]["ethMiso"]);
	if (isset($my_post["ethMosi"])  and $my_post["ethMosi"] != "255") $ahoy_conf["eth"]["ethMosi"] = $my_post["ethMosi"];
	else unset($ahoy_conf["eth"]["ethMosi"]);
	if (isset($my_post["ethIrq"])   and $my_post["ethIrq"]  != "255") $ahoy_conf["eth"]["ethIrq"]  = $my_post["ethIrq"];
	else unset($ahoy_conf["eth"]["ethIrq"]);
	if (isset($my_post["ethRst"])   and $my_post["ethRst"]  != "255") $ahoy_conf["eth"]["ethRst"]  = $my_post["ethRst"];
	else unset($ahoy_conf["eth"]["ethRst"]);
	if (isset($ahoy_conf["eth"])	and count($ahoy_conf["eth"]) == 0) unset($ahoy_conf["eth"]);
	

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

	saveToAhoyConfigFile($ahoy_conf, $ahoy_config);
}


function ap_ctrl($my_post){			# Active Power Control for inverter
	# _my_post: {"id":0,"token":"*","cmd":"limit_nonpersistent_relative","val":"66"}	# relative = in %
	# _my_post: {"id":0,"token":"*","cmd":"limit_persistent_absolute","val":"870"}		# absolute in Watt
	# _my_post: {"id":0,"token":"*","cmd":"limit_persistent_relative","val":"66"}		# persistent = Keep limit over inverter restart = yes
	# _my_post: {"id":0,"token":"*","cmd":"limit_nonpersistent_relative","val":"870"}	# nonpersistent = no
	# _my_post: {"id":0,"token":"*","cmd":"restart","val":"0"}							# Restart Inverter
	# _my_post: {"id":0,"token":"*","cmd":"power","val":"0"}							# Inverter Ausschalten
	# _my_post: {"id":0,"token":"*","cmd":"power","val":"1"}							# Inverter Einschalten

	global $ahoy_conf, $ahoy_config;
	saveDebug($my_post, $ahoy_conf);

	# Welche Kommandos müssen nun von der AhoyDTU an den WR gesendet werden?
	$RC = sendInverterConfig($ahoy_config["filename"], $my_post);
    # RC = 0 --> all OK
    # RC = 1 --> no	ftokKey created
    # RC = 2 --> no msg_queue_exists
    # RC = 3 --> creation of message-queue failed
    # RC = 4 --> message-queue not empty

	switch ($RC) {
	case 0:
		print json_encode(["success" => true]);
		break;
	case 1:
		print json_encode(["error" => "ERR_LIMIT_NOT_ACCEPT", "code" => "no ftokKey created"]);
		break;
	case 2:
		print json_encode(["error" => "ERR_LIMIT_NOT_ACCEPT", "code" => "no msg_queue_exists"]);
		break;
	case 3:
		print json_encode(["error" => "ERR_LIMIT_NOT_ACCEPT", "code" => "creation of message-queue failed"]);
		break;
	case 4:
		print json_encode(["error" => "ERR_LIMIT_NOT_ACCEPT", "code" => "message-queue not empty"]);
		break;
	}

	# JavaScript Return-Code messages:
	# "ERR_AUTH"				--> "authentication error"
	# "ERR_INDEX"				--> "inverter index invalid"
	# "ERR_UNKNOWN_CMD")		--> "unknown cmd"
	# "ERR_LIMIT_NOT_ACCEPT")	--> "inverter does not accept dev control request at this moment"
	# "ERR_UNKNOWN_CMD")		--> "authentication error"
}

function importSettings($my_post){		# UPLOAD / IMPORT SETTINGS
	require_once 'generic_json.php';	# to load current AhoyDTU configuration

	if (isset($_SERVER["QUERY_STRING"]) and $_SERVER["QUERY_STRING"] == "upload") {
		# file content
		$fileContentString = file_get_contents(htmlspecialchars($_FILES["upload"]["tmp_name"]));
		$fileContentArray  = json_decode($fileContentString, true);  ## WICHTIG: ",true" - sonst JSON und kein ARRAY

		saveDebug($my_post, ["fileContentString" => $fileContentString, "fileContentArray" => $fileContentArray]);
		$ahoy_conf = $fileContentArray["ahoy"];
	}
#	saveDebug($my_post, $ahoy_conf);
	saveToAhoyConfigFile($ahoy_conf, $ahoy_config);
}


# {"cmd":"save_iv","token":"*","id":1,"ser":142929835590,"name":"qwert","en":true,
# "ch":[{"pwr":"","name":"","yld":"0"},{"pwr":"","name":"","yld":"0"},
#       {"pwr":"","name":"","yld":""},{"pwr":"","name":"","yld":""},
#       {"pwr":"","name":"","yld":""},{"pwr":"","name":"","yld":""}],
# "pa":"0","freq":"12","disnightcom":false}

function saveInverter($my_post){
	require_once 'generic_json.php';    # to load AhoyDTU configuration
	saveDebug($my_post, $ahoy_conf);

	if (isset($my_post["cmd"]) and $my_post["cmd"] == "serial_utc_offset")	# call from serial WebConsole
		$ahoy_conf["WebServer"]["TimezoneOffset"] = $my_post["val"];

	# add new / delete inverter ## see setup.html:736
	if (isset($my_post["cmd"]) and $my_post["cmd"] == "save_iv") {  ## detect inverter commands

		if ($my_post["ser"] == 0) {                                   ## delete inverter
			# unset($ahoy_conf["inverters"][$my_post["id"]]);
			array_splice($ahoy_conf["inverters"], $my_post["id"], 1);

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
				"txpower"	=> $txpower,
				"strings"	=> [],
				"disnightcom" => $my_post["disnightcom"]
			];
			foreach ($my_post["ch"] as $ii => $channel) {             ## add channel to inv var
				if (isset($channel["name"]) and $channel["name"] != "") 
				$inverter["strings"][$ii] = array(
					"s_name"     => $channel["name"],
					"s_maxpower" => intval($channel["pwr"]),
					"s_yield"    => floatval($channel["yld"])
				);
	 		}
			#global $debug_fn;
			#file_put_contents($debug_fn, "\n" . json_encode($inverter), FILE_APPEND | LOCK_EX);
			if (! isset($ahoy_conf["inverters"])) $ahoy_conf["inverters"] = [];
			$ahoy_conf["inverters"][$my_post["id"]] = $inverter;
		}	
	}
	saveToAhoyConfigFile($ahoy_conf, $ahoy_config);
}

function saveToAhoyConfigFile($ahoy_conf, $ahoy_config) {
	global $debug_fn;
	file_put_contents($debug_fn, "//ahoy_config: " . gettype($ahoy_config) ."::". json_encode($ahoy_config, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
	file_put_contents($debug_fn, "//ahoy_conf_e: " . json_encode($ahoy_conf) . PHP_EOL, FILE_APPEND | LOCK_EX);

	# Save changed data to AhoyDTU config file
	$RC = yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_conf]);

	#print ($RC ? "alles OK\n" : "Fehler:".$ahoy_config);
	if ($RC == true) {
		print json_encode(["success" => true]);

		##  [reboot] => on - from SAVE button
		# if (isset($my_post["reboot"])) { reboot in 1 sec.}
	} else {
		print_r ("\n\n" . "one ore more Settings changed, Error while saving config to: " . $ahoy_config["filename"] . PHP_EOL);
		print ("Length: " . count($ahoy_conf) . ": " . json_encode($ahoy_conf) . PHP_EOL);
	}
}

