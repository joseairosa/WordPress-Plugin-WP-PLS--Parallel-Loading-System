<?php
ob_start();
ini_set('display_errors',0);
ini_set ( "memory_limit", "64M" );

define('ROOT_PATH','/home/joseairo/domains/joseairosa.com/public_html/');
define('FILE_REQUEST',$_GET['file_src']);

function getExtension($filename) {
	return $ext = strtolower ( array_pop ( explode ( '.', $filename ) ) );
}

function remove_subdomain($subdomain) {
	$array = explode(".",$subdomain);
	if(count($array) > 2) {
		unset($array[0]);
		return implode(".",$array);
	} else {
		$subdomain;
	}
}

function render_image($file) {
	header('Cache-Control: max-age=2592000, must-revalidate');
	header('Last-Modified: '.date(RFC822,mktime(0, 0, 0, 0, 1,   date("Y"))));
	header('Expires: '.date(RFC822,mktime(0, 0, 0, 0, 1,   date("Y")+1)));
	if (getExtension ( $file ) == 'png') {
		$myImage = imagecreatefrompng ( $file ) or die ( "Error: Cannot find image!" );
		imagealphablending($myImage, true);
		imagesavealpha($myImage, true);
		header ( 'Content-type: image/png' );
		imagepng ( $myImage,null,9 );
	} elseif (getExtension ( $file ) == 'jpg' || getExtension ( $file ) == 'jpeg' || getExtension ( $file ) == 'jpe') {
		$myImage = imagecreatefromjpeg ( $file ) or die ( "Error: Cannot find image!" );
		header ( 'Content-type: image/jpeg' );
		imagejpeg ( $myImage,null,100 );
	} elseif (getExtension ( $file ) == 'gif') {
		$myImage = imagecreatefromgif ( $file ) or die ( "Error: Cannot find image!" );
		header ( 'Content-type: image/gif' );
		imagegif ( $myImage );
	}
	imagedestroy ( $myImage );
}

if(file_exists(ROOT_PATH.FILE_REQUEST)) {
	if (getExtension ( ROOT_PATH.FILE_REQUEST ) == 'php') {
		$_SERVER["HTTP_HOST"] = remove_subdomain($_SERVER["HTTP_HOST"]);
		unset($_GET['file_src'],$_GET['extension'],$_GET['t']);
		require_once ROOT_PATH.FILE_REQUEST;
		exit();
	} else {
		render_image(ROOT_PATH.FILE_REQUEST);
	}
} elseif(preg_match("/http[s]?\:\/\/?/i",$_GET['file_src'])) {
	if(preg_match("/http[s]?\:\/[^\/.*]/i",$_GET['file_src'])) {
		$url = str_replace(array("http:/","https:/"),array("http://","https://"),$_GET['file_src']);
		
		if (getExtension ( $url ) == 'php') {
			$_SERVER["HTTP_HOST"] = remove_subdomain($_SERVER["HTTP_HOST"]);
			
			$url = str_replace(array("http://www.".$_SERVER["HTTP_HOST"],"http://".$_SERVER["HTTP_HOST"],$_SERVER["HTTP_HOST"]),"",$url);
			if(substr($url, 0, 1) == "/")
				$url = substr($url, 1, strlen($url)-1);

			unset($_GET['file_src'],$_GET['extension'],$_GET['t']);
			require_once ROOT_PATH.$url;
			exit();
		} else {
			render_image($url);
		}
		
		
		$_SERVER["HTTP_HOST"] = remove_subdomain($_SERVER["HTTP_HOST"]);
		die($_SERVER["HTTP_HOST"]);
		unset($_GET['file_src'],$_GET['extension'],$_GET['t']);
		require_once $url;
		exit();
		
		render_image($url);
	}
	else {
		header("HTTP/1.0 404 Not Found");
	}
} else {
	header("HTTP/1.0 404 Not Found");
}
ob_end_flush();
?>