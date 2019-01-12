# pdf-figure-extraction

Extract figures from born-digital PDFs and render in JATS XML


Assumes we have pdf2xml available (a MacOS X executable is provided here) and a RIS file for a reference that includes ISSN, volume, space, and a URL for the PDF.

Attempts to extract text and images from PDF and extract figures and associated captions, creating a simple JATS XML file summarising article metadata and including figures. This is rendered into HTML using a XSL stylesheet.

Next step would be processing JATS XML into JSON suitable for uploading to Zenodo and BLR.
