<?php
#include 'generic_json.php';
include 'system_json.php';

$html_save_json = $generic_json + [
	"pending" => false,
	"success" => true,
	"reboot"  => false,
	"reload"  => 20
];

$html_system_json = [
	"system" => $system_json ] + $generic_json + [
	"html" => "<a href=\"/factory\" class=\"btn\">Ahoy auf Werkseinstellungen zurücksetzen</a>
			   <a href=\"/reboot\" class=\"btn\">Ahoy neustarten</a>
			   <a href=\"/coredump\" class=\"btn\">CoreDump herunterladen</a><br/><br/>
			   <a href=\"/AhoyDTU_status\" class=\"btn\">AhoyDTU status</a>
			   <a href=\"/AhoyDTU_restart\" class=\"btn\">restart</a>
			   <a href=\"/AhoyDTU_start\" class=\"btn\">start</a>
			   <a href=\"/AhoyDTU_stop\" class=\"btn\">stop</a>
			   <a href=\"/AhoyDTU_enable\" class=\"btn\">enable</a>
			   <a href=\"/AhoyDTU_disable\" class=\"btn\">disable</a>
			  "
];

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/html/save_json:\n"   . json_encode($html_save_json)   . "\n";
	print "/html/system_json:\n" . json_encode($html_system_json) . "\n";
}
?>
