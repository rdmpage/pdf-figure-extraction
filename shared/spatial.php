<?php

define('EPSILON', 0.000001);

global $svg;

// Lots of code from http://martin-thoma.com/how-to-check-if-two-line-segments-intersect/

//--------------------------------------------------------------------------------------------------
class Point
{
	var $x;
	var $y;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x=0, $y=0)
	{
		$this->x = $x;
		$this->y = $y;
	}
	
	//----------------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<circle cx="' . $this->x . '" cy="' . $this->y . '" r="5" style="fill:white;stroke:black"/>';
		return $svg;
	}
	
}


//--------------------------------------------------------------------------------------------------
function crossProduct($a, $b)
{
	return $a->x * $b->y - $b->x * $a->y;
}

//--------------------------------------------------------------------------------------------------
class Line
{
	var $x0;
	var $y0;
	var $x1;
	var $y1;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x0=0, $y0=0, $x1=0, $y1=0)
	{
		$this->x0 = $x0;
		$this->y0 = $y0;
		$this->x1 = $x1;
		$this->y1 = $y1;
	}

	//----------------------------------------------------------------------------------------------
	function fromPoints($pt1, $pt2)
	{
		$this->x0 = $pt1->x;
		$this->y0 = $pt1->y;
		$this->x1 = $pt2->x;
		$this->y1 = $pt2->y;
	}

	//----------------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<circle cx="' . $this->x0 . '" cy="' . $this->y0 . '" r="5" />';
		$svg .= '<line x1="' . $this->x0 . '" y1="' . $this->y0 . '" x2="' . $this->x1 . '" y2="' . $this->y1 . '" stroke="black" />';
		$svg .= '<circle cx="' . $this->x1 . '" cy="' . $this->y1 . '" r="5" />';
		return $svg;
	}
		
	//----------------------------------------------------------------------------------------------
	function isPointOnLine ($pt)
	{
		$a = new Point($this->x1 - $this->x0, $this->y1 - $this->y0);
		$b = new Point($pt->x - $this->x0, $pt->y - $this->y0);
		$r = crossProduct($a, $b);
		return abs($r) < EPSILON;
	}	

	//----------------------------------------------------------------------------------------------
	function isPointRightOfLine ($pt)
	{
		$a = new Point($this->x1 - $this->x0, $this->y1 - $this->y0);
		$b = new Point($pt->x - $this->x0, $pt->y - $this->y0);
		$r = crossProduct($a, $b);
		return $r < 0;
	}	

	//----------------------------------------------------------------------------------------------
	function lineSegmentTouchesOrCrossesLine ($otherLine)
	{
		$pt_b_first = new Point($otherLine->x0, $otherLine->y0);
		$pt_b_second = new Point($otherLine->x1, $otherLine->y1);
		
		return $this->isPointOnLine($pt_b_first)
			|| ($this->isPointRightOfLine($pt_b_first) xor $this->isPointRightOfLine($pt_b_second));
	}	

}

//--------------------------------------------------------------------------------------------------
class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	var $id;
	
	//----------------------------------------------------------------------------------------------
	function __construct($x=0, $y=0, $w=0, $h=0, $id=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
		$this->id = $id;
	}
	
	//----------------------------------------------------------------------------------------------
	function getArea()
	{
		return ($this->w * $this->h);
	}
			
	//----------------------------------------------------------------------------------------------
	function getCentre()
	{
		$centre = new Point($this->x + $this->w/2.0, $this->y + $this->h/2.0);
		return $centre;
	}
	
	//------------------------------------------------------------------------------------
	function getTopLeft()
	{
		$pt = new Point($this->x, $this->y);
		return $pt;
	}

	//------------------------------------------------------------------------------------
	function getTopRight()
	{
		$pt = new Point($this->x + $this->w, $this->y);
		return $pt;
	}
	
	//------------------------------------------------------------------------------------
	function getBottomLeft()
	{
		$pt = new Point($this->x, $this->y + $this->h);
		return $pt;
	}
	
	//------------------------------------------------------------------------------------
	function getBottomRight()
	{
		$pt = new Point($this->x + $this->w, $this->y + $this->h);
		return $pt;
	}
	
	//------------------------------------------------------------------------------------
	function createFromPoints($pt1, $pt2)
	{
		$this->x = min($pt1->x, $pt2->x);
		$this->y = min($pt1->y, $pt2->y);
		$this->w = abs($pt2->x - $pt1->x);
		$this->h = abs($pt2->y - $pt1->y);
	}
	
	//------------------------------------------------------------------------------------
	function lineBbox($line)
	{
		$this->x = min($line->x0, $line->x1);
		$this->y = min($line->y0, $line->y1);
		$this->w = abs($line->x1 - $line->x0);
		$this->h = abs($line->y1 - $line->y0);
	}
	
	//------------------------------------------------------------------------------------
	function intersectsLine($line)
	{
		global $svg;
		
		$intersects = false;
		
		$rect2 = new Rectangle();
		$rect2->lineBbox($line);

		if ($this->intersectsRect($rect2))
		{
			if (!$intersects)
			{
				// top
				$side = new Line($this->x, $this->y, $this->x + $this->w, $this->y);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);
				//if ($intersects) { echo "top\n"; $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// right
				$side = new Line($this->x + $this->w, $this->y, $this->x + $this->w, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);		
				//if ($intersects) { echo "right\n";  $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// bottom
				$side = new Line($this->x, $this->y + $this->h, $this->x + $this->w, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);	
				//if ($intersects) { echo "bottom\n";  $svg .= $side->toSvg(); }
			}
			if (!$intersects)
			{
				// left
				$side = new Line($this->x, $this->y, $this->x, $this->y + $this->h);
				$intersects = $line->lineSegmentTouchesOrCrossesLine($side);	
				//if ($intersects) { echo "left\n";  $svg .= $side->toSvg(); }
	
			}
		}			
		return $intersects;
	}
	
	
	//------------------------------------------------------------------------------------
	function intersectsRect($otherRect)
	{
		$intersects = 
			($this->x <= ($otherRect->x + $otherRect->w))
			&& (($this->x + $this->w) >= $otherRect->x)
			&& ($this->y <= ($otherRect->y + $otherRect->h))
			&& (($this->y + $this->h) >= $otherRect->y);
			
		return $intersects;
	}
	
	
	//------------------------------------------------------------------------------------
	// Return overlap of this rectabgle with $otherRect. If they don't intersect, return null
	function getOverlap($otherRect)
	{
		$overlap = null;
		
		if ($this->intersectsRect($otherRect))
		{
			$r1_topleft = $this->getTopLeft();
			$r2_topleft = $otherRect->getTopLeft();
	
			$topLeft = new Point(
				max($r1_topleft->x, $r2_topleft->x),
				max($r1_topleft->y, $r2_topleft->y)
				);
		
			$r1_bottomRight = $this->getBottomRight();
			$r2_bottomRight = $otherRect->getBottomRight();
	
			$bottomRight = new Point(
				min($r1_bottomRight->x, $r2_bottomRight->x),
				min($r1_bottomRight->y, $r2_bottomRight->y)
				);
	
			$overlap = new Rectangle();
			$overlap->createFromPoints($topLeft, $bottomRight);
			
			$overlap->id = "overlap";
		}
		return $overlap;
	}
	
	//------------------------------------------------------------------------------------
	// Given a bounding rectangle $r that completely encloses this rect, compute the four
	// candidate rects around this rect (that acts as the "pivot")
	// Breuel, T. M. (2002). Two Geometric Algorithms for Layout Analysis. 
	// Lecture Notes in Computer Science. Springer Science + Business Media. 
	// http://doi.org/10.1007/3-540-45869-7_23
	//
	function getCandidatesAroundPivot($r)
	{
		$candidates = array();
		
		$r0 = new Rectangle();
		$r0->createFromPoints(
			new Point($this->getBottomRight()->x, $r->getTopLeft()->y),
			$r->getBottomRight()
			);

		$candidates[] = $r0;

		$r1 = new Rectangle();
		$r1->createFromPoints(
			$r->getTopLeft(),		
			new Point($this->getTopLeft()->x, $r->getBottomRight()->y)
			);
			
		$candidates[] = $r1;	

		$r2 = new Rectangle();
		$r2->createFromPoints(
			new Point($r->getTopLeft()->x, $this->getBottomRight()->y),
			$r->getBottomRight()
			);
			
		$candidates[] = $r2;

		$r3 = new Rectangle();
		$r3->createFromPoints(
			$r->getTopLeft(),
			new Point($r->getBottomRight()->x, $this->getTopLeft()->y)
			);

		$candidates[] = $r3;

		return $candidates;
	}
  	

	//------------------------------------------------------------------------------------
	function toSvg()
	{
		$svg = '<rect id="' . $this->id . '" x="' . $this->x . '" y="' . $this->y . '" width="' . $this->w . '" height="' . $this->h . '"
  style="stroke:black;fill:rgb(' . rand(0,256) . ',' . rand(0,256) . ',' . rand(0,256) . ');fill-opacity:0.1;" />'; 
		return $svg;
	}
	
}

//----------------------------------------------------------------------------------------
function doLinesIntersect($line1, $line2)
{
	$rect1 = new Rectangle();
	$rect1->lineBbox($line1);
	
	$rect2 = new Rectangle();
	$rect2->lineBbox($line2);

	return $rect1->intersectsRect($rect2)
		&& 
		$line1->lineSegmentTouchesOrCrossesLine($line2)
		&& $line2->lineSegmentTouchesOrCrossesLine($line1);
}

//----------------------------------------------------------------------------------------
// Bounding box
class BBox
{
	var $minx;
	var $maxx;
	var $miny;
	var $maxy;
	
	function __construct($x1=0,$y1=0,$x2=0,$y2=0)
	{
		$this->minx = $x1;
		$this->miny = $y1;
		$this->maxx = $x2;
		$this->maxy = $y2;		
	}
	
	function merge($bbox)
	{
		if (
			($this->minx == 0)
			&& ($this->maxx == 0)
			&& ($this->miny == 0)
			&& ($this->maxy == 0)
			)
		{
			$this->minx = $bbox->minx;
			$this->maxx = $bbox->maxx;
			$this->miny = $bbox->miny;
			$this->maxy = $bbox->maxy;
		}
		else
		{
			$this->minx = min($this->minx, $bbox->minx);
			$this->maxx = max($this->maxx, $bbox->maxx);
			$this->miny = min($this->miny, $bbox->miny);
			$this->maxy = max($this->maxy, $bbox->maxy);
		}
	}
	
	function inflate($x, $y)
	{
		$this->minx -= $x;
		$this->maxx += $x;
		$this->miny -= $y;
		$this->maxy += $y;
	}
	
	function overlap($bbox)
	{
		// P1,3 = minx, miny P1,2 = this, P3,4 = bbox
		// P2,4 = maxx, maxy
		// ! ( P2.y < P3.y || P1.y > P4.y || P2.x < P3.x || P1.x > P4.x )
		if (
			$this->maxy < $bbox->miny
			|| $this->miny > $bbox->maxy
			|| $this->maxx < $bbox->minx
			|| $this->minx > $bbox->maxx
			)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	function toHtml($class = 'block-other')
	{
		$html =
			'<div class="' . $class . '" ' 
			. 'style="position:absolute;'
			. 'left:' . $this->minx . 'px;'
			. 'top:' . $this->miny . 'px;'
			. 'width:' . ($this->maxx - $this->minx) . 'px;'
			. 'height:' . ($this->maxy - $this->miny) . 'px;'
			//. 'border:1px solid black;'
			//. 'background-color:rgba(0,0,190, 0.3);'
			. '">'
			. '</div>';
		return $html;
			
	}
	
}


?>