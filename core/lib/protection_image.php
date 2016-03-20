<?php
define('HASH_DELTA', 2539); //число прибавляемое к коду на картинке
define('PATH_FONTS', ROOT_HTML_PATH.'fonts/OpenSans.ttf'); //шрифт
@session_start();
class protection_image {
    var $width  = 97; //ширина картинки
    var $height = 25; //высота картинки
    var $font_size = 15; //размер шрифта
    var $letter_spacing = 20; //растояние между цифрами
    var $font_x = 10; //положение по горизонтали
    var $font_y = 20; //положение по вертикали
    var $font_random = 8; //смещение
    var $k = 1.9; //коэффициент увеличения/уменьшения картинки
    var $line_num = 0; //количество линий
    var $hashcode;
    var $hashvalue;
    function get_code($hmin = 1000, $hmax = 9999)
    {
        $this->hashcode = rand($hmin, $hmax);
        $this->hashvalue = md5($this->hashcode+HASH_DELTA);
        $_SESSION['secret_number'] = $this->hashvalue;
    }
    function check_code($code)
    {
        if ($_SESSION['secret_number']) {
            $hash = md5($code + HASH_DELTA);
            if ($_SESSION['secret_number'] == $hash) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
    function get_image()
    {
        header ('Content-type: image/jpeg');
        $im = imagecreate($this->width, $this->height);
        $w = imagecolorallocate($im, 255, 255, 255);
        $g1 = imagecolorallocate($im, rand(190, 220), rand(190, 220), rand(190, 220));
        $g2 = imagecolorallocate($im, rand(50, 70), rand(50, 70), rand(50, 70));
        //выделяем четыре случайных темных цвета для символов
        $cl1 = imagecolorallocate($im, rand(0, 128), rand(0, 128), rand(0, 128));
        $cl2 = imagecolorallocate($im, rand(0, 128), rand(0, 128), rand(0, 128));
        $cl3 = imagecolorallocate($im, rand(0, 128), rand(0, 128), rand(0, 128));
        $cl4 = imagecolorallocate($im, rand(0, 128), rand(0, 128), rand(0, 128));
        //рисуем сетку
      /*  for ($i = 0; $i <= $this->width; $i += 8) {
			$g44 = imagecolorallocate($im, rand(160, 190), rand(160, 190), rand(160, 190));
			imageline($im, $i, 0, $i, $this->height, $g44);
		}
        for ($i = 0; $i <= $this->height; $i += 8) {
			$g44 = imagecolorallocate($im, rand(160, 190), rand(160, 190), rand(160, 190));
			imageline($im, 0,$i, $this->width, $i, $g44);
		} */
        //выводим каждую цифру по отдельности, немного смещая случайным образом
        imagettftext($im, $this->font_size, 0 + rand(0, $this->font_random), $this->font_x + rand(0, $this->font_random), $this->font_y + rand(0, $this->font_random), $cl1, PATH_FONTS, substr($this->hashcode, 0, 1));
        imagettftext($im, $this->font_size, 0 + rand(0, $this->font_random), $this->font_x + $this->letter_spacing + rand(0, $this->font_random), $this->font_y + rand(0, $this->font_random), $cl2, PATH_FONTS, substr($this->hashcode, 1, 1));
        imagettftext($im, $this->font_size, 0 + rand(0, $this->font_random), $this->font_x + $this->letter_spacing * 2 + rand(0, $this->font_random), $this->font_y + rand(0, $this->font_random), $cl3, PATH_FONTS, substr($this->hashcode, 2, 1));
        imagettftext($im, $this->font_size, 0 + rand(0, $this->font_random), $this->font_x + $this->letter_spacing * 3 + rand(0, $this->font_random), $this->font_y + rand(0, $this->font_random), $cl4, PATH_FONTS, substr($this->hashcode, 3, 1));
        //выводим пару случайных линий тесного цвета, прямо поверх символов
        for ($i = 0; $i < $this->line_num; $i++) {
            imageline($im, rand(0, $this->width), rand(0, $this->height), rand(0, $this->width), rand(0, $this->height), $g2);
        }
        //создаем новое изображение, увеличенного размера
        $im1 = imagecreatetruecolor($this->width * $this->k, $this->height * $this->k);
        //копируем изображение с изменением размеров в большую сторону
        imagecopyresized($im1, $im, 0, 0, 0, 0, $this->width * $this->k, $this->height * $this->k, $this->width, $this->height);
        //создаем новое изображение, нормального размера
        $im2 = imagecreatetruecolor($this->width, $this->height);
        //копируем изображение с изменением размеров в меньшую сторону
        imagecopyresampled($im2, $im1, 0, 0, 0, 0, $this->width, $this->height, $this->width * $this->k, $this->height * $this->k);
        imagejpeg($im2);
        imagedestroy($im2);
        imagedestroy($im1);
        imagedestroy($im);
    }
}
?>