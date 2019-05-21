<?php

// Convert a reference object or a figure to Zenodo metadata

//----------------------------------------------------------------------------------------
function reference_to_zenodo($reference, $figure = null, $community = '', $parts = array())
{	
	$data = new stdclass;
	$data->metadata = new stdclass;
	
	// common metadata
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
	
	// Parts
	if (count($parts) > 0)
	{
		$data->metadata->related_identifiers = array();
		
		foreach ($parts as $part)
		{
			$related = new stdclass;
			$related->relation = 'hasPart';
			$related->identifier = 'https://doi.org/' . strtolower($part);
		
			$data->metadata->related_identifiers[] = $related;
		}
	}
	
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
				
		$data->metadata->title = $figure->caption;
		$data->metadata->description = $figure->caption;
		
		// Figures are always open and CC-0 by default
		$data->metadata->access_right 	= 'open';	
		$data->metadata->license 		= 'cc-zero';		
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
				
				case 'secondary_title':
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
		
		// If PDF is on the web then open access, if local then closed
		if (preg_match('/localhost/', $reference->pdf))
		{
			$data->metadata->access_right = 'closed';	
		}
		else
		{
			$data->metadata->access_right = 'open';	
		}
	}
	
	
	return $data;
}


?>

