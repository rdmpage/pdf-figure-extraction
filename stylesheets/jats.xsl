<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform' xmlns:xlink="http://www.w3.org/1999/xlink">

<xsl:output method='html' version='1.0' encoding='utf-8' indent='yes'/>

<xsl:template match="/">
	<body style="font-family:sans-serif;padding:20px;">
	<div>
	<div>
	
	<p style="font-size:80%">
		<xsl:value-of select="//journal-meta/journal-title-group/journal-title" />
		<xsl:text> </xsl:text>
		
		<xsl:if test="//article-meta/pub-date/day">
			<xsl:value-of select="//article-meta/pub-date/day" />
			<xsl:text> </xsl:text>
		</xsl:if>
		
		<xsl:if test="//article-meta/pub-date/month">
			<xsl:choose>
				<xsl:when test="//article-meta/pub-date/month = 1">
					<xsl:text>January</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 2">
					<xsl:text>February</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 3">
					<xsl:text>March</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 4">
					<xsl:text>April</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 5">
					<xsl:text>May</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 6">
					<xsl:text>June</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 7">
					<xsl:text>July</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 8">
					<xsl:text>August</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 9">
					<xsl:text>September</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 10">
					<xsl:text>October</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 11">
					<xsl:text>November</xsl:text>
				</xsl:when>
				<xsl:when test="//article-meta/pub-date/month = 12">
					<xsl:text>December</xsl:text>
				</xsl:when>
			</xsl:choose>
			<xsl:text> </xsl:text>
		</xsl:if>
		
		<xsl:value-of select="//article-meta/pub-date/year" />
		<xsl:text> </xsl:text>
		<xsl:value-of select="//article-meta/volume" />
		<xsl:if test="//article-meta/issue">
			<xsl:text>(</xsl:text><xsl:value-of select="//article-meta/issue" /><xsl:text>)</xsl:text>
		</xsl:if>
		<xsl:text>: </xsl:text>
		<xsl:value-of select="//article-meta/fpage" />
		<xsl:text>-</xsl:text>
		<xsl:value-of select="//article-meta/lpage" />

	</p>	
	<h1>
		<xsl:value-of select="//article-title" />
	</h1>
	
	<ul>
		<xsl:apply-templates select="//article-id"/>
	</ul>
	
	<div>
		<xsl:apply-templates select="//contrib-group/contrib[@contrib-type='author']/name" />
	</div>
	
	<h2>Abstract</h2>
	<xsl:value-of select="//abstract" />
	<h2>Full text</h2>
	<p>
	<xsl:text>
	Full text is available as a scanned copy of the original print version. 
	Get a printable copy (PDF file) of the 
	</xsl:text>
	<a>
		<xsl:attribute name="href">
			<xsl:value-of select="//article-id[@pub-id-type='pii']" />
			<xsl:text>.pdf</xsl:text>
			</xsl:attribute>
		<xsl:text>complete article</xsl:text>
	</a>
	<xsl:text>, or click on a page image below to browse page by page. 
	Links are also available for 
	</xsl:text>
	<a href="#reference-sec">Selected References</a>.	
	</p>
	<div>
		<xsl:apply-templates select="//supplementary-material/graphic"/>
	</div>
	<div style="clear:both;" />	
	
	<h2>Images in this article</h2>

	<xsl:apply-templates select="//fig"/>
	
	<h2 id="reference-sec">Selected references</h2>
	<xsl:apply-templates select="//back"/>

	</div>
	</div>
	</body>
</xsl:template>

<xsl:template match="article-id">
	<xsl:choose>
		<xsl:when test="@pub-id-type='doi'">
			<li>
				<xsl:text>DOI:</xsl:text>
				<a>
					<xsl:attribute name="href">
						<xsl:text>https://doi.org/</xsl:text>
						<xsl:value-of select="." />
					</xsl:attribute>				
					<xsl:value-of select="." />
				</a>
			</li>
		</xsl:when>
		<xsl:when test="@pub-id-type='pmid'">
			<li>
				<xsl:text>PMID:</xsl:text>
				<xsl:value-of select="." />
			</li>
		</xsl:when>
		<xsl:when test="@pub-id-type='pmc'">
			<li>
				<xsl:text>PMC</xsl:text>
				<xsl:value-of select="." />
			</li>
		</xsl:when>
	
		<xsl:otherwise />
	</xsl:choose>
</xsl:template>	

<xsl:template match="name">
        <xsl:if test="position() != 1">
            <xsl:text>, </xsl:text>
        </xsl:if>
        
        <xsl:if test="string-name">
        	<xsl:value-of select="string-name" />
        </xsl:if>
        
        <xsl:if test="given-names">
			<xsl:value-of select="given-names" />
			<xsl:text> </xsl:text>
			<xsl:value-of select="surname" />
		</xsl:if>
</xsl:template>


<xsl:template match="fig">
	<figure>
	<xsl:apply-templates select="graphic"/>
	
	<figcaption>
		<b>
		<xsl:value-of select="label" />
		</b>
		<xsl:text> </xsl:text> 
		<xsl:value-of select="caption" /> 
	</figcaption>
	
	</figure>
	
</xsl:template>

<xsl:template match="graphic">
	<div>
		<img style="width:300px;border:1px solid rgb(192,192,192);padding:5px;">
			<xsl:attribute name="src">			
			<xsl:value-of select="@xlink:href" /> 
			</xsl:attribute>
		</img>
	</div>
</xsl:template>



<xsl:template match="back">
	<xsl:apply-templates select="ref-list"/>
</xsl:template>

<xsl:template match="ref-list">
	<ul>
		<xsl:apply-templates select="ref" />
	</ul>
</xsl:template>

<xsl:template match="ref">
	<li>
		<xsl:apply-templates select="mixed-citation" />
	</li>
</xsl:template>

<xsl:template match="mixed-citation">
		<xsl:value-of select="." />
		<xsl:apply-templates select="ext-link" />
</xsl:template>

<xsl:template match="ext-link">
	<xsl:variable name="uri" select="@xlink:href" />
	<xsl:if test="contains($uri, 'http://dx.doi.org/')">
		<xsl:text> DOI: </xsl:text>
		<a>
			<xsl:attribute name="href">
				<xsl:value-of select="$uri" />
			</xsl:attribute>
			<xsl:value-of select="substring-after($uri, 'http://dx.doi.org/')" />
		</a>
	</xsl:if>
</xsl:template>


</xsl:stylesheet>