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
- Acta Phytotaxonomica et Geobotanica 1346-7565
- Arnaldoa 1815-8242
- Austrobaileya 0155-4131
- Blumea 0006-5196
- Botanical Studies (Taipei) 1817-406
- Garden’s Bulletin Singapore 0374-7859
- Lankesteriana 1409-3871
- Muelleria 0077-1813
- Philippine Journal of Science 0031-7683
- Proceedings of The California Academy of Sciences 0068-547X
- Raffles Bulletin of Zoology 0217-2445
- South African Journal of Botany (Elsevier open access) 0254-6299
- Taiwania 0372-333X (issues using curl to get file, need user-agent)
- Teleopea 0312-9764

### Fails

- Annales Botanici Fennici 0003-3847 (some captions are to the side of the image, need to be clever about this).
- Bothalia (complex multipart pictures) 0006-8241
- Bull. Bot. Res., Harbin (Chinese text, images not extracted by pdftoxml) 1673-5102
- Candollea 0373-2967 (figure captions small and left aligned, current code doesn’t find figs 3 and 4)
- Contributions To Natural History (1660-9972) all seem to be combined text plus images
- Nordic Journal 0107-055X (early issue) PDF is OCR’d so need to extract images from page. 
- Plants 2223-7747 (PDF seems complex, but we also have JATS XML)
- Smithsonian Contributions to Zoology 0081-0282 DOI:10.5479/si.00810282.636.1 got most but some figures are rotated with captions rotated as well :(
- Thai Forest Bulletin (Botany) 0495-3843 (Composite figure causes problems, e.g. S0495-38432016004400128 )






