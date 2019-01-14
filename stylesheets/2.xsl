<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

<xsl:output method='html' version='1.0' encoding='utf-8' indent='yes'/>

<xsl:variable name="scale" select="800 div //PAGE/@width" />
<xsl:param name="image" />


<xsl:template match="/">
	<xsl:apply-templates select="//PAGE" />
</xsl:template>

<xsl:template match="//PAGE">
	<div>

	<xsl:attribute name="style">
		<xsl:variable name="height" select="@height" />
		<xsl:variable name="width" select="@width" />

		<xsl:text>position:relative;</xsl:text>
		<!--<xsl:text>border:1px solid rgb(128,128,128);</xsl:text>-->
		<xsl:text>width:</xsl:text><xsl:value-of select="$width * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>height:</xsl:text><xsl:value-of select="$height * $scale" /><xsl:text>px;</xsl:text>
	</xsl:attribute>

<xsl:comment>Scanned image</xsl:comment>
<img>
	<xsl:attribute name="src">
		<xsl:value-of select="$image" />
	</xsl:attribute> 
	<xsl:attribute name="width">
		<xsl:value-of select="@width * $scale" />
	</xsl:attribute>
	<xsl:attribute name="height">
		<xsl:value-of select="@height * $scale" />
	</xsl:attribute> 
</img>

	<xsl:apply-templates select="//IMAGE" />
	<xsl:apply-templates select="//TEXT" />
	

	</div>

</xsl:template>

<xsl:template match="//IMAGE">
<div>
	<xsl:attribute name="style">
		<xsl:text>position:absolute;</xsl:text>
		<xsl:text>left:</xsl:text><xsl:value-of select="@x * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>top:</xsl:text><xsl:value-of select="@y * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>width:</xsl:text><xsl:value-of select="@width * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>height:</xsl:text><xsl:value-of select="@height * $scale" /><xsl:text>px;</xsl:text>

	<xsl:text>border:1px solid black;</xsl:text>
	</xsl:attribute>

<!--
<img>
	<xsl:attribute name="src">
		<xsl:value-of select="@href" />
	</xsl:attribute> 
	<xsl:attribute name="width">
		<xsl:value-of select="@width * $scale" />
	</xsl:attribute>
	<xsl:attribute name="height">
		<xsl:value-of select="@height * $scale" />
	</xsl:attribute> 
 
</img>
-->

</div>
</xsl:template>
<xsl:template match="//TEXT">

	<div>
	<xsl:attribute name="id">
		<xsl:value-of select="position()"/>
	</xsl:attribute>


	<xsl:attribute name="style">
		<xsl:text>position:absolute;</xsl:text>
		<xsl:text>left:</xsl:text><xsl:value-of select="@x * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>top:</xsl:text><xsl:value-of select="@y * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>width:</xsl:text><xsl:value-of select="@width * $scale" /><xsl:text>px;</xsl:text>
		<xsl:text>height:</xsl:text><xsl:value-of select="@height * $scale" /><xsl:text>px;</xsl:text>

	<xsl:text>border:1px solid rgb(128,128,128);</xsl:text>

	<!-- font-->
	<!--
	<xsl:text>font-size:</xsl:text><xsl:value-of select="@font-size * $scale" /><xsl:text>px;</xsl:text>

	<xsl:if test="@italic='yes'">
		<xsl:text>font-style:italic;</xsl:text>
	</xsl:if>
	-->
	</xsl:attribute>

	<!-- actual text -->	
	<!-- <xsl:value-of select="." /> -->
	

	</div>
</xsl:template>




</xsl:stylesheet>