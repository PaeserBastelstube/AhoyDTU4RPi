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
	"html" => "<a href=\"/factory\" class=\"btn\">Ahoy auf Werkseinstellungen zurücksetzen</a><br/><br/><a href=\"/reboot\" class=\"btn\">Ahoy neustarten</a><br/><br/><a href=\"/coredump\" class=\"btn\">CoreDump herunterladen</a>"
];

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/html/save_json:\n"   . json_encode($html_save_json)   . "\n";
	print "/html/system_json:\n" . json_encode($html_system_json) . "\n";
}
?>
