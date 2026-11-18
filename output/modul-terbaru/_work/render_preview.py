from pathlib import Path
import pypdfium2 as pdfium
from PIL import Image, ImageDraw
import json, sys
root=Path(__file__).resolve().parent
name=sys.argv[1] if len(sys.argv)>1 else 'final'
pdf=pdfium.PdfDocument(root/f'{name}.pdf')
out=root/f'{name}-render';out.mkdir(exist_ok=True)
pages=[]
for i in range(len(pdf)):
    page=pdf[i];im=page.render(scale=1.5).to_pil();im.save(out/f'page-{i+1}.png')
    text=page.get_textpage().get_text_range();pages.append({'page':i+1,'length':len(text),'text':text})
    im.close()
for start in range(0,len(pdf),8):
    sheet=Image.new('RGB',(4*340,2*490),'#dddddd');draw=ImageDraw.Draw(sheet)
    for j in range(start,min(start+8,len(pdf))):
        im=Image.open(out/f'page-{j+1}.png');im.thumbnail((320,455));x=(j-start)%4*340;y=(j-start)//4*490
        sheet.paste(im,(x,y+24));draw.text((x+6,y+5),f'Page {j+1}',fill='black')
    sheet.save(out/f'contact-{start//8+1}.png')
(out/'pages.json').write_text(json.dumps(pages,ensure_ascii=False,indent=2),encoding='utf-8')
print('Pages',len(pdf));print('Sparse',[(p['page'],p['length'],p['text'][:100]) for p in pages if p['length']<150])
print('Errors',[(p['page'],p['text'][:100]) for p in pages if 'Error!' in p['text'] or 'Kesalahan!' in p['text']])
