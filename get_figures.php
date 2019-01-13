<?php

$debug = true;

if (!isset($config['cache_dir']))
{
	$config['cache_dir'] 		= dirname(__FILE__) . '/cache';
}

if (!isset($config['pdftoxml']))
{
	$config['pdftoxml']			= dirname(__FILE__) . '/pdftoxml/pdftoxml';
}

if (!isset($config['output_dir']))
{
	$config['output_dir']		= dirname(__FILE__);
}

require_once (dirname(__FILE__) . '/shared/components.php');
require_once (dirname(__FILE__) . '/shared/spatial.php');


//----------------------------------------------------------------------------------------
function rectIsBelowOtherRect($thisRect, $otherRect, $bounding)
{
	$below = new Rectangle();
	$below->createFromPoints(
		$otherRect->getBottomLeft(),
		new Point($otherRect->getBottomRight()->x, $bounding->getBottomRight()->y)
		);
		
	return ($below->getOverlap($thisRect) != null);
}


//----------------------------------------------------------------------------------------
function get_figures($json_files, $centered = false)
{
	global $config;
	global $debug;
	
	$figures = array();
	
	// Generate SVG to explore block structure
	foreach ($json_files as $json_filename)
	{
		if ($debug)
		{
			echo "Doing $json_filename\n";
		}
	
		$page_number = 0;
		if (preg_match('/pageNum-(?<page>\d+)/', $json_filename, $m))
		{
			$page_number = $m['page'];
		}
	
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
		
		
		// big problem if we have lots of blocks (e.g., table) as then we get trapped in looping through all the blocks...
		// need to be a bit cleverer
		if ($n < 30)
		{
	
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
					$text_block = -1;
					$image_block = -1;
				
					if ($obj->blocks[$i]->type == 'image')
					{
						$image_block = $i;
					}
					if ($obj->blocks[$j]->type == 'image')
					{
						$image_block = $j;
					}
					if ($obj->blocks[$i]->type == 'text')
					{
						$text_block = $i;
					}
					if ($obj->blocks[$j]->type == 'text')
					{
						$text_block = $j;
					}
				
					//--------------------------------------------------------------------
					// test for text below image
					if (
						($text_block != -1)
						&& 
						($image_block != -1)
						&&
						($text_block != $image_block)
					)
					{
						// If text is below image then we may have a caption
						//if ($blocks[$image_block]->getCentre()->y < $blocks[$text_block]->getCentre()->y)
						if (rectIsBelowOtherRect($blocks[$text_block], $blocks[$image_block], $text_area))
						{
						
							// other possible checks
							$accept = true;
							
							// figure caption centred w.r.t. figure
							/*
							if ($blocks[$text_block]->x < $blocks[$image_block]->x)
							{
								$accept = false;
							}
							if (($blocks[$text_block]->x + $blocks[$text_block]->w) > ($blocks[$image_block]->x + $blocks[$image_block]->w))
							{
								$accept = false;
							}
							*/
							
							if ($centered)
							{
								if (abs($blocks[$image_block]->getCentre()->x - $blocks[$text_block]->getCentre()->x) > 10)
								{
									$accept = false;
								} 
							}
														
							if ($accept)
							{
								$X[$image_block][$text_block] = 1;
								$X[$text_block][$image_block] = 1;
							}
						
							/*
							// sanity check for caption
							if (preg_match('/^Fig/i', $obj->blocks[$text_block]->text))
							{
								$figure = new stdclass;
								$figure->page_number = $page_number;
								$figure->href = $obj->blocks[$image_block]->href;
								$figure->caption = $obj->blocks[$text_block]->text;
						
								$figures[] = $figure;
							}
							*/
						
						}
				
					}		
					
					/*
					//--------------------------------------------------------------------
					// test for image below image									
					if (($obj->blocks[$i]->type == 'image') && ($obj->blocks[$j]->type == 'image'))
					{
					
					 	if (rectIsBelow($source, $target, $bounding)
					
					
					
						$is_below = true;
						
						// 1. text is below image
					
						if (
							($blocks[$i]->getCentre()->y < $blocks[$j]->getCentre()->y)
							|| ($blocks[$j]->getCentre()->y < $blocks[$i]->getCentre()->y)
							)
						{
							$is_below = true;
						}
						else
						{
							$is_below = false;
						}
							
						if ($is_below)	
						{
							$X[$i][$j] = 1;
							$X[$j][$i] = 1;
						}
					}
					*/

				}
			}	
		}
		
		}
	
		if (1)
		{
			if ($debug)
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
			
			$c = get_components($X);
			
			if ($debug)
			{
				print_r($c);
			}
			
			foreach ($c as $cluster)
			{
				$cluster_size = count($cluster);
				
				switch($cluster_size)
				{
					case 1:
						// one block, ignore
						break;
						
					case 2:
						// if image and text then figure and caption 
						if ($obj->blocks[$cluster[0]]->type != $obj->blocks[$cluster[1]]->type)
						{
							if ($obj->blocks[$cluster[0]]->type == 'text')
							{
								$text_block = $cluster[0];
								$image_block = $cluster[1];							
							}
							else
							{
								$text_block = $cluster[1];
								$image_block = $cluster[0];
							}
							
							if (preg_match('/^(Plate|Fig)/i', $obj->blocks[$text_block]->text))
							{							
								$figure = new stdclass;
								$figure->page_number = $page_number;
								$figure->href = $obj->blocks[$image_block]->href;
								
								if (preg_match('/\/(?<name>[^\/]+\.(gif|jpeg|jpg|png))/', $figure->href, $m))
								{
									$figure->filename = $m['name'];
								}
								
								$figure->caption = $obj->blocks[$text_block]->text;
								
								// can we get label?
								if (preg_match('/(?<label>(Plate|Fig(\.|ure)?)\s+(?<number>\d+(-\d+)?))\.\s+(?<caption>.*)/ui', $figure->caption, $m))
								{
									$figure->label = $m['label'];
									$figure->number = $m['number'];
									$figure->caption = $m['caption'];
								}											
						
								$figures[] = $figure;
							}						
						}
						break;
						
					default:
						// more than one block
						
						
						$text_blocks = array();
						$image_blocks = array();
						
						foreach ($cluster as $k => $v)
						{
							switch ($obj->blocks[$v]->type)
							{
								case 'text':
									$text_blocks[] = $v;
									break;
									
								case 'image':
									$image_blocks[] = $v;
									break;
							}
							
						}
						
						if ($debug)
						{
							echo "Text blocks\n";
							print_r($text_blocks);
							
							echo "Image blocks\n";
							print_r($image_blocks);
						}
												
						// for born-digital we associate each image with the same caption, could
						// manually edit caption to reflect just figure parts.
						
						if (1)
						{
							if (count($text_blocks) == 1)
							{
								if (preg_match('/^(Plate|Fig)/i', $obj->blocks[$text_blocks[0]]->text))
								{
									foreach ($image_blocks as $image_block)
									{
										$figure = new stdclass;
										$figure->page_number = $page_number;
										$figure->href = $obj->blocks[$image_block]->href;
										if (preg_match('/\/(?<name>[^\/]+\.(gif|jpeg|jpg|png))/', $figure->href, $m))
										{
											$figure->filename = $m['name'];
										}
										$figure->caption = $obj->blocks[$text_blocks[0]]->text;
										
										// can we get label?
										if (preg_match('/(?<label>(Plate|Fig(\.|ure)?)\s+(?<number>\d+(-\d+)?))\.\s+(?<caption>.*)/ui', $figure->caption, $m))
										{
											$figure->label = $m['label'];
											$figure->number = $m['number'];
											$figure->caption = $m['caption'];
										}
						
										$figures[] = $figure;									
									}
								
								}
							}
						
						
						
						}
						
						// for OCR we could merge image bounding boxes then extract that region from
						// scanned image.
						
						break;
				
				
				}
			
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

// test
if (0)
{

	$json_files = array('pageNum-2.json');
	//$json_files = array('pageNum-5.json');
	$json_files = array('pageNum-4.json');
	$json_files = array('pageNum-7.json');

	$json_files = array('pageNum-6.json');
		
		
	$json_files = array('output/pageNum-7.json');		
		
	// Block relationships
	$figures = get_figures($json_files);

	// Figures
	$html = '';
	$html .= '<html>';
	foreach ($figures as $figure)
	{
		$html .= '<img style="border:1px solid rgb(192,192,192);padding:10px;" height="200" src="' . $config['cache_dir'] . '/' . $figure->href . '" />';
		$html .= '<p>' . $figure->caption . '</p>';	
	}

	$html .= '</html>';

	file_put_contents('figures.html', $html);

}
				




?>