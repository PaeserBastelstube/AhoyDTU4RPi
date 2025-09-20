<?PHP
header('Content-Type: application/json; charset=utf-8');

if (file_exists('../html/colorBright.css') and ! is_link('../html/colors.css')) {
	symlink ('../html/colorBright.css', '../html/colors.css');
	header("Refresh:0");
}
if (file_exists('../html/visualization.html') and ! is_link('../html/live.html')) {
	symlink ('../html/visualization.html', '../html/live.html');
	header("Refresh:0");
}
# ACHTUNG: symlink must be set at first action - before "include" 
require_once 'index_json.php';

print json_encode($index_json);
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm") {
  print("\n");
}
?>
