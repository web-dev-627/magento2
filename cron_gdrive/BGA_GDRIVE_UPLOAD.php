<?php

$APP_PATH ='/home/master/applications/zdyunmettj/public_html/cron_gdrive/';
$backup_folder ="/home/master/applications/zdyunmettj/local_backups";
$processed_folder ="/home/master/applications/zdyunmettj/tmp/";
$parentId   = '1uox4unL_2qGKmDdoQ3GLn9M9KVLX29ly';
$redirect_uri = 'https://kidscover.nl/cron_gdrive/BGA_GDRIVE_UPLOAD.php';

$file = $processed_folder.'BGA_GDRIVE_UPLOAD.log';
$tokenPath = $APP_PATH.'token.json';
$credentialsPath = $APP_PATH.'credentials.json';
require_once($APP_PATH.'vendor/autoload.php');
require($APP_PATH.'KLogger.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);
date_default_timezone_set('Europe/London');
ini_set('memory_limit', '-1');
ini_set("max_execution_time", "-1");
$time = date('Y-m-d H:i:s');
	
if(!file_exists($file)) 
{ 
    file_put_contents($file, '');
} 
$log_BGA = new KLogger($file, 1);
$log_BGA->LogInfo("=================================BGA_GDRIVE_UPLOAD========================================");

$files_array = array();
$backup_fileList = glob($backup_folder."/*");
foreach($backup_fileList as $lst_file){
    $myFile = pathinfo($lst_file);
	$filename =	$myFile['basename'];
	array_push($files_array, $filename);
	//if (strpos($filename, '_backup') == false) {
	//	array_push($files_array, $filename);
	//}
}

//echo var_dump($files_array)."<br>";
//echo var_dump($redirect_uri)."<br>";

function getClient()
{
	global $redirect_uri;
	global $tokenPath;
	global $credentialsPath;
	//echo var_dump($redirect_uri)."<br>";
	//echo var_dump($credentialsPath)."<br>";
	//echo var_dump($tokenPath)."<br>";
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
	$client->setScopes(array('https://www.googleapis.com/auth/drive'));
    $client->setAuthConfig($credentialsPath);
	$client->setRedirectUri($redirect_uri);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    if (file_exists($tokenPath) && filesize($tokenPath) > 0) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else { 		
			if (isset($_GET['code'])) {
				$accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
				$client->setAccessToken($accessToken);
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}
				if (!file_exists(dirname($tokenPath))) {
					mkdir(dirname($tokenPath), 0700, true);
				}
				file_put_contents($tokenPath, json_encode($client->getAccessToken()));				
			} else {
				$authUrl = $client->createAuthUrl();
				printf("Open the following link in your browser:\n%s\n", $authUrl);
				if (!file_exists(dirname($tokenPath))) {
					mkdir(dirname($tokenPath), 0700, true);
				}
                file_put_contents($tokenPath, "");				
				exit();
			}			
        }
    }
    return $client;
}
	
$client = getClient();


/*
DELETE THE WEEK OLD FILES
*/

$yesterday = date('Y.m.d',strtotime("-1 days"));
$optParams = array(
        'pageSize' => 10,
        'fields' => "nextPageToken, files(id, name, size,createdTime)",
        'q' => "'".$parentId."' in parents AND trashed=false "
        );
$service = new Google_Service_Drive($client);
$results = $service->files->listFiles($optParams)->getFiles(); 
foreach($results as $files) {
    $fileId =  $files['id'];
    $file_name =  $files['name'];
    $createdTime =  $files['createdTime'];
	$date_file_stmp = strtotime($createdTime);
	$date_week_stmp = strtotime('-7 days');
	$date_file = date('Y-m-d', $date_file_stmp);
	$date_week = date('Y-m-d', $date_week_stmp);
	if( $date_file < $date_week) {
		//Older
		//echo  '<pre>' ,var_dump("FILE ID:".$fileId."FILE NAME:".$file_name."FILE TIME :".$createdTime), '</pre>';
		try {
		  $service->files->delete($fileId);
		  $log_BGA->LogInfo("FILE DELETED:".$fileId." # ".$file_name);
		} catch (Exception $e) {
		  $log_BGA->LogInfo("An error occurred::".$e->getMessage());
		}
	}else{
		//Newer
	}
}

/*
Upload the Files
*/

$service = new Google_Service_Drive($client);
$path_array = parse_url($redirect_uri);
$host = $path_array['host'];
$chunkSizeBytes = 1 * 1024 * 1024;
foreach($files_array as $item=>$values){
	$time_stamp= date("Y.m.d");
	$file = new Google_Service_Drive_DriveFile();
	$title = basename($values);
	$file->setName($time_stamp.'_'.$host.'_'.$title);
	$file->setDescription('BackupUpload_'.$time_stamp);
	$file->setParents(array($parentId));
	$file->setMimeType('application/octet-stream');
	
	//echo var_dump("FILE UPLOAD:".$backup_folder.'/'.$values)."<br>";
	
	$chunkSize = 1024*1024; 
	$client->setDefer(true);
	$request = $service->files->create($file, array(
	  'mimeType' => 'application/octet-stream',
	  'uploadType' => 'resumable'
	));
	$media = new Google_Http_MediaFileUpload($client, $request,'application/octet-stream', null, true, $chunkSizeBytes); 
	$media->setFileSize(filesize($backup_folder.'/'.$values)); 
	$status = false; 
	$handle = fopen($backup_folder.'/'.$values, "rb"); 
	while(!$status && !feof($handle)) { 
		$chunk = fread($handle, $chunkSizeBytes); 
		$status = $media->nextChunk($chunk); 
	} 
    $result = false;
    if ($status != false) {
		$result = $status;
    }
    fclose($handle);	
		
	echo var_dump("FILE UPLOAD:".$values)."<br>";
	$log_BGA->LogInfo("FILE UPLOAD:".$values." # ".json_encode($result));
}



							
?>
