<?php
include 'generic_json.php'; # incl. reading ahoy.yml


# read Info-Files in {filepath} with current inverter data
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "inverter_json.php") {
	$inverter_id = 0; # for test only
}

# load (read) AhoyDTU-data-file
if ((isset($inverter_id)) and ($inverter_id >= 0)) {
	$filepath = $ahoy_data["WebServer"]["filepath"];
	$data_yaml = @yaml_parse_file($filepath . "/AhoyDTU_" . $ahoy_data["inverters"][$inverter_id]["serial"] . ".yml");
} else {
	$inverter_id = 0;
}

if (isset($data_yaml["GridOnProFilePara"])) $grid_data = $data_yaml["GridOnProFilePara"];
else $data_yaml = array();
if (isset($data_yaml["InverterDevInform_Simple"])) $hw_data_simple = $data_yaml["InverterDevInform_Simple"];
else $hw_data_simple = array();
if (isset($data_yaml["InverterDevInform_All"])) $hw_data_all = $data_yaml["InverterDevInform_All"];
else $hw_data_all = array();
if (isset($data_yaml["RealTimeRunData_Debug"])) $status_data_yaml = $data_yaml["RealTimeRunData_Debug"];
else $status_data_yaml = array();
if (isset($data_yaml["AlarmData"])) $alarm_data = $data_yaml["AlarmData"];
else $alarm_data = array();
if (! isset($data_yaml["RealTimeRunData_Debug"]["time"])) $status_data_yaml['time'] = 0;

if (isset($data_yaml["InverterDevInform_All"])) {
  $hw_data = $data_yaml["InverterDevInform_All"];
  $fw_date = $hw_data["FW_build_dd"] . "." . $hw_data["FW_build_mm"] . "." . $hw_data["FW_build_yy"];  #"0-00-00"
  $fw_time = $hw_data["FW_build_HH"] . ":" . $hw_data["FW_build_MM"];                                  #"00:00"
  $fw_ver  = $hw_data["FW_ver_maj"]  . "." . $hw_data["FW_ver_min"]  . "." . $hw_data["FW_ver_pat"];   #"0.00.00"
} else {
  $fw_date = "0-00-00";
  $fw_time = "00:00";
  $fw_ver  = "0.00.00";
}

# 1121-Series Intervers, 1 MPPT, 1 Phase
# 1141-Series Inverters, 2 MPPT, 1 Phase
# 1161-Series Inverters, 2 MPPT, 1 Phase
$max_pwr = 0;
if (isset($status_data_yaml["inverter_ser"])) {
  switch (substr($status_data_yaml["inverter_ser"], 0,4)) {
    case 1121:
	  $max_pwr = 400; break;
    case 1141:
	  $max_pwr = 800; break;
    case 1161:
	  $max_pwr = 1500; break;
  }
}

$inverter_grid = "inverter_grid_" . $inverter_id . "_json";
if (isset($ahoy_data["inverters"][$inverter_id]["name"])) {
	$$inverter_grid = ["name" => $ahoy_data["inverters"][$inverter_id]["name"],
	"grid" => $grid_data['gridData']];
 } else {
	$$inverter_grid = [];
}

$inverter_pwrack = "inverter_pwrack_" . $inverter_id . "_json";
$$inverter_pwrack = ["ack" => false];

$inverter_radiostat = "inverter_radiostat_" . $inverter_id . "_json";
if (isset($ahoy_data["inverters"][$inverter_id]["name"])) {
	$$inverter_radiostat = [
		"name" => $ahoy_data["inverters"][$inverter_id]["name"],
		"rx_success" => 0, "rx_fail" => 0, "rx_fail_answer" => 0,
		"frame_cnt" => 0,
		"tx_cnt" => 0,
		"retransmits" => 0,
		"ivLoss" => 0, "ivSent" => 0,
		"dtuLoss" => 0, "dtuSent" => 0];
} else {
	$$inverter_radiostat = [];
}

$inverter_var_id = "inverter_id_" . $inverter_id . "_json";
$$inverter_var_id = [
	"id"			=> $inverter_id,
	"enabled"		=> $ahoy_data["inverters"][$inverter_id]["enable"] ?? false,
	"name"			=> $ahoy_data["inverters"][$inverter_id]["name"] ?? "",
	"serial"		=> $ahoy_data["inverters"][$inverter_id]["serial"] ?? "",
	"version"		=> "0",
	"power_limit_read" => 100,
	"power_limit_ack" => false,
	"max_pwr"		=> $max_pwr,
	"ts_last_success" => strtotime($status_data_yaml['time'].'CEST'),
	"generation"	=> 1,
	"status"		=> (time() - strtotime($status_data_yaml["time"].'CEST')) > 60 ? 0 : 1,
	"alarm_cnt"		=> $status_data_yaml["event_count"] ?? 0,
	"rssi"	=> 0,
	"ts_max_ac_pwr"	=> $data_yaml["MaxValues"]["max_power_ts"] ?? 0,
	"ts_max_temp"	=> $data_yaml["MaxValues"]["max_temp_ts"] ?? 0,
    # V   ,A   ,W   ,Hz  ,""   , °C ,   kWh    ,  Wh    ,W   ,   %      ,var ,W       ,  °C
    # U_AC,I_AC,P_AC,F_AC,PF_AC,Temp,YieldTotal,YieldDay,P_DC,Efficiency,Q_AC,MaxPower,MaxTemp
	"ch"			=> [],
	"ch_name"		=> ["AC"],
	"ch_max_pwr"	=> [null]
];

$ACvoltage = isset($status_data_yaml["phases"][0]["voltage"])   ? $status_data_yaml["phases"][0]["voltage"] : 0;
$ACcurrent = isset($status_data_yaml["phases"][0]["current"])   ? $status_data_yaml["phases"][0]["current"] : 0;
$ACpower   = isset($status_data_yaml["phases"][0]["power"])     ? $status_data_yaml["phases"][0]["power"] : 0;
$frequency = isset($status_data_yaml["phases"][0]["frequency"]) ? $status_data_yaml["phases"][0]["frequency"] : 0;
$ACQpower  = isset($status_data_yaml["phases"][0]["reactive_power"]) ? $status_data_yaml["phases"][0]["reactive_power"] : 0;

if (isset($status_data_yaml["phases"])){
  if (count($status_data_yaml["phases"]) > 1) {
    for ($ii = 0; $ii < count($status_data_yaml["phases"]); $ii++) {
	    $ACvoltage += $status_data_yaml["phases"][$ii]["voltage"];
        $ACcurrent += $status_data_yaml["phases"][$ii]["current"];
		$ACpower   += $status_data_yaml["phases"][$ii]["power"];
		$frequency += $status_data_yaml["phases"][$ii]["frequency"];
		$ACQpower  += $status_data_yaml["phases"][$ii]["reactive_power"]; 
	}
    $ACvoltage /= count($status_data_yaml["phases"]);
    $frequency /= count($status_data_yaml["phases"]);
  }
}

$DCpower = 0;
if (isset($status_data_yaml["strings"])){
  for ($ii = 0; $ii < count($status_data_yaml["strings"]); $ii++) {
	$DCpower += $status_data_yaml["strings"][$ii]["power"];
  }
}

if (isset($status_data_yaml["yield_total"])) {
  array_push($$inverter_var_id["ch"],[ 
    $ACvoltage,                                 # U_AC [V]
    $ACcurrent,                                 # I_AC [A]
	$ACpower,                                   # P_AC [W]
	$frequency,                                 # F_AC [Hz]
	$status_data_yaml["powerfactor"],           # Pf_AC
	$status_data_yaml["temperature"],           # Temp [°C]
	$status_data_yaml["yield_total"] / 1000,    # Pmax-total [kW]
	$status_data_yaml["yield_today"],           # Pmax-today [kW]
	$DCpower,                                   # P_DC [W]
	$status_data_yaml["efficiency"],            # [%]
	$ACQpower,                                  # Q [var]
	$data_yaml["MaxValues"]["max_power"],     # MaxPower [W]
	$data_yaml["MaxValues"]["max_temp"]       # MaxTemp [°C]
  ]);
} else {
  array_push($$inverter_var_id["ch"],[0,0,0,0,0,0,0,0,0,0,0,0]);
}

## tbd knut
# erst schleife, dann abfrage
# schleife über config, nicht status-data
# else zweig mit 0 werten
for ($ii = 0; $ii < count($ahoy_data["inverters"][$inverter_id]["strings"] ?? []); $ii++) {
  if (isset($status_data_yaml["strings"])){
  #for ($ii = 0; $ii < count($status_data_yaml["strings"]); $ii++) {
    $$inverter_var_id["ch"][$ii + 1] = [
	  $status_data_yaml["strings"][$ii]["voltage"],      # U_DC [V]
	  $status_data_yaml["strings"][$ii]["current"],      # I_DC [A]
	  $status_data_yaml["strings"][$ii]["power"],        # P_DC [W]
	  $status_data_yaml["strings"][$ii]["energy_daily"], # YieldDay [Wh]
	  $status_data_yaml["strings"][$ii]["energy_total"] / 1000, # YieldTotal [kWh]
	  $status_data_yaml["strings"][$ii]["irradiation"],  # Irradiation [%]
	  $data_yaml["MaxValues"]["strings"][$ii]      # MaxPower [W]
    ];
  } else $$inverter_var_id["ch"][$ii + 1] = [0,0,0,0,0,0,0];
  array_push($$inverter_var_id["ch_name"],    $ahoy_data["inverters"][$inverter_id]["strings"][$ii]["s_name"]);
  array_push($$inverter_var_id["ch_max_pwr"], $ahoy_data["inverters"][$inverter_id]["strings"][$ii]["s_maxpower"]);
}

$inverter_list_json = ["inverter" => []];
# print_r ($ahoy_data);
# loop over inverters
if (isset($ahoy_data["inverters"]) and count($ahoy_data["inverters"]) > 0) {
	#print("\n");print (json_encode($ahoy_data["inverters"]) . "\n");
	foreach ($ahoy_data["inverters"] as $ii => $inv) {
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
	"interval"    => $ahoy_data["interval"],
	"max_num_inverters" => 16,
	"rstMid"      => $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] ?? false,	# Reset values and YieldDay at midnight
	"rstNotAvail" => $ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] ?? false,	# Reset values when inverter status is 'not available'
	"rstComStop"  => $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] ?? false,	# Reset values at sunrise
	"rstComStart" => $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] ?? false,		# Reset values at sunset
	"rstMaxMid"   => $ahoy_data["WebServer"]["InverterReset"]["MaxValues"] ?? false,	# Include reset 'max' values
	"strtWthtTm"  => $ahoy_data["WebServer"]["strtWthtTm"] ?? false,					# Start without time sync
	"rdGrid"      => $ahoy_data["WebServer"]["rdGrid"] ?? false,						# Read Grid Profile
];

#vergl. RestApi.h - zeile 695
$prod_year = 2088;
$prod_cw   = 88;
if (isset($status_data_yaml["inverter_ser"])) {
	$prod_year = intval($status_data_yaml["inverter_ser"][4]) + 2014;
	$prod_cw   = intval($status_data_yaml["inverter_ser"][5]) * 10 + intval($status_data_yaml["inverter_ser"][6]);
}
$inverter_version = "inverter_version_" . $inverter_id . "_json";
$$inverter_version = [
	"id" => $inverter_id,
	"name" => $status_data_yaml["inverter_name"] ?? "",
	"serial" => $status_data_yaml["inverter_ser"] ?? "",
	"generation" => 1,
	"max_pwr" => $max_pwr,
	"part_num" => $hw_data_simple["FLD_PART_NUM"] ?? 0,
	"hw_ver" => $hw_data_simple["FLD_HW_VERSION"] ?? 0,
	"prod_cw" => $prod_cw,
	"prod_year" => $prod_year,
	"fw_date" => $fw_date, # "0-00-00",
	"fw_time" => $fw_time, # "00:00",
	"fw_ver" =>  $fw_ver,  # "0.00.00",
	"boot_ver" => $hw_data_all["BL_VER"] ?? 0 
];

# $inverter_alarm_0_json = [
$inverter_alarm = "inverter_alarm_" . $inverter_id . "_json";
$$inverter_alarm = [
	"iv_id" => $inverter_id,
	"iv_name" => $status_data_yaml["inverter_name"] ?? "",
	"cnt" => 0,
	"last_id" => count($alarm_data) ?? 0,
	"alarm" => []
];
# for ($ii = 0; $ii < 50; $ii++) {
#for ($ii = 0; $ii < $$inverter_alarm["last_id"]; $ii++) {
for ($ii = $$inverter_alarm["last_id"]; $ii >= 0; $ii--) {
	if ($alarm_data[$ii]["inv_alarm_num"] == 1) $alarm_data[$ii]["inv_alarm_cnt"] = 1;
	if (isset($alarm_data[$ii])) array_push($$inverter_alarm["alarm"], 
		[
		"code" => $alarm_data[$ii]["inv_alarm_cnt"], 
		"str" => $alarm_data[$ii]["inv_alarm_txt"] , 
		"start" => $alarm_data[$ii]["inv_alarm_stm"],
		"end" => $alarm_data[$ii]["inv_alarm_etm"]
		]
	);
	else array_push($$inverter_alarm["alarm"], ["code" => 0, "str" => "Unknown", "start" => 0, "end" => 0]);
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "inverter_json.php") {
	# header('Content-Type: application/json; charset=utf-8');
	# print json_encode($_SERVER, JSON_PRETTY_PRINT);

	print "/inverter_list_json:\n" . json_encode($inverter_list_json) . "\n";
	print "/" . $inverter_pwrack . ":\n" . json_encode($$inverter_pwrack) . "\n";
	print "/" . $inverter_var_id . ":\n" . json_encode($$inverter_var_id) . "\n";
	print "/" . $inverter_version . ":\n" . json_encode($$inverter_version) . "\n";
	print "/" . $inverter_grid . ":\n" . json_encode($$inverter_grid) . "\n";
	print "/" . $inverter_alarm . ":\n" . json_encode($$inverter_alarm) . "\n";
}
?>
