<?php

// pdf blocks

require_once(dirname(__FILE__) . '/shared/components.php');
require_once(dirname(__FILE__) . '/shared/spatial.php');

//----------------------------------------------------------------------------------------
// $text_block is array of line numbers that belong to that block
// For block of text get details
function get_block_info($page, $text_block)
{
	$block = new stdclass;
	
	// get bounding box for this block
	$block->bbox = new BBox();		
	foreach ($text_block as $i)
	{
		$block->bbox->merge($page->lines[$i]->bbox);
	}
	
	$block->tokens = array();
		
	// text attributes
	$block->font_bold = 0;
	$block->font_italic = 0;
	$block->font_name = array();
	$block->font_size = array();
	
	$block->line_ids = array();
	
	foreach ($text_block as $i)
	{		
		$block->line_ids[] = $i;
	
		// grab block of text
		
		foreach ($page->lines[$i]->tokens as $token)
		{
			if ($token->bold)
			{
				$block->font_bold++;
			}
			if ($token->italic)
			{
				$block->font_italic++;
			}
		
			$key = strtolower($token->font_name);
			if (!isset($block->font_name[$key]))
			{
				$block->font_name[$key] = 0;
			}
			$block->font_name[$key]++;
			
			
			if (!isset($block->font_size[$token->font_size]))
			{
				$block->font_size[$token->font_size] = 0;
			}
			$block->font_size[$token->font_size]++;
			
			
			$block->tokens[] = $token->text;
		}
	}
	
	// get most common font size for this block
	// Sort array of font sizes by frequency (highest to lowest)
	arsort($block->font_size, SORT_NUMERIC );
	
	// The font sizes are the keys to the array, so first key is most common font size
	$block->modal_font_size = array_keys($block->font_size)[0];	

	return $block;
}

//----------------------------------------------------------------------------------------
function find_blocks($page)
{
	// Find blocks by looking for overlap between (inflated)
	// bounding boxes, then find components of graph of overlaps
	$X = array();
	$n = count($page->lines);

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
	
	// Populate adjacency graph
	foreach ($page->lines as $line)
	{
		$bbox = new BBox();
		$bbox->merge($line->bbox);
		
		// magic_number
		$bbox->inflate(10,10); // to do: need rule for overlap value
		
		$overlap = array();
		foreach ($page->lines as $other_lines)
		{
			if ($other_lines->bbox->overlap($bbox))
			{
				$lines_overlap = true;
				if (1)
				{
					// just accept overlap
				}
				else
				{
					$lines_overlap = false;
					
					// try and develop other rules...
					if ($line->id < $other_lines->id)
					{
						$lines_overlap = $line->bbox->minx  < $other_lines->bbox->minx;
					}
					
					if (!$lines_overlap)
					{
						if ($line->id < $other_lines->id)
						{
							if ($line->bbox->minx == $other_lines->bbox->minx)
							{
								$lines_overlap = $line->bbox->mix > $page->text_bbox->minx;
							}
						}
					}										
				}
				if ($lines_overlap)
				{
					$overlap[] = $other_lines->id;
				}
			}
		}
		
		foreach ($overlap as $o)
		{
			$X[$line->id][$o] = 1;
			$X[$o][$line->id] = 1;
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
	
	// Components of X are blocks of overlapping text
	$blocks = get_components($X);
	
	
	// A block may comprise more than one paragraph or other unit, so see if we can cut the blocks further
	//$blocks = cut($page, $blocks, $X);
	
	// Return partition of text lines into blocks
	return $blocks;

}

//----------------------------------------------------------------------------------------
// Grab PDF XML and process
// $filter_overlap setting toggles whether we remove text blocks that overlap images
function pdf_blocks($filename, $filter_overlap = false)
{
	$xml = file_get_contents($filename);

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$page = new stdclass;
	
	$nodeCollection = $xpath->query ('//PAGE');
	foreach($nodeCollection as $node)
	{
		// coordinates
		if ($node->hasAttributes()) 
		{ 
			$attributes2 = array();
			$attrs = $node->attributes; 
			
			foreach ($attrs as $i => $attr)
			{
				$attributes2[$attr->name] = $attr->value; 
			}
		}
		
		$page->width = $attributes2['width'];
		$page->height = $attributes2['height'];
	}
	
	$page->bbox = new BBox(0, 0, $page->width, $page->height);
	$page->text_bbox = new BBox($page->width, $page->height, 0, 0);
	
	
	$page->images = array();
		
	
	// include image of full page (e.g., from OCR PDF)
	if (0)
	{
		// page image from PDF
		$image = $filename;
		$image = str_replace('pageNum', 'image', $image);
		$image = preg_replace('/xml$/', 'png', $image);
		$html .= '<img src="' . $image . '" width="' . $page->width . '">';
	}
		
	// images (figures) from born native PDF
	if (1)
	{
		$images = $xpath->query ('//IMAGE');
		foreach($images as $image)
		{
			// coordinates
			if ($image->hasAttributes()) 
			{ 
				$attributes2 = array();
				$attrs = $image->attributes; 
				
				foreach ($attrs as $i => $attr)
				{
					$attributes2[$attr->name] = $attr->value; 
				}
			}
			
			if (0)	
			{

				$html .= '<div style="position:absolute;' . 'border:1px solid rgba(200, 200, 200, 0.5);' . 'left:' .  $attributes2['x'] . ';'
					. 'top:' . $attributes2['y'] . ';'
					. 'width:' . $attributes2['width'] . ';'
					. 'height:' . $attributes2['height'] . ';">';
				$html .= '<img src="' . $attributes2['href'] . '"'
					. ' width="' . $attributes2['width'] . '"'
					. ' height="' . $attributes2['height'] . '"/>';
				$html .= '</div>';
			}
			
			// ignore block x=0, y=0 as this is the whole page(?)
			if (($attributes2['x'] != 0) && ($attributes2['y'] != 0))
			{
			
				// save
				$image_obj = new stdclass;
				$image_obj->bbox = new BBox(
				$attributes2['x'], 
				$attributes2['y'],
				$attributes2['x'] + $attributes2['width'],
				$attributes2['y'] + $attributes2['height']
				);
			
				$image_obj->href = $attributes2['href'];
			
				$page->images[] = $image_obj;
			}		
		}
	}
	
	// Get blocks	
	$line_counter = 0; // global line counter
	$blocks = $xpath->query ('//BLOCK');
	foreach($blocks as $block)
	{
		$b = new stdclass;
		$b->bbox = new BBox($page->width, $page->height, 0, 0); 
		$b->tokens = array();
		
		// Get lines of text
		$lines = $xpath->query ('TEXT', $block);
		
		foreach($lines as $line)
		{
			// coordinates
			if ($line->hasAttributes()) 
			{ 
				$attributes2 = array();
				$attrs = $line->attributes; 
		
				foreach ($attrs as $i => $attr)
				{
					$attributes2[$attr->name] = $attr->value; 
				}
			}
	
			$text = new stdclass;
	
			$text->id = $line_counter++;
	
			$text->bbox = new BBox(
				$attributes2['x'], 
				$attributes2['y'],
				$attributes2['x'] + $attributes2['width'],
				$attributes2['y'] + $attributes2['height']
				);

		
			$b->bbox->merge($text->bbox);
	
			// text	
			$text->tokens = array();

			$nc = $xpath->query ('TOKEN', $line);
				
			foreach($nc as $n)
			{
				// coordinates
				if ($n->hasAttributes()) 
				{ 
					$attributes2 = array();
					$attrs = $n->attributes; 
			
					foreach ($attrs as $i => $attr)
					{
						$attributes2[$attr->name] = $attr->value; 
					}
				}
		
				$token = new stdclass;
				$token->bold = $attributes2['bold'] == 'yes' ? true : false;
				$token->italic = $attributes2['italic'] == 'yes' ? true : false;
				$token->font_size = $attributes2['font-size'];
				$token->font_name = $attributes2['font-name'];			
				$token->text = $n->firstChild->nodeValue;
		
				// Store font size for this token
				if (!isset($page->font_size[$token->font_size]))
				{
					$page->font_size[$token->font_size] = 0;
				}
				$page->font_size[$token->font_size]++;

		
				$text->tokens[] = $token;	
				$b->tokens[] = $token->text;			
			}	

			$page->lines[] = $text;			
			
		}
		
		$page->text_bbox->merge($b->bbox);
		
		$page->blocks[] = $b;
	}
	
	// test of clustering lines of text into blocks,
	// use this if "BLOCKS" are lines of text, as can occur if PDF is OCR not born-digital
	if (0)
	{
		// $text_blocks is a list of blocks, each block is a list of line ids that belong to that block
		$text_blocks = find_blocks($page);
		

		// get block info 
		$blocks = array();
		if (count($text_blocks) > 0)
		{
			foreach ($text_blocks as $k => $text_block)
			{
				$block = get_block_info($page, $text_block);
				$block->id = $k;			
				$blocks[] = $block;
			}
		}	
		
		//print_r($blocks);
		
		$page->blocks = $blocks;
		
	}

	if (0)
	{
		echo '<pre>';
		print_r($page);
		echo '</pre>';
	}	
	
	
	// Dump blocks as JSON-style object
	$export = new stdclass;
	
	// page size
	$export->x = 0;
	$export->y = 0;
	$export->w = $page->width;
	$export->h = $page->height;
	
	// text bounding box
	$export->text_area = new stdclass;
	$export->text_area->x = $page->text_bbox->minx;
	$export->text_area->y = $page->text_bbox->miny;
	$export->text_area->w = $page->text_bbox->maxx - $page->text_bbox->minx;
	$export->text_area->h = $page->text_bbox->maxy - $page->text_bbox->miny;
	
	// blocks
	$export->blocks = array();
	
	// text blocks
	foreach ($page->blocks as $block)
	{
		$b = new stdclass;
		
		$b->type = 'text';
		
		$b->x = $block->bbox->minx;
		$b->y = $block->bbox->miny;
		$b->w = $block->bbox->maxx - $block->bbox->minx;
		$b->h = $block->bbox->maxy - $block->bbox->miny;
		
		$b->text = join(' ', $block->tokens);
		
		$export->blocks[] = $b;
	}
	
	// image blocks
	foreach ($page->images as $block)
	{
		$b = new stdclass;
		
		$b->type = 'image';
		
		$b->x = $block->bbox->minx;
		$b->y = $block->bbox->miny;
		$b->w = $block->bbox->maxx - $block->bbox->minx;
		$b->h = $block->bbox->maxy - $block->bbox->miny;
		
		$b->href = $block->href;
		
		if ($filter_overlap)
		{
			// Delete any text blocks that overlap this image (e.g., figure labels)
			$r = new Rectangle($b->x, $b->y, $b->w, $b->h);
		
			$blocks_to_delete = array();
			foreach ($export->blocks as $k => $v)
			{
				if ($v->type == 'text')
				{
					$tr = new Rectangle($v->x, $v->y, $v->w, $v->h);
				
					if ($r->intersectsRect($tr))
					{
						$blocks_to_delete[] = $k;
					}				
				}
			}
		
			//print_r($blocks_to_delete);
		
			foreach ($blocks_to_delete as $k)
			{
				unset($export->blocks[$k]);
			}
		}
				
		$export->blocks[] = $b;
	}
	
	// unset doesn't reset the array keys, so for loops may fail, hence we reindex the block list 
	$temp_blocks = array();
	foreach ($export->blocks as $k => $v)
	{
		$temp_blocks[] = $v;
	}
	$export->blocks = $temp_blocks;
	
	/*
	foreach ($export->blocks as $k => $v)
	{
		echo $k . "\n";
	}
	echo "----\n";
	*/
	
	//echo json_encode($export, JSON_PRETTY_PRINT);

	return $export;
	
}

// test
if (0)
{
	$json = pdf_blocks('cache/565940.xml_data/pageNum-8.xml');
	
	echo json_encode($json, JSON_PRETTY_PRINT);
	
	
}



?>