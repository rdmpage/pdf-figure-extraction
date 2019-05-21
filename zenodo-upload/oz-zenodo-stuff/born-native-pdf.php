<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/shared/ris.php');
require_once (dirname(__FILE__) . '/pdf_blocks.php');

require_once (dirname(__FILE__) . '/zenodo/api.php');
require_once (dirname(__FILE__) . '/zenodo/metadata.php');

require_once (dirname(__FILE__) . '/get_figures.php');

//----------------------------------------------------------------------------------------
function extract_pages($pdf_filename)
{
	global $config;
	
	$basedir = $config['cache_dir'];

	$path_parts = pathinfo($pdf_filename);
	$xml_dir = $basedir . '/' . $path_parts['filename'] . '.xml_data';

	if (!file_exists($xml_dir))
	{
		echo "Converting PDF to XML...\n";
		$command = $config['pdftoxml'] . ' -cutPages -blocks ' . $basedir . '/' . $pdf_filename;
		echo $command . "\n";
		system($command);
	}
	else
	{
		echo "Conversion done already!\n";
	}
}

//----------------------------------------------------------------------------------------
function xml_to_json($xml_dir)
{
	global $config;

	$files = scandir($xml_dir);
	$xml_files = array();

	foreach ($files as $filename)
	{
		if (preg_match('/pageNum-(?<page>\d+)\.xml$/', $filename, $m))
		{	
			$xml_files[] = 'pageNum-' . str_pad($m['page'], 3, '0', STR_PAD_LEFT) . '.xml';
		}
	}

	asort($xml_files);

	// Process XML into simplified representation of page
	$json_files = array();

	foreach ($xml_files as $xml_filename)
	{
		$page_name = preg_replace('/pageNum-[0]+/', 'pageNum-', $xml_filename);
		$page_name = preg_replace('/\.xml/', '', $page_name);

		$xml_filename = $page_name . '.xml';

		$obj = pdf_blocks($xml_dir . '/' . $xml_filename);
	
		$json_filename = $page_name . '.json';
	
		file_put_contents($config['output_dir'] . '/' . $json_filename, json_encode($obj, JSON_PRETTY_PRINT));
	
		$json_files[] = $json_filename;
	}
	
	return $json_files;
}

/*
//----------------------------------------------------------------------------------------
function get_figures($json_files)
{
	global $config;
	
	$figures = array();
	
	// Generate SVG to explore block structure
	foreach ($json_files as $json_filename)
	{
		$json = file_get_contents($config['output_dir'] . '/' . $json_filename);
	
		$obj = json_decode($json);
	
		$svg = '<?xml version="1.0" ?>
	<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
			xmlns="http://www.w3.org/2000/svg"
			width="1000px" 
			height="1000px" >';	
		$svg .= '<g>';
	
	
		$r = new Rectangle($obj->x, $obj->y, $obj->w, $obj->h, 'page');
		$svg .= $r->toSvg();

		$text_area = new Rectangle($obj->text_area->x, $obj->text_area->y, $obj->text_area->w, $obj->text_area->h, 'text');
		$svg .= $text_area->toSvg();

	
		$n = count($obj->blocks);
	
		$blocks = array();
	
		$X = array();

		// Create adjacency matrix and fill with 0's
		$X = array();
		for ($i = 0; $i < $n; $i++)
		{
			$X[$i] = array();
	
			for ($j = 0; $j < $n; $j++)
			{ 
				$X[$i][$j] = 0;
			}
		}
	
		for ($i = 0; $i < $n; $i++)
		{
			$blocks[$i] = new Rectangle($obj->blocks[$i]->x, $obj->blocks[$i]->y, $obj->blocks[$i]->w, $obj->blocks[$i]->h, $i);
			$svg .= $blocks[$i]->toSvg();
		}
	
		// Get relationship between blocks
		for ($i = 0; $i < $n-1; $i++)
		{
			for ($j = 1; $j < $n; $j++)
			{	
				$line = new Line();
				$line->fromPoints($blocks[$i]->getCentre(), $blocks[$j]->getCentre());
		
				// does this line hit any other rects?
				$hits = 0;
				for ($k = 0; $k < $n; $k++)
				{
					if ($k != $i && $k != $j)
					{
						if ($blocks[$k]->intersectsLine($line))
						{
							$hits++;
						}
					}
				}
				if ($hits == 0)
				{				
					$svg .= $line->toSvg();
				
					// store relationship between blocks
				
					if (
						($obj->blocks[$i]->type == 'image')
						&& 
						($obj->blocks[$j]->type == 'text')
						)
					{
						if ($blocks[$i]->getCentre()->y < $blocks[$j]->getCentre()->y)
						{
							$X[$i][$j] = 1;
						
							// sanity check for caption
							if (preg_match('/^Fig/i', $obj->blocks[$j]->text))
							{
								$figure = new stdclass;
								$figure->href = $obj->blocks[$i]->href;
								$figure->caption = $obj->blocks[$j]->text;
						
								$figures[] = $figure;
							}
						
						}
				
					}

					if (
						($obj->blocks[$j]->type == 'image')
						&& 
						($obj->blocks[$i]->type == 'text')
						)
					{
						if ($blocks[$j]->getCentre()->y < $blocks[$i]->getCentre()->y)
						{
							$X[$j][$i] = 1;
						
							// sanity check for caption
							if (preg_match('/^Fig/i', $obj->blocks[$i]->text))
							{
								$figure = new stdclass;
								$figure->href = $obj->blocks[$j]->href;
								$figure->caption = $obj->blocks[$i]->text;
						
								$figures[] = $figure;
							}
						}
				
					}
				}
			}	
		}
	
		if (0)
		{
			for($i=0;$i<$n;$i++)
			{
				for($j=0;$j<$n;$j++)
				{
					echo $X[$i][$j];
				}
				echo "\n";
			}
		}	
	
		//print_r($figures);
		
		$svg .= '</g>';
		$svg .= '</svg>';
	
		$svg_filename = str_replace('.json', '.svg', $json_filename);
	
		file_put_contents($config['output_dir'] . '/' . $svg_filename, $svg);	
	}
	
	return $figures;
}
*/
//----------------------------------------------------------------------------------------


//----------------------------------------------------------------------------------------
function get_pdf_filename($pdf)
{
	$filename = '';
	

	// if no name use basename
	if ($filename == '')
	{
		$filename = basename($pdf);
		$filename = str_replace('(', '-', $filename);
		$filename = str_replace(')', '-', $filename);
		
		$filename = str_replace('?sequence=1', '', $filename);
		$filename = str_replace('&isAllowed=y', '', $filename);
		
		$filename = preg_replace('/\?.*$/', '', $filename);
		
	}
		
	echo "filename=$filename\n";
	
	return $filename;
}

//----------------------------------------------------------------------------------------
function ris_import($reference)
{
	global $force;
	global $config;

	print_r($reference);
	
	// Grab PDF		
	if (isset($reference->pdf))
	{
		// fetch PDF
		$cache_dir =  $config['cache_dir'];
		
		$pdf_filename = get_pdf_filename($reference->pdf);
		
		$article_pdf_filename = $cache_dir . '/' . $pdf_filename;
			
		if (file_exists($article_pdf_filename) && !$force)
		{
			echo "Have PDF $article_pdf_filename\n";
		}
		else
		{				
			$command = "curl --location '" . $reference->pdf . "' > '" . $article_pdf_filename . "'";
			echo $command . "\n";
			system ($command);
		}
		
		// Convert to XML	
		extract_pages($pdf_filename);
		
		
		// Clean up output directory
		
		$output_files = scandir($config['output_dir']);
		foreach ($output_files as $output_filename)
		{
			if (preg_match('/\.(json|svg)$/', $output_filename))
			{
				echo $output_filename . "\n";
				unlink($config['output_dir'] . '/' . $output_filename);
			}
		}
		
		// Generate summary JSON from XML
		$path_parts = pathinfo($pdf_filename);
		
		$base_name = $path_parts['filename'];
		
		$xml_dir = $config['cache_dir']. '/' . $base_name . '.xml_data';
		
		$json_files = xml_to_json($xml_dir);
		
		// Block relationships
		
		echo "Getting figures\n";
		
		$figures = get_figures($json_files);
		
		echo "Done getting figures\n";
		
		// Figures
		$html = '';
		$html .= '<html>';
		foreach ($figures as $figure)
		{
			$html .= '<img style="border:1px solid rgb(192,192,192);padding:10px;" height="200" src="' . $config['cache_dir'] . '/' . $figure->href . '" />';
			$html .= '<p>' . $figure->caption . '</p>';	
		}

		$html .= '</html>';
		
		file_put_contents($base_name . '-figures.html', $html);
				
		// Zenodo upload
		$use_zenodo = false;
		$publish = false; // if true danger Will Robinson!

		$community = '';
		$community = 'biosyslit';

		if ($use_zenodo) // only if we are going to upload to Zenodo
		{
			$parts = array();
			
			// Add figures first so we can keep track of part relationships,
			// we want to have both isPartOf and hasPart to ease discoverability
			
			// figures 		
			foreach ($figures as $figure)
			{		
				$deposit = create_deposit();
				
				// Metadata
				$data = reference_to_zenodo($reference, $figure, $community);
				upload_metadata($deposit, $data);
			
				// Upload image
				$image_path = $config['cache_dir'] . '/' . $figure->href;
				$image_filename = basename($figure->href);
			
				upload_file($deposit, $image_path, $image_filename);
				
				// Construct DOI so we can link to work without publishing
				$parts[] = $config['zenodo_doi_prefix'] . '/zenodo.' . $deposit->record_id;
				
				// publish
				if ($publish)
				{
					$deposit = publish($deposit);					
					//$parts[] = $deposit->doi;					
				}
			}
			
			// work
			$deposit = create_deposit();
		
			// Add metadata
			$data = reference_to_zenodo($reference, null, $community, $parts);
			upload_metadata($deposit, $data);	
		
			// Add file	
			upload_file($deposit, $article_pdf_filename, $pdf_filename);
		
			// Publish
			if ($publish)
			{
				publish($deposit);
			}		
			
			
		}
		
	}
}

//----------------------------------------------------------------------------------------


$filename = '';
if ($argc < 2)
{
	echo "Usage: import.php <RIS file> <mode>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}


$file = @fopen($filename, "r") or die("couldn't open $filename");
fclose($file);

import_ris_file($filename, 'ris_import');


?>