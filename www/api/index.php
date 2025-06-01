<?PHP
if (file_exists('../html/colorBright.css') and ! is_link('../html/colors.css')) {
	symlink ('../html/colorBright.css', '../html/colors.css');
}
if (file_exists('../html/visualization.html') and ! is_link('../html/live.html')) {
	symlink ('../html/visualization.html', '../html/live.html');
}
header('Content-Type: application/json; charset=utf-8');
include 'index_json.php';
print json_encode($index_json);
?>
