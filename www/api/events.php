<?php
# include 'generic_json.php';

print("
retry: 1000
id: 394727593
data: hello!

id: 394730067
event: serial
data: 
	13:38:49.084 I: (#1) RX  78ms | 27 -65dBm | 95 02<rn>
	13:38:49.085 I: (#1) RX 118ms | 27 -65dBm | 95 03<rn>
	13:38:49.086 I: (#1) RX 177ms | 27 -65dBm | 95 04<rn>
	13:38:49.086 I: (#1) RX 217ms | 15 -65dBm | 95 85<rn>
	13:38:49.223 I: (#1) RX  53ms | 27 -65dBm | 95 02<rn>
	13:38:49.223 I: (#1) RX 100ms | 27 -65dBm | 95 03<rn>
	13:38:49.284 I: (#1) RX  57ms | 15 -65dBm | 95 85<rn>
	13:38:49.498 I: (#1) RX  78ms | 27 -65dBm | 95 01<rn>
	13:38:49.499 W: (#1) CRC Error -> Fail<rn>
	13:38:49.499 -----<rn>
	13:38:49.499 I: com loop duration: 499ms<rn>
	13:38:49.500 -----<rn>
");




if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	#header('Content-Type: application/json; charset=utf-8');
	# print json_encode($generic_json["generic"]);
}
?>
