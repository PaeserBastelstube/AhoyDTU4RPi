<?php
$output_including_status = shell_exec("iwlist eth0 scan  2>&1; echo $?");
print gettype($output_including_status) . "\n";
print_r ($output_including_status);
print ("linie\n");

$o_array = explode("\n", trim($output_including_status));
print end($o_array) . "\n";

print "GETTYPE: " . gettype($o_array) . "\n";
for ($ii = 0; $ii < count($o_array); $ii++) {
	print $ii . ": " . $o_array[$ii] . "\n";
}
?>
