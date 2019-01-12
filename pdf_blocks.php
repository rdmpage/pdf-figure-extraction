<?php

// pdf blocks

//require_once(dirname(__FILE__) . '/components.php');
require_once(dirname(__FILE__) . '/shared/spatial.php');


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
	
	// Get blocks	
	$blocks = $xpath->query ('//BLOCK');
	foreach($blocks as $block)
	{
		$b = new stdclass;
		$b->bbox = new BBox($page->width, $page->height, 0, 0); 
		$b->tokens = array();
		
		// Get lines of text
		$lines = $xpath->query ('TEXT', $block);
		$line_counter = 0;
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
	process('cache/arac-30-02-219.xml_data/pageNum-3.xml');
}



?>