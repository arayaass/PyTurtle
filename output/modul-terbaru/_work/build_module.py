from pathlib import Path
from copy import deepcopy
from zipfile import ZipFile, ZIP_DEFLATED
from lxml import etree as E
from PIL import Image, ImageDraw, ImageFont
import json, hashlib, math, re

ROOT=Path(__file__).resolve().parent
REPO=ROOT.parents[2]
SRC=REPO.parent/'Modul-PyTurtle-Petualangan-Shiha.docx'
OUT=ROOT.parent/'Modul-PyTurtle-Terbaru-2026.docx'
W='http://schemas.openxmlformats.org/wordprocessingml/2006/main'
R='http://schemas.openxmlformats.org/officeDocument/2006/relationships'
NS={'w':W,'r':R,'a':'http://schemas.openxmlformats.org/drawingml/2006/main','wp':'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing'}
def q(n): return '{'+W+'}'+n
with ZipFile(SRC) as z: parts={n:z.read(n) for n in z.namelist()}
doc=E.fromstring(parts['word/document.xml']); body=doc.find(q('body')); old=body.findall(q('p'))
rels=E.fromstring(parts['word/_rels/document.xml.rels'])
styles_xml=E.fromstring(parts['word/styles.xml'])
STYLE_IDS={s.find(q('name')).get(q('val')):s.get(q('styleId')) for s in styles_xml.findall(q('style')) if s.find(q('name')) is not None}
STYLE_IDS.update({f'Heading {i}':STYLE_IDS.get(f'heading {i}',f'Heading{i}') for i in range(1,4)})
STYLE_NAMES={v:k for k,v in STYLE_IDS.items()}
levels=json.loads((REPO/'resources/data/levels.json').read_text(encoding='utf-8-sig'))
def txt(el): return ''.join(el.itertext()) if False else ''.join(el.xpath('.//w:t/text()',namespaces=NS))
def p(text='',style='Normal'):
    el=E.Element(q('p')); pr=E.SubElement(el,q('pPr')); E.SubElement(pr,q('pStyle')).set(q('val'),STYLE_IDS.get(style,style))
    for i,line in enumerate(text.split('\n')):
        run=E.SubElement(el,q('r'))
        if i:E.SubElement(run,q('br'))
        t=E.SubElement(run,q('t'));t.set('{http://www.w3.org/XML/1998/namespace}space','preserve');t.text=line
    return el
def setp(i,text):
    el=old[i]
    for c in list(el):
        if c.tag!=q('pPr'):el.remove(c)
    for c in list(p(text))[1:]:el.append(c)
def replace_range(a,b,els):
    first=old[a];last=old[b];i=body.index(first);end=body.index(last)
    for c in list(body)[i:end]:body.remove(c)
    for j,el in enumerate(els):body.insert(i+j,el)
def insert_before(i,els):
    n=body.index(old[i])
    for j,el in enumerate(els):body.insert(n+j,el)
def lead(t):return p(t,'Report Lead')
def code(t):
    el=p(t,'Report Code');pr=el.find(q('pPr'));E.SubElement(pr,q('keepLines'));E.SubElement(pr,q('spacing'),{q('after'):'100'})
    return el
def h(t,n=2):return p(t,'Heading '+str(n))
def cap(t):return p(t,'Report Caption')
def bullets(items):return [p('• '+s,'Report List') for s in items]
def table(headers,rows,widths=None):
    template=body.findall(q('tbl'))[0]
    tb=deepcopy(template);n=len(headers)
    for row in list(tb.findall(q('tr'))):tb.remove(row)
    widths=widths or [int(7938/n)]*n
    grid=tb.find(q('tblGrid'))
    for x in list(grid):grid.remove(x)
    for width in widths:E.SubElement(grid,q('gridCol'),{q('w'):str(width)})
    prop=tb.find(q('tblPr')); borders=prop.find(q('tblBorders'))
    if borders is not None:prop.remove(borders)
    borders=E.SubElement(prop,q('tblBorders'))
    for side in ['top','left','bottom','right','insideH','insideV']:E.SubElement(borders,q(side),{q('val'):'single',q('sz'):'4',q('color'):'D9D9D9'})
    for ri,row in enumerate([headers]+rows):
        tr=E.SubElement(tb,q('tr'));trpr=E.SubElement(tr,q('trPr'));E.SubElement(trpr,q('cantSplit'))
        if ri==0:E.SubElement(trpr,q('tblHeader'))
        for ci,text in enumerate(row):
            tc=E.SubElement(tr,q('tc'));tcpr=E.SubElement(tc,q('tcPr'));E.SubElement(tcpr,q('tcW'),{q('w'):str(widths[ci]),q('type'):'dxa'})
            E.SubElement(tcpr,q('vAlign'),{q('val'):'center'})
            E.SubElement(tcpr,q('shd'),{q('fill'):'E7EEF1' if ri==0 else 'FFFFFF'})
            mar=E.SubElement(tcpr,q('tcMar'))
            for side in ['top','left','bottom','right']:E.SubElement(mar,q(side),{q('w'):'90',q('type'):'dxa'})
            pp=p(str(text));pr=pp.find(q('pPr'));E.SubElement(pr,q('spacing'),{q('line'):'240',q('lineRule'):'auto',q('after'):'30'})
            for run in pp.findall(q('r')):
                rp=E.Element(q('rPr'));E.SubElement(rp,q('sz'),{q('val'):'21'})
                if ri==0:E.SubElement(rp,q('b'))
                run.insert(0,rp)
            tc.append(pp)
    return tb

# Exact source package and page patterns retained as the layout authority.
contract=f'''# PyTurtle module revision contract
Reference: {SRC}\nSHA256: {hashlib.sha256(SRC.read_bytes()).hexdigest()}
Reference Word render: reference.pdf, 53 pages, 2 sections.
Page: A4 portrait; margins top bottom right 3 cm and left 4 cm.
Typography retained: Times New Roman 12 pt normal with 1.5 spacing;
Heading 1 14 pt; Heading 2 12 pt; Report Code Courier New 10 pt;
Report Caption 11 pt; Report TOC 11 pt. Source alignment and numbering retained.
Components: full page anchored cover at paragraph 0; roman front matter;
Arabic body page numbering restarts at Bab I. Source headers footers and cover preserved.
Slots: document.xml body paragraphs 2 to 6 preface, 83 to 105 guide and learning map,
531 to 632 Maze chapter, 916 to 942 Maze answers, specific practice code and quiz rows.
TOC and illustration/table lists rebuilt from bookmarks with cached page references.
Retained: Bab I to III and V, Python examples, figures 1 to 3, source styles/theme,
numbering, cover, headers, footers and existing media. New maps added as new media.
Old Maze illustrations replaced with data-derived diagrams for 3 tutorial stages and 5 levels.
No invented login URL or published deployment claims; scope is current workspace on 23 October 2026.
Source fields audited from XML; Word refresh required for final cached page references.
All package parts preserved byte-for-byte except document.xml, its relationships,
settings.xml and the newly added map media. Word field refresh may normalize package XML.
Final gate: inspect all rendered pages, verify no stale Maze solution or missing variables,
verify all five level definitions and lesson unlock thresholds against current source.
'''
(ROOT/'artifact.md').write_text(contract,encoding='utf-8')
(ROOT/'package-inventory.json').write_text(json.dumps({n:{'size':len(v),'sha256':hashlib.sha256(v).hexdigest()} for n,v in parts.items()},indent=2),encoding='utf-8')

setp(2,'Modul ini disusun sebagai pendamping belajar PyTurtle melalui materi Python Turtle, Code Matching, dan Maze Quest Petualangan Shiha. Edisi ini menyesuaikan alur dan fitur pada kode proyek yang diperiksa tanggal 23 Oktober 2026. Pembaca belajar menafsirkan perintah, mencoba kode, memperbaiki kesalahan, dan menyelesaikan perjalanan sampai Harta Kakek.')
setp(4,'Setiap bab memuat tujuan pembelajaran, konteks kegiatan, materi dan praktik, pengujian hasil, rekap, serta evaluasi. Materi dasar dalam sebelas sesi tetap digunakan. Bagian Maze diperbarui menjadi lima level, dengan tiga tutorial pada level pertama, penggunaan variabel wajib, serta pengenalan pola perulangan pada jalur lanjut.')
setp(6,'Edisi pembaruan 23 Oktober 2026')
setp(83,'Gunakan modul ini berurutan sambil membuka aplikasi PyTurtle yang disediakan pengajar atau pengelola proyek. Modules memuat sebelas sesi. Jawaban evaluasi yang benar menandai sesi selesai, memberi 20 XP satu kali, dan membuka sesi berikutnya. Membaca halaman saja belum menyelesaikan materi.')
setp(96,'Di Maze Quest, satu kotak dihitung sebagai satu langkah. forward(3) berarti maju tiga kotak. Pada versi ini, setiap solusi harus memakai sedikitnya satu variabel sebagai argumen gerak atau putaran, misalnya langkah = 3 diikuti forward(langkah). Editor dibuka kosong dan setiap Run dimulai dari titik awal.')
setp(99,'Editor gerak pada materi menerima enam perintah sederhana, sedangkan editor Maze juga menerima variabel angka dan for dengan range satu argumen. Contoh Python lengkap dengan import, objek Turtle, warna, stamp, goto, atau range dengan dua dan tiga argumen dijalankan di Python atau Runestone.')
setp(263,'Bab ini mendampingi sesi 04 sampai 07. Cobalah pengulangan teks dan pola gambar di Python atau Runestone. Pada Maze, bentuk for i in range(jumlah): juga tersedia untuk mengulang perintah gerak. Perhatikan bahwa editor gerak di halaman materi tetap hanya menerima perintah gerak dan pena sederhana.')
setp(639,'Code Matching terbuka setelah empat sesi materi pertama selesai. Kegiatan ini memeriksa hubungan antara deskripsi gerak dan potongan kode. Proyek dua bentuk dikerjakan di Python atau Runestone untuk menggabungkan pengulangan dan pena setelah materi dasar dipahami.')
insert_before(640,[p('Di web, pilih satu deskripsi gerakan, lalu pilih kode yang cocok. Pasangan benar terkunci dan bertanda centang; pasangan salah dapat dicoba lagi. Ada empat pasangan, masing-masing 25 poin. Hasil lengkap disimpan setelah keempat pasangan cocok. Accuracy dihitung dari jumlah pasangan benar dibagi seluruh percobaan, lalu dikalikan 100 persen. Empat jawaban benar dan satu salah menghasilkan accuracy 80 persen.'),p('Tombol Reset pada Code Matching memulai latihan baru. Skor terbaik Code Matching ikut dihitung dalam XP. Urutan kartu kode di web dapat berubah; cocokkan isi kode, bukan nomor posisinya. Pilihan A sampai D berikut merupakan latihan cetak pada modul ini.')])

guide=[h('Mengenal Akun dan Menu PyTurtle'),lead('Masuk dan memulai belajar'),p('Buka alamat aplikasi yang diberikan pengajar. Jika belum memiliki akun, pilih pendaftaran, isi nama penjelajah, email, kata sandi minimal delapan karakter, dan ulangi kata sandi. Setelah masuk, buka Modules untuk memulai sesi pertama. Gunakan akun yang sama agar catatan belajarmu tersimpan.'),p('Tombol Google dapat digunakan bila layanan Google pada aplikasi sudah dikonfigurasi. Pada Profil saya, penautan Google memakai alamat email yang sama. Untuk akun dengan kata sandi, gunakan Lupa kata sandi bila diperlukan. Pengiriman tautan pemulihan mengikuti konfigurasi email aplikasi; hubungi pengelola bila tautan tidak diterima.'),cap('Tabel P.3 Menu dan kegiatan pengguna'),table(['Menu','Kegiatan'],[['Home','Melihat ringkasan XP, materi, dan akses lanjut petualangan.'],['Modules','Membaca sebelas sesi, mencoba latihan gerak, dan menjawab evaluasi.'],['Maze Quest','Memilih level yang sudah terbuka dan menyelesaikan perjalanan Shiha.'],['Code Match','Mencocokkan empat deskripsi gerak dengan potongan kode.'],['Leaderboard','Melihat peringkat XP dan badge Turtle Master.'],['Progress','Memeriksa materi selesai, skor terbaik, dan catatan petualangan.'],['Profil saya dan Pengaturan','Mengubah nama tampilan, menautkan Google, serta mengatur audio dan animasi.']],[2000,5938]),h('Membuka Materi dan Petualangan'),p('Sesi pada Modules terbuka berurutan setelah evaluasi sesi sebelumnya dijawab benar. Selain prasyarat materi berikut, Maze tetap mensyaratkan level sebelumnya selesai. Tiga tutorial level 1 harus selesai sebelum level 2 dapat dimainkan.'),cap('Tabel P.4 Syarat membuka fitur'),table(['Materi selesai','Fitur yang memenuhi syarat materi'],[['Sesi 01 sampai 04','Code Matching dan Maze level 1 sampai 2.'],['Sesi 01 sampai 08','Maze level 3.'],['Sesi 01 sampai 11','Maze level 4 sampai 5.']],[2300,5638]),p('Contoh: selesai sesi 04 belum otomatis membuka level 2 jika level 1 belum selesai. Demikian juga level 4 masih menunggu level 3 selesai walaupun seluruh materi telah dipelajari.'),h('Menggunakan Latihan pada Modules'),p('Baca tujuan, materi, dan contoh. Gunakan Salin kode atau Unduh .py untuk contoh Python lengkap, lalu jalankan di Python atau buka sumber Runestone melalui Coba di Runestone. Pada latihan gerak, kamu dapat mengubah perintah sederhana, menjalankan, menjeda, melanjutkan, menghentikan, atau mengulangi contoh. Pilihan kecepatan tersedia dari 0,5 kali sampai 4 kali.'),p('Amati posisi, arah, keadaan pena, dan baris aktif. Setelah mencoba latihan mandiri, pilih jawaban pada Cek pemahaman lalu tekan Periksa jawaban. Pop-up menjelaskan jawaban. Jika belum tepat, baca penjelasan dan coba lagi; jika benar, lanjutkan ke sesi berikutnya.'),h('Membaca Progres dan Menyimpan Hasil'),p('Progress menampilkan jumlah materi selesai, status setiap chapter, percobaan, hint, serta skor terbaik. Skor petualangan merupakan jumlah skor terbaik kelima level, maksimal 500. Persentase petualangan naik 20 persen untuk setiap level yang selesai; tutorial level 1 belum dihitung sebagai tiga level tersendiri.'),p('XP menggabungkan skor terbaik Maze, skor Code Matching terbaik, dan 20 XP per materi selesai. Dengan sebelas materi, lima level, dan satu kegiatan Code Matching saat ini, total maksimum adalah 820 XP. Level penjelajah dihitung dari 1 ditambah pembulatan ke bawah XP dibagi 100; level penjelajah berbeda dari chapter Maze.'),p('Hasil Run dan progres dikirim ke server. Bila koneksi terputus, hasil pada halaman yang sudah dimuat dapat masuk antrean lokal dan dikirim ulang saat terhubung. Periksa status penyimpanan dan tunggu hingga tersimpan sebelum menutup halaman. Halaman baru tetap membutuhkan koneksi. Saat membuka ulang Maze, editor kosong dan Shiha kembali ke awal; tahap tutorial dan skor terbaik yang sudah tersimpan tetap ada.'),h('Mengatur Kenyamanan dan Mengulang Petualangan'),p('Buka menu pengguna untuk mengganti nama tampilan pada Profil saya. Di Pengaturan, atur musik suasana, efek suara, dan Kurangi animasi, kemudian tekan Simpan pengaturan. Musik dan suara mulai setelah interaksi pengguna.'),p('Reset Code hanya mengosongkan kode dan mengembalikan percobaan ke awal. Ulangi penilaian pada level 5 memulai penilaian baru sambil mempertahankan skor terbaik dan badge. Mulai Ulang Petualangan pada Progress atau Pengaturan menghapus progres level dan skor Maze setelah konfirmasi; akun, materi, hasil Code Match, serta pengaturan tetap tersimpan. XP dihitung ulang mengikuti data yang tersisa.')]
insert_before(101,guide)

# Update learning-map table in its original location.
maptb=body.findall(q('tbl'))
for tb in maptb:
    if 'Tiga chapter Maze Petualangan Shiha' in txt(tb):
        for t in tb.xpath('.//w:t',namespaces=NS):
            if t.text=='Tiga chapter Maze Petualangan Shiha':t.text='Lima level Maze dengan tiga tutorial pada level 1'
            if t.text=='Menyusun langkah per kotak dan mengikuti penanda':t.text='Memakai variabel dan perulangan untuk mengikuti penanda hingga Harta Kakek'
    if 'Program lengkap dengan import dan objek' in txt(tb):
        for t in tb.xpath('.//w:t',namespaces=NS):
            if t.text=='Perintah gerak dan belokan 90 derajat':t.text='Variabel, gerak, putaran 90 derajat, dan for dengan range satu argumen'

# Add new diagrams by reusing the source's inline-image paragraph component.
img_template=deepcopy(old[557]); map_counter=0
fontpath=Path('C:/Windows/Fonts/arial.ttf');fontbold=Path('C:/Windows/Fonts/arialbd.ttf')
def draw_map(definition,label):
    global map_counter
    map_counter+=1; im=Image.new('RGB',(1200,860),'white'); dr=ImageDraw.Draw(im)
    ft=ImageFont.truetype(str(fontpath),24); fb=ImageFont.truetype(str(fontbold),26)
    # Fit the logical 600 by 460 game plane into a generous diagram margin.
    scale=1.55; ox=135;oy=42
    def xy(x,y):return (round(ox+x*scale),round(oy+y*scale))
    step=definition.get('step_size',60)
    for x,y,ww,hh in definition['path']:
        dr.rectangle([xy(x,y),xy(x+ww,y+hh)],fill='#eef4ee',outline='#c4d3c6',width=2)
    start=definition['start'];checks=definition['checkpoints'];pts=[(start['x'],start['y'])]+[tuple(c) for c in checks]
    # Numbered checkpoints are the design authority; fine grid shows steps.
    for a,b in zip(pts,pts[1:]):
        dr.line([xy(*a),xy(*b)],fill='#446d51',width=4)
        dist=math.dist(a,b);count=round(dist/step)
        for j in range(1,count):
            xx=a[0]+(b[0]-a[0])*j/count;yy=a[1]+(b[1]-a[1])*j/count
            cx,cy=xy(xx,yy);dr.ellipse((cx-4,cy-4,cx+4,cy+4),fill='#446d51')
    for i,(x,y) in enumerate(pts):
        cx,cy=xy(x,y);rad=17
        fill='#385c45' if i==0 else ('#b68222' if i==len(pts)-1 else '#ffffff')
        dr.ellipse((cx-rad,cy-rad,cx+rad,cy+rad),fill=fill,outline='#385c45',width=2)
        num='S' if i==0 else str(i); bbox=dr.textbbox((0,0),num,font=ft)
        dr.text((cx-(bbox[2]-bbox[0])/2,cy-14),num,font=ft,fill='white' if i in [0,len(pts)-1] else '#182b1e')
    dr.text((40,785),'S = awal menghadap timur     Angka = urutan checkpoint',font=ft,fill='black')
    dr.text((40,820),'Titik kecil di jalur = perpindahan satu kotak     Emas = tujuan',font=ft,fill='black')
    # Remove unused game-plane whitespace so short tutorial paths remain compact.
    from PIL import ImageChops
    bbox=ImageChops.difference(im,Image.new('RGB',im.size,'white')).getbbox()
    if bbox:im=im.crop((max(0,bbox[0]-30),max(0,bbox[1]-30),min(im.width,bbox[2]+30),min(im.height,bbox[3]+20)))
    file=ROOT/f'maze-{map_counter}.png';im.save(file)
    rid='rIdMazeRevision'+str(map_counter)
    E.SubElement(rels,'{http://schemas.openxmlformats.org/package/2006/relationships}Relationship',Id=rid,Type=R+'/image',Target=f'media/maze-revisi-{map_counter}.png')
    parts[f'word/media/maze-revisi-{map_counter}.png']=file.read_bytes()
    el=deepcopy(img_template)
    for b in el.xpath('.//a:blip',namespaces=NS):b.set('{'+R+'}embed',rid)
    width=4953000;height=int(width*im.height/im.width)
    for ext in el.xpath('.//wp:extent | .//a:xfrm/a:ext',namespaces=NS):ext.set('cx',str(width));ext.set('cy',str(height))
    for pr in el.xpath('.//wp:docPr',namespaces=NS):pr.set('id',str(500+map_counter));pr.set('name',label);pr.set('descr',label)
    return el

chapter=[h('BAB IV\nMAZE PETUALANGAN SHIHA',1),h('4.1 Tujuan Pembelajaran')]+bullets(['Menghitung langkah dari pusat kotak ke pusat kotak dan menentukan arah belokan.','Memakai variabel sebagai argumen gerak atau putaran.','Mengulang pola jalur dengan for dan range satu argumen.','Menyelesaikan lima level secara berurutan serta menjelaskan hasil dan skor.'])
chapter += [h('4.2 Konteks Kegiatan dalam PyTurtle'),p('Shiha menyusuri hutan dengan peta peninggalan Kakek. Perjalanan terdiri atas Memasuki Hutan, Jembatan Sungai, Jejak Reruntuhan, Lembah Berliku, dan Harta Kakek. Peti harta serta hadiah akhir berada pada level 5. Level 1 mengajarkan gerak secara bertahap melalui tiga tutorial.'),p('Selesaikan materi sesuai Tabel P.4 dan mainkan level secara berurutan. Editor berada di sisi kiri dan peta di sisi kanan pada layar lebar; pada layar kecil susunannya dapat bertumpuk. Baca misi, perhatikan checkpoint, dan rencanakan rute sebelum menulis kode.'),h('4.3 Aturan dan Langkah Mencoba'),h('4.3.1 Menghitung Langkah dan Arah',3),p('Satu kotak sama dengan satu langkah. forward(3) memindahkan Shiha tiga kotak sesuai arah hadap. Shiha selalu memulai Run dari titik awal dengan arah timur. left(90) memutar ke kiri dan right(90) memutar ke kanan; kedua perintah mengubah arah tanpa memindahkan posisi.'),deepcopy(old[542]),cap('Gambar 4.1 Arah hadap untuk menentukan kiri dan kanan'),p('Saat menghadap timur, kiri berarti utara dan kanan berarti selatan. Saat menghadap utara, kanan berarti timur. backward bergerak ke belakang tanpa membalik arah hadap. Ikuti jalan dan lewati seluruh checkpoint sesuai urutan, kemudian berhenti di tujuan.'),h('4.3.2 Menggunakan Variabel',3),p('Variabel menyimpan nilai untuk digunakan lagi. Nama variabel ditulis tanpa spasi. Isi nilainya terlebih dahulu, lalu gunakan nama itu dalam perintah. Pada setiap level, setidaknya satu variabel harus dipakai sebagai argumen gerak atau putaran. Menulis assignment saja belum memenuhi syarat.'),code('langkah = 3\nsudut = 90\nforward(langkah)\nleft(sudut)'),p('Contoh tersebut berarti maju tiga kotak, lalu menghadap utara. Ini contoh konsep, bukan solusi untuk semua peta. Perintah langkah = 3 diikuti forward(3) saja belum memakai variabel pada gerakan; ubah menjadi forward(langkah).'),h('4.3.3 Mengulang Pola Jalur',3),p('Maze menerima bentuk for i in range(jumlah): dengan satu argumen berupa angka atau variabel. Jumlah harus bilangan bulat 1 sampai 100. Gunakan titik dua dan isi blok yang menjorok empat spasi. Variabel tetap harus digunakan dalam argumen gerak atau putaran.'),code('langkah = 1\njumlah = 3\nfor i in range(jumlah):\n    forward(langkah)'),p('Hasil contoh adalah maju tiga kotak dalam tiga gerakan. Jangan menyalin contoh ke sembarang peta tanpa memeriksa jalan. Bentuk range(awal, akhir) dan range(awal, akhir, selang) pada Bab II dipakai di Python atau Runestone, bukan di editor Maze. Batas Maze adalah 100 perintah hasil pengulangan dan empat tingkat pengulangan.'),p('Pustaka kode menyediakan contoh variabel jarak, variabel sudut, for, dan perulangan dengan variabel untuk disisipkan pada kursor. Ubah nilai sesuai rute. Ctrl + Space membuka saran kode. Perintah yang diizinkan mengikuti level; pustaka umum tidak menjamin semua contoh putaran tersedia pada tutorial awal.'),h('4.3.4 Menjalankan dan Membaca Umpan Balik',3)]+bullets(['Hitung kotak, tentukan belokan, lalu catat urutan checkpoint.','Tulis variabel dan perintah. Jalankan melalui Run Code atau Ctrl atau Command + Enter.','Amati gerakan dan pesan hasil. Kecepatan animasi dapat dipilih 1, 2, atau 4 kali.','Jika muncul ERROR, periksa nama perintah, kurung, variabel, dan indentasi.','Jika muncul PARTIAL, kode telah dibaca tetapi rute belum selesai atau gerak tertahan. Periksa jarak dan belokan.','SUCCESS berarti rute dan penanda terpenuhi. Pada dua tutorial awal, lanjutkan melalui Sublevel berikutnya setelah hasil tersimpan.'])
chapter += [p('Reset Code mengosongkan editor dan mengembalikan Shiha ke titik awal; skor terbaik tetap tersimpan. Memuat ulang halaman juga membuka editor kosong. Tulis ulang seluruh rencana ketika mencoba lagi karena setiap Run dimulai dari awal, bukan dari posisi akhir percobaan sebelumnya.'),p('Bantuan rute muncul otomatis pada setiap tiga hasil Run yang belum berhasil dan tercatat di server. Tutup melalui Mengerti, coba lagi, lalu perbaiki kode. Bantuan otomatis ini tidak menambah potongan hint manual. Tidak semua kesalahan lokal yang belum dikirim, seperti editor kosong, tercatat sebagai percobaan server.'),p('penup() dan pendown() tersedia pada level 3 untuk mengatur jejak. Mengangkat pena tidak membolehkan Shiha keluar dari jalur. import, pembuatan objek Turtle, goto, warna, print, serta ekspresi seperti langkah + 1 tidak termasuk sintaks Maze. Gunakan Python atau Runestone untuk contoh lengkap.'),h('4.4 Praktik Maze')]
for idx,stage in enumerate(levels[0]['tutorial_stages']):
    definition={**levels[0],**stage};num=idx+2
    if idx==0:
        chapter += [h('4.4.1 Level 1 Memasuki Hutan',3),p('Level ini memuat tiga tutorial. Selesaikan ketiganya untuk menandai level 1 selesai dan memenuhi syarat perjalanan menuju level 2. Tahap tutorial yang sudah tersimpan tetap dikenali saat halaman dibuka kembali.')]
    chapter += [lead(f'Tutorial {idx+1} {stage["title"]}'),p(['Rencanakan gerak lurus tiga kotak ke timur. Ada penanda di sepanjang jalan dan tujuan pada ujung rute.','Rencanakan tiga kotak ke timur, belok kiri, lalu tiga kotak ke utara.','Rencanakan tiga kotak ke timur, tiga kotak ke utara, lalu tiga kotak ke timur. Belokan yang digunakan adalah kiri, kemudian kanan.'][idx]),draw_map(definition,f'Tutorial {idx+1}'),cap(f'Gambar 4.{num} Peta tutorial {idx+1} pada level 1')]
    if idx==0:chapter += [lead('Contoh kode awal'),code(stage['solution']),p('Bandingkan forward(langkah) dengan forward(3). Jaraknya sama, tetapi bentuk pertama memenuhi kewajiban memakai variabel. Setelah berhasil, tekan Sublevel berikutnya untuk membuka tutorial kedua.')]
    else:chapter += [lead('Latihan'),p('Susun kode menggunakan langkah = 3 dan sudut = 90. Pakai kedua variabel itu dalam perintah yang sesuai. Perkirakan arah akhir sebelum menjalankan. Contoh jawaban tersedia pada bagian kunci.')]
    chapter += [lead('Hasil yang diperiksa'),p(['Shiha mencapai ujung jalan dan tutorial berikutnya dapat dibuka setelah tersimpan.','Shiha berhenti di tujuan utara dan tutorial ketiga dapat dibuka setelah tersimpan.','Shiha melewati ketiga penanda, berhenti menghadap timur, dan level 1 selesai.'][idx])]
for l in levels[1:]:
    k=l['id'];description={2:'Gunakan rute timur tiga kotak, utara tiga kotak, dan timur tiga kotak. Setelah materi 04 dan seluruh tutorial level 1 selesai, level ini dapat dibuka. Tulis kode sendiri menggunakan variabel jarak dan sudut.',3:'Setelah materi 08 dan level 2 selesai, ikuti enam bagian jalan: timur 2 kotak, utara 2, timur 3, selatan 2, timur 2, lalu utara 3. Simpan jarak atau sudut dalam variabel. Reruntuhan merupakan bagian tengah perjalanan, sebelum lembah dan harta akhir.',4:'Setelah seluruh sebelas materi dan level 3 selesai, amati pola tangga. Bergerak tiga kotak ke timur, putar kiri, tiga kotak ke utara, lalu putar kanan. Ulangi pola itu tiga kali dan tambahkan tiga kotak ke timur. Tujuh penanda harus dilewati berurutan.',5:'Setelah level 4 selesai dan seluruh materi tuntas, susuri labirin sampai peti Kakek. Ada 16 penanda dengan petak lebih rapat. Jumlah langkah tetap dihitung per kotak meskipun ukuran kotak berbeda dari peta awal. Perhatikan urutan penanda agar jalan yang berdekatan tidak tertukar.'}[k]
    chapter += [h(f'4.4.{k} Level {k} {l["title"]}',3),p(description),draw_map(l,f'Level {k} {l["title"]}'),cap(f'Gambar 4.{k+3} Peta level {k} {l["title"]}')]
    if k==4:chapter += [lead('Contoh kode pola tangga'),code(l['solution']),p('Isi blok for mengulang satu pasangan gerak timur dan utara. right(sudut) menyiapkan arah timur untuk putaran berikutnya. forward(langkah) di luar blok menambahkan bagian terakhir menuju penanda ketujuh.')]
    elif k==5:chapter += [lead('Latihan perencanaan'),p('Tuliskan arah menuju penanda 1 sampai 16. Bagian antartitik pada rute acuan masing-masing tiga kotak; dua bagian lurus yang berurutan dapat diringkas menjadi pengulangan. Buat kode lengkap memakai variabel dan periksa bahwa setiap penanda tetap dilalui. Bandingkan dengan kunci setelah mencoba sendiri.'),lead('Hadiah akhir'),p('Selesaikan level 5 dalam satu Run penilaian tanpa kegagalan dan tanpa hint manual untuk memperoleh 100 XP serta badge Turtle Master. Setiap Run ERROR atau PARTIAL yang tercatat pada penilaian ini mengurangi calon reward sebesar 5; hint manual mengurangi 5 sekali. Reward minimum 60. Bantuan otomatis setelah tiga kegagalan tidak menambah penalti hint manual.'),p('Contoh: dua kegagalan lalu berhasil tanpa hint manual memberi reward 90. Jika skor terbaik sebelumnya 95, hasil 90 tidak menurunkan XP terbaik. Ulangi penilaian memulai kesempatan baru; bila skor terbaik meningkat dari 95 menjadi 100, total XP bertambah 5. Badge yang sudah diperoleh tetap ada.')]
    else:chapter += [lead('Latihan dan pengujian'),p('Tulis seluruh kode dari titik awal, gunakan variabel pada gerak atau putaran, lalu jalankan. Pastikan semua checkpoint dilewati berurutan dan Shiha berhenti di tujuan. Pada level 3, cobalah penup() lalu pendown() untuk melihat pengaruh jejak tanpa mengubah rute.')]
chapter += [h('4.5 Pengujian Hasil')]+bullets(['Sedikitnya satu variabel digunakan pada argumen gerak atau putaran.','Jumlah kotak dan arah belokan sesuai peta yang sedang dibuka.','Semua checkpoint dilalui berurutan dan posisi akhir tepat di tujuan.','Level 1 baru selesai setelah ketiga tutorial tuntas.','Hasil tersimpan sebelum berpindah halaman; skor terbaik dapat dilihat pada Progress.'])
chapter += [p('Pada level 1 sampai 4, penyelesaian mendapat skor awal 100, dengan pengurangan 2 untuk setiap percobaan tambahan yang diperhitungkan sampai maksimum pengurangan percobaan 20, serta 5 jika hint manual dipakai. Skor minimal 60. Dua tutorial awal yang berhasil tidak dikenai pengurangan sebagai kegagalan. Pada level 5 berlaku aturan penilaian khusus yang dijelaskan sebelumnya.'),h('4.6 Rekap Bab'),p('Petualangan sekarang terdiri atas lima level dan tiga tutorial pada level pertama. Gunakan variabel untuk menyimpan jarak atau sudut, ikuti penanda, dan ulangi pola bila membantu. Bedakan Reset Code, Ulangi penilaian, dan Mulai Ulang Petualangan. Keberhasilan belajar dinilai dari pemahaman kode, bukan hanya banyaknya XP.'),h('4.7 Evaluasi Pemahaman'),p('Pilih satu jawaban. Kerjakan sebelum membaca kunci. Setiap jawaban benar bernilai 20 pada evaluasi modul ini.')]
questions=[('Kode mana memenuhi syarat variabel Maze untuk rute lurus tiga kotak?',['A. langkah = 3 lalu forward(langkah)','B. langkah = 3 lalu forward(3)','C. forward(3) saja','D. import turtle'],'A'),('Syarat lengkap membuka level 3 adalah ...',['A. Hanya membuka halaman materi 08','B. Menyelesaikan sesi 01 sampai 08 dan level 2','C. Menekan Reset Code','D. Menyelesaikan Code Matching saja'],'B'),('Apa fungsi Reset Code?',['A. Menghapus seluruh materi','B. Memberi badge otomatis','C. Mengosongkan editor dan mengembalikan Shiha ke awal','D. Menyelesaikan semua tutorial'],'C'),('Kapan bantuan rute otomatis muncul?',['A. Setiap halaman dibuka','B. Setelah memilih kecepatan 4 kali','C. Setelah satu checkpoint','D. Setiap tiga Run belum berhasil yang tercatat di server'],'D'),('Bagaimana memperoleh badge Turtle Master?',['A. Menyelesaikan level 5 dalam satu Run penilaian tanpa kegagalan dan hint manual','B. Menyelesaikan level 3 saja','C. Mengulangi Reset Code tiga kali','D. Menjawab satu evaluasi materi'],'A')]
for i,(qq,opts,ans) in enumerate(questions,1):chapter += [lead(f'{i}. {qq}')]+[p(t,'Report List') for t in opts]
# Keep each new question together with its choices.
for el in chapter:
    text=txt(el)
    if re.match(r'^\d\. ',text) or re.match(r'^[ABC]\. ',text):
        E.SubElement(el.find(q('pPr')),q('keepNext'))
chapter += [p('Kunci dan alasan jawaban tersedia pada bagian Kunci Jawaban dan Pembahasan.')]
in_quiz=False
for el in chapter:
    if txt(el)=='4.7 Evaluasi Pemahaman':
        in_quiz=True
        E.SubElement(el.find(q('pPr')),q('pageBreakBefore'))
        continue
    if in_quiz:
        pr=el.find(q('pPr'))
        spacing=pr.find(q('spacing'))
        if spacing is None:spacing=E.SubElement(pr,q('spacing'))
        spacing.set(q('line'),'264');spacing.set(q('lineRule'),'auto');spacing.set(q('after'),'60')
        for run in el.findall(q('r')):
            rp=E.Element(q('rPr'));E.SubElement(rp,q('sz'),{q('val'):'22'});run.insert(0,rp)
replace_range(531,633,chapter)

# Keep practice and answer code valid under the required-variable rule.
for i in [821,957]:
    pass
for i in range(819,828):
    if old[i].getparent() is not None and 'forward(3)' in txt(old[i]):setp(i,levels[1]['solution'])
setp(821,'Shiha mulai menghadap timur. Susun kode untuk maju 3 kotak ke timur, 3 kotak ke utara, lalu 3 kotak ke timur. Gunakan variabel sebagai argumen gerak atau putaran. Tugas ini sesuai rute level 2 atau tutorial ketiga level 1, setelah syarat aksesnya terpenuhi.')
for i in range(943,969):
    if txt(old[i])=='Tugas 1':
        # Replace the consecutive individual code paragraphs as one correct block.
        seq=[];j=i+1
        while j<969 and old[j].find(q('pPr')) is not None and old[j].xpath('./w:pPr/w:pStyle/@w:val',namespaces=NS)==[STYLE_IDS['Report Code']]:
            seq.append(old[j]);j+=1
        if seq:
            pos=body.index(seq[0])
            for el in seq:body.remove(el)
            body.insert(pos,code(levels[1]['solution']))

answers=[h('C Pembahasan Maze dan Code Matching'),p('Kode di bawah mengikuti definisi peta proyek saat diperiksa. Jalankan pada level atau tahap yang sesuai. Variabel pada gerak atau putaran wajib; contoh lama yang hanya memakai angka literal perlu diperbarui.')]
for i,st in enumerate(levels[0]['tutorial_stages'],1):answers += [lead(f'Level 1 tutorial {i} {st["title"]}'),code(st['solution'])]
for l in levels[1:]:
    answers += [lead(f'Level {l["id"]} {l["title"]}')]
    if l['id']==5:
        lines=l['solution'].splitlines();answers += [p('Bagian awal dan lanjutan berikut merupakan satu program. Sambungkan keduanya sesuai urutan; pertahankan indentasi pada blok for.'),lead('Bagian awal'),code('\n'.join(lines[:20])),lead('Lanjutan program yang sama'),code('\n'.join(lines[20:]))]
    else:answers += [code(l['solution'])]
    answers += [p({2:'Arah akhir timur. Variabel langkah digunakan tiga kali dan sudut digunakan pada dua belokan.',3:'Enam bagian jalan dilalui sesuai urutan. Mengangkat pena boleh dicoba, tetapi tidak mengubah syarat jalur atau penanda.',4:'Tiga pengulangan membentuk enam bagian tangga; satu gerak tambahan melengkapi bagian ketujuh.',5:'Program melewati seluruh 16 penanda. Perulangan dua kali maju tetap melewati penanda di tengah ruas lurus. Hasil sempurna dapat memperoleh badge pada penilaian baru sesuai aturan level akhir.'}[l['id']])]
answers += [lead('Code Matching'),p('Untuk pilihan cetak pada Bab V: 1 dengan C, 2 dengan D, 3 dengan B, dan 4 dengan A. Di web, urutan kartu dapat berubah. Maju 120 lalu mundur 40 menghasilkan perpindahan bersih 80 unit dan mempertahankan arah hadap.')]
replace_range(916,943,answers)
for i,text in enumerate(['1. A. langkah dipakai pada forward(langkah), sehingga syarat variabel terpenuhi. Assignment yang tidak digunakan pada gerak belum cukup.','2. B. Materi sampai sesi 08 dan penyelesaian level 2 sama-sama diperlukan.','3. C. Reset Code mengosongkan kode dan mengembalikan posisi awal tanpa menghapus skor terbaik.','4. D. Bantuan otomatis diberikan pada setiap tiga kegagalan yang tercatat; bantuan ini tidak menambah penalti hint manual.','5. A. Badge diperoleh pada level 5 melalui satu Run penilaian tanpa kegagalan dan tanpa hint manual.']):setp(865+i,text)
setp(971,'Python Software Foundation. (tanpa tahun). Turtle graphics. Dokumentasi Python 3. https://docs.python.org/3/library/turtle.html (diakses 23 Oktober 2026).')
insert_before(969,[h('Catatan Pembaruan Modul'),p('Modul ini menyesuaikan sumber proyek aktif PyTurtle pada 23 Oktober 2026. Bagian yang diperiksa mencakup routes/web.php, resources/data/levels.json, resources/data/turtle-lessons.json, resources/data/code-match.json, layanan akses materi dan progres, serta tampilan Modules, Maze, Code Matching, Progress, profil, dan pengaturan. Peta pada Bab IV merupakan diagram pembelajaran dari koordinat level, bukan tangkapan layar web.'),p('Struktur lima bab, sebelas sesi, contoh Python, dan tujuan pembelajaran dari modul lama dipertahankan. Revisi mencakup lima level Maze, tiga tutorial awal, variabel wajib, subset perulangan, akses fitur berdasarkan materi, bantuan otomatis, penilaian akhir, serta panduan akun dan progres. Dokumentasi ini mengikuti implementasi di folder proyek aktif; isi publikasi web lain dapat berbeda bila belum memakai pembaruan yang sama.')])

# Fix damaged replacement characters while leaving source examples unchanged.
for t in doc.xpath('//w:t',namespaces=NS):
    if t.text:t.text=t.text.replace('90\ufffd','90°').replace('\ufffd','')

# Number front-matter tables in their actual reading order.
for el in body.findall(q('p')):
    for node in el.xpath('.//w:t',namespaces=NS):
        if node.text:
            node.text=node.text.replace('Tabel P.3 Menu','Tabel P.2 Menu').replace('Tabel P.4 Syarat','Tabel P.3 Syarat').replace('Tabel P.2 Urutan','Tabel P.4 Urutan').replace('sesuai Tabel P.4','sesuai Tabel P.3')

# Dynamic bookmark-backed page references replace the obsolete manually typed lists.
def bookmark(el,name,num):
    s=E.Element(q('bookmarkStart'),{q('id'):str(num),q('name'):name});e=E.Element(q('bookmarkEnd'),{q('id'):str(num)})
    el.insert(1,s);el.append(e)
def refline(label,key):
    el=p(label,'Report TOC')
    pr=el.find(q('pPr'));tabs=E.SubElement(pr,q('tabs'));E.SubElement(tabs,q('tab'),{q('val'):'right',q('leader'):'dot',q('pos'):'7938'})
    run=E.SubElement(el,q('r'));E.SubElement(run,q('tab'))
    field=E.SubElement(el,q('fldSimple'),{q('instr'):f' PAGEREF {key} \\h ',q('dirty'):'true'})
    r=E.SubElement(field,q('r'));E.SubElement(r,q('t')).text='1'
    return el
lists={'toc':[],'fig':[],'tab':[]};counter=1000
for el in body.findall(q('p')):
    text=txt(el);styles=el.xpath('./w:pPr/w:pStyle/@w:val',namespaces=NS);st=STYLE_NAMES.get(styles[0],styles[0]) if styles else 'Normal'
    kind=None
    if st in ['Heading 1','Heading 2'] and text not in ['DAFTAR ISI','DAFTAR GAMBAR','DAFTAR TABEL']:kind='toc'
    if st=='Report Caption':kind='fig' if text.startswith('Gambar') else ('tab' if text.startswith('Tabel') else None)
    if kind:
        counter+=1;name='revloc'+str(counter);bookmark(el,name,counter)
        # The chapter subtitle contains an explicit line break rather than a text space.
        label=text.replace('BAB IGERAK','BAB I GERAK').replace('BAB IIPENGULANGAN','BAB II PENGULANGAN').replace('BAB IIIPENA','BAB III PENA').replace('BAB IVMAZE','BAB IV MAZE').replace('BAB VCODE','BAB V CODE')
        lists[kind].append(refline(label,name))
replace_range(10,62,lists['toc'])
replace_range(63,73,lists['fig'])
replace_range(74,82,lists['tab'])

# Avoid table fragments containing only a header and one short row.
for tb in body.findall(q('tbl')):
    rows=tb.findall(q('tr'))
    for row in rows[:2]:
        for para in row.xpath('.//w:p',namespaces=NS):
            pr=para.find(q('pPr'))
            if pr is None:pr=E.SubElement(para,q('pPr'))
            keep=pr.find(q('keepNext'))
            if keep is None:keep=E.SubElement(pr,q('keepNext'))
            keep.set(q('val'),'1')

# Concise guide wording prevents a short continuation page before the learning map.
for el in body.findall(q('p')):
    text=txt(el)
    replacement=None
    if text.startswith('XP menggabungkan skor terbaik Maze'):
        replacement='XP adalah gabungan skor terbaik Maze, Code Matching, dan 20 XP per materi. Maksimum saat ini 820 XP dari sebelas materi, lima level, dan satu Code Matching. Level penjelajah adalah 1 ditambah pembulatan ke bawah XP dibagi 100; nilainya berbeda dari chapter Maze.'
    if text.startswith('Reset Code hanya mengosongkan kode'):
        replacement='Reset Code mengosongkan kode dan mengembalikan posisi awal. Ulangi penilaian pada level 5 mempertahankan skor terbaik dan badge. Mulai Ulang Petualangan menghapus progres serta skor Maze setelah konfirmasi. Akun, materi, Code Match, dan pengaturan tetap tersimpan; XP dihitung ulang.'
    if text.startswith('Hasil Run dan progres dikirim ke server'):
        replacement='Hasil Run dikirim ke server. Saat koneksi terputus, hasil dapat diantrekan pada halaman yang sudah dimuat. Tunggu status tersimpan sebelum menutup halaman; membuka halaman baru membutuhkan koneksi. Saat Maze dibuka ulang, editor kosong dan Shiha kembali ke awal. Tahap tutorial serta skor terbaik yang tersimpan tetap ada.'
    if replacement:
        for child in list(el):
            if child.tag!=q('pPr'):el.remove(child)
        for child in list(p(replacement))[1:]:el.append(child)

# Let the learning map follow the expanded guide instead of leaving a short last page.
mappr=old[101].find(q('pPr'))
pagebreak=mappr.find(q('pageBreakBefore'))
if pagebreak is None:pagebreak=E.SubElement(mappr,q('pageBreakBefore'))
pagebreak.set(q('val'),'0')

settings=E.fromstring(parts['word/settings.xml']);update=settings.find(q('updateFields'))
if update is None:update=E.SubElement(settings,q('updateFields'))
update.set(q('val'),'true')
parts['word/settings.xml']=E.tostring(settings,xml_declaration=True,encoding='UTF-8',standalone=True)
parts['word/document.xml']=E.tostring(doc,xml_declaration=True,encoding='UTF-8',standalone=True)
parts['word/_rels/document.xml.rels']=E.tostring(rels,xml_declaration=True,encoding='UTF-8',standalone=True)
with ZipFile(OUT,'w',ZIP_DEFLATED) as z:
    for n,v in parts.items():z.writestr(n,v)
print(OUT)
print('New maps',map_counter,'source preserved',hashlib.sha256(SRC.read_bytes()).hexdigest())
