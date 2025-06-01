<?PHP

# $my_login =  "<html><head><title>asdf</title></head>
# <body><center><h1>asdf</h1></center>";
# $my_login .= "</body> </html>";

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER["REQUEST_METHOD"] = "POST") {
	print_r ($_POST);
} else {
	print_r ($_SERVER);
}

# print ($my_login);

if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  print "\n";
}
?>
