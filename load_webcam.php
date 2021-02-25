<?php
/*
	Copyright (C) 2013-2021 Filippo Gurgoglione (ziofil@gmail.com)
	All rights reserved.
	
	Permission is hereby granted, free of charge, to any person obtaining a 
	copy of this software and associated documentation files (the "Software"), 
	to deal in the Software without restriction, including without limitation 
	the rights to use, copy, modify, merge, publish, distribute, sublicense, 
	and/or sell copies of the Software, and to permit persons to whom the 
	Software is furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included 
	in all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS 
	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
	SOFTWARE.
*/

define('DEBUG', false);

function process_data_in($data_in, $type) {
	switch ($type) {
		case "file":
			return load_jpg($data_in);
			break;
		case "folder":
			return load_jpg(get_last_and_remove_files($data_in));
			break;
		default:
			debug("Invalid type");
			return false;
	}

}

function get_last_and_remove_files($folder)
{
	$dirlist = scandir_ (getcwd ( )  . "/" . $folder ,  '/^.*\.(jpg|JPG|jpeg|JPEG)$/i', 'ctime', 1);
	array_walk($dirlist, function(&$item) use ($folder) { $item = getcwd ( )  . "/" . $folder . $item; });
	if (count($dirlist) > 1) {
		$new_file = array_shift($dirlist);
		foreach($dirlist as $fileitem) {
			unlink($fileitem);
		}
		return $new_file;
	}
	
	return false;
}

function scandir_($dir, $exp, $how='name', $desc=0)
{
    $r = array();
    $dh = @opendir($dir);
    if ($dh) {
        while (($fname = readdir($dh)) !== false) {
            if (preg_match($exp, $fname)) {
                $stat = stat("$dir/$fname");
                $r[$fname] = ($how == 'name')? $fname: $stat[$how];
            }
        }
        closedir($dh);
        if ($desc) {
            arsort($r);
        }
        else {
            asort($r);
        }
    }
    return(array_keys($r));
} 

function load_jpg($filename)
{
	$jpgdata = @file_get_contents($filename);
	// http://stackoverflow.com/questions/1459882/check-manually-for-jpeg-end-of-file-marker-ffd9-in-php-to-catch-truncation-e
	if (substr($jpgdata,-2)!="\xFF\xD9") {
		return false;
	}
	return $jpgdata;
}

function debug($str){
	if (DEBUG != false)
		print($str."\n");
}

if (!isset($_GET["webcam"]) || empty($_GET["webcam"]))
{
	http_response_code(404);
	echo "Could not find the webcam\n";
	exit();
}

$webcam_selected = $_GET["webcam"];

$json_webcam_config = file_get_contents("webcam_config.json");
$webcam_config = json_decode($json_webcam_config );

$json_available = FALSE;
$json_webcam_timestamp = file_get_contents("webcam_timestamp.json");
if ($json_webcam_timestamp == FALSE) {
	debug("Error on internal file");
	exit;
}

$webcam_timestamp = json_decode($json_webcam_timestamp, TRUE);
if ($webcam_timestamp == NULL) {
	debug("Error on internal file");
	exit;
}

foreach($webcam_config->webcams as $webcam) {
	if ($webcam->name == $webcam_selected) {
		$jpg_content = process_data_in($webcam->data_in, $webcam->type);
		$file_cache = $webcam->name . "_cache";
		$jpg_content_cache = @file_get_contents($file_cache);
		if ($jpg_content != FALSE) {
			if ($jpg_content_cache == FALSE || $jpg_content != $jpg_content_cache) {
				debug("save new file");
				file_put_contents ($file_cache, $jpg_content); 
				// update json with timestamps
				$webcam_timestamp[$webcam->name] = time();
				file_put_contents ("webcam_timestamp.json",
				  json_encode($webcam_timestamp));

			}
		} else {
			$jpg_content = $jpg_content_cache;
		}

		if ($jpg_content == FALSE) {
			debug("not available");
		} else {
			debug("print jpg_content");
			if (DEBUG != true) {
				// Getting headers sent by the client.
				$headers = apache_request_headers();

				// Checking if the client is validating his cache and if it is current.
				if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $webcam_timestamp[$webcam->name])) {
					header('Last-Modified: '.gmdate('D, d M Y H:i:s', $webcam_timestamp[$webcam->name]).' GMT', true, 304);
				} else {
					// Image not cached or cache outdated, we respond '200 OK' and output the image.
					header('Last-Modified: '.gmdate('D, d M Y H:i:s', $webcam_timestamp[$webcam->name]).' GMT', true, 200);
					header('Content-Length: '.strlen($jpg_content));
					header('Content-Type: image/png');
					print $jpg_content;
				}
			}
		}
		exit;
	}
}

http_response_code(404);
echo "Could not find the webcam " . $webcam_selected ."\n";

?>