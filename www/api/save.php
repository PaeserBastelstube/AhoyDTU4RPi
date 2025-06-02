<?PHP

# from web.h - line 455
# aus System Config
##    [device] =>  $(hostname)      # wird im Betriebssystem verwaltet - nicht änderbar
##    [schedReboot] => on           # Neustart des Web-Servers # sudo systemctl restart nginx
##    [darkMode] => on              # ln -fs ../html/colorBright.css ../html/colors.css
##    [cstLnk] => asdf              # wird in ahoy.yml gespeichert
##    [cstLnkTxt] => asf-Text       # wird in ahoy.yml gespeichert
##    [region] => 0                 # wird im Betriebssystem verwaltet - nicht änderbar
##    [timezone] => 13              # wird im Betriebssystem verwaltet - nicht änderbar


##    [priv] => on

# aus Network
##    [ap_pwd] => esp_8266          #Standard in AhoyDTU
##    [ssid] => wifi-ssid
##    [hidd] => off
##    [pwd] => {PWD}
##    [ipAddr] => 1.1.1.1
##    [ipMask] => 2.2.2.2
##    [ipDns1] => 3.3.3.3
##    [ipDns2] => 4.4.4.4
##    [ipGateway] => 5.5.5.5

# aus Protection
##    [adminpwd] => {PWD}
##    [protMask0] => on
##    [protMask2] => on
##    [protMask3] => on
##    [protMask4] => on
##    [protMask5] => on

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

	



header('Content-Type: application/json; charset=utf-8');
if ($_SERVER["REQUEST_METHOD"] = "POST") {
	print_r ($_POST);
	# print_r ($_POST["darkMode"]);
	# print `pwd`;
    # print `ls -l ../html`;
    # print "\n";

	# check switch Dark or Bright color
	if ($_POST["darkMode"] == "on") {
		unlink ('../html/colors.css');
		symlink ('../html/colorDark.css', '../html/colors.css');
	} else {
		unlink ('../html/colors.css');
		symlink ('../html/colorBright.css', '../html/colors.css');
	}

	# Check custom link
	include 'generic_json.php';
	if (($ahoy_data["cst"]["lnk"] != $_POST["cstLnk"]) or
	    ($ahoy_data["cst"]["txt"] != $_POST["cstLnkTxt"])) {
		$yaml_data["ahoy"]["cst"]["lnk"] = $_POST["cstLnk"];
		$yaml_data["ahoy"]["cst"]["txt"] = $_POST["cstLnkTxt"];
		print_r ($yaml_data);
	}

    # check inverter - invInterval 
	if ($ahoy_data["interval"] != $_POST["invInterval"]) {
		$yaml_data["ahoy"]["interval"] = $_POST["invInterval"];
		print_r ($yaml_data);
	}

    # check Reset values
	if (($ahoy_data["InverterResetValues"]["AtMidnight"] != $_POST["invRstMid"]) or
        ($ahoy_data["InverterResetValues"]["AtSunrise"] != $_POST["invRstComStart"]) or
        ($ahoy_data["InverterResetValues"]["AtSunset"] != $_POST["invRstComStop"]) or
        ($ahoy_data["InverterResetValues"]["NotAvailable"] != $_POST["invRstNotAvail"]) or
        ($ahoy_data["InverterResetValues"]["MaxValues"] != $_POST["invRstMaxMid"])) 
    {
		$yaml_data["ahoy"]["AtMidnight"] = $_POST["invRstMid"];
        $ahoy_data["InverterResetValues"]["AtSunrise"] = $_POST["invRstComStart"];
        $ahoy_data["InverterResetValues"]["AtSunset"] = $_POST["invRstComStop"];
        $ahoy_data["InverterResetValues"]["NotAvailable"] = $_POST["invRstNotAvail"];
        $ahoy_data["InverterResetValues"]["MaxValues"] = $_POST["invRstMaxMid"];
		print_r ($yaml_data);
	}

} else {
	print_r ($_SERVER);
}

# print ($my_login);

if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  print "\n";
}
?>
