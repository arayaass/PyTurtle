from docx import Document
from docx.shared import Cm,Pt,RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT, WD_TAB_LEADER
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
import re
src=r'C:\Users\Raya\Downloads\Modul-PyTurtle-Petualangan-Shiha1.docx'
out=r'C:\Users\Raya\Documents\TUGAS SEMS 5\PPL\PyTurtle-PHP\output\modul-rapi\Modul-PyTurtle-Margin-3344-Rapi.docx'
d=Document(src)
for s in d.sections:
 s.top_margin=Cm(3);s.right_margin=Cm(3);s.bottom_margin=Cm(4);s.left_margin=Cm(4)
 s.header_distance=Cm(1.3);s.footer_distance=Cm(1.5)
width=Cm(14)
for name in ['Normal','Title','Subtitle','Heading 1','Heading 2','Heading 3','Caption']:
 st=d.styles[name];st.font.name='Calibri';st.font.color.rgb=RGBColor(0,0,0)
 if name=='Normal':
  st.font.size=Pt(11);st.paragraph_format.line_spacing=1.08;st.paragraph_format.space_after=Pt(5)
 elif name.startswith('Heading'):
  st.font.size=Pt(17 if name=='Heading 1' else 12)
  st.paragraph_format.keep_with_next=True;st.paragraph_format.space_before=Pt(10);st.paragraph_format.space_after=Pt(5)
 elif name=='Title':st.font.size=Pt(25)
 elif name=='Subtitle':st.font.size=Pt(16)
 elif name=='Caption':st.font.size=Pt(9)
bookmarks={}
for i,p in enumerate(d.paragraphs):
 f=p.paragraph_format;f.widow_control=True
 for r in p.runs:
  if '\ufffd' in r.text:r.text=r.text.replace('\ufffd ', '\u2022 ').replace('90\ufffd','90\u00b0')
 if p.style.name.startswith('Heading') or p.style.name in ['Title','Subtitle']:
  for r in p.runs:r.font.color.rgb=RGBColor(0,0,0)
 if p.style.name=='Heading 1' or p.style.name=='Caption':
  key='loc'+str(i);bookmarks[p.text]=key
  start=OxmlElement('w:bookmarkStart');start.set(qn('w:id'),str(i+100));start.set(qn('w:name'),key)
  end=OxmlElement('w:bookmarkEnd');end.set(qn('w:id'),str(i+100));p._p.insert(1,start);p._p.append(end)
 iscode=any(r.font.name=='DejaVu Sans Mono' for r in p.runs)
 if iscode:
  f.line_spacing=1;f.space_before=Pt(0);f.space_after=Pt(1);f.left_indent=Cm(.25)
  for r in p.runs:r.font.name='Consolas';r.font.size=Pt(10)
  if not p.text:f.line_spacing=Pt(4)
 elif p.style.name=='Normal' and len(p.text)>140 and '\t' not in p.text:
  p.alignment=WD_ALIGN_PARAGRAPH.JUSTIFY
 if re.fullmatch(r'\.{20,}',p.text):
  p.text='';f.tab_stops.clear_all();f.tab_stops.add_tab_stop(width,WD_TAB_ALIGNMENT.RIGHT,WD_TAB_LEADER.DOTS);p.add_run('\t');f.space_after=Pt(3)
 if p._p.xpath('.//w:drawing'):
  p.alignment=WD_ALIGN_PARAGRAPH.CENTER;f.keep_with_next=True
 if p.style.name=='Caption':p.alignment=WD_ALIGN_PARAGRAPH.CENTER;f.keep_with_next=False
for p in d.paragraphs:
 if '\t' in p.text and p.text.split('\t')[0] in bookmarks:
  label=p.text.split('\t')[0];key=bookmarks[label];p.clear();p.add_run(label+'\t')
  field=OxmlElement('w:fldSimple');field.set(qn('w:instr'),' PAGEREF '+key+' \\h ');field.set(qn('w:dirty'),'true')
  p._p.append(field);p.paragraph_format.tab_stops.clear_all();p.paragraph_format.tab_stops.add_tab_stop(width,WD_TAB_ALIGNMENT.RIGHT,WD_TAB_LEADER.DOTS)
  p.paragraph_format.space_after=Pt(4)
  for r in p.runs:r.font.size=Pt(10)
for shape in d.inline_shapes:
 if shape.width>width:
  ratio=width/shape.width;shape.height=int(shape.height*ratio);shape.width=width
for t in d.tables:
 t.autofit=False;t.alignment=WD_TABLE_ALIGNMENT.CENTER
 old=[c.width or width/len(t.columns) for c in t.columns];total=sum(old);sizes=[int(width*v/total) for v in old]
 for c,w in zip(t.columns,sizes):c.width=w
 for ri,row in enumerate(t.rows):
  row.height=None
  trpr=row._tr.get_or_add_trPr();no=OxmlElement('w:cantSplit');trpr.append(no)
  if ri==0:trpr.append(OxmlElement('w:tblHeader'))
  for c,w in zip(row.cells,sizes):
   c.width=w;c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
   for p in c.paragraphs:
    p.paragraph_format.space_after=Pt(4);p.paragraph_format.space_before=Pt(4);p.paragraph_format.line_spacing=1.05
    for r in p.runs:r.font.size=Pt(10);r.text=r.text.replace('90\ufffd','90\u00b0')
   cp=c._tc.get_or_add_tcPr();m=OxmlElement('w:tcMar')
   for side in ['top','left','bottom','right']:
    e=OxmlElement('w:'+side);e.set(qn('w:w'),'85');e.set(qn('w:type'),'dxa');m.append(e)
   cp.append(m)
 settings=d.settings.element
u=OxmlElement('w:updateFields');u.set(qn('w:val'),'true');d.settings.element.append(u)
d.save(out)
print(out)
check=Document(out)
print('Validated:',len(check.tables),'tables,',len(check.inline_shapes),'images; margins:',[round(getattr(check.sections[0],x).cm,2) for x in ['top_margin','right_margin','bottom_margin','left_margin']])

