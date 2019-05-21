<?php

/* Idea: Parse JATS-XML to extract info we want to upload to BLR in Zenodo */

$filename = '../journal/0372-333X/S0372-333X2015006000039/S0372-333X2015006000039.xml';

$xml = file_get_contents($filename);

$dom= new DOMDocument;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);

$xpath->registerNamespace("xmlns", 		"http://www.w3.org/1999/xlink");



// DOI	
$nodeCollection = $xpath->query ("//article-id[@pub-id-type='doi']");
foreach($nodeCollection as $node)
{
	echo $node->firstChild->nodeValue . "\n";
}


// Figures
/*
    <fig id="F1" position="float">
      <label>Fig. 1</label>
      <caption>Illustrations of Hoya rostellata Kidyoo. A: Flowering branch. B: Blooming flower, side view. C: Blooming flower, top view. D: Corona, bottom view. E: Calyx. F: Pollinarium. Drawn by Manit Kidyoo from M. Kidyoo 1590.</caption>
      <graphic position="float" xlink:href="image-2.png"/>
    </fig>
*/

$figs = $xpath->query ("//fig");
foreach($figs as $fig)
{
	echo "-----\n";
	$values = $xpath->query ("label", $fig);
	foreach($values as $value)
	{
		echo $value->firstChild->nodeValue . "\n";
	}

	$values = $xpath->query ("caption", $fig);
	foreach($values as $value)
	{
		echo $value->firstChild->nodeValue . "\n";
	}

	$values = $xpath->query ("graphic/@xlink:href", $fig);
	foreach($values as $value)
	{
		echo $value->firstChild->nodeValue . "\n";
	}
	
	
}	
	
	




?>
