<?php
	class files {
		var $qualityJPEG = 90;

		function files() {
		}

		function createDirectory($dir) {
//			echo $dir."<br>";
			if (!file_exists($dir))
				if (!mkdir($dir, 0777)) {
//					echo 'Error create directory!!!';
					return false;
				}
			@chmod($dir, 0777);
			return true;
		}
		function checkDir($dir) {
			$dir = str_replace(ROOT_HTML_PATH, "", $dir);
			$aDir = explode("/", $dir);
			$fullDir = ROOT_HTML_PATH;
			foreach($aDir as $subdir) {
				if(trim($subdir)) {
					$fullDir .= $subdir."/";
					if (!$this->createDirectory($fullDir)) return false;
				}
			}
			return true;
		}
		function deleteDir($path) {
			if (file_exists($path)) {
				$current_dir = opendir($path);
				while($entryname = readdir($current_dir)){
					if($entryname != "." && $entryname!="..") {
						clearstatcache();
						if (!is_dir($path.$entryname)) unlink($path.$entryname);
						else $this->deleteDir($path.$entryname."/");
					}
				}
				closedir($current_dir);
			}
			$this->deleteOneDir($path);
		}
		function deleteOneDir($path) {
			return @rmdir($path);
		}
		function deleteFile($file) {
			if(trim($file) && file_exists($file))
			return @unlink(addslashes($file));
		}
		function saveFileToDisk($dir, $var) {
			global $SystemConnect;
//			echo $dir." ".$var;
			if (empty($dir)) return false;
			switch ($_FILES[$var]['error']) {
				case 0:	$str_error_second = ''; break;
				case 1:	$str_error_second = 'Ошибка загрузки файла! Размер файла превышает максимально допустимый размер сервера для динамической загрузки'; break;
				case 2:	$str_error_second = 'Ошибка загрузки файла! Размер файла превышает максимально допустимый размер скрипта для динамической загрузки'; break;
				case 3:	$str_error_second = 'Ошибка загрузки файла! Файл не был полностью загружен на сервер'; break;
				case 4:	$str_error_second = ''; break;
			}
			if ($str_error_second == '') {
				$dateTime = date("Y_m_d_H_i_s");
				$file_name = $this->CyrillicToLatin($_FILES[$var]['name']);
//				echo $dir."<br>";
				$this->checkDir($dir);
//				die();

				if (file_exists($dir.$file_name)) {
					$aFileName = explode(".", $file_name);
					$newFileName = "";
					for ($i=0; $i<count($aFileName)-1; $i++) $newFileName .= $aFileName[$i];
					$newFileName .= "_".$dateTime.".".$aFileName[count($aFileName)-1];
					$file_destination = $dir.$newFileName;
				}
				else $file_destination = $dir.$file_name;

				if (!move_uploaded_file($_FILES[$var]['tmp_name'], $file_destination)) {
					$str_error_second = 'Ошибка при перемещении файла внутри сервера';
					return false;
				}

				@chmod($file_destination, 0777);
				return $file_destination;
			}
			else {
				return false;
			}
			return false;
		}
		function getContentFile($filePath, $headerFileName, $contentType) {
			$filename = $filePath; 
			if (!file_exists($filename)) { 
				header ("HTTP/1.0 404 Not Found"); 
				exit; 
			} 
			$fsize = filesize($filename); 
			$ftime = date("D, d M Y H:i:s T", filemtime($filename)); 
			$fd = @fopen($filename, "rb"); 
			if (!$fd) {
			  header ("HTTP/1.0 403 Forbidden"); 
			  exit; 
			} 
			// Если запрашивающий агент поддерживает докачку 
			if ($HTTP_SERVER_VARS["HTTP_RANGE"]) { 
			  $range = $HTTP_SERVER_VARS["HTTP_RANGE"]; 
			  $range = str_replace("bytes=", "", $range); 
			  $range = str_replace("-", "", $range); 
			  if ($range) {fseek($fd, $range);} 
			} 
			$content = fread($fd, filesize($filename)); 
			fclose($fd); 
			if ($range) { header("HTTP/1.1 206 Partial Content"); } 
			else { header("HTTP/1.1 200 OK"); } 

			header("Pragma: public"); 
			header("Expires: 0"); 
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
			header("Cache-Control: public"); 
			header("Content-Description: File Transfer"); 

			header("Content-Type: ".$contentType);
			header("Content-Disposition: attachment; filename=".$headerFileName); 
			header("Last-Modified: $ftime"); 
			header("Accept-Ranges: bytes"); 
			header("Content-Length: ".($fsize-$range)); 
			header("Content-Range: bytes $range-".($fsize -1)."/".$fsize); 
			print $content; 

			exit;
		}
		function getDir($path) {
			$ret = array();
			if (file_exists($path)) {
				$current_dir = opendir($path);
				while($entryname = readdir($current_dir)){
					if($entryname != "." and $entryname!="..") {
						clearstatcache();
						if (!is_dir($path.$entryname)) {
							$files["name"] = $entryname;
							$files["path"] = $path.$entryname;
							$ret["files"][] = $files;
						}
						else {
							$dirs["name"] = $entryname;
							$dirs["path"] = $path.$entryname;
							$ret["dirs"][] = $dirs;

						}
					}
				}
				closedir($current_dir);
			}
			return $ret;
		}
		function copyFile($filePath, $fileDestination) {
//			$fileDestination = $this->CyrillicToLatin($fileDestination);
			@copy($filePath, $fileDestination);
			return $fileDestination;
		}
		function getNameFileForResize($fileSource, $prefix="_small", $prefix2="") {
			$fileInfo = pathinfo($fileSource);
			$ext = strtolower($fileInfo["extension"]);
			$fileName = str_replace(".".$fileInfo["extension"], "", str_replace($fileInfo["dirname"]."/", "", $fileSource));
			$fileDestination = $fileInfo["dirname"]."/".$this->CyrillicToLatin($fileName.$prefix.$prefix2).".".$ext;

			$dateTime = date("Y_m_d_H_i_s");
			if (file_exists($fileDestination)) {
				$fileDestination = $fileInfo["dirname"]."/".$this->CyrillicToLatin($fileName.$prefix.$prefix2."_".$dateTime).".".$ext;
			}
			return $fileDestination;
		}
		function CyrillicToLatin($text, $replacespace=true) {
			$result = "";
			$cyr = array("","а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ь"=>"","ы"=>"y","ъ"=>"","э"=>"e","ю"=>"yu","я"=>"ya"," "=>"_","А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E","Ё"=>"E","Ж"=>"Zh","З"=>"Z","И"=>"I","Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"Kh","Ц"=>"Ts","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Sch","Ь"=>"","Ы"=>"Y","Ъ"=>"","Э"=>"E","Ю"=>"Yu","Я"=>"Ya");
			$other = array("","q","w","e","r","t","y","u","i","o","p","a","s","d","f","g","h","j","k","l","z","x","c","v","b","n","m",".","_","");
			for($i=0; $i<10; $i++) $other[] = $i;
			for($i=0; $i<strlen($text); $i++) {
				if(isset($cyr[$text[$i]])) {
					$result.=$cyr[$text[$i]];
				}
				elseif(ord($text[$i])>=97 && ord($text[$i])<=122 || ord($text[$i])>=65 && ord($text[$i])<=90) {
					$result.=$text[$i];
				}
				else {
					$result.="";
				}
			}
			return $result;
		}
		function nameFileForDB(&$fileName) {
			$fileName = str_replace(ROOT_HTML_PATH, "/", $fileName);
		}

//***********************************************************************************************************
//Работа с zip (начало)
//***********************************************************************************************************
		function ectractZip($fileSource, $decompresTo="") {
			require_once(cms_PATH_TOOLS."pclzip.lib.php");

			$fileInfo = pathinfo($fileSource);
//			echo "<pre>"; print_r($fileInfo); echo "</pre>";die();
			if(!$decompresTo) {
				$decompresTo = $fileInfo["dirname"]."/";
			}

			$fileCounters = 0;
			$folderCounters = 0;
			$tot_comp = 0;
			$tot_uncomp = 0;
			$action = "unzip"; //Command

			$zip = new PclZip($fileSource);
			if (($list = $zip->listContent()) == 0) {
//				echo ("Error : ".$zip->errorInfo(true));
				return false;
			}
//			echo "<pre>"; print_r($list); echo "</pre>";//die();
			for ($i=0; $i<sizeof($list); $i++) {
				if ($list[$i]["folder"]=='1') {
					$folderCounters++;
					$dirs[$fold] = $list[$i]["stored_filename"];
					if ($action == 'unzip') {
						$dirname = $list[$i]["stored_filename"];
						$dirname = substr($dirname,0,strlen($dirname)-1);
						$this->checkDir($basedir.'/'.$dirname);
					}
					@chmod($basedir.'/'.$dirname,0777);
				}
				else {
					$fileCounters++;
				}
				$tot_comp += $list[$i]["compressed_size"];
				$tot_uncomp += $list[$i]["size"];
			}
			if ($action == 'unzip') {
//				echo "<br><b>Starting to decompress...</b><br>";
				$extractResult = $zip->extract($decompresTo);
//				echo "<pre>"; print_r($extractResult); echo "</pre>";
				return $extractResult;
//				echo "Archive sucessfuly extracted!<br>\n";
			}

			if ($action == 'view') {
				echo "<br>";
				for ($i=0; $i<sizeof($list); $i++) {
					if ($list[$i]["folder"] == 1) {
						echo "<b>Folder: ".$list[$i]["stored_filename"]."</b><br>";
					}
					else {
						echo $list[$i]["stored_filename"]." (".$this->convertsize($list[$i]["size"]).")<br>";
					}
				}
			}
		}
		function convertsize($size) {
			$times = 0;
			$comma = '.';
			while ($size>1024) {
				$times++;
				$size = $size/1024;
			}
			$size2 = floor($size);
			$rest = $size - $size2;
			$rest = $rest * 100;
			$decimal = floor($rest);

			$addsize = $decimal;
			if ($decimal<10) {
				$addsize .= '0';
			}

			if ($times == 0) {
				$addsize=$size2;
			}
			else {
				$addsize=$size2.$comma.substr($addsize,0,2);
			}
			switch ($times) {
				case 0 : $mega = ' bytes'; break;
				case 1 : $mega = ' KB'; break;
				case 2 : $mega = ' MB'; break;
				case 3 : $mega = ' GB'; break;
				case 4 : $mega = ' TB'; break;
			}
			$addsize .= $mega;
			return $addsize;
		}
//***********************************************************************************************************
//Работа с zip (конец)
//***********************************************************************************************************

//***********************************************************************************************************
//Работа с графикой (начало)
//***********************************************************************************************************
		function resizeImage($filename, $max_width = 60, $max_height = 60, $jpgQual=90) {
			$file_info = pathinfo($filename);
			$ext = $file_info["extension"];
			$img = null;
			if ( ($ext == 'jpg' || $ext == 'jpeg') && function_exists('imagecreatefromjpeg') ) {
				$img = imagecreatefromjpeg($filename);
			}
			elseif ($ext == 'png' && function_exists('imagecreatefrompng')) {
				$img = imagecreatefrompng($filename);
			}
			elseif ($ext == 'gif' && function_exists('imagecreatefromgif')) {
				$img = imagecreatefromgif($filename);
			}
			else {
				return false;
			}
			if ($img) {
				$width = imagesx($img);
				$height = imagesy($img);
				if($width<=$max_width && $height<=$max_height) return;
				$scale = min($max_width/$width, $max_height/$height);
				if ($scale < 1) {
					$new_width = round($scale*$width);  //// floor
					$new_height = round($scale*$height);
					$tmp_img = imagecreatetruecolor($new_width, $new_height);
					imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
					imagedestroy($img);
					$img = $tmp_img;
				}
			}

			if (!$img) {
				$img = imagecreate($max_width, $max_height);
				imagecolorallocate($img,0,0,0);
				$c = imagecolorallocate($img,70,70,70);
				imageline($img,0,0,$max_width,$max_height,$c2);
				imageline($img,$max_width,0,0,$max_height,$c2);
			}

			if ( ($ext == 'jpg' || $ext == 'jpeg') && function_exists('imagejpeg') ) {
				$img = imagejpeg($img, $filename, $jpgQual);
			}
			elseif ($ext == 'png' && function_exists('imagepng')) {
				$img = imagepng($img, $filename);
			}
			elseif ($ext == 'gif' && function_exists('imagegif')) {
				$img = imagegif($img, $filename);
			}
			else {
				return false;
			}
		}
		function resizeImageMode($file, $max_width = 200, $max_height = 200, $mode = 0, $overlay = '') {
/*
			mode:
			0 - масштабирование
			1 - масштабирование с добавлением полей
			2 - масштабирование с обрезанием частей
			3 - масштабирование с обрезанием частей и добавлением полей
			дописать действия при:
				- добавлении изображения меньше максимума
				- прозрачность
*/
			$file_info = pathinfo($file);
			$ext = $file_info['extension'];
			if (($ext == 'jpg') && function_exists('imagecreatefromjpeg')) {
				$img = imagecreatefromjpeg($file);
			}
			elseif ($ext == 'png' && function_exists('imagecreatefrompng')) {
				$img = imagecreatefrompng($file);
			}
			elseif ($ext == 'gif' && function_exists('imagecreatefromgif')) {
				$img = imagecreatefromgif($file);
			}
			else {
				return false;
			}
			if ($overlay != '' && function_exists('imagecreatefrompng')) {
				$overlay = imagecreatefrompng($overlay);
				$overlay_width = imagesx($overlay);
				$overlay_height = imagesy($overlay);
			}
			if ($img) {
				$src_width = imagesx($img);
				$src_height = imagesy($img);
				$ratio_w = $max_width/$src_width;
				$ratio_h = $max_height/$src_height;
				$ratio = min($ratio_w, $ratio_h);
				if ($ratio < 1) {// если нужно уменьшать
					if($mode == 0) {
						$dst_width = ($ratio == $ratio_w) ? $max_width : floor($ratio*$src_width);
						$dst_height = ($ratio == $ratio_h) ? $max_height : floor($ratio*$src_height);
						$dst_img = imagecreatetruecolor($dst_width, $dst_height);
						imagecopyresampled($dst_img, $img, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
					}
					elseif ($mode == 1) {
						$dst_width = ($ratio == $ratio_w) ? $max_width : floor($ratio*$src_width);
						$dst_height = ($ratio == $ratio_h) ? $max_height : floor($ratio*$src_height);
						$dst_img = imagecreatetruecolor($max_width, $max_height);
						$background = imagecolorallocate($dst_img, 255, 255, 255);
						imagefill($dst_img, 0, 0, $background);
						if ($dst_width < $max_width) {
							imagecopyresampled($dst_img, $img, floor($max_width-$dst_width)/2, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
						}
						elseif ($dst_height < $max_height) {
							imagecopyresampled($dst_img, $img, 0, floor($max_height-$dst_height)/2, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
						}
						else {
							imagecopyresampled($dst_img, $img, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
						}
					}
					elseif ($mode == 2 || $mode == 3) {
						$ratio = max($ratio_w, $ratio_h);
						if ($src_width <= $max_width || $ratio >= 1) {//если ширина меньше или равна максимуму
							$dst_width = $src_width;
						}
						else {
							$dst_width = ($ratio == $ratio_w) ? $max_width : floor($ratio*$src_width);//если жмем по ширине
						}
						if ($src_height <= $max_height || $ratio >= 1) {//если высота больше или равна максимуму
							$dst_height = $src_height;
						}
						else {
							$dst_height = ($ratio == $ratio_h) ? $max_height : floor($ratio*$src_height);//если жмем по высоте
						}
						if ($ratio_w < 1 && $ratio_h < 1) {//масштабируем, все вылазит
							$tmp_img = imagecreatetruecolor($dst_width, $dst_height);
							imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
						}
						else {
							$tmp_img = $img;
						}
						if ($dst_width > $max_width) {//режем ширину
							$dst_img = imagecreatetruecolor($max_width, $dst_height);
//							imagecopyresampled($dst_img, $tmp_img, 0, 0, 0, 0, $max_width, $dst_height, $max_width, $dst_height); // от левого края
							imagecopyresampled($dst_img, $tmp_img, 0, 0, floor($dst_width-$max_width)/2, 0, $max_width, $dst_height, $max_width, $dst_height); // от середины
						}
						elseif ($dst_height > $max_height) {//режем высоту
							$dst_img = imagecreatetruecolor($dst_width, $max_height);
							imagecopyresampled($dst_img, $tmp_img, 0, 0, 0, 0, $dst_width, $max_height, $dst_width, $max_height); // от верха
//							imagecopyresampled($dst_img, $tmp_img, 0, 0, 0, floor($dst_height-$max_height)/2, $dst_width, $max_height, $dst_width, $max_height); // от середины
						}
						else {
							$dst_img = $tmp_img;
						}
					}
					if ($mode == 3) {//добавляем поля
						if ($dst_width < $max_width) {//сверху и снизу
							$max_img = imagecreatetruecolor($max_width, $max_height);
							$background = imagecolorallocate($max_img, 255, 255, 255);
							imagefill($max_img, 0, 0, $background);
							imagecopyresampled($max_img, $dst_img, floor($max_width-$dst_width)/2, 0, 0, 0, $dst_width, $dst_height, $dst_width, $dst_height);
							$dst_img = $max_img;
						}
						elseif ($dst_height < $max_height) {//по бокам
							$max_img = imagecreatetruecolor($max_width, $max_height);
							$background = imagecolorallocate($max_img, 255, 255, 255);
							imagefill($max_img, 0, 0, $background);
							imagecopyresampled($max_img, $dst_img, 0, floor($max_height-$dst_height)/2, 0, 0, $dst_width, $dst_height, $dst_width, $dst_height);
							$dst_img = $max_img;
						}
					}
					imagedestroy($img);
					$img = $dst_img;
				}
			}
			else {// генерация изображения при ошибке
				$img = imagecreate($max_width, $max_height);
				imagecolorallocate($img,0,0,0);
				$c = imagecolorallocate($img,70,70,70);
				imageline($img,0,0,$max_width,$max_height,$c2);
				imageline($img,$max_width,0,0,$max_height,$c2);
			}

			if ($overlay != '') {
				imagecopyresampled($img, $overlay, 0, 0, 0, 0, $overlay_width, $overlay_height, $overlay_width, $overlay_height);
			}

			if (($ext == 'jpg') && function_exists('imagejpeg')) {
				$img = imagejpeg($img, $file, $this->qualityJPEG);
			}
			elseif ($ext == 'png' && function_exists('imagepng')) {
				$img = imagepng($img, $file);
			}
			elseif ($ext == 'gif' && function_exists('imagegif')) {
				$img = imagegif($img, $file);
			}
			else {
				return false;
			}
		}
//***********************************************************************************************************
//Работа с графикой (конец)
//***********************************************************************************************************
	
	}
?>