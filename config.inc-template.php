<?php

global $config;

// Date timezone
date_default_timezone_set('UTC');

$config['cache_dir'] 		= dirname(__FILE__) . '/cache';
$config['pdftoxml']			= dirname(__FILE__) . '/pdftoxml/pdftoxml';
$config['output_dir']		= dirname(__FILE__) . '/output';

$config['journal_dir']		= dirname(__FILE__) . '/journal';


// Zenodo---------------------------------------------------------------------------------

if (0)
{
	// Live site
	$config['access_token'] 	 = '';
	$config['zenodo_server'] 	 = 'https://zenodo.org';
	$config['zenodo_doi_prefix'] = '10.5281';
}
else
{
	// Sandbox
	$config['access_token']  	 = '';
	$config['zenodo_server'] 	 = 'https://sandbox.zenodo.org';
	$config['zenodo_doi_prefix'] = '10.5072';
}

?>