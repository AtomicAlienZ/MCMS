<?php
  
    function resize_image_in_memory($img, $max_width = 200, $max_height = 200) {
        $img = imagecreatefromstring($img);
        if ($img) {
            $width = imagesx($img);
            $height = imagesy($img);
            $scale = min($max_width/$width, $max_height/$height);
            if ($scale < 1) {
                $new_width = floor($scale*$width);
                $new_height = floor($scale*$height);
                $tmp_img = imagecreatetruecolor($new_width, $new_height);
                imagecopyresized($tmp_img, $img, 0, 0, 0, 0,
                                 $new_width, $new_height, $width, $height);
                imagedestroy($img);
                $img = $tmp_img;
            }
        }
        // Create error image if necessary 
        if (!$img) {
            $img = imagecreate($max_width, $max_height);
            $bgc = imagecolorallocate ($img, 255, 0, 0); 
            $tc  = imagecolorallocate ($img, 255, 255, 255); 
            imagefilledrectangle ($img, 0, 0, $max_width, $max_height, $bgc); 
            imagestring ($img, 3, 3, 10, 'Error', $tc);
        }
        ob_start();
        Imagejpeg($img);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    function resize_image($filename, $max_width = 200, $max_height = 200, $jpeg_quality = 90) {
        if (function_exists('exif_imagetype')) {
            $img_type = exif_imagetype($filename);
        } else {
            list($img_width, $img_height, $img_type, $img_atr) = getimagesize($filename);
        }
        switch ($img_type) {
            case IMAGETYPE_GIF:
                $image_create = 'imagecreatefromgif';
                $image_output = 'imagegif';
                break;
            
            case IMAGETYPE_JPEG:
                $image_create = 'imagecreatefromjpeg';
                $image_output = 'imagejpeg';
                break;

            case IMAGETYPE_PNG:
                $image_create = 'imagecreatefrompng';
                $image_output = 'imagepng';
                break;
            
            default: return FALSE;
        }
        if (!function_exists($image_create) || !function_exists($image_output)) {
            return FALSE;
        }
        $img = $image_create($filename);
        if (function_exists('imageantialias')) {
            imageantialias($img, true);
        }
        if ($img) {
            $width = imagesx($img);
            $height = imagesy($img);
            $scale = min($max_width/$width, $max_height/$height);
            if ($scale < 1) {
                $new_width = floor($scale*$width);
                $new_height = floor($scale*$height);                
                if( function_exists ('imagecreatetruecolor') ) {
                    $tmp_img = imagecreatetruecolor($new_width, $new_height);                
                } else {
                    $tmp_img = imagecreate($new_width, $new_height);
                }
                if (function_exists('imagecopyresampled')) {
                    imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                } else {
                    imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                }                
                imagedestroy($img);
                $img = $tmp_img;
            }
        }
        // Create error image if necessary 
        if (!$img) {
            $img = imagecreate($max_width, $max_height);
            $bgc = imagecolorallocate ($img, 255, 0, 0); 
            $tc  = imagecolorallocate ($img, 255, 255, 255); 
            imagefilledrectangle ($img, 0, 0, $max_width, $max_height, $bgc); 
            imagestring ($img, 3, 3, 10, "Error", $tc);
        }
        $img = $image_output($img, $filename, $jpeg_quality);
        return TRUE;
    }   

?>
