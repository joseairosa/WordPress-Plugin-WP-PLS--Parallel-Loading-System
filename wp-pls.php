<?php
/*
Plugin Name: WP Parallel Loading System
Plugin URI: http://wp-pls.joseairosa.com/
Description: Implements the ability to load elements using paralled connections by using virtual subdomains
Author: Jos&eacute; P. Airosa
Version: 0.1.9.1
Author URI: http://www.joseairosa.com/

Copyright 2010  José P. Airosa  (email : me@joseairosa.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Added/Changed on version 0.1.2
global $wpdb,$system_info,$message;
$system_info = array();
$message = array();

//========================================
// Plugin Settings
//========================================
define("PLS_DB_VERISON","1.0.1");
define("PLS_VERISON","0.1.9.1");
define("PLS_REQUIRED_PHP_VERSION","5.1.3");
define("PLS_DB_TABLE",$wpdb->prefix . "pls");
define("PLS_PLUGIN_FOLDER_NAME","parallel-loading-system");
// Added on version 0.1.3
define("PLS_IS_NGINX",check_for_nginx());
define("PLS_IS_WIN",check_for_windows());
// Added on version 0.1.3 - END
define("PLS_SCRIPT_NAME","load_image.php");
define("PLS_SCRIPT_PATH",check_for_trailing_slash(WP_PLUGIN_DIR).check_for_trailing_slash(PLS_PLUGIN_FOLDER_NAME).PLS_SCRIPT_NAME);
define("PLS_HTACCESS_NAME",".htaccess");
define("PLS_HTACCESS_PATH",check_for_trailing_slash(WP_PLUGIN_DIR).check_for_trailing_slash(PLS_PLUGIN_FOLDER_NAME).PLS_HTACCESS_NAME);
define("PLS_HTACCESS_CODE","#BEGIN WP-PLS
RewriteRule ^(.*\.(jpg|jpeg|gif|png|php)) load_image.php?file_src=$1&extension=$2 [L,QSA]
#END WP-PLS
");
define("PLS_HTACCESS_REGEX",'/\#BEGIN WP\-PLS\sRewriteRule \^\(\.\*\\\.\(jpg\|jpeg\|gif\|png\|php\)\) load\_image\.php\?file\_src\=\$1\&extension\=\$2 \[L\,QSA\]\s\#END WP\-PLS\s/i');
define("PLS_TEST_IMAGE_URL",check_for_trailing_slash(WP_PLUGIN_URL).check_for_trailing_slash(PLS_PLUGIN_FOLDER_NAME)."check_headers.jpg");
// Added on version 0.1.3
define("PLS_HAS_MOD_REWRITE",in_array("mod_rewrite",_apache_get_modules()));
define("PLS_HAS_MOD_HEADERS",in_array("mod_headers",_apache_get_modules()));
define("PLS_WEBCONFIG_NAME",'web.config');
define("PLS_WEBCONFIG_PATH",check_for_trailing_slash(WP_PLUGIN_DIR).check_for_trailing_slash(PLS_PLUGIN_FOLDER_NAME).PLS_WEBCONFIG_NAME);
// Added on version 0.1.3 - END
define("HTTP_SERVER_ROOT",get_root());

//========================================
// Checks for windows format folders
//========================================
// Added on version 0.1.3
// Changed on version 0.1.8 (Thank you Christian blog@tagdocs.de)
function check_for_windows() {
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	    return true;
	}
	return false;
}

//========================================
// Checks for windows format folders
//========================================
// Added on version 0.1.3
function check_for_nginx() {
	$apache_options = _apache_get_modules();
	return ((!in_array("core",$apache_options)) ? true : false );
}

//========================================
// Checks for apache modules (the safe way)
//========================================
// Added on version 0.1.3
function _apache_get_modules() {
	if(function_exists("apache_get_modules"))
		return apache_get_modules();
	else
		return array('mod_rewrite','mod_headers','core');
}

//========================================
// Checks for active plugins for compatibility issues
//========================================
// Added on version 0.1.3
function check_for_plugins() {
	return array('Simple View' => is_dir(check_for_trailing_slash(WP_PLUGIN_DIR).'simple-view'),'WP Super Cache' => @is_dir(check_for_trailing_slash(WP_PLUGIN_DIR).'wp-super-cache'),'WP Cache' => @is_dir(check_for_trailing_slash(WP_PLUGIN_DIR).'wp-cache'));
}

//========================================
// Checks whether a given string has a trailer slash and if not, add it. 
//========================================
function check_for_trailing_slash($path) {
	// Changed on version 0.1.3
	if(!PLS_IS_WIN) {
		if(substr($path,-1) != "/")
			return $path."/";
		else
			return $path;
	} else {
		if(substr($path,-1) != "\\")
			return $path."\\";
		else
			return $path;
	}
}

//========================================
// Checks whether a given url exists. 
//========================================
// Changed on version 0.1.5
function url_exists($url) {
    if(!strstr($url, "http://")) {
        $url = "http://".$url;
    }
    $header = get_headers($url,1);
    if(isset($header[0])) {
    	return true;
    } else {
	    $url = str_replace(array("http://","https://"),array("",""),$url);
    	if(fsockopen($url, 80, $errno, $errstr, 30)) {
    		return true;
    	}
    }
    return false;
} 

//========================================
// Checks what kind of HTTP code a given domain replies
//========================================
function subdomain_health($subdomain) {
	// Changed on version 0.1.5
	if(get_option('wp_pls_simple_method')) {
		$headers = get_headers(check_for_trailing_slash($subdomain).PLS_TEST_IMAGE_URL,1);
	} else {
		$headers = get_headers(check_for_trailing_slash($subdomain).str_replace(array(check_for_trailing_slash("http://".$_SERVER['SERVER_NAME']),check_for_trailing_slash("https://".$_SERVER['SERVER_NAME'])),array("",""),PLS_TEST_IMAGE_URL),1);
	}
	if(count($headers) == 0)
		return false;
	else
		return current($headers);
}

//========================================
// Attempt to copy plugin system files from plugin folder to a given path
//========================================
function pls_file_copy($path,$type = "script") {
	global $system_info,$message;
	$path = check_for_trailing_slash($path);
	switch($type){
		case 'script':
			// Added on version 0.1.2
			if(file_exists($path.PLS_SCRIPT_NAME)) {
				$backup_file_name = PLS_SCRIPT_NAME.".bak_".date("m-d-y_H:i");
				$backup_file = $path.$backup_file_name;
				$system_info['script_backup'] = true;
				if(@copy($path.PLS_SCRIPT_NAME,$backup_file)) {
					$system_info['script_backup_text_success'] = "When trying to update ".PLS_SCRIPT_NAME." on "._highlight_string($path)." I detected that there was already a version there.<br/><br/>For security reasons I've created a backup, on the same folder, named: "._highlight_string($backup_file_name)."";
				} else {
					$system_info['script_backup_text_error'] = "When trying to update ".PLS_SCRIPT_NAME." on "._highlight_string($path)." I detected that there was already a version there.<br/><br/>I was not able to create a backup (might be permission issues), therefore, for security reasons, I have canceled the action";
					return false;
				}
			}
			if(@copy(PLS_SCRIPT_PATH,$path.PLS_SCRIPT_NAME)) {
				return true;
			}
			break;
		case 'htaccess':
			if(PLS_IS_NGINX) {
				// Added on version 0.1.3
				// Specific condition for a NGINX system
				$message[] = 'Nginx is not yet fully supported. However take a look at this code:<br/><br/><code>if (-f $request_filename) {<br/>break;<br/>}<br/>if (-d $request_filename) {<br/>break;<br/>}<br/>rewrite ^(.*\.(jpg|jpeg|gif|png|php)) load_image.php?file_src=$1&extension=$2 last;</code><br/><br/>This should be added to your '._highlight_string("nginx.conf").' file.<br/>If you need further support please visit <a href="http://www.joseairosa.com/2010/05/17/wordpress-plugin-parallel-loading-system/" target="_blank">my website</a> or <a href="mailto:me@joseairosa.com" target="_blank">email me</a>. I will try to reply as fast as possible.';
			} elseif(PLS_IS_WIN) {
				// Added on version 0.1.3
				// Specific condition for a Windows system
				$message[] = 'Windows based server is not yet fully supported. However take a look at this code:<br/><br/><code>&lt;rewrite&gt;<br/>&lt;rules&gt;<br/>&lt;rule name="Main Rule" stopProcessing="true"&gt;<br/>&lt;match url="^(.*\.(jpg|jpeg|gif|png|php))" /&gt;<br/>&lt;conditions logicalGrouping="MatchAll"&gt;<br/>&lt;add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" /&gt;<br/>&lt;add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" /&gt;<br/>&lt;/conditions&gt;<br/>&lt;action type="Rewrite" url="load_image.php?file_src={R:1}&extension={R:2}" appendQueryString="true" /&gt;<br/>&lt;/rule&gt;<br/>&lt;/rules&gt;<br/>&lt;/rewrite&gt;</code><br/><br/>This should be added to your '._highlight_string("web.config").' file.<br/>If you need further support please visit <a href="http://www.joseairosa.com/2010/05/17/wordpress-plugin-parallel-loading-system/" target="_blank">my website</a> or <a href="mailto:me@joseairosa.com" target="_blank">email me</a>. I will try to reply as fast as possible.';
			} else {
				// Added on version 0.1.2
				$append_file = false;
				$file_contents = "";
				$matches = array();
				if(file_exists($path.PLS_HTACCESS_NAME)) {
					// Added on version 0.1.3
					// Try to check if we need to append file or rewrite it.
					$file_contents = file_get_contents($path.PLS_HTACCESS_NAME);
					// Try to find WordPress own htaccess by searching for the common match "RewriteRule . /index.php [L]"
					if(preg_match("/(RewriteRule \. \/index\.php \[L\])/i",$file_contents,$matches)) {
						$append_file = true;
					}
					$backup_file_name = PLS_HTACCESS_NAME.".bak_".date("m-d-y_H:i");
					$backup_file = $path.$backup_file_name;
					$system_info['script_backup'] = true;
					if(@copy($path.PLS_HTACCESS_NAME,$backup_file)) {
						$system_info['script_backup_text_success'] = "When trying to update ".PLS_HTACCESS_NAME." on "._highlight_string($path)." I detected that there was already a version there.<br/><br/>For security reasons I've created a backup, on the same folder, named: "._highlight_string($backup_file_name)."";
					} else {
						$system_info['script_backup_text_error'] = "When trying to update ".PLS_HTACCESS_NAME." on "._highlight_string($path)." I detected that there was already a version there.<br/><br/>I was not able to create a backup (might be permission issues), therefore, for security reasons, I have canceled the action";
						return false;
					}
				}
				if(!$append_file) {
					// Copy file
					if(@copy(PLS_HTACCESS_PATH,$path.PLS_HTACCESS_NAME)) {
						return true;
					}
				} else {
					// Added on version 0.1.3
					// Append file
					if(!isset($matches[1])) {
						// Just making sure there is something on this variable
						$matches[1] = "nothing-to-change";
					}
					if(!@is_writable($path.PLS_HTACCESS_NAME)) {
						$system_info['script_backup_text_error'] = "When trying to update ".PLS_HTACCESS_NAME." on "._highlight_string($path)." I detected that this is probably your WordPress .htaccess.<br/><br/>However, I was not able to update it, therefore, for security reasons, I have canceled the action.<br/><br/>In order for me to be able to update you need to change its permissions to "._highlight_string("777")." temporarly.";
						return false;
					}
					preg_match(PLS_HTACCESS_REGEX,$file_contents,$mat);
					if(!preg_match(PLS_HTACCESS_REGEX,$file_contents)) {
						$file_resource = fopen($path.PLS_HTACCESS_NAME,"w");
						// Append content
						$htaccess_new_content = str_replace($matches[1],PLS_HTACCESS_CODE.$matches[1],$file_contents);
						fwrite($file_resource, $htaccess_new_content);
						fclose($file_resource);
					}
					return true;
				}
			}
			break;
		default:
			break;
	}
	return false;
}

//========================================
// Highlight a string on alerts
//========================================
function _highlight_string($string) {
	return sprintf("%s$string%s",'<span style="-moz-border-radius: 5px;-webkit-border-radius:5px;background-color: #666666; padding: 3px 10px;color: #FFFFFF;line-height:25px;">','</span>');
}

//========================================
// Clear backup variables
//========================================
function clear_script_backup_flags() {
	global $system_info;
	if(isset($system_info['script_backup'])){unset($system_info['script_backup']);}
	if(isset($system_info['script_backup_text_success'])){unset($system_info['script_backup_text_success']);}
	if(isset($system_info['script_backup_text_error'])){unset($system_info['script_backup_text_error']);}
}

//========================================
// Attempt to update both plugin system files
//========================================
function update_domain_files($subdomain) {
	global $system_info,$message;
	$subdomain_path = get_subdomain_folder($subdomain);
	if(!empty($subdomain_path)) {
		if(@is_writable($subdomain_path)) {
			$copy_was_successfull_script = true;
			$copy_was_successfull_htaccess = true;
			// Check if it's really needed to update this file
			if(!check_filesize(PLS_SCRIPT_PATH,check_for_trailing_slash($subdomain_path).PLS_SCRIPT_NAME)) {
				if(!pls_file_copy($subdomain_path,$type = "script")) {
					if($system_info['script_backup']) {
						// Check if there was an error while trying to create the backup
						// Added on version 0.1.2
						if(isset($system_info['script_backup_text_error']) && !empty($system_info['script_backup_text_error'])) {
							return array(true,$system_info['script_backup_text_error']);
						}
					}
					clear_script_backup_flags();
					$copy_was_successfull_script = false;
				} else {
					// Check if a backup was created
					// Added on version 0.1.2
					if($system_info['script_backup']) {
						if(isset($system_info['script_backup_text_success']) && !empty($system_info['script_backup_text_success'])) {
							$message[] = $system_info['script_backup_text_success'];
						}
					}
					clear_script_backup_flags();
				}
			}
			// Check if it's really needed to update this file
			$file_contents = @file_get_contents(check_for_trailing_slash($subdomain_path).PLS_HTACCESS_NAME);
			if(!check_filesize(PLS_HTACCESS_PATH,check_for_trailing_slash($subdomain_path).PLS_HTACCESS_NAME) || !preg_match(PLS_HTACCESS_REGEX,$file_contents)) {
				if(!pls_file_copy($subdomain_path,$type = "htaccess")) {
					if($system_info['script_backup']) {
						// Check if there was an error while trying to create the backup
						// Added on version 0.1.2
						if(isset($system_info['script_backup_text_error']) && !empty($system_info['script_backup_text_error'])) {
							return array(true,$system_info['script_backup_text_error']);
						}
					}
					clear_script_backup_flags();
					$copy_was_successfull_htaccess = false;
				} else {
					// Check if a backup was created
					// Added on version 0.1.2
					if($system_info['script_backup']) {
						if(isset($system_info['script_backup_text_success']) && !empty($system_info['script_backup_text_success'])) {
							$message[] = $system_info['script_backup_text_success'];
						}
					}
					clear_script_backup_flags();
				}
			}
			
			// Check if both files have been updated updated
			if($copy_was_successfull_script && $copy_was_successfull_htaccess) {
				return array(false,"Congratulations! I was able to update/install \"".$_POST['domain_name']."\" files<br/><br/>You should now revert the permissions of both "._highlight_string($subdomain_path)." folder and file "._highlight_string(PLS_SCRIPT_NAME)." inside it to "._highlight_string("755")."");
			} else {
				if(!$copy_was_successfull_script && $copy_was_successfull_htaccess)
					return array(true,"Please double check the permissions as I can't write on ".PLS_SCRIPT_NAME." on folder ".$subdomain_path);
				else if($copy_was_successfull_script && !$copy_was_successfull_htaccess)
					return array(true,"Please double check the permissions as I can't write on ".PLS_HTACCESS_NAME." on folder ".$subdomain_path);
				else if(!$copy_was_successfull_script && !$copy_was_successfull_htaccess)
					return array(true,"Please double check the permissions as I can't write on ".PLS_SCRIPT_NAME." and ".PLS_HTACCESS_NAME." on folder ".$subdomain_path);
			}
		} else {
			return array(true,"I've detected your subdomain folder, however it was impossible for me to write on it.<br/><br/>Please either change the subdomain folder "._highlight_string($subdomain_path)." permissions to "._highlight_string("777")." or manually upload to this folder these two files, "._highlight_string(PLS_SCRIPT_NAME)." and "._highlight_string(PLS_HTACCESS_NAME).".<br/><br/>You can find both original files on wordpress plugin folder "._highlight_string(check_for_trailing_slash(WP_PLUGIN_DIR).PLS_PLUGIN_FOLDER_NAME)."");
		}
	} else {
		return array(true,"I was unable to find \"".$_POST['domain_name']."\" subdomain folder. Please add it manually bellow",true);
	}
}

//========================================
// Trim a given path and retrive it according to a number of folders to trim
//========================================
// Added on version 0.1.1
function trim_path($path,$n_folders = 1) {
	$array = explode("/",$path);
	if(count($array) > 1 && count($array) > ($n_folders-1)) {
		$return_array = array();
		for($i=0;$i<$n_folders;$i++) {
			array_pop($array);
		}
		return implode("/",$array);
	} else {
		return $path;
	}
}

//========================================
// Returns true if they are igual and false if they difer
//========================================
// Added on version 0.1.3
function check_filesize($path1,$path2) {
	//echo filesize($path1)." ".filesize($path2);
	if(file_exists($path1) && file_exists($path2)) {
		if(@filesize($path1) != @filesize($path2)) {
			return false;
		} else { 
			return true;
		} 
	} else {
		return false;
	}
}

//========================================
// Attempt to find http root
//========================================
function get_root() {
	// Changes made here on version 0.1.1 & 0.1.6
	if(get_option("wp_pls_root") != "") {
		return get_option("wp_pls_root");
	} else {
		// Added on version 0.1.3
		// Changed on version 0.1.8 (Thank you Christian blog@tagdocs.de)
		$slash = DIRECTORY_SEPARATOR;
		
		// Get our current location
		$current_dir = getcwd();
		
		// Try to find our relative root
		$temp_path_array = explode($slash,$current_dir);
		
		$pos = array_search("httpdocs",$temp_path_array);
		$path = "";
		if($pos) {
			$path = implode($slash,current(array_chunk($temp_path_array,$pos+1)));
		}
		$pos = array_search("httpsdocs",$temp_path_array);
		if($pos) {
			$path = implode($slash,current(array_chunk($temp_path_array,$pos+1)));
		}
		$pos = array_search("public_html",$temp_path_array);
		if($pos) {
			$path = implode($slash,current(array_chunk($temp_path_array,$pos+1)));
		}
		$pos = array_search("html",$temp_path_array);
		if($pos) {
			$path = implode($slash,current(array_chunk($temp_path_array,$pos+2)));
		}
		$pos = array_search("httpd.www",$temp_path_array);
		if($pos) {
			$path = implode($slash,current(array_chunk($temp_path_array,$pos+1)));
		}
		// Changes made here on version 0.1.7.1 - 2 lines
		update_option("wp_pls_root",$path);
		return get_option("wp_pls_root",$path);
	}
	return false;
}

//========================================
// Extract file extension
//========================================
function getExtension($filename) {
	return strtolower ( array_pop ( explode ( '.', $filename ) ) );
}

//========================================
// This function will change the normal url of an element for the subdomain link
//========================================
function process_post_element($elements_array) {
	global $wpdb;
	
	// Get host without www.
	$our_host = str_replace("www.","",$_SERVER['HTTP_HOST']);
	
	if(is_array($elements_array) && count($elements_array) > 0) {
		
		$return_array = array();
		
		$sql = "SELECT `domain` AS `name`,`token` FROM ".PLS_DB_TABLE." WHERE `id` > 0 AND `active` = 1";
		$domains = $wpdb->get_results ($sql);
		if(count($domains) > 0) {
			foreach($elements_array as $url) {
				
				if(get_option('wp_pls_simple_method')) {
					$filename = $url;
				} else {
					// Added on version 0.1.8
					$extra_path = str_replace(check_for_trailing_slash(HTTP_SERVER_ROOT),'',ABSPATH);
					$filename = str_replace(array(check_for_trailing_slash("http://".$_SERVER['SERVER_NAME']),check_for_trailing_slash("https://".$_SERVER['SERVER_NAME'])),array("",""),$url);
					if(!preg_match("/^http.*/",$filename))
						$filename = $extra_path.$filename;
				}
				$domain_index = array_rand($domains);
				// Check if we chose to load or not external elements 
				if(get_option('wp_pls_load_external')) {
					$return_array[] = check_for_trailing_slash($domains[$domain_index]->name).$filename."&t=".$domains[$domain_index]->token;
				} else {
					if(strpos($url,$our_host) !== false) {
						$return_array[] = check_for_trailing_slash($domains[$domain_index]->name).$filename."&t=".$domains[$domain_index]->token;
					} else {
						$return_array[] = $url;
					}
				}
		    }
		} else {
			return $elements_array;
		}
	    
	    return $return_array;
	    
	} else { return $elements_array; }
	
}

//========================================
// This function will process wordpress posts to implement PLS
//========================================
function update_post_elements($content) {
	// Changed on version 0.1.8 - 4 lines
	if(get_option('wp_pls_process_gif_images'))
		preg_match_all("%src=\"(https?:\/\/[^\s]+(\.jpg|\.jpeg|\.jpe|\.png|\.gif))%i", $content, $matches, PREG_PATTERN_ORDER);
	else
		preg_match_all("%src=\"(https?:\/\/[^\s]+(\.jpg|\.jpeg|\.jpe|\.png))%i", $content, $matches, PREG_PATTERN_ORDER);
	if(count($matches > 0)) {
		$matches = array_unique($matches);
		$matches[0] = array_unique($matches[0]);
		array_walk($matches[0],"remove_from_array");
		$content = str_replace($matches[0],process_post_element($matches[0]),$content);
	}
	return $content;
}
function remove_from_array(&$item, $key)
{
	$item = str_replace("src=\"","",$item);
}

//========================================
// Attempt to find a given subdomain server folder name
//========================================
function get_subdomain_folder_name($subdomain) {
	return str_replace(array("http://","https://"),array("",""),current(explode(".",$subdomain)));
}

//========================================
// Attempt to find a given subdomain server folder path
//========================================
function get_subdomain_folder($subdomain) {
	global $wpdb;
	// Added on version 0.1.4
	$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `domain` LIKE '$subdomain' LIMIT 1";
	$domain = $wpdb->get_row ($sql);
	// Check if this domain already has its root path on the database
	if(isset($domain->root_path) && !empty($domain->root_path)) {
		if(@is_dir($domain->root_path)) {
			return $domain->root_path;
		}
	}
	// Add a trailing slash
	$root = check_for_trailing_slash(HTTP_SERVER_ROOT);
	// Get Subdomain name
	$subdomain_name = get_subdomain_folder_name($subdomain);
	return ((@is_dir($root.$subdomain_name)) ? $root.$subdomain_name : ((@is_dir($root."../subdomains/".$subdomain_name)) ? $root."../subdomains/".$subdomain_name : ((isset($_POST['root_path'])) ? $_POST['root_path'] : '' ) ) );
}

//========================================
// Apply PLS on entire website
//========================================
function wp_pls_callback($buffer) {
	// Modify buffer here, and then return the updated code
	return update_post_elements($buffer);
}

function buffer_start() { ob_start("wp_pls_callback"); }
 
function buffer_end() { ob_get_contents(); }
 
add_action('get_header', 'buffer_start');
add_action('get_footer', 'buffer_end');
//add_action('the_content','update_post_elements');

//========================================
// Install Plugin
//========================================
function update_script($this,$for_this,$file) {
	if(@is_writable($file)) {
		if(file_put_contents($file,str_replace($this,$for_this,file_get_contents($file)))){
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function wp_pls_install() {
	global $wpdb;
	
	$db_installed_version = get_option('wp_pls_db_version');
	
	// Update database in case this is an update
	if($db_installed_version != PLS_DB_VERISON) {
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		
		$sql = "CREATE TABLE ".PLS_DB_TABLE." (
			`id` int(4) NOT NULL AUTO_INCREMENT,
			`domain` varchar(128) NOT NULL,
			`token` varchar(32) NOT NULL,
			`active` tinyint(1) NOT NULL DEFAULT 1,
			`root_path` varchar (128) NOT NULL,
			UNIQUE KEY `id` (id)
		)";
		
		dbDelta($sql);
		
		update_option("wp_pls_db_version", PLS_DB_VERISON);
	}
	
	// Update version in case this is an update
	$installed_version = get_option('wp_pls_version');
	if(PLS_VERISON != $installed_version)
		update_option("wp_pls_version", PLS_VERISON);
	
	// Added on version 0.1.1
	// Check if root path is still valid
	if(get_option("wp_pls_found_root")) {
		if(!@is_dir(HTTP_SERVER_ROOT)) {
			delete_option("wp_pls_root");
			update_option("wp_pls_found_root",false);
		}
	} else {
		// Added on version 0.1.6
		if(@is_dir(HTTP_SERVER_ROOT)) {
			update_option("wp_pls_found_root",true);
		}
	}
		
	// Attempt to add root folder information to our subdomain processing script
	$file_updated = false;
	if(@is_writable(PLS_SCRIPT_PATH)) {
		// Changes made here on version 0.1.1
		if(HTTP_SERVER_ROOT) {
			if(update_script("define('ROOT_PATH','../');","define('ROOT_PATH','".HTTP_SERVER_ROOT."/');",PLS_SCRIPT_PATH)) {
				$file_updated = true;
			}
			update_option("wp_pls_found_root", true);
		} else {
			update_option("wp_pls_found_root", false);
		}
	} elseif(chmod(PLS_SCRIPT_PATH,0777)) {
		if(@is_writable(PLS_SCRIPT_PATH)) {
			// Changes made here on version 0.1.1
			if(HTTP_SERVER_ROOT) {
				if(update_script("define('ROOT_PATH','../');","define('ROOT_PATH','".HTTP_SERVER_ROOT."/');",PLS_SCRIPT_PATH)) {
					$file_updated = true;
				}
				update_option("wp_pls_found_root", true);
			} else {
				update_option("wp_pls_found_root", false);
			}
		}
	}
	
	if($file_updated)
		update_option("wp_pls_has_updated_files", 1);
	else
		update_option("wp_pls_has_updated_files", 0);
	if (extension_loaded('gd') && function_exists('gd_info')) {
	    update_option("wp_pls_has_gd", 1);
	} else {
		update_option("wp_pls_has_gd", 0);
	}
	
	// Check if we have our default options set
	if(!get_option('wp_pls_load_external')) {
		update_option('wp_pls_load_external',1);
	}
	if(!get_option('wp_pls_simple_method')) {
		update_option('wp_pls_simple_method',0);
	}
}

function wp_pls_uninstall() {
	global $wpdb;
	// Get Active Subdomains
	
	// Added on version 0.1.4
	// Revert .htaccess to normal (if it's wordpress .htaccess)
	$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `id` > 0 ORDER BY `id` ASC";
	$domains = $wpdb->get_results ($sql);
	foreach($domains as $domain) {
		if(isset($domain->root_path) && !empty($domain->root_path)) {
			$filepath = check_for_trailing_slash($domain->root_path).PLS_HTACCESS_NAME;
			if(file_exists($filepath)) {
				$file_contents = @file_get_contents($filepath);
				if(preg_match(PLS_HTACCESS_REGEX,$file_contents)) {
					// Remove PLS code
					$file_resource = fopen($filepath,"w");
					$htaccess_new_content = str_replace(PLS_HTACCESS_CODE,"",$file_contents);
					fwrite($file_resource, $htaccess_new_content);
					fclose($file_resource);
				}
			}
		}
	}
}

add_action('activate_'.plugin_basename(__FILE__), 'wp_pls_install');
add_action('deactivate_'.plugin_basename(__FILE__), 'wp_pls_uninstall');

//========================================
// HTML code for Options
//========================================
// Added on version 0.1.4
function html_options() {
?>
	<div class="wrap" style="margin-top:50px;">
    	<h2>Options:</h2>
    	<div style="margin:10px 0 40px;">
			<form action="" method="post">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Load external elements</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Load external elements</span></legend>
										<label for="load_external_elements"><input type="checkbox" <?php echo ((get_option('wp_pls_load_external')) ? 'checked="checked"' : '' )?> value="1" id="load_external_elements" name="load_external_elements"></label><br>
										<span style="display: block;" class="description">Every image element found on your code will be applied to PLS.</span>
									</fieldset>
								</td>
							</th>
						</tr>
						<tr valign="top">
							<th scope="row">Activate Simple Method</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Load external elements</span></legend>
										<label for="simple_method"><input type="checkbox" <?php echo ((get_option('wp_pls_simple_method')) ? 'checked="checked"' : '' )?> value="1" id="simple_method" name="simple_method"></label><br>
										<span style="display: block;" class="description">Even tho this method is simpler and should only be used if you're having problems configuring root paths, it's not as effective.</span>
									</fieldset>
								</td>
							</th>
						</tr>
						<?php //Added on version 0.1.7.1 - 11 lines ?>
						<tr valign="top">
							<th scope="row">Root Path</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Root Path</span></legend>
										<label for="change_root_path"><input type="text" value="<?php echo get_option("wp_pls_root")?>" id="change_root_path" name="change_root_path" style="width: 700px;"></label><br>
										<span style="display: block;" class="description">WARNING, changing root path might break the plugin.</span>
									</fieldset>
								</td>
							</th>
						</tr>
						<?php //Added on version 0.1.8 - 11 lines ?>
						<tr valign="top">
							<th scope="row">Process GIF images</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span>Process GIF images</span></legend>
										<label for="process_gif_images"><input type="checkbox" <?php echo ((get_option('wp_pls_process_gif_images')) ? 'checked="checked"' : '' )?> value="1" id="process_gif_images" name="process_gif_images"></label><br>
										<span style="display: block;" class="description">GIF images sometimes contain animations. These animations are not supported and the rendered image will not animate.<br/>By checking this GIF images will be ignored.</span>
									</fieldset>
								</td>
							</th>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save_options" value="Save Options">
				</p>
			</form>
		</div>
    </div>
<?php
}

//========================================
// HTML code for Compatibility
//========================================
// Added on version 0.1.4
function html_compatibility() {
?>
	<div class="wrap" style="margin-top:50px;">
    	<h2>Compatibility:</h2>
    	<table cellspacing="0" class="widefat">
			<tbody>
				<?php foreach(check_for_plugins() as $plugin_name => $plugin_is_active):?>
					<?php if($plugin_is_active) :?>
						<?php if($plugin_name == "WP Super Cache" || $plugin_name == "WP Cache") :?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;"><?php echo $plugin_name;?></td>
					<td class="desc">Please remember to <b>clear the cache</b> when you make changes to this plugin configuration.</td>
				</tr>
						<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;"><?php echo $plugin_name;?></td>
					<td class="desc">There are known compatibility issue with this plugin, please be aware. I'm working on resolving this problem.</td>
				</tr>
						<?php endif;?>
					<?php endif;?>
				<?php endforeach;?>
				<?php if(get_option('wp_pls_has_gd')):?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: green;">GD Library</td>
					<td class="desc">It seams to be installed and working well.</td>
				</tr>
				<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;">GD Library</td>
					<td class="desc">You don't seem to have it <b>working</b> or <b>installed</b>. This plugin cannot function without it.</td>
				</tr>
				<?php endif;?>
				<?php if(version_compare(PHP_VERSION, PLS_REQUIRED_PHP_VERSION) >= 0):?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: green;">PHP Version</td>
					<td class="desc">Great! You're using PHP version <?php echo PHP_VERSION?>.</td>
				</tr>
				<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;">PHP Version</td>
					<td class="desc">Damn! I require you to have at least version <?php echo PLS_REQUIRED_PHP_VERSION?> of PHP. You're using version <?php echo PHP_VERSION?>.</td>
				</tr>
				<?php endif;?>
				<?php if(PLS_HAS_MOD_REWRITE):?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: green;">Apache mod_rewrite</td>
					<td class="desc">Great! Your apache instalation has mod_rewrite module loaded.</td>
				</tr>
				<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;">Apache mod_rewrite</td>
					<td class="desc">Damn! I require your server to have mod_rewrite module loaded on your apache instalation.</td>
				</tr>
				<?php endif;?>
				<?php if(PLS_HAS_MOD_HEADERS):?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: green;">Apache mod_headers</td>
					<td class="desc">Great! Your apache instalation has mod_headers module loaded.</td>
				</tr>
				<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;">Apache mod_headers</td>
					<td class="desc">Damn! I require your server to have mod_headers module loaded on your apache instalation.</td>
				</tr>
				<?php endif;?>
				<?php if(subdomain_health(PLS_TEST_IMAGE_URL) !== false):?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: green;">Health checks</td>
					<td class="desc">Impressive! Your server has ability to check domains health. This however doesn't mean that your system will be health aware.</td>
				</tr>
				<?php else:?>
				<tr class="alternate">
					<td class="import-system row-title" style="color: red;">Health checks</td>
					<td class="desc">This might not be a problem, however a few checks that the plugin makes for your security purposes will not be possible.</td>
				</tr>
				<?php endif;?>
			</tbody>
		</table>
    </div>
<?php
}

//========================================
// HTML code for Status
//========================================
// Added on version 0.1.4
function html_status() {
?>
	<div class="wrap" style="margin-top:50px;">
    	<h2>Status:</h2>
    	<table cellspacing="0" class="widefat">
			<tbody>
				<tr class="alternate">
				<?php if(PLS_IS_WIN): ?>
					<td class="import-system row-title" style="color: orange;">Windows</td>
					<td class="desc">You are running a Windows system. This is not fully supported yet. I'm working on it.</td>
				<?php elseif(PLS_IS_NGINX): ?>
					<td class="import-system row-title" style="color: orange;">Nginx</td>
					<td class="desc">You are running a Nginx system. This is not fully supported yet. I'm working on it.</td>
				<?php else:?>
					<td class="import-system row-title" style="color: green;">Apache</td>
					<td class="desc">You are running an Apache system.</td>
				<?php endif;?>
				</tr>
				<?php if(HTTP_SERVER_ROOT):?>
				<tr class="alternate">
				<?php if(PLS_IS_WIN): ?>
					<td class="import-system row-title" style="color: green;">Windows Root Path</td>
					<td class="desc">Your windows root path seems to be healthy. <i>"<?php echo HTTP_SERVER_ROOT;?>"</i></td>
				<?php else:?>
					<td class="import-system row-title" style="color: green;">Apache Root Path</td>
					<td class="desc">Your apache root path seems to be healthy. <i>"<?php echo HTTP_SERVER_ROOT;?>"</i></td>
				<?php endif;?>
				</tr>
				<?php else:?>
				<tr class="alternate">
				<?php if(PLS_IS_WIN): ?>
					<td class="import-system row-title" style="color:red;">Windows Root Path</td>
					<td class="desc">There seems to be a problem with your windows root path!</td>
				<?php else:?>
					<td class="import-system row-title" style="color:red;">Apache Root Path</td>
					<td class="desc">There seems to be a problem with your apache root path!</td>
				<?php endif;?>
				</tr>
				<?php endif;?>
			</tbody>
		</table>
    </div>
<?php
}

//========================================
// Add an entry to the admin settings menu
//========================================
function wp_pls_add_options_page() {
	global $wpdb,$message;
	$error = false;
	$prompt_for_subdomain_path = false;
	
	// Added on version 0.1.3
	if(PLS_HAS_MOD_HEADERS && PLS_HAS_MOD_REWRITE) {
		// Save Options
		// Added on version 0.1.4
		if(isset($_POST['save_options'])) {
			// Save Load external elements
			if(isset($_POST['load_external_elements'])) {
				update_option('wp_pls_load_external',1);
			} else {
				update_option('wp_pls_load_external',0);	
			}
			// Save simple method
			if(isset($_POST['simple_method'])) {
				update_option('wp_pls_simple_method',1);
			} else {
				update_option('wp_pls_simple_method',0);	
			}
			// Added on version 0.1.8
			// Save GIF support
			if(isset($_POST['process_gif_images'])) {
				update_option('wp_pls_process_gif_images',1);
			} else {
				update_option('wp_pls_process_gif_images',0);	
			}
			// Save root path
			if(isset($_POST['change_root_path'])) {
				update_option('wp_pls_root',$_POST["change_root_path"]);
			}
			// Changed on version 0.1.8 - 1 line
			$message[] = "I have saved your preferences.";
		}
		
		// Changes made here on version 0.1.1
		if(get_option("wp_pls_found_root")) {
			// In case we clicked the update script button we'll try to update the file again an inform on success or failure
			if(isset($_POST['update_script'])) {
				if(update_script("define('ROOT_PATH','../');","define('ROOT_PATH','".check_for_trailing_slash(HTTP_SERVER_ROOT)."');",PLS_SCRIPT_PATH)) {
					update_option("wp_pls_has_updated_files", 1);
					$message[] = "Congratulations! I was able to update the file";
				} else {
					$error = true;
					$message[] = "Please double check the permissions as I still can't write on "._highlight_string(PLS_SCRIPT_NAME)."";
				}
			}
			
			preg_match('$(define\(\'ROOT_PATH\'\,.+\)\;)$i',file_get_contents(PLS_SCRIPT_PATH),$match);
			if($match[0] != "define('ROOT_PATH','".check_for_trailing_slash(HTTP_SERVER_ROOT)."');") {
				if(update_script($match[0],"define('ROOT_PATH','".check_for_trailing_slash(HTTP_SERVER_ROOT)."');",PLS_SCRIPT_PATH)) {
					$message[] = "I've detected that the original script file didn't have your correct root path. This has been fixed :)";
				} else {
					$error = true;
					$message[] = "I've detected that the original script file didn't have your correct root path. This could not be fixed because I didn't have enough permissions to edit it.<br/><br/>Please change the permissions to "._highlight_string("777")." on file "._highlight_string(PLS_SCRIPT_NAME)." that can be found on your plugin folder "._highlight_string(check_for_trailing_slash(WP_PLUGIN_DIR).PLS_PLUGIN_FOLDER_NAME)."";
				}
			}
			
			// Adding all actions and fancy stuff
			if(get_option('wp_pls_has_updated_files')) {
				if(isset($_POST['domain_id']) && is_numeric($_POST['domain_id'])) {
					// Deactivate Sub-Domain
					if(isset($_POST['deactivate_domain'])) {
						$sql = "UPDATE ".PLS_DB_TABLE." SET `active` = 0 WHERE `id` = ".$_POST['domain_id']."";
						$wpdb->query($sql);
						$message[] = "Sub-Domain has been deactivated";
					}
					// Activate Sub-Domain
					if(isset($_POST['activate_domain'])) {
						$subdomain_health = subdomain_health($_POST['domain_name']);
						// Changes made here on version 0.1.7.1 - 1 line 
						if($subdomain_health == "HTTP/1.1 200 OK" || $subdomain_health == "HTTP/1.1 301 Moved Permanently" || $subdomain_health == "HTTP/1.1 302 Moved Temporarily" || !$subdomain_health) {
							$sql = "UPDATE ".PLS_DB_TABLE." SET `active` = 1 WHERE `id` = ".$_POST['domain_id']."";
							$wpdb->query($sql);
							if(!$subdomain_health)
								$message[] = "I'm sorry but I was not able to determine the sub-domain health. I'm not sure if the system will perform 100%";
							else
								$message[] = "Sub-Domain has been activated";
						} else {
							$error = true;
							$message[] = "That sub-domain is returning an HTTP error. I cannot add it knowing it will not work.";
						}
					}
					// Remove Sub-Domain
					if(isset($_POST['remove_domain'])) {
						$sql = "DELETE FROM ".PLS_DB_TABLE." WHERE `id` = ".$_POST['domain_id']."";
						$wpdb->query($sql);
						$message[] = "Sub-Domain has been removed";
					}
					// Update Sub-Domain Script
					if(isset($_POST['update_domain_script'])) {
						//update_domain_files($_POST['domain_name']);
						$result = update_domain_files($_POST['domain_name']);
						if($result[0]) {
							$error = true;
							$message[] = $result[1];
							if(isset($result[2])) {
								$prompt_for_subdomain_path = true;
							}
						} else {
							$message[] = $result[1];
						}
					}
				}
				
				// Add Sub-Domain
				if(isset($_POST['add_domain'])) {
					$subdomain_path = "";
					if(isset($_POST['root_path'])) {
						$subdomain_path = $_POST['root_path'];
					} else {
						$subdomain_path = get_subdomain_folder($_POST['domain_name']);
					}
					$require_manual = false;
					if((isset($_POST['domain_name']) && empty($_POST['domain_name'])) || !isset($_POST['domain_name'])) {
						// Check if we have an empty subdomain has been submitted
						$error = true;
						$message[] = "Please provide a valid subdomain";
					} else {
						if(HTTP_SERVER_ROOT) {
							// Changed on version 0.1.6.1 - Bug Fix - 10 lines
							if(!isset($_POST['root_path'])) {
								if((!file_exists(check_for_trailing_slash($subdomain_path).PLS_SCRIPT_NAME) || !file_exists(check_for_trailing_slash($subdomain_path).PLS_HTACCESS_NAME)) && !get_option('wp_pls_simple_method')) {
									$result = update_domain_files($_POST['domain_name']);
									if($result[0]) {
										$error = true;
										$message[] = $result[1];
										if(isset($result[2])) {
											$prompt_for_subdomain_path = true;
										}
									} else {
										$message[] = $result[1];
									}
								} else {
									$message[] = "Sub-Domain Added.<br/><br/>You are using WP-PLS with Simple Method. Using this method I might not able to check for the existence of both "._highlight_string(PLS_HTACCESS_NAME)." and "._highlight_string(PLS_SCRIPT_NAME).".<br/>Please check this manually.";
								}
							} else {
								if(!file_exists(check_for_trailing_slash($subdomain_path).PLS_SCRIPT_NAME) || !file_exists(check_for_trailing_slash($subdomain_path).PLS_HTACCESS_NAME)) {
									$result = update_domain_files($_POST['domain_name']);
									if($result[0]) {
										$require_manual = true;
									}
								}
							}
						} else {
							$error = true;
							$message[] = "I was unable to find your apache server root.";
						}
						/*if(isset($_POST['root_path'])) {
							if(!@is_dir($_POST['root_path'])) {
								$error = true;
								$message[] = "The path you provided could not be found. Please double check it.";
								$prompt_for_subdomain_path = true;
							}
						}*/
						/*if(!url_exists($_POST['domain_name'])) {
							$message = array("The subdomain you just added could not be verified. Click <a href=\"".$_POST['domain_name']."\" target=\"_blank\">here</a> to check it.");
							
							if(isset($_POST['root_path'])) {
								$prompt_for_subdomain_path = true;
							}
						}*/
						if(!$error) {
							if(isset($_POST['root_path'])) {
								$sql = "INSERT INTO ".PLS_DB_TABLE." (`domain`,`token`,`root_path`) VALUES ('".$_POST['domain_name']."','".md5($_POST['domain_name'])."','".$_POST['root_path']."')";
							} else {
								$sql = "INSERT INTO ".PLS_DB_TABLE." (`domain`,`token`) VALUES ('".$_POST['domain_name']."','".md5($_POST['domain_name'])."')";
							}
							$wpdb->query($sql);
							// Changed on version 0.1.5 - 12 lines
							$subdomain_health = subdomain_health($_POST['domain_name']);
							// Changed on version 0.1.6 - 1 line
							if($subdomain_health && url_exists($_POST['domain_name']) && @is_dir($subdomain_path)) {
								// Changes made here on version 0.1.7.1 - 1 line 
								if($subdomain_health != "HTTP/1.1 200 OK" && $subdomain_health != "HTTP/1.1 301 Moved Permanently" && $subdomain_health != "HTTP/1.1 302 Moved Temporarily") {
									$sql = "UPDATE ".PLS_DB_TABLE." SET `active` = 0 WHERE `id` > 0 ORDER BY `id` DESC LIMIT 1";
									$wpdb->query($sql);
								}
								$message[] = "Sub-Domain has been added";
								update_option('wp_pls_cant_update',0);
							} else {
								// Changed on version 0.1.6 - 8 lines
								$sql = "UPDATE ".PLS_DB_TABLE." SET `active` = 0 WHERE `id` > 0 ORDER BY `id` DESC LIMIT 1";
								$wpdb->query($sql);
								if($require_manual) {
									$message[] = "Sub-Domain has been added but I was not able to determine its health and/or existance. Please confirm it's working properly (<a href=\"".$_POST['domain_name']."\" target=\"_blank\">check here</a>) before activating it.<br/><br/>I was also not able to add both required files for the system to work. You'll have to do it manually.<br/>Don't worry, it's easy!<br/><br/>Go to the plugin folder and copy "._highlight_string(PLS_SCRIPT_NAME)." and "._highlight_string(PLS_HTACCESS_NAME)." to your subdomain root folder "._highlight_string($_POST['root_path']).".<br/><br/>I have also activated simple method for integrity properties.";
									update_option('wp_pls_simple_method',1);
									update_option('wp_pls_cant_update',1);
								} else {
									$message[] = "Sub-Domain has been added but I was not able to determine its health. Please confirm it's working properly (<a href=\"".$_POST['domain_name']."\" target=\"_blank\">check here</a>) before activating it.";
								}
							}
						}
					}
				}
				
				// Get Active Subdomains
				$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `id` > 0 AND `active` = 1 ORDER BY `id` ASC";
				$active_domains = $wpdb->get_results ($sql);
				
				// Added on version 0.1.3
				// Check if any domain is returning an error code
				$subdomains_updated = false;
				foreach($active_domains as $active_domain) {
					// Changed on version 0.1.5 - 2 lines
					$subdomain_health = subdomain_health($active_domain->name);
					// Changes made here on version 0.1.7.1 - 1 line 
					if($subdomain_health && $subdomain_health != "HTTP/1.1 200 OK" && $subdomain_health != "HTTP/1.1 301 Moved Permanently" && $subdomain_health != "HTTP/1.1 302 Moved Temporarily") {
						$sql_update = "UPDATE ".PLS_DB_TABLE." SET `active` = 0 WHERE `id` = ".$active_domain->id." LIMIT 1";
						$wpdb->query($sql_update);
						$subdomains_updated = true;
						$message[] = "Sub-Domain "._highlight_string($active_domain->name)." has been deactivated because I'm getting an HTTP status error.";
					}
				}
				// Refetch sub-domains from the database if there was any update from above
				if($subdomains_updated)
					$active_domains = $wpdb->get_results ($sql);
				
				// Get Inactive Subdomains
				$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `id` > 0 AND `active` = 0 ORDER BY `id` ASC";
				$inactive_domains = $wpdb->get_results ($sql);
			}
		} else {
			// Added on version 0.1.1
			if(isset($_POST['update_root'])) {
				if(count(explode("/",$_POST['root_path'])) >= 2) {
					update_option("wp_pls_root",$_POST['root_path']);
					update_option("wp_pls_found_root",true);
					$message[] = "Root path has been successfully saved. Thank you.";
				
					// Get Active Subdomains
					$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `id` > 0 AND `active` = 1 ORDER BY `id` ASC";
					$active_domains = $wpdb->get_results ($sql);
						
					// Get Inactive Subdomains
					$sql = "SELECT *,`domain` AS `name` FROM ".PLS_DB_TABLE." WHERE `id` > 0 AND `active` = 0 ORDER BY `id` ASC";
					$inactive_domains = $wpdb->get_results ($sql);
				} else {
					$error = true;
					$message = array("The path you provided doesn't seem to be correct. Please double check it.");
				}
			}
		}
	} else {
		$error = true;
		$message = array("There are problems with plugin compatibility and your WordPress Installation / Server configuration. Before you can use this plugin you must resolve this issues.<br/><br/>If you require additional support please visit <a href=\"http://www.joseairosa.com/2010/05/17/wordpress-plugin-parallel-loading-system/\" target=\"_blank\">my website</a> or <a href=\"mailto:me@joseairosa.com\" target=\"_blank\">email me</a>. I will try to reply as fast as possible.");
	}
?>
	<div class="wrap">
    	<h2>Parallel Loading System - <?php echo PLS_VERISON;?></h2>
    	<p>You can use this plugin to enhance the loading time of your website by parallelizing the connections to the server, since standard HTTP v1.1 only allows 2 connections, at the same time, from the same domain.</p>
    	<p>This plugin will virtualize connections, through defined subdomains. You can have as many subdomains as you like, but I do recomend using a maximum of 5.</p>
    	<p>Plugin health bellow is based on sub-domain HTTP status code when it tries to check for the following image: http://my-example-subdomain/wp-content/plugins/parallel-loading-system/check_headers.jpg.</p>
    	<br/>
    	<p><b>NOTE:</b> It's recomended that you add an index.php file to every subdomain root folder that you create. This way it will not be possible to read folder contents and possibily compromise the security of your file system.</p>
    	<p>
	    	What you should do (if not there already) is go to your subdomain root folder and create an index.php file. Make sure there is no <b>index.html</b> there, and if present remove it.
	    	<br/>
	    	Then edit its contents and add the following:
	    	<br/>
	    	<pre>
	    		<?php echo htmlentities('<?php header("Location:'.get_bloginfo('url').'"); exit(); ?>');?>
	    	</pre>
	    	<br/>
	    	Save the file and you're set! :)
	    	<br/>
	    	<small>Thank you Vera (<a href="mailto:blackpanther2905@hotmail.com">blackpanther2905@hotmail.com</a>) for this.</small>
    	</p>
    	<br/>
    	<br/>
		<?php if(!empty($message) && !$error) : ?>
    	<?php foreach($message as $message_single) :?>
    	<div class="updated fade" id="message">
    		<p><strong><?php echo $message_single;?></strong></p>
    	</div>
    	<?php endforeach;?>
    	<?php elseif(!empty($message) && $error) : ?>
    	<?php foreach($message as $message_single) :?>
    	<div class="error" id="message">
    		<p><strong><?php echo $message_single;?></strong></p>
    	</div>
    	<?php endforeach;?>
    	<?php endif; ?>
    </div>
	<?php if(get_option('wp_pls_has_gd') && get_option("wp_pls_found_root") && PLS_HAS_MOD_HEADERS && PLS_HAS_MOD_REWRITE) :?>
	    <?php if(get_option('wp_pls_has_updated_files')) :?>
	    <!-- START PLS FORMS -->
	    <div class="wrap">
	    	<h2>Active Sub-Domains:</h2>
	    	<?php if(count($active_domains) == 0): ?>
	    	<div style="margin:10px 0">
				<h4 style="display: inline;">We could not find any active subdomains. Please add them using the form bellow.</h4>
			</div>
	    	<?php else :?>
			<?php foreach($active_domains as $active_domain) :?>
			<div style="border-left: 5px solid #CCC; padding-left: 10px; margin: 10px 0pt 40px;">
				<form action="" method="post">
					<input type="hidden" name="domain_id" value="<?php echo $active_domain->id?>"/>
					<input type="hidden" name="domain_name" value="<?php echo $active_domain->name?>"/>
					<h4 style="display: inline;"><?php echo $active_domain->name?></h4>
					
					<?php if(!get_option("wp_pls_cant_update")) :?>
						<?php
						// Added on version 0.1.3 
						$htaccess_file_contents = @file_get_contents(check_for_trailing_slash(get_subdomain_folder($active_domain->name)).PLS_HTACCESS_NAME);
						?>
						<?php if((!check_filesize(PLS_SCRIPT_PATH,check_for_trailing_slash(get_subdomain_folder($active_domain->name)).PLS_SCRIPT_NAME) || !check_filesize(PLS_HTACCESS_PATH,check_for_trailing_slash(get_subdomain_folder($active_domain->name)).PLS_HTACCESS_NAME)) && !get_option('wp_pls_simple_method')) : if(!preg_match(PLS_HTACCESS_REGEX,$htaccess_file_contents)) :?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Update Script" name="update_domain_script"><span class="description" style="margin-left:20px">I have detected that this Sub-Domain files are out of sync. Click the button to sync.</span>
						</p>
						
						<?php elseif(!check_filesize(PLS_SCRIPT_PATH,check_for_trailing_slash(get_subdomain_folder($active_domain->name)).PLS_SCRIPT_NAME) && !get_option('wp_pls_simple_method')):?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Update Script" name="update_domain_script"><span class="description" style="margin-left:20px">I have detected that this Sub-Domain files are out of sync. Click the button to sync.</span>
						</p>
						
						<?php else : ?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Deactivate" name="deactivate_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						
						<?php endif; else : ?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Deactivate" name="deactivate_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						
						<?php endif;?>
					<?php else :?>
					<p class="submit" style="display:inline;">
						<input type="submit" value="Deactivate" name="deactivate_domain">
					</p>
					<p class="submit" style="display:inline;">
						<input type="submit" value="Remove" name="remove_domain">
					</p>
					<?php endif;?>
					<p style="font:italic 14px Georgia,'Times New Roman','Bitstream Charter',Times,serif">
						<?php $subdomain_headers_response = subdomain_health($active_domain->name);?>
						<?php if($subdomain_headers_response):
						// Changes made here on version 0.1.7.1 - 1 line ?>
						Sub-Domain Health: <?php echo (($subdomain_headers_response == "HTTP/1.1 200 OK") ? '<span style="color:green">'.$subdomain_headers_response.'</span>' : (($subdomain_headers_response == 'HTTP/1.1 301 Moved Permanently' || $subdomain_headers_response == 'HTTP/1.1 302 Moved Temporarly') ? '<span style="color:orange">'.$subdomain_headers_response.'</span>' : '<span style="color:red">'.$subdomain_headers_response.'</span>' ) )?>
						<?php else:?>
						Sub-Domain Health: <span style="color:orange"><img src="<?php echo check_for_trailing_slash($active_domain->name).PLS_TEST_IMAGE_URL?>" alt="" /> &larr; If you can see this image your sub-domain is working.</span>
						<?php endif;?>
					</p>
				</form>
			</div>
			<?php endforeach;?>
			<?php endif;?>
	    </div>
	    <div class="wrap">
	    	<h2>Inactive Sub-Domains:</h2>
	    	<?php if(count($inactive_domains) == 0): ?>
	    	<div style="margin:10px 0">
				<h4 style="display: inline;">We could not find any inactive subdomains. Please add them using the form bellow.</h4>
			</div>
	    	<?php else :?>
			<?php foreach($inactive_domains as $inactive_domain) :?>
			<div style="border-left: 5px solid #CCC; padding-left: 10px; margin: 10px 0pt 40px;">
				<form action="" method="post">
					<input type="hidden" name="domain_id" value="<?php echo $inactive_domain->id?>"/>
					<input type="hidden" name="domain_name" value="<?php echo $inactive_domain->name?>"/>
					<h4 style="display: inline;"><?php echo $inactive_domain->name?></h4>
					
					<?php if(!get_option("wp_pls_cant_update")) :?>
						<?php
						// Added on version 0.1.3 
						$htaccess_file_contents = @file_get_contents(check_for_trailing_slash(get_subdomain_folder($inactive_domain->name)).PLS_HTACCESS_NAME);
						?>
						<?php if((!check_filesize(PLS_SCRIPT_PATH,check_for_trailing_slash(get_subdomain_folder($inactive_domain->name)).PLS_SCRIPT_NAME) || !check_filesize(PLS_HTACCESS_PATH,check_for_trailing_slash(get_subdomain_folder($inactive_domain->name)).PLS_HTACCESS_NAME)) && !get_option('wp_pls_simple_method')) : if(!preg_match(PLS_HTACCESS_REGEX,$htaccess_file_contents)) :?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Update Script" name="update_domain_script"><span class="description" style="margin-left:20px">I have detected that this Sub-Domain files are out of sync. Click the button to sync.</span>
						</p>
						
						<?php elseif(!check_filesize(PLS_SCRIPT_PATH,check_for_trailing_slash(get_subdomain_folder($inactive_domain->name)).PLS_SCRIPT_NAME) && !get_option('wp_pls_simple_method')):?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Update Script" name="update_domain_script"><span class="description" style="margin-left:20px">I have detected that this Sub-Domain files are out of sync. Click the button to sync.</span>
						</p>
						
						<?php else : ?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Activate" name="activate_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						
						<?php endif; else : ?>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Activate" name="activate_domain">
						</p>
						<p class="submit" style="display:inline;">
							<input type="submit" value="Remove" name="remove_domain">
						</p>
						
						<?php endif;?>
					<?php else :?>
					<p class="submit" style="display:inline;">
						<input type="submit" value="Activate" name="activate_domain">
					</p>
					<p class="submit" style="display:inline;">
						<input type="submit" value="Remove" name="remove_domain">
					</p>
					<?php endif;?>
					<p style="font:italic 14px Georgia,'Times New Roman','Bitstream Charter',Times,serif">
						<?php $subdomain_headers_response = subdomain_health($inactive_domain->name);?>
						<?php if($subdomain_headers_response):
						// Changes made here on version 0.1.7.1 - 1 line ?>
						Sub-Domain Health: <?php echo (($subdomain_headers_response == "HTTP/1.1 200 OK") ? '<span style="color:green">'.$subdomain_headers_response.'</span>' : (($subdomain_headers_response == 'HTTP/1.1 301 Moved Permanently' || $subdomain_headers_response == 'HTTP/1.1 302 Moved Temporarly') ? '<span style="color:orange">'.$subdomain_headers_response.'</span>' : '<span style="color:red">'.$subdomain_headers_response.'</span>' ) )?>
						<?php else:?>
						Sub-Domain Health: <span style="color:orange"><img src="<?php echo check_for_trailing_slash($inactive_domain->name).PLS_TEST_IMAGE_URL?>" alt="" /> &larr; If you can see this image your sub-domain is working.</span>
						<?php endif;?>
					</p>
				</form>
			</div>
			<?php endforeach;?>
			<?php endif;?>
	    </div>
	    <div class="wrap">
	    	<h2>Add Sub-Domain:</h2>
	    	<form action="" method="post">
	    		<table class="form-table">
	    			<tr>
				        <th><label for="domain"><?php _e('Sub-Domain'); ?></label></th>
				        <td>
				        	<input type="text" class="regular-text code" name="domain_name" id="domain" value="<?php echo ((isset($_POST['add_domain'])) ? @$_POST['domain_name'] : 'http://')?>" />
				        	<span class="description" style="display:block;">I would go for something like http://img1.example.com, http://img2.example.com...</span>
				        </td>
				    </tr>
				    <?php if($prompt_for_subdomain_path):?>
				    <tr>
				        <th><label for="root_path"><?php _e('Sub-Domain root path'); ?></label></th>
				        <td>
				        	<input type="text" class="regular-text code" name="root_path" id="root_path" style="width:50em;" value="<?php echo get_root();?>" />
				        	<span class="description" style="display:block;">The subdomain folder is on your server. It has the same name as the domain you attempted to add<?php echo ' "'.get_subdomain_folder_name($_POST['domain_name']).'"'?>.<br/>I already added your http root folder on the field above, it should be easier this way.<br/>If you have any doubts contact your system administrator.</span>
				        </td>
				    </tr>
				    <?php endif;?>
	    		</table>
				<p class="submit">
					<input type="submit" value="Add Sub-Domain" name="add_domain">
				</p>
	    	</form>
	    </div>
	    <!-- END PLS FORMS -->
	    <?php else :?>
	    <!-- START PLS UPDATE SCRIPT -->
	    <div class="wrap">
	    	<div class="error" id="message_update_script">
	    		<p><strong>While installing this script I was unable to edit <?php echo PLS_SCRIPT_NAME;?> with your server definitions. Please change <?php echo PLS_SCRIPT_NAME;?> permissions to 777 and click the button bellow.</strong></p>
	    	</div>
	    	<form action="" method="post">
				<p class="submit">
					<input type="submit" value="Update Script" name="update_script">
				</p>
	    	</form>
	    </div>
	    <!-- END PLS UPDATE SCRIPT -->
	    <?php endif;?>
	<?php endif;?> 
	<?php if(!get_option("wp_pls_found_root")) :?>
	<?php // Added on version 0.1.1 ?>
	    <!-- START PLS ROOT MANUAL INSERTION FORM -->
	    <div class="wrap">
	    	<div class="error" id="message_update_root_script">
	    		<p><strong>I was unable to find your correct root path. Please use the input field below to add it manually.</strong></p>
	    	</div>
	    	<h2>Specify Apache Root Path:</h2>
	    	<p>If you don't know how to retrieve your root path I will walk you through.<br/>At the moment, I'm is being run from this folder:<br/><br/><b><?php echo trim_path(getcwd());?></b><br/><br/>However this might not be your correct root path.<br/>Normally your root path ends on a "public_html", "html" or "httpdocs" folder.<br/><br/>If you're still having problems <a href="mailto:me@joseairosa.com">email me</a> this path and I'll tell you what to use.</p>
	    	<form action="" method="post">
	    		<table class="form-table">
				    <tr>
				        <th><label for="root_path"><?php _e('Apache root path'); ?></label></th>
				        <td>
				    		<input type="text" class="regular-text code" name="root_path" id="root_path" style="width:50em;" value="<?php echo trim_path(getcwd());?>" />
				        </td>
				    </tr>
	    		</table>
				<p class="submit">
					<input type="submit" value="Update Root" name="update_root" />
				</p>
	    	</form>
	    </div>
	    <!-- END PLS ROOT MANUAL INSERTION FORM -->
	<?php endif;?>
	<?php
		// Added on version 0.1.4 
		html_options();
	?>
	<?php
		// Added on version 0.1.4 
		html_compatibility();
	?>
    <?php
    	// Added on version 0.1.4 
    	html_status();
    ?>
<?php
}

add_action('admin_menu', 'wp_pls_add_page');

function wp_pls_add_page() {
	add_options_page("Parallel Loading System", "Parallel Loading System", "administrator", "pls", "wp_pls_add_options_page");
}
?>