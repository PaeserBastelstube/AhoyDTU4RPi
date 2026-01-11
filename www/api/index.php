<?PHP
header('Content-Type: application/json; charset=utf-8');

chdir('../html');
if (file_exists('colorBright.css') and ! is_link('colors.css')) {
	symlink ('colorBright.css', 'colors.css');
	header("Refresh:0");
}
if (file_exists('visualization.html') and ! is_link('live.html')) {
	symlink ('visualization.html', 'live.html');
	header("Refresh:0");
}
# ACHTUNG: symlink must be set at first action - before "include"  or "require"
require_once 'index_json.php';

print json_encode($index_json);
termPrint ("");		# print "\n"
?>
