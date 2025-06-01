<?php
include 'generic_json.php'; # incl. reading ahoy.yml

if (! isset($inverter_id)) {$inverter_id = 0;}

# read Info-Files in /tmp with current data
$pre_fn = "/tmp/AhoyDTU_" . $ahoy_data["inverters"][$inverter_id]["serial"];
$hw_data_yaml     = @yaml_parse_file($pre_fn . '_HardwareInfoResponse.yml');
$status_data_yaml = @yaml_parse_file($pre_fn . '_StatusResponse.yml');
$event_data_yaml  = @yaml_parse_file($pre_fn . '_EventsResponse.yml');
#  if (!$hw_data_yaml) {
#    #echo 'Error: ', yaml_last_error_msg();
#  }
#print_r ($hw_data_yaml);
#print_r ($status_data_yaml);
#print_r ($event_data_yaml);

$fw_date = $hw_data_yaml["FW_build_dd"] . "." . $hw_data_yaml["FW_build_mm"] . "." . $hw_data_yaml["FW_build_yy"];  #"0-00-00"
#$fw_date = "0-00-00";
$fw_time = $hw_data_yaml["FW_build_HH"] . ":" . $hw_data_yaml["FW_build_MM"];                                       #"00:00"
#$fw_time = "00:00";
$fw_ver  = $hw_data_yaml["FW_ver_maj"]  . "." . $hw_data_yaml["FW_ver_min"]  . "." . $hw_data_yaml["FW_ver_pat"];   #"0.00.00"
#$fw_ver  = "0.00.00";

# 1121-Series Intervers, 1 MPPT, 1 Phase
# 1141-Series Inverters, 2 MPPT, 1 Phase
# 1161-Series Inverters, 2 MPPT, 1 Phase
# print (substr($hw_data_yaml["inverter_ser"], 0,4));
switch (substr($hw_data_yaml["inverter_ser"], 0,4)) {
  case 1121:
	$max_pwr = 400; break;
  case 1141:
	$max_pwr = 800; break;
  case 1161:
	$max_pwr = 1500; break;
  default:
	$max_pwr = 0;
}


$inverter_grid = "inverter_grid_" . $inverter_id . "_json";
$$inverter_grid = ["name" => $ahoy_data["inverters"][$inverter_id]["name"],
	"grid" => "03 00 20 01 00 0A 08 FC 07 30 00 1E 0B 3B 00 01 04 0B 00 1E 09 E2 10 00 13 88 12 8E 00 01 14 1E 00 01 20 00 00 01 30 03 02 58 09 E2 07 A3 13 92 12 8E 40 00 07 D0 00 10 50 08 00 01 13 9C 01 90 00 10 13 9C 13 74 70 02 00 01 27 10 80 00 00 00 08 5B 01 2C 08 B7 09 41 09 9D 01 2C 90 00 00 00 00 5F B0 00 00 00 01 F4 00 5F A0 02 00 00 00 00 "];


$inverter_pwrack = "inverter_pwrack_" . $inverter_id . "_json";
$$inverter_pwrack = ["ack" => false];

$inverter_radiostat = "inverter_radiostat_" . $inverter_id . "_json";
$$inverter_radiostat = [
	"name" => $ahoy_data["inverters"][$inverter_id]["name"],
	"rx_success" => 0, "rx_fail" => 0, "rx_fail_answer" => 0,
	"frame_cnt" => 0,
	"tx_cnt" => 0,
	"retransmits" => 0,
	"ivLoss" => 0, "ivSent" => 0,
	"dtuLoss" => 0, "dtuSent" => 0];

$inverter_var_id = "inverter_id_" . $inverter_id . "_json";
$$inverter_var_id = [
	"id" => $inverter_id,
	"enabled" => "true",
	"name"    => $status_data_yaml["inverter_name"],
	"serial"  => $status_data_yaml["inverter_ser"],
	"version" => "0",
	"power_limit_read" => 100,
	"power_limit_ack" => false,
	"max_pwr" => $max_pwr,
	"ts_last_success" => strtotime($status_data_yaml["time"]),
	"generation" => 2,
	"status" => (time() - strtotime($status_data_yaml["time"])) > 60 ? 0 : 1,
	"alarm_cnt" => $status_data_yaml["event_count"],
	"rssi" => 0,
	"ts_max_ac_pwr" => $status_data_yaml["max_data"]["temp_ts"],
	"ts_max_temp" => $status_data_yaml["max_data"]["temp_ts"],
    # V   ,A   ,W   ,Hz  ,""   , °C ,   kWh    ,  Wh    ,W   ,   %      ,var ,W       ,  °C
    # U_AC,I_AC,P_AC,F_AC,PF_AC,Temp,YieldTotal,YieldDay,P_DC,Efficiency,Q_AC,MaxPower,MaxTemp
	"ch" => [[$status_data_yaml["phases"][0]["voltage"],  # U_AC [V]
			  $status_data_yaml["phases"][0]["current"],  # I_AC [A]
			  $status_data_yaml["phases"][0]["power"],    # P_AC [W]
			  $status_data_yaml["phases"][0]["frequency"],# F_AC [Hz]
			  $status_data_yaml["powerfactor"],           # Pf_AC
			  $status_data_yaml["temperature"],           # Temp [°C]
			  $status_data_yaml["yield_total"],           # Pmax-total [kW]
			  $status_data_yaml["yield_today"],           # Pmax-today [kW]
			  $status_data_yaml["strings"][0]["power"] + $status_data_yaml["strings"][1]["power"], #  109,
			  $status_data_yaml["efficiency"],            # [%]
			  $status_data_yaml["phases"][0]["reactive_power"], # Q [var]
			$status_data_yaml["max_data"]["power"],       # MaxPower [W]
			$status_data_yaml["max_data"]["temp"]         # MaxTemp [°C]
		]
    ]
];

$$inverter_var_id += [
	"ch_name" => ["AC"],
	"ch_max_pwr" => [null]
];

for ($ii = 0; $ii < count($status_data_yaml["strings"]); $ii++) {
  $$inverter_var_id["ch"][$ii + 1] = [
	$status_data_yaml["strings"][$ii]["voltage"],      # U_DC [V]
	$status_data_yaml["strings"][$ii]["current"],      # I_DC [A]
	$status_data_yaml["strings"][$ii]["power"],        # P_DC [W]
	$status_data_yaml["strings"][$ii]["energy_daily"], # YieldDay [Wh]
	$status_data_yaml["strings"][$ii]["energy_total"], # YieldTotal [kWh]
	$status_data_yaml["strings"][$ii]["irradiation"],  # Irradiation [%]
	$status_data_yaml["max_data"]["strings"][$ii]      # MaxPower [W]
  ];
  array_push($$inverter_var_id["ch_name"],    $ahoy_data["inverters"][$inverter_id]["strings"][$ii]["s_name"]);
  array_push($$inverter_var_id["ch_max_pwr"], $ahoy_data["inverters"][$inverter_id]["strings"][$ii]["s_maxpower"]);
}

$inverter_list_json = ["inverter" => []];
# loop over inverters
for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
  array_push($inverter_list_json["inverter"], [
	"enabled" => ! $ahoy_data["inverters"][$ii]["disabled"],
	"id" => $ii,
	"name" => $ahoy_data["inverters"][$ii]["name"],
	"serial" => $ahoy_data["inverters"][$ii]["serial"],
	"channels" => 2,
	"freq" => 12,
	"disnightcom" => false,
	"pa" => 0,
	"ch_yield_cor" => [0,0],
	"ch_name" => [],
	"ch_max_pwr" => []
	]);
	for ($jj = 0; $jj < count($ahoy_data["inverters"][$ii]["strings"]); $jj++) {
		array_push($inverter_list_json["inverter"][$ii]["ch_name"],    $ahoy_data["inverters"][$ii]["strings"][$jj]["s_name"]);
		array_push($inverter_list_json["inverter"][$ii]["ch_max_pwr"], $ahoy_data["inverters"][$ii]["strings"][$jj]["s_maxpower"]);
	}
}
$inverter_list_json += [
	"interval"    => $ahoy_data["interval"],
	"max_num_inverters" => 16,
	"rstMid"      => $ahoy_data["WebServer"]["InverterResetValues"]["AtMidnight"],  # Reset values and YieldDay at midnight
	"rstNotAvail" => $ahoy_data["WebServer"]["InverterResetValues"]["NotAvailable"],# Reset values when inverter status is 'not available'
	"rstComStop"  => $ahoy_data["WebServer"]["InverterResetValues"]["AtSunrise"],   # Reset values at sunrise
	"rstComStart" => $ahoy_data["WebServer"]["InverterResetValues"]["AtSunset"],    # Reset values at sunset
	"rstMaxMid"   => $ahoy_data["WebServer"]["InverterResetValues"]["MaxValues"],   # Include reset 'max' values
	"strtWthtTm"  => $ahoy_data["WebServer"]["strtWthtTm"],                         # Start without time sync
	"rdGrid"      => $ahoy_data["WebServer"]["rdGrid"],                             # Read Grid Profile
];

$inverter_version = "inverter_version_" . $inverter_id . "_json";
$$inverter_version = [
	"id" => $inverter_id,
	"name" => $hw_data_yaml["inverter_name"],
	"serial" => $hw_data_yaml["inverter_ser"],
	"generation" => 1,
	"max_pwr" => $max_pwr,
	"part_num" => 0,
	"hw_ver" => $hw_data_yaml["FW_HW_ID"], #0,
	"prod_cw" => 18,
	"prod_year" => 2022,
	"fw_date" => $fw_date, # "0-00-00",
	"fw_time" => $fw_time, # "00:00",
	"fw_ver" =>  $fw_ver,  # "0.00.00",
	"boot_ver" => 0 
];

$inverter_alarm_0_json = [
	"iv_id" => 0,
	"iv_name" => $hw_data_yaml["inverter_name"],
	"cnt" => 0,
	"last_id" => 0,
	"alarm" => [
		["code" => 1,"str" => "Inverter start","start" => 11101,"end" => 11101],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0],
		["code" => 0,"str" => "Unknown","start" => 0,"end" => 0]
	]
];


# if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
  # header('Content-Type: application/json; charset=utf-8');
  # print json_encode($_SERVER, JSON_PRETTY_PRINT);

  echo "\n" . $inverter_pwrack . "\n";
  print json_encode($$inverter_pwrack) . "\n";

  echo "\ninverter_list_json:\n";
  print json_encode($inverter_list_json) . "\n";

  echo "\n" . $inverter_var_id . "\n";
  print json_encode($$inverter_var_id) . "\n";

  echo "\n" . $inverter_version . "\n";
  print json_encode($$inverter_version);

  print_r ($hw_data_yaml);
  # print_r ($status_data_yaml);
  print_r ($event_data_yaml);
}
?>
