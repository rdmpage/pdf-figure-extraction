<?php

// Upload via Zenodo API

require_once(dirname(__FILE__) . '/config.inc.php');


//----------------------------------------------------------------------------------------
function create_deposit()
{
	global $config;
	
	$deposit = null;
		
	// POST empty deposit, gives us the id for an empty bucket	
	$url = $config['zenodo_server'] . '/api/deposit/depositions';
			
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_POST => TRUE,
	  CURLOPT_HTTPHEADER => array(
	  	"Content-type: application/json", 
	  	"Authorization: Bearer " . $config['access_token']
	  	),
	  CURLOPT_POSTFIELDS => "{}"
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	
	$result = curl_exec($ch);
	
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	if ($info['http_code'] == 201)
	{
		$deposit = json_decode($result);
	}
	else
	{
		echo "Error: HTTP " . $info['http_code'] . "\n";
		exit();
	}
	
	return $deposit;
}

//----------------------------------------------------------------------------------------
function upload_metadata($deposit, $metadata)
{
	global $config;

	$url = $config['zenodo_server'] . '/api/deposit/depositions/' . $deposit->id;
			
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_CUSTOMREQUEST => 'PUT',
	  CURLOPT_HTTPHEADER => array(
	  	"Content-type: application/json", 
	  	"Authorization: Bearer " . $config['access_token']
	  	),
	  CURLOPT_POSTFIELDS => json_encode($metadata)
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$result = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	//print_r($info);
	
	if ($info['http_code'] == 200)
	{
		$deposit = json_decode($result);
	}
	else
	{
		echo "Error: HTTP " . $info['http_code'] . "\n";
		exit();
	}
	
	
	echo $result;

}

//----------------------------------------------------------------------------------------
function upload_file($deposit, $full_filename, $short_filename)
{
	global $config;

	$url = $deposit->links->bucket . '/' . $short_filename;

	$command = 'curl -X PUT -H "Accept: application/json"'
		. ' -H "Content-Type: application/octet-stream"'
		. ' -H "Authorization: Bearer ' . $config['access_token'] . '"'
		. ' -T "' . $full_filename . '" '
		. ' ' . $url;
	
	echo $command . "\n";
	system($command);

}

//----------------------------------------------------------------------------------------
function publish($deposit)
{
	global $config;

	$url = $config['zenodo_server'] . '/api/deposit/depositions/' . $deposit->id . '/actions/publish';
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_POST => TRUE,
	  CURLOPT_HTTPHEADER => array(
	  	"Authorization: Bearer " . $config['access_token']
	  	)
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$result = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	// print_r($info);
	
	if ($info['http_code'] == 202)
	{
		$deposit = json_decode($result);
	}
	else
	{
		echo "Error: HTTP " . $info['http_code'] . "\n";
		exit();
	}

	return $deposit;
}

?>
