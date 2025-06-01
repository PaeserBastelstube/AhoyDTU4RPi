<?php
include'generic_json.php';

# Schleife über alle Inverter in ahoy.yml Suche nach Info-Files in /tmp
for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
  $pre_fn = "/tmp/AhoyDTU_" . $ahoy_data["inverters"][$ii]["serial"];
  #$hw_data_yaml[$ii]     = @yaml_parse_file($pre_fn . '_HardwareInfoResponse.yml');
  $status_data_yaml[$ii] = @yaml_parse_file($pre_fn . '_StatusResponse.yml');
  #$event_data_yaml[$ii]  = @yaml_parse_file($pre_fn . '_EventsResponse.yml');
  if (file_exists ($pre_fn . '_StatusResponse.yml')) {
    $ts_last_success[$ii]  = filemtime($pre_fn . '_StatusResponse.yml');
  } else {
    $ts_last_success[$ii]  = NULL;
  }
  if ($status_data_yaml[$ii]) {
 	$cur_pwr[$ii] = $status_data_yaml[$ii]["phases"][0]["power"];
  } else {
	$cur_pwr[$ii] = 0;
  }
}

$sun_info = date_sun_info(time(), $ahoy_data["sunset"]["latitude"], $ahoy_data["sunset"]["longitude"]);
if (isset($ahoy_data["sunset"]["disable"]) and $ahoy_data["sunset"]["disable"] = "true")
	{$disNightComm = false;} else {$disNightComm = true;}

$index_json = $generic_json + [
	"ts_now"     => time(),
	"ts_sunrise" => $sun_info["sunrise"],
	"ts_sunset"  => $sun_info["sunset"],
	"ts_offsSr"  => 0,
	"ts_offsSs"  => 0
];

for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
  $index_json["inverter"][$ii] = [
		"enabled" => true,                                                    # aus Setup abfragen
		"id" => $ii,                                                          # zähler nummer für Inverter
		"name" => $ahoy_data["inverters"][$ii]["name"],                       # Name des Inverters aus ahoy.yml
		"cur_pwr" => $cur_pwr[$ii],                                           # momentane Leistung des Inverters
		"is_avail" => $ts_last_success[$ii] != NULL,                          # Prüfung, ob letzte Meldung verfügbar ist
		"is_producing" => $index_json["ts_now"] - $ts_last_success[$ii] < 60, # Prüfung, ob letzte Meldung nicht älter als 60 Sek ist
		"ts_last_success" => $ts_last_success[$ii]                            # Timestamp der letzten Meldung
  ];
}
$index_json["disNightComm"] = $disNightComm;                                  # Inverter werden bei Dunkelheit nicht abgefragt - siehe: ahoy.yml
$index_json["warnings"] = [];                                                 # Anzahl von Meldungen des Inverters

# if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm") {
  # header('Content-Type: application/json; charset=utf-8');
  # print json_encode($_SERVER, JSON_PRETTY_PRINT);

  #print_r ($hw_data_yaml);
  # print_r ($status_data_yaml);
  #print_r ($event_data_yaml);
  # print_r ($ts_last_success);
  print_r ($index_json);

}
?>
