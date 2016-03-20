<?php
@session_start();
$im = @imagecreate (80, 20) or die ("Cannot initialize new GD image stream!");
$bg = imagecolorallocate ($im, 248, 248, 248);
if(isset($_GET["c"]) && trim($_GET["c"])!='') $char = trim($_GET["c"]);
if(isset($_SESSION['code']) && trim($_SESSION['code'])!='') $char = $_SESSION['code'];

//создаём шум на фоне
/*for ($i=0; $i<=128; $i++) {
  $color = imagecolorallocate ($im, rand(0,255), rand(0,255), rand(0,255)); //задаём цвет
  imagesetpixel($im, rand(2,80), rand(2,20), $color); //рисуем пиксель
}*/

for($i=0; $i<6; $i++) {
	$y1 = rand(0,1);
	if($y1) {
		$x1 = 0; 
		$y1 = rand(0, 20); 
		$x2 = 80;
		$y2 = rand(0, 20); 
	}
	else {
		$x1 = rand(0, 80); 
		$y1 = 0; 
		$x2 = rand(0, 80);
		$y2 = 20; 
	}
	$colorLine = GetColor($im, rand(160, 224).",".rand(160, 224).",".rand(160, 224));
	ImageLine($im, $x1, $y1, $x2, $y2, $colorLine);
}
function GetColorArray($color) {
	$a_color = explode(",", $color);
	return $a_color;
}
function GetColor(&$im, $color) {
	$acolor = GetColorArray($color);
	return ImageColorAllocate($im, $acolor[0], $acolor[1], $acolor[2]);
}

//выводим символы кода
$font = "verdana.ttf";
$font_size = 12;
for ($i = 0; $i < strlen($char); $i++) {
  $color = imagecolorallocate ($im, rand(0,128), rand(0,128), rand(0,128)); //задаём цвет
  $x = 5 + $i * 20;
  $y = rand(1, 6)+12;
  $angle = 20-rand(0, 40);
//  imagechar ($im, 5, $x, $y, $char[$i], $color);
  @ImageTTFText($im, $font_size, $angle, $x, $y, $color, $font, $char[$i]);
}

//упрощённый вариант
//$color = imagecolorallocate($img, 0, 0, 0);
//imagestring($im, 3, 5, 3, $char, $color);

//антикеширование
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//создание рисунка в зависимости от доступного формата
if (function_exists("imagepng")) {
   header("Content-type: image/png");
   imagepng($im);
} elseif (function_exists("imagegif")) {
   header("Content-type: image/gif");
   imagegif($im);
} elseif (function_exists("imagejpeg")) {
   header("Content-type: image/jpeg");
   imagejpeg($im);
} else {
   die("No image support in this PHP server!");
}
imagedestroy ($im);
// */
?>