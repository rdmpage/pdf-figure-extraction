# pdf-figure-extraction

Extract figures from born-digital PDFs and render in JATS XML


Assumes we have pdf2xml available (a MacOS X executable is provided here) and a RIS file for a reference that includes ISSN, volume, space, and a URL for the PDF.

Attempts to extract text and images from PDF and extract figures and associated captions, creating a simple JATS XML file summarising article metadata and including figures. This is rendered into HTML using a XSL stylesheet.

Next step would be processing JATS XML into JSON suitable for uploading to Zenodo and BLR.

## Journals

Journal specific things:
- alignment of figure caption and image
- whether we need to filter out text that overlaps with image

### Works
- Arnaldoa 1815-8242
- Botanical Studies (Taipei) 1817-406X
- Lankesteriana 1409-3871
- Muelleria 0077-1813
- Proceedings of The California Academy of Sciences 0068-547X
- South African Journal of Botany (Elsevier open access) 0254-6299
- Taiwania 0372-333X (issues using curl to get file, need user-agent)
- Teleopea 0312-9764

### Fails

- Bothalia (complex multipart pictures) 0006-8241
- Bull. Bot. Res., Harbin (Chinese text, images not extracted by pdftoxml) 1673-5102




