<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/shared/ris.php');
require_once (dirname(__FILE__) . '/pdf_blocks.php');
require_once (dirname(__FILE__) . '/get_figures.php');

//----------------------------------------------------------------------------------------
function jats_xml($reference, $pii, $figures)
{
	$doc = DOMImplementation::createDocument(null, '',
		DOMImplementation::createDocumentType("article", 
			"SYSTEM", 
			"jats-archiving-dtd-1.0/JATS-archivearticle1.dtd"));
	
	// http://stackoverflow.com/questions/8615422/php-xml-how-to-output-nice-format
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;	
	
	// root element is <records>
	$article = $doc->appendChild($doc->createElement('article'));
	
	$article->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
	
	$front = $article->appendChild($doc->createElement('front'));
	
	$journal_meta = $front->appendChild($doc->createElement('journal-meta'));
	$journal_title_group = $journal_meta->appendChild($doc->createElement('journal-title-group'));
	$journal_title = $journal_title_group->appendChild($doc->createElement('journal-title'));
	$journal_title->appendChild($doc->createTextNode($reference->secondary_title));
	
	if (isset($reference->issn))
	{
		$issn = $journal_meta->appendChild($doc->createElement('issn'));
		$issn->appendChild($doc->createTextNode($reference->issn));
	}
	
	$article_meta = $front->appendChild($doc->createElement('article-meta'));
		
	$article_id = $article_meta->appendChild($doc->createElement('article-id'));
	$article_id->setAttribute('pub-id-type', 'pii');
	$article_id->appendChild($doc->createTextNode($pii));	

	if (isset($reference->doi))
	{
		$article_id = $article_meta->appendChild($doc->createElement('article-id'));
		$article_id->setAttribute('pub-id-type', 'doi');
		$article_id->appendChild($doc->createTextNode($reference->doi));
	}
	
	$title_group = $article_meta->appendChild($doc->createElement('title-group'));
	$article_title = $title_group->appendChild($doc->createElement('article-title'));
	$article_title->appendChild($doc->createTextNode($reference->title));
	
	if (count($reference->authors) > 0)
	{
		$contrib_group = $article_meta->appendChild($doc->createElement('contrib-group'));
		
		foreach ($reference->authors as $author)
		{
			$contrib = $contrib_group->appendChild($doc->createElement('contrib'));
			$contrib->setAttribute('contrib-type', 'author');
			
			$name = $contrib->appendChild($doc->createElement('name'));
			
			// simple string for author name	
			$stringname = $name->appendChild($doc->createElement('string-name'));
			$stringname->appendChild($doc->createTextNode($author));		
			
			/*
			// parsed author name
			$name = $contrib->appendChild($doc->createElement('name'));
			$surname = $name->appendChild($doc->createElement('surname'));
			$surname->appendChild($doc->createTextNode($author->lastname));
			if (isset($author->forename))
			{
				$given_name = $name->appendChild($doc->createElement('given-names'));
				$given_name->appendChild($doc->createTextNode($author->forename));
			}
			*/
		}
	}
	
	if (isset($reference->date))
	{
		$pub_date = $article_meta->appendChild($doc->createElement('pub-date'));
		$pub_date->setAttribute('pub-type', 'ppub');
		
		if (preg_match('/(?<year>[0-9]{4})-(?<month>\d+)-(?<day>\d+)/', $reference->date, $m))
		{
			if ($m['day'] != '00')
			{
				$day = $pub_date->appendChild($doc->createElement('day'));
				$day->appendChild($doc->createTextNode(str_replace('0','', $m['day'])));			
			}
			
			if ($m['month'] != '00')
			{
				$month = $pub_date->appendChild($doc->createElement('month'));
				$month->appendChild($doc->createTextNode(str_replace('0','', $m['month'])));
			}
		
			$year = $pub_date->appendChild($doc->createElement('year'));
			$year->appendChild($doc->createTextNode($m['year']));
		}	
	}
	else
	{
		$pub_date = $article_meta->appendChild($doc->createElement('pub-date'));
		$pub_date->setAttribute('pub-type', 'ppub');
		$year = $pub_date->appendChild($doc->createElement('year'));
		$year->appendChild($doc->createTextNode($reference->year));
	}
	
	if (isset($reference->volume))
	{
		$volume = $article_meta->appendChild($doc->createElement('volume'));
		$volume->appendChild($doc->createTextNode($reference->volume));
	}
	
	if (isset($reference->issue))
	{
		$issue = $article_meta->appendChild($doc->createElement('issue'));
		$issue->appendChild($doc->createTextNode($reference->issue));
	}	
	
	if (isset($reference->spage))
	{
		$fpage = $article_meta->appendChild($doc->createElement('fpage'));
		$fpage->appendChild($doc->createTextNode($reference->spage));		
	}
	
	if (isset($reference->epage))
	{
		$fpage = $article_meta->appendChild($doc->createElement('lpage'));
		$fpage->appendChild($doc->createTextNode($reference->epage));		
	}
		
	if (isset($reference->abstract))
	{
		$abstract = $article_meta->appendChild($doc->createElement('abstract'));
		$p = $abstract->appendChild($doc->createElement('p'));
		$p->appendChild($doc->createTextNode($reference->abstract));		
	}
	
	$body = $article->appendChild($doc->createElement('body'));
	
	/*
      <fig id="F1" position="float" orientation="portrait">
        <label>Figure 1.</label>
        <caption>
          <p>Main localities sampled during Lengguru 2014 expedition and distribution of the four new species in West Papua, Indonesia.</p>
        </caption>
        <graphic xlink:href="phytokeys-61-e7590-g001.jpg" position="float" orientation="portrait" xlink:type="simple" id="oo_79795.jpg"/>
      </fig>
	*/	
	
	// Extracted figures
	$count = 1;
	foreach ($figures as $figure)
	{
		$fig = $body->appendChild($doc->createElement('fig'));

		$fig->setAttribute('id', 'F' . $count++);

		$fig->setAttribute('position', 'float');
		
		if (isset($figure->label))
		{		
			$label = $fig->appendChild($doc->createElement('label'));
			$label->appendChild($doc->createTextNode($figure->label));	
		}
		
		if (isset($figure->caption))
		{		
			$caption = $fig->appendChild($doc->createElement('caption'));
			$caption->appendChild($doc->createTextNode($figure->caption));	
		}
		
		$graphic = $fig->appendChild($doc->createElement('graphic'));
		$graphic->setAttribute('position', 'float');
		$graphic->setAttribute('xlink:href', $figure->filename);
	}
	
	
	/*
	$supplementary_material = $body->appendChild($doc->createElement('supplementary-material'));
	$supplementary_material->setAttribute('content-type', 'scanned-pages');
	
	$n = count($reference->bhl_pages);
	for($i = 0; $i < $n; $i++)
	{
		$graphic = $supplementary_material->appendChild($doc->createElement('graphic'));
		$graphic->setAttribute('xlink:href', 'bw_images/' . $reference->bhl_pages[$i] . '.png');
		$graphic->setAttribute('xlink:role', $reference->bhl_pages[$i]);
		$graphic->setAttribute('xlink:title', 'scanned-page');
	}
	*/
	
	return $doc->saveXML();
}

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
function xml_to_json($xml_dir, $filter_overlap = false)
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

		$obj = pdf_blocks($xml_dir . '/' . $xml_filename,  $filter_overlap); 

		$json_filename = $page_name . '.json';
	
		file_put_contents($config['output_dir'] . '/' . $json_filename, json_encode($obj, JSON_PRETTY_PRINT));
	
		$json_files[] = $json_filename;
	}
	
	return $json_files;
}

//----------------------------------------------------------------------------------------
function get_pdf_filename($pdf)
{
	$filename = '';
	
	// http://bbr.nefu.edu.cn/CN/article/downloadArticleFile.do?attachType=PDF&id=3628	
	if ($filename == '')
	{
		if (preg_match('/&(amp;)?id=(?<id>\d+)$/', $pdf, $m))
		{
			$filename = $m['id'] . '.pdf';
		}
	}	
	
	// http://plantnet.rbgsyd.nsw.gov.au/emuwebnswlive/objects/common/webmedia.php?irn=79359&reftable=ebibliography
	if ($filename == '')
	{
		if (preg_match('/irn=(?<id>\d+)/', $pdf, $m))
		{
			$filename = $m['id'] . '.pdf';
		}
	}	

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
// Generate a PII-like standard name for the article PDF
function article_pii ($reference)
{
	$pii = 'S' . $reference->issn 
		. $reference->year 
		. str_pad($reference->volume, 4, '0', STR_PAD_LEFT) 
		. str_pad($reference->spage, 5, '0', STR_PAD_LEFT); 

	return $pii;
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
			$command = "curl "
				. "--user-agent \"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0\""
				. "--location '" . $reference->pdf . "' > '" . $article_pdf_filename . "'";
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
		
		// to do: some PDFs need to have extraneous bits of text under or
		// over images filtered, for others filtering breaks figure
		// extraction
		
		$filter = false;
		
		switch ($reference->issn)
		{
			case '0068-547X':
				$filter = true;
				break;				
		
			default:
				$filter = false;
				break;				
		}
		
		
		$json_files = xml_to_json($xml_dir, $filter);
		
		// Block relationships
		
		echo "Getting figures\n";
		
		// to do: include style info to help inrease chance of getting a match
		$centered = false;
		
		switch ($reference->issn)
		{
			case '0254-6299':
				$centered = true;
				break;				
		
			default:
				$centered = false;
				break;				
		}
		
		$figures = get_figures($json_files, $centered);
		
		echo "Done getting figures\n";
		
		// Export to JATS XML, use consistent naming for article
		
		print_r($reference);
				
		print_r($figures);
		
		// Identifier for article
		$pii = article_pii($reference);
		
		$xml = jats_xml($reference, $pii, $figures);		
		
		// Copy any extracted figures
		if (isset($reference->issn))
		{
			$dir = dirname(__FILE__) . '/' . $reference->issn;
			if (!file_exists($dir))
			{
				$oldumask = umask(0); 
				mkdir($dir, 0777);
				umask($oldumask);
			}
			
			$dir = $dir . '/' . $pii;
			if (!file_exists($dir))
			{
				$oldumask = umask(0); 
				mkdir($dir, 0777);
				umask($oldumask);
			}
			
			$xml_filename = $dir . '/' . $pii . '.xml';
			$html_filename = $dir . '/' . $pii . '.html';
			
			// Export JATS XML
			file_put_contents($xml_filename, $xml);
			
			// Generate HTML
			
   			$xslDoc = new DOMDocument();
   			$xslDoc->load(dirname(__FILE__) . '/stylesheets/jats.xsl');
			
  			$xslt = new XSLTProcessor();
   			$xslt->importStylesheet($xslDoc);
   			
   			$html = $xslt->transformToXml(new SimpleXMLElement($xml));
			file_put_contents($html_filename, $html);
			
			// Copy PDF			
			$source = $article_pdf_filename;
			$desination = $dir . '/' . $pii . '.pdf';
			copy($source, $desination);
			
			// Copy figures
			foreach ($figures as $figure)
			{
				$source = $config['cache_dir'] . '/' . $figure->href;
				$desination = $dir . '/' . $figure->filename;
				
				echo $source . "\n";
				echo $desination . "\n";
				
				
				copy($source, $desination);
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