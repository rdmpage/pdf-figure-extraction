<?php

/* Idea: Parse JATS-XML to extract info we want to upload to BLR in Zenodo */

require_once(dirname(__FILE__) . '/api.php');

//----------------------------------------------------------------------------------------
// Create data object for figure or a reference
function to_zenodo($reference, $figure = null, $community = '')
{	
	$data = new stdclass;
	$data->metadata = new stdclass;
	
	// metadata common to a reference and a figure
	foreach ($reference as $k => $v)
	{
		switch ($k)
		{				
			case 'authors':
				$data->metadata->creators = array();
				foreach ($reference->authors as $a)
				{
					$author = new stdclass;
					$author->name = $a;
				
					$data->metadata->creators[] = $author;
				}
				break;				
		
			case 'date':
				$data->metadata->publication_date = $v;
				break;

			case 'year':
				$data->metadata->publication_date = $v . '-01-01';
				break;	
	
			default:
				break;
		}
	}
	
	// Add to a community
	if ($community != '')
	{
		$data->metadata->communities = array();
		
		$identifier = new stdclass;
		$identifier->identifier = $community;
		
		$data->metadata->communities[] = $identifier;
	}
	
	// What kind of item are we adding?
	if ($figure)
	{
		// Figure
		$data->metadata->upload_type = 'image';
		$data->metadata->image_type = 'figure';
		
		foreach ($reference as $k => $v)
		{
			switch ($k)
			{									
				case 'doi':
					$data->metadata->related_identifiers = array();
					
					$related = new stdclass;
					$related->relation = 'isPartOf';
					$related->identifier = 'https://doi.org/' . strtolower($v);
					
					$data->metadata->related_identifiers[] = $related;
					break;
			
				default:
					break;
			}	
		}		
				
		$data->metadata->title = $figure->label;
		
		if (isset($reference->bibliographicCitation))
		{
			$data->metadata->title .= ' from: ' . $reference->bibliographicCitation;
		}
		
		$data->metadata->description = $figure->caption;
		
		
		// Can get license ids from https://zenodo.org/api/licenses/?page=1&size=100
		if (isset($reference->license))
		{

			switch 	($reference->license)
			{
			
				case 'http://creativecommons.org/licenses/by/4.0/':
					$data->metadata->license 		= 'cc-by';
					$data->metadata->access_right 	= 'open';
					break;

				default:
					$data->metadata->access_right 	= 'open';
					$data->metadata->license 		= 'cc-zero';			
					break;
			}
		}
		else
		{		
			// Figures are always open and CC-0 by default according to Plazi
			$data->metadata->access_right 	= 'open';	
			$data->metadata->license 		= 'cc-zero';
		}
	}
	else
	{
		// Work
		$data->metadata->upload_type = 'publication';
		$data->metadata->publication_type = 'article';
				
		foreach ($reference as $k => $v)
		{
			switch ($k)
			{
				case 'title':
					$data->metadata->{$k} = $v;
					break;

				case 'doi':
					$data->metadata->{$k} = strtolower($v);
					break;
			
				case 'abstract':
					$data->metadata->description = $v;
					break;
				
				case 'journal':
					$data->metadata->journal_title = $v;
					break;
					
				case 'volume':
					$data->metadata->journal_volume = $v;
					break;
					
				case 'issue':
					$data->metadata->journal_issue = $v;
					break;
			
				case 'spage':
					$data->metadata->journal_pages = $v;
					break;

				case 'epage':
					$data->metadata->journal_pages .= '-' . $v;
					break;

				default:
					break;
			}	
		}		
		
		// We need a description, use title if no abstract		
		if (!isset($reference->abstract))
		{
			$data->metadata->description = $data->metadata->title;
		}
		
		// License
		if (isset($reference->license))
		{

			switch 	($reference->license)
			{

				case 'http://creativecommons.org/licenses/by/4.0/':
					$data->metadata->license 		= 'cc-by';
					$data->metadata->access_right 	= 'open';
					break;
			
				default:
					$data->metadata->access_right 	= 'open';
					break;
			}
		}
		else
		{		
			// Articles are closed by default
			//$data->metadata->access_right 	= 'closed';				
			$data->metadata->access_right 	= 'open';	
		}
	
	}
	
	
	return $data;
}

//----------------------------------------------------------------------------------------
function process($filename, $live)
{

	$path_parts = pathinfo($filename);
	
	print_r($path_parts);

	$xml = file_get_contents($filename);

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);

	$xpath->registerNamespace("xmlns", 		"http://www.w3.org/1999/xlink");

	//reference
	$reference = new stdclass;
	
	// DOI	
	$nodeCollection = $xpath->query ("//article-id[@pub-id-type='doi']");
	foreach($nodeCollection as $node)
	{
		$reference->doi = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ("//title-group/article-title");
	foreach($nodeCollection as $node)
	{
		$reference->title = $node->firstChild->nodeValue;
	}
	
	$nodeCollection = $xpath->query ("//journal-meta/journal-title-group/journal-title");
	foreach($nodeCollection as $node)
	{
		$reference->journal = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ("//journal-meta/journal-title-group/journal-title");
	foreach($nodeCollection as $node)
	{
		$reference->journal = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ("//journal-meta/issn");
	foreach($nodeCollection as $node)
	{
		$reference->issn = $node->firstChild->nodeValue;
	}
	
	$nodeCollection = $xpath->query ("//article-meta/volume");
	foreach($nodeCollection as $node)
	{
		$reference->volume = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ("//article-meta/fpage");
	foreach($nodeCollection as $node)
	{
		$reference->spage = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ("//article-meta/lpage");
	foreach($nodeCollection as $node)
	{
		$reference->epage = $node->firstChild->nodeValue;
	}

	$nodeCollection = $xpath->query ('//article-meta/pub-date[@pub-type="ppub"]/year');
	foreach($nodeCollection as $node)
	{
		$reference->year = $node->firstChild->nodeValue;
	}

	$reference->authors = array();
	$nodeCollection = $xpath->query ('//article-meta/contrib-group/contrib[@contrib-type="author"]/name/string-name');
	foreach($nodeCollection as $node)
	{
		$reference->authors[] = $node->firstChild->nodeValue;
	}
	
	//------------------------------------------------------------------------------------
	// Create a bibliographic citation that we can use for the figure caption
	$terms = array();
	if (isset($reference->authors))
	{
		$terms[] = join('; ', $reference->authors);
	}

	if (isset($reference->date))
	{
		$terms[] = ' (' . substr($reference->date, 0, 4) . ')';
	}
	else
	{
		if (isset($reference->year))
		{
			$terms[] = ' (' . $reference->year . ')';
		}	
	}

	if (isset($reference->title))
	{
		$terms[] = ' ' . $reference->title;
	}

	if (isset($reference->journal))
	{
		$terms[] = '. ' . $reference->journal;
	}

	if (isset($reference->volume))
	{
		$terms[] = ': ' . $reference->volume;
	}

	if (isset($reference->spage))
	{
		$terms[] = ' ' . $reference->spage;
	}

	if (isset($reference->epage))
	{
		$terms[] = '-' . $reference->epage;
	}

	if (isset($reference->doi))
	{
		$terms[] = ' https://doi.org/' . $reference->doi;
	}

	$reference->bibliographicCitation = join('', $terms);	
	
	print_r($reference);
	
	//------------------------------------------------------------------------------------
	$metadata = to_zenodo ($reference, null, 'biosyslit');
	
	echo "\nCreate deposit\n";
	$deposit = create_deposit();
		
	echo "\nUpload metadata\n";
	upload_metadata($deposit, $metadata);
		
	echo "\nUpload file\n";	
	$pdf_filename_short = $path_parts['filename'] . '.pdf';
	$pdf_filename_full = $path_parts['dirname'] . '/' . $pdf_filename_short;
	
	upload_file($deposit, $pdf_filename_full, $pdf_filename_short);
	
	if ($live)
	{
		echo "\Publish\n";		
		publish($deposit);
	}
	
	//------------------------------------------------------------------------------------
	// figures
	$figs = $xpath->query ("//fig");
	foreach($figs as $fig)
	{
		$figure = new stdclass;
	
		$values = $xpath->query ("label", $fig);
		foreach($values as $value)
		{
			$figure->label = $value->firstChild->nodeValue;
		}

		$values = $xpath->query ("caption", $fig);
		foreach($values as $value)
		{
			$figure->caption = $value->firstChild->nodeValue;
		}

		$values = $xpath->query ("graphic/@xlink:href", $fig);
		foreach($values as $value)
		{
			$figure->filename = $value->firstChild->nodeValue;
		}
	
		//print_r($figure);
		
		$metadata = to_zenodo ($reference, $figure, 'biosyslit');
	
		echo "\nCreate deposit\n";
		$deposit = create_deposit();
		
		echo "\Upload metadata\n";		
		upload_metadata($deposit, $metadata);
				
		echo "\nUpload file\n";	
		$image_filename_short = $figure->filename;
		$image_filename_full = $path_parts['dirname'] . '/' . $image_filename_short;
		
		upload_file($deposit,$image_filename_full, $image_filename_short);
		
		if ($live)
		{
			echo "\Publish\n";		
			publish($deposit);
		}
			
	}	
}


//----------------------------------------------------------------------------------------


$filename = 'journal/0372-333X/S0372-333X2015006000039/S0372-333X2015006000039.xml';
$filename = 'journal/2200-4025/S2200-40252021002400303/S2200-40252021002400303.xml';
$filename = 'journal/1409-3871/S1409-38712017001700000/S1409-38712017001700000.xml';

process($filename, $live = false); // false means we don't upload yet

	




?>
