<?php
require_once 'generic_json.php'; # incl. reading ahoy.yml

# Default values
$simple_hw_data = array();
$all_hw_data = array();
$grid_data = array();
$config_data = array();
$status_data = array();
$alarm_data = array();
$MaxValues_data = array();
if (!isset($inverter_id)) $inverter_id = 0;

################################################################################
# Wie ist die Serien-Nummer der Inverter aufgebaut?
#   erste beiden Ziffern: 10 = Gen 2 (MI) und 11 = Gen 3(HM/HMS), 13 = HMT
#   dritte Ziffer: 2 = 1in1, 4 = 2in1 und 6 = 4in1, 8 = 6in1 Modell.
#   vierte Ziffer: 1 = HM, 2 = HMT und "Special MI´s", 4 = HMS
#   fünfte Ziffer ist die Jahreszahl, 1 = 2015, 2=2016, 3=2017, 4=2018, 5=2019,
#     6=2020, 7=2021, 8=2022, 9=2023, 2024 geht es wohl wieder bei 0 oder 1 los, 
#     da ja die Serien von vor 10 Jahren nicht mehr zu der Zeit Produziert werden.
#   sechste und siebte Ziffer ist die Kalenderwoche in der der WR vom Band lief.
#
# 1121-Series Intervers, 1 MPPT, 1 Phase
# 1141-Series Inverters, 2 MPPT, 1 Phase
# 1161-Series Inverters, 2 MPPT, 1 Phase
# 
# define max-power in 2 steps
# 1) look at "serial numer"
# 2) read "Hardware numer" and compare with list:
#    $ahoy_conf["inverters"][$inverter_id]["name"] ?? "",
#    .../src/hm/hmDefines.h[333]
################################################################################
$max_pwr = 0;

if (isset($ahoy_conf["inverters"][$inverter_id]["serial"])) {
	# read AhoyDTU operating data
    $ahoy_data = readOperatingData($ahoy_config["filename"]);
	$data_yaml = $ahoy_data[$ahoy_conf["inverters"][$inverter_id]["serial"]];

	if (isset($data_yaml["InverterDevInform_Simple"]))	$simple_hw_data	= $data_yaml["InverterDevInform_Simple"];
	if (isset($data_yaml["InverterDevInform_All"]))		$all_hw_data	= $data_yaml["InverterDevInform_All"];
	if (isset($data_yaml["GridOnProFilePara"]))			$grid_data		= $data_yaml["GridOnProFilePara"];
	if (isset($data_yaml["SystemConfigPara"]))			$config_data	= $data_yaml["SystemConfigPara"];
	if (isset($data_yaml["RealTimeRunData_Debug"]))		$status_data	= $data_yaml["RealTimeRunData_Debug"]; # rtrd_data
	if (isset($data_yaml["AlarmData"]))					$alarm_data		+= $data_yaml["AlarmData"];
	if (isset($data_yaml["AlarmUpdate"]))				$alarm_data		+= $data_yaml["AlarmUpdate"];
	if (isset($data_yaml["MaxValues"]))					$MaxValues_data	= $data_yaml["MaxValues"];

	# 1) look at "serial numer"
	switch (substr($ahoy_conf["inverters"][$inverter_id]["serial"], 0,4)) {
		case 1121: $max_pwr =  400; break;
		case 1141: $max_pwr =  800; break;
		case 1161: $max_pwr = 1500; break;
	}

	# 2) read "Hardware numer" and compare with list:
	if (isset($simple_hw_data["FLD_PART_NUM"])){
		$part_num = intval("0x" . substr(dechex($simple_hw_data["FLD_PART_NUM"]),0,6),0);

		require_once 'inverter_defines.php';
		$max_pwr = $devInfo[$part_num];

		##if (isset($argv) and $argv[0] == "inverter_json.php") 
		##	termPrint("FLD_PART_NUM: " . $simple_hw_data["FLD_PART_NUM"] . 
		##							 " - in hex: " . dechex($part_num) .
		##							 " - max_pwr: " . $max_pwr);
	}
}

if (! isset($status_data["time"])) $status_data['time'] = 0;

if (count($all_hw_data) > 0) {
  $fw_date = $all_hw_data["FW_build_dd"] . "." . $all_hw_data["FW_build_mm"] . "." . $all_hw_data["FW_build_yy"];	#"0-00-00"
  $fw_time = $all_hw_data["FW_build_HH"] . ":" . $all_hw_data["FW_build_MM"];										#"00:00"
  $fw_ver  = $all_hw_data["FW_ver_maj"]  . "." . $all_hw_data["FW_ver_min"]  . "." . $all_hw_data["FW_ver_pat"];	#"0.00.00"
} else {
  $fw_date = "0-00-00";
  $fw_time = "00:00";
  $fw_ver  = "0.00.00";
}

$inverter_grid = "inverter_grid_" . $inverter_id . "_json";
if (isset($ahoy_conf["inverters"][$inverter_id]["name"])) {
	$$inverter_grid = [
		"name" => $ahoy_conf["inverters"][$inverter_id]["name"],
		"grid" => $grid_data['gridData'] ?? ""
	];
 } else {
	$$inverter_grid = [];
}

$inverter_radiostat = "inverter_radiostat_" . $inverter_id . "_json";
if (isset($ahoy_conf["inverters"][$inverter_id]["name"])) {
	$$inverter_radiostat = [
		"name" => $ahoy_conf["inverters"][$inverter_id]["name"],
		"rx_success" => 0, "rx_fail" => 0, "rx_fail_answer" => 0,
		"frame_cnt" => 0,
		"tx_cnt" => 0,
		"retransmits" => 0,
		"ivLoss" => 0, "ivSent" => 0,
		"dtuLoss" => 0, "dtuSent" => 0];
} else {
	$$inverter_radiostat = [];
}

	# "ts_last_success"	=> strtotime($status_data['time'].'CEST'),
	# "status"		=> (time() - strtotime($status_data["time"].'CEST')) > 60 ? 0 : 1,
$inverter_var_id = "inverter_id_" . $inverter_id . "_json";
$$inverter_var_id = [
	"id"			=> $inverter_id,
	"enabled"		=> $ahoy_conf["inverters"][$inverter_id]["enabled"] ?? false,
	"name"			=> $ahoy_conf["inverters"][$inverter_id]["name"] ?? "",
	"serial"		=> $ahoy_conf["inverters"][$inverter_id]["serial"] ?? "",
	"version"		=> "0",
	"power_limit_read"	=> $config_data['FLD_ACT_ACTIVE_PWR_LIMIT'],
	"power_limit_ack"	=> false,
	"max_pwr"			=> $max_pwr,
	"ts_last_success"	=> $status_data['time'],
	"generation"	=> 1,
	"status"		=> (time() - $status_data["time"]) > 60 ? 0 : 1,
	"alarm_cnt"		=> $status_data["event_count"] ?? 0,
	"rssi"			=> 0,
	"ts_max_ac_pwr"	=> $data_yaml["MaxValues"]["max_power_ts"] ?? 0,
	"ts_max_temp"	=> $data_yaml["MaxValues"]["max_temp_ts"] ?? 0,
    # V   ,A   ,W   ,Hz  ,""   , °C ,   kWh    ,  Wh    ,W   ,   %      ,var ,W       ,  °C
    # U_AC,I_AC,P_AC,F_AC,PF_AC,Temp,YieldTotal,YieldDay,P_DC,Efficiency,Q_AC,MaxPower,MaxTemp
	"ch"			=> [],
	"ch_name"		=> ["AC"],
	"ch_max_pwr"	=> [null]
];

$ACvoltage = isset($status_data["phases"][0]["voltage"])   ? $status_data["phases"][0]["voltage"] : 0;
$ACcurrent = isset($status_data["phases"][0]["current"])   ? $status_data["phases"][0]["current"] : 0;
$ACpower   = isset($status_data["phases"][0]["power"])     ? $status_data["phases"][0]["power"] : 0;
$frequency = isset($status_data["phases"][0]["frequency"]) ? $status_data["phases"][0]["frequency"] : 0;
$ACQpower  = isset($status_data["phases"][0]["reactive_power"]) ? $status_data["phases"][0]["reactive_power"] : 0;

if (isset($status_data["phases"])){
  if (count($status_data["phases"]) > 1) {
    for ($ii = 0; $ii < count($status_data["phases"]); $ii++) {
	    $ACvoltage += $status_data["phases"][$ii]["voltage"];
        $ACcurrent += $status_data["phases"][$ii]["current"];
		$ACpower   += $status_data["phases"][$ii]["power"];
		$frequency += $status_data["phases"][$ii]["frequency"];
		$ACQpower  += $status_data["phases"][$ii]["reactive_power"]; 
	}
    $ACvoltage /= count($status_data["phases"]);
    $frequency /= count($status_data["phases"]);
  }
}

$DCpower = 0;
if (isset($status_data["strings"])){
  for ($ii = 0; $ii < count($status_data["strings"]); $ii++) {
	$DCpower += $status_data["strings"][$ii]["power"];
  }
}

if (isset($status_data["yield_total"])) {
	array_push($$inverter_var_id["ch"],[ 
		$ACvoltage,								# U_AC [V]
		$ACcurrent,								# I_AC [A]
		$ACpower,								# P_AC [W]
		$frequency,								# F_AC [Hz]
		$status_data["powerfactor"],			# Pf_AC
		$status_data["temperature"],			# Temp [°C]
		$status_data["yield_total"],			# P_total [kW]
		$status_data["yield_today"],			# P_today [W]
		$DCpower,								# P_DC [W]
		$status_data["efficiency"],				# [%]
		$ACQpower,								# Q [var]
		$data_yaml["MaxValues"]["max_power"],	# MaxPower [W]
		$data_yaml["MaxValues"]["max_temp"]		# MaxTemp [°C]
	]);
} else {
	array_push($$inverter_var_id["ch"],[0,0,0,0,0,0,0,0,0,0,0,0]);
}

## tbd knut
# erst schleife, dann abfrage
# schleife über config, nicht status-data
# else zweig mit 0 werten
for ($ii = 0; $ii < count($ahoy_conf["inverters"][$inverter_id]["strings"] ?? []); $ii++) {
	if (isset($status_data["strings"])) {
		$$inverter_var_id["ch"][$ii + 1] = [
			$status_data["strings"][$ii]["voltage"],			# U_DC [V]
			$status_data["strings"][$ii]["current"],			# I_DC [A]
			$status_data["strings"][$ii]["power"],				# P_DC [W]
			$status_data["strings"][$ii]["energy_daily"],		# YieldDay [Wh]
			$status_data["strings"][$ii]["energy_total"] / 1000,# YieldTotal [kWh]
			$status_data["strings"][$ii]["irradiation"],		# Irradiation [%]
			$data_yaml["MaxValues"]["strings"][$ii]				# MaxPower [W]
		];
	} else $$inverter_var_id["ch"][$ii + 1] = [0,0,0,0,0,0,0];  # no inverter configured
	array_push($$inverter_var_id["ch_name"],    $ahoy_conf["inverters"][$inverter_id]["strings"][$ii]["s_name"]);
	array_push($$inverter_var_id["ch_max_pwr"], $ahoy_conf["inverters"][$inverter_id]["strings"][$ii]["s_maxpower"]);
}

$inverter_list_json = ["inverter" => []];
# print_r ($ahoy_conf);
# loop over inverters
if (isset($ahoy_conf["inverters"]) and count($ahoy_conf["inverters"]) > 0) {
	#print("\n");print (json_encode($ahoy_conf["inverters"]) . "\n");
	foreach ($ahoy_conf["inverters"] as $ii => $inv) {
		#print_r($ii); print("\n");print (json_encode($inv) . "\n");
		array_push($inverter_list_json["inverter"], [
			"id"           => $ii,
			"enabled"      => $inv["enabled"],
			"name"         => $inv["name"],
			"serial"       => $inv["serial"],
			"channels"     => count($inv["strings"]),
			"freq"         => 12,
			"disnightcom"  => $inv["disnightcom"],
			"pa"           => $inv["txpower"],
			"ch_name"      => [],
			"ch_max_pwr"   => [],
			"ch_yield_cor" => []
		]);
		if (isset($inv["strings"]) and count($inv["strings"]) > 0) {
			foreach ($inv["strings"] as $jj => $string) {
				#print_r( $string);
				#print_r($ii);
				#print_r($inverter_list_json);
				array_push($inverter_list_json["inverter"][$ii]["ch_name"],      $string["s_name"]);
				array_push($inverter_list_json["inverter"][$ii]["ch_max_pwr"],   $string["s_maxpower"]);
				array_push($inverter_list_json["inverter"][$ii]["ch_yield_cor"], isset($string["s_yield"]) ? $string["s_yield"] : 0);
			}
		}
	} 
} else {
  $inverter_list_json["inverter"] = [];
}
$inverter_list_json += [
	"interval"    => $ahoy_conf["interval"],
	"max_num_inverters" => 16,
	"rstMid"      => $ahoy_conf["WebServer"]["InverterReset"]["AtMidnight"] ?? false,	# Reset values and YieldDay at midnight
	"rstNotAvail" => $ahoy_conf["WebServer"]["InverterReset"]["NotAvailable"] ?? false,	# Reset values when inverter status is 'not available'
	"rstComStop"  => $ahoy_conf["WebServer"]["InverterReset"]["AtSunrise"] ?? false,	# Reset values at sunrise
	"rstComStart" => $ahoy_conf["WebServer"]["InverterReset"]["AtSunset"] ?? false,		# Reset values at sunset
	"rstMaxMid"   => $ahoy_conf["WebServer"]["InverterReset"]["MaxValues"] ?? false,	# Include reset 'max' values
	"strtWthtTm"  => $ahoy_conf["WebServer"]["strtWthtTm"] ?? false,					# Start without time sync
	"rdGrid"      => $ahoy_conf["WebServer"]["rdGrid"] ?? false,						# Read Grid Profile
];

#vergl. RestApi.h - zeile 695
$prod_year = 2088;
$prod_cw   = 88;
if (isset($status_data["inverter_ser"])) {
	$prod_year = intval($status_data["inverter_ser"][4]) + 2014;
	$prod_cw   = intval($status_data["inverter_ser"][5]) * 10 + intval($status_data["inverter_ser"][6]);
}
$inverter_version = "inverter_version_" . $inverter_id . "_json";
$$inverter_version = [
	"id"			=> $inverter_id,
	"name"			=> $ahoy_conf["inverters"][$inverter_id]["name"] ?? "",
	"serial"		=> $ahoy_conf["inverters"][$inverter_id]["serial"] ?? "",
	"generation"	=> 1,
	"max_pwr"		=> $max_pwr,
	"part_num"		=> $simple_hw_data["FLD_PART_NUM"] ?? 0,
	"hw_ver"		=> $simple_hw_data["FLD_HW_VERSION"] ?? 0,
	"prod_cw"		=> $prod_cw,
	"prod_year"		=> $prod_year,
	"fw_date"		=> $fw_date, # "0-00-00",
	"fw_time"		=> $fw_time, # "00:00",
	"fw_ver"		=>  $fw_ver,  # "0.00.00",
	"boot_ver"		=> $all_hw_data["BL_VER"] ?? 0 
];

# $inverter_alarm_0_json 
$inverter_alarm = "inverter_alarm_" . $inverter_id . "_json";
$$inverter_alarm = [
	"iv_id"   => $inverter_id,
	"iv_name" => $ahoy_conf["inverters"][$inverter_id]["name"] ?? "",
	"cnt"     => 0,
	"last_id" => count($alarm_data) ?? 0,
	"alarm"   => []
];
# print ("count Alarm: " . $$inverter_alarm["last_id"] . PHP_EOL);
# print_r($alarm_data);
krsort($alarm_data);
foreach ($alarm_data as $ii => $a_event) {
	if (isset($a_event)) {
		array_push($$inverter_alarm["alarm"], 
			[
			"code"  => $ii,
			"str"   => $alarm_data[$ii]["a_event_txt"] ?? "", 
			"start" => $alarm_data[$ii]["a_event_sts"] ?? 0,
			"end"   => $alarm_data[$ii]["a_event_ets"] ?? 0
			]
		);
	}
	else array_push($$inverter_alarm["alarm"], ["code" => 999, "str" => "Unknown", "start" => 0, "end" => 0]);
}

# $inverter_pwrack_0_json                                         # power acknowledge
$inverter_pwrack = "inverter_pwrack_" . $inverter_id . "_json";
## $$inverter_pwrack = ["ack" => false];
$$inverter_pwrack = ["ack" => false];

if (isset($argv) and $argv[0] == "inverter_json.php"){
	termPrint(
		"/inverter_list_json:"			  . PHP_EOL . json_encode($inverter_list_json)	. PHP_EOL .
		"/" . $inverter_grid		. ":" . PHP_EOL . json_encode($$inverter_grid)		. PHP_EOL .
		"/"	. $inverter_radiostat	. ":" . PHP_EOL . json_encode($$inverter_radiostat)	. PHP_EOL .
		"/"	. $inverter_var_id		. ":" . PHP_EOL . json_encode($$inverter_var_id)	. PHP_EOL .
		"/"	. $inverter_version		. ":" . PHP_EOL . json_encode($$inverter_version)	. PHP_EOL .
		"/" . $inverter_alarm		. ":" . PHP_EOL . json_encode($$inverter_alarm)		. PHP_EOL .
		"/"	. $inverter_pwrack		. ":" . PHP_EOL . json_encode($$inverter_pwrack)
	);
}
?>
