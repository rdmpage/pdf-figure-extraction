<?php

global $config;

// Date timezone
date_default_timezone_set('UTC');

// Proxy settings for connecting to the web-----------------------------------------------

// Set these if you access the web through a proxy server. This
// is necessary if you are going to use external services such
// as PubMed.
$config['proxy_name'] 	= '';
$config['proxy_port'] 	= '';

//$config['proxy_name'] 	= 'wwwcache.gla.ac.uk';
//$config['proxy_port'] 	= '8080';

$config['cache_dir'] 		= dirname(__FILE__) . '/cache';
$config['pdftoxml']			= dirname(__FILE__) . '/pdftoxml/pdftoxml';
$config['output_dir']		= dirname(__FILE__) . '/output';

$config['journal_dir']		= dirname(__FILE__) . '/journal';


// Zenodo---------------------------------------------------------------------------------

if (1)
{
	// Live site
	$config['access_token'] 	 = 'jANSOUG6EfMPyWuapSRyrh18rP0XJuc5HckmVpsx5Chudp8G6evBrcoCgDpr';
	$config['zenodo_server'] 	 = 'https://zenodo.org';
	$config['zenodo_doi_prefix'] = '10.5281';
}
else
{
	// Sandbox
	$config['access_token']  	 = 'PdNUeuws7m2OAGKytOZJ6ZeHEqlMfgFv83vX3fC7S4lfVCvCMtsWpuuc4jMM';
	$config['zenodo_server'] 	 = 'https://sandbox.zenodo.org';
	$config['zenodo_doi_prefix'] = '10.5072';
}

?>