from pathlib import Path
import json, math, textwrap, zipfile
from PIL import Image, ImageDraw, ImageFont
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader

ROOT=Path(__file__).resolve().parents[1]
ASSET=ROOT/'diagram'; ASSET.mkdir(exist_ok=True)
FONT='C:/Windows/Fonts/arial.ttf'; BOLD='C:/Windows/Fonts/arialbd.ttf'
pdfmetrics.registerFont(TTFont('Arial',FONT)); pdfmetrics.registerFont(TTFont('ArialB',BOLD))
def F(n,b=False): return ImageFont.truetype(BOLD if b else FONT,n)

# Node tuple: lane, label, optional (failure label, action, return node index or None).
U=[]
def uc(i,name,actor,goal,pre,post,nodes,note,source):
    U.append(dict(id=f'UC{i:02}',name=name,actor=actor,goal=goal,pre=pre,post=post,nodes=nodes,note=note,source=source))
uc(1,'Mendaftar akun','Pengunjung','Membuat akun untuk mulai belajar.','Belum masuk ke PyTurtle.','Akun dibuat dan pengguna masuk ke beranda.',[
('Pengunjung','Membuka formulir pendaftaran'),('Pengunjung','Mengisi nama, email, kata sandi dan konfirmasi'),('PyTurtle','Data valid dan email belum terdaftar?',('Tidak','Tampilkan kesalahan isian',1)),('PyTurtle','Membuat akun dan nama tampilan'),('PyTurtle','Membuat sesi masuk'),('PyTurtle','Menampilkan beranda')],
'Nama maksimal 40 karakter. Kata sandi minimal 8 karakter dan harus sama dengan konfirmasi. Isian yang ditolak diperbaiki lalu dikirim ulang.','AuthController::register; /register')
uc(2,'Masuk dengan email','Pengunjung','Mengakses akun menggunakan email dan kata sandi.','Sudah memiliki akun dan belum masuk.','Sesi aktif dan halaman tujuan ditampilkan.',[
('Pengunjung','Membuka halaman login'),('Pengunjung','Mengisi email, kata sandi dan opsi ingat saya'),('PyTurtle','Isian valid dan batas percobaan belum terlampaui?',('Tidak','Tampilkan kesalahan atau waktu tunggu',1)),('PyTurtle','Email dan kata sandi cocok?',('Tidak','Catat kegagalan dan tampilkan pesan',1)),('PyTurtle','Membuat ulang sesi masuk'),('PyTurtle','Membuka halaman tujuan atau beranda')],
'Kegagalan autentikasi dibatasi menurut email dan alamat IP. Pengguna menunggu jika batas percobaan tercapai.','AuthController::login; /login')
uc(3,'Masuk dengan Google','Pengunjung dan Google','Mengakses PyTurtle memakai identitas Google.','Belum masuk dan integrasi Google telah dikonfigurasi.','Identitas dipetakan ke akun dan sesi masuk aktif.',[
('Pengunjung','Memilih Masuk dengan Google'),('PyTurtle','Mengarahkan permintaan autentikasi'),('Google','Meminta pemilihan akun dan persetujuan'),('Google','Mengembalikan hasil autentikasi'),('PyTurtle','Sesi dan identitas Google valid?',('Tidak','Tampilkan kegagalan atau pembatalan',None)),('PyTurtle','Akun Google tertaut atau email masih baru?',('Tidak','Minta login email lalu tautkan di profil',None)),('PyTurtle','Mengambil akun tertaut atau membuat akun baru'),('PyTurtle','Membuat sesi dan membuka halaman tujuan')],
'Email Google harus terverifikasi. Email yang sudah memiliki akun PyTurtle tidak digabung otomatis; pemilik masuk dengan kata sandi lalu menjalankan UC18. Jika integrasi belum aktif, sistem menampilkan pesan.','GoogleAuthController; GoogleAccountService; /auth/google')
uc(4,'Memulihkan kata sandi','Pengunjung','Mengganti kata sandi yang terlupa melalui tautan pemulihan.','Belum masuk dan memiliki akun dengan email terdaftar.','Kata sandi diperbarui dan halaman login ditampilkan.',[
('Pengunjung','Mengisi email pada Lupa kata sandi'),('PyTurtle','Memvalidasi email dan memproses tautan pemulihan'),('PyTurtle','Menampilkan konfirmasi umum'),('Pengunjung','Membuka tautan lalu mengisi kata sandi baru'),('PyTurtle','Token, email dan kata sandi baru valid?',('Tidak','Tampilkan pesan dan minta perbaikan atau tautan baru',None)),('PyTurtle','Menyimpan kata sandi baru dan memperbarui token'),('PyTurtle','Mengarahkan ke halaman login')],
'Konfirmasi permintaan tidak mengungkap apakah email terdaftar. Token tidak valid atau kedaluwarsa memerlukan tautan baru. Pada konfigurasi pengembangan, email dicatat ke log; pengiriman ke inbox memerlukan konfigurasi email.','AuthController::forgot/reset; /forgot-password; /reset-password')
uc(5,'Keluar dari akun','Pengguna terdaftar','Mengakhiri sesi penggunaan akun.','Pengguna sudah masuk.','Sesi diakhiri dan halaman login ditampilkan.',[
('Pengguna','Memilih Keluar'),('PyTurtle','Mengakhiri autentikasi pengguna'),('PyTurtle','Menghapus sesi aktif dan memperbarui token'),('PyTurtle','Menampilkan halaman login')],
'Akses kembali ke fitur yang dilindungi memerlukan login.','AuthController::logout; /logout')
uc(6,'Melihat beranda','Pengguna terdaftar','Mengetahui ringkasan belajar dan pilihan kegiatan berikutnya.','Pengguna sudah masuk.','Ringkasan akun, XP dan kemajuan tersedia.',[
('Pengguna','Membuka Beranda'),('PyTurtle','Mengambil profil, materi dan progres level'),('PyTurtle','Menyusun ringkasan XP dan kemajuan'),('PyTurtle','Menampilkan beranda dan navigasi belajar'),('Pengguna','Membaca ringkasan dan pilihan kegiatan')],
'Memilih kegiatan dari beranda memulai use case terkait, misalnya belajar materi atau melanjutkan petualangan.','HomeController; /home')
uc(7,'Mempelajari dan menyelesaikan materi','Pengguna terdaftar','Memahami materi Turtle dan menyelesaikan kuisnya.','Pengguna sudah masuk.','Materi selesai, XP diperbarui dan materi berikutnya terbuka.',[
('Pengguna','Membuka daftar dan memilih materi'),('PyTurtle','Materi sudah terbuka?',('Tidak','Arahkan ke materi prasyarat',None)),('PyTurtle','Menampilkan materi, contoh dan kuis'),('Pengguna','Mempelajari materi dan mengirim jawaban'),('PyTurtle','Jawaban valid dan benar?',('Tidak','Tampilkan kesalahan atau penjelasan',3)),('PyTurtle','Mencatat penyelesaian dan memperbarui XP'),('PyTurtle','Menampilkan hasil dan akses materi berikutnya')],
'Materi dasar dibuka berurutan. Penyelesaian pertama memberi 20 XP; pengulangan materi yang sudah selesai tidak menggandakan XP.','ModuleController; LessonAccessService; /modules')
uc(8,'Menyelesaikan Code Match','Pengguna terdaftar','Mencocokkan empat deskripsi dengan potongan kode yang tepat.','Pengguna sudah masuk.','Hasil, akurasi dan skor tersimpan serta XP diperbarui.',[
('Pengguna','Membuka Code Match'),('PyTurtle','Menampilkan empat pasangan yang harus dicocokkan'),('Pengguna','Memilih deskripsi dan potongan kode'),('PyTurtle','Pasangan benar?',('Tidak','Tampilkan umpan balik salah',2)),('PyTurtle','Mengunci pasangan benar'),('PyTurtle','Semua pasangan selesai?',('Tidak','Lanjutkan pasangan berikutnya',2)),('PyTurtle','Menilai ulang riwayat dan menyimpan hasil'),('PyTurtle','Menampilkan skor serta akurasi')],
'Server memeriksa riwayat pilihan dan menolak penyelesaian yang belum lengkap. Pengiriman ulang dengan ID yang sama tidak membuat hasil ganda. Kegagalan simpan perlu dicoba kembali.','CodeMatchController; CodeMatchService; /code-match')
uc(9,'Bermain Maze Quest','Pengguna terdaftar','Menyelesaikan level petualangan menggunakan perintah Turtle.','Pengguna sudah masuk.','Hasil percobaan tersimpan; jika berhasil, progres dan reward diperbarui.',[
('Pengguna','Memilih level pada Maze Quest'),('PyTurtle','Level sudah terbuka?',('Tidak','Tolak akses ke level terkunci',None)),('PyTurtle','Menampilkan cerita, peta dan editor'),('Pengguna','Menulis kode dan memilih Run'),('PyTurtle','Memvalidasi kode, menjalankan simulasi dan menyimpan hasil'),('PyTurtle','Seluruh checkpoint dan tujuan tercapai?',('Tidak','Tampilkan ERROR atau PARTIAL',3)),('PyTurtle','Catat sukses, hitung reward dan buka level berikutnya'),('PyTurtle','Menampilkan hasil dan cerita penyelesaian')],
'Validasi ulang server menentukan hasil resmi. Hint tersedia melalui UC11. Reset editor mengosongkan kode dan mengembalikan karakter ke awal tanpa menghapus best score. Saat offline pada halaman yang telah dimuat, antrean disimpan lokal dan dicoba lagi; reward resmi menunggu sinkronisasi.','MazeController; GameProgressService; game/maze.js; /maze')
uc(10,'Melanjutkan petualangan','Pengguna terdaftar','Membuka kembali chapter petualangan yang sesuai progres.','Pengguna sudah masuk.','Halaman chapter yang dipilih sistem terbuka.',[
('Pengguna','Memilih Lanjut petualangan'),('PyTurtle','Membaca chapter terakhir atau memilih chapter pertama'),('PyTurtle','Chapter terakhir sudah selesai?',('Ya','Pilih chapter berikutnya, maksimal chapter 3',3)),('PyTurtle','Mengarahkan ke chapter terpilih'),('PyTurtle','Memeriksa hak akses dan membuka halaman level'),('Pengguna','Melanjutkan dengan menulis kode baru')],
'Cabang Tidak memakai chapter terakhir. Cabang Ya memilih chapter sesudahnya, dibatasi chapter 3. Editor dibuka kosong dan karakter berada di titik awal; fitur ini melanjutkan chapter, bukan mengeksekusi ulang kode tersimpan.','MazeController::resume/show; /game/continue')
uc(11,'Meminta hint','Pengguna terdaftar','Mendapatkan petunjuk saat mengerjakan Maze Quest.','Level terbuka; pada chapter 3 penilaian masih aktif.','Petunjuk ditampilkan dan penggunaan hint tercatat.',[
('Pengguna','Memilih Hint pada halaman level'),('PyTurtle','Akses dan versi petualangan atau penilaian valid?',('Tidak','Tolak permintaan dan tampilkan pesan',None)),('PyTurtle','Mencatat penggunaan hint'),('PyTurtle','Mengirim petunjuk level'),('Pengguna','Membaca petunjuk dan kembali menulis kode')],
'UC11 merupakan perluasan opsional UC09 pada saat pengerjaan kode. Penggunaan hint memengaruhi skor penyelesaian; chapter 3 memakai status hint per penilaian.','GameProgressController::hint; /game/{level}/hint')
uc(12,'Mengulangi penilaian chapter tiga','Pengguna terdaftar','Memulai penilaian baru pada chapter akhir.','Chapter 3 terbuka dan pengguna sudah masuk.','Penilaian baru aktif dengan best score sebelumnya dipertahankan.',[
('Pengguna','Memilih Ulangi penilaian di chapter 3'),('PyTurtle','Akses dan versi penilaian valid?',('Tidak','Tampilkan pesan dan minta muat ulang',None)),('PyTurtle','Menaikkan versi dan mereset kegagalan serta hint penilaian'),('PyTurtle','Mengembalikan karakter ke awal chapter 3'),('PyTurtle','Mempertahankan best score dan badge yang telah diperoleh'),('Pengguna','Mengerjakan kembali kode pada penilaian baru')],
'Permintaan versi lama ditolak. Kesempatan memperoleh 100 XP dan badge Turtle Master tersedia dengan penyelesaian bersih tanpa kesalahan dan tanpa hint.','GameProgressController::replay; FinalLevelRewardService; /game/3/replay')
uc(13,'Memulai ulang petualangan','Pengguna terdaftar','Mengulang progres petualangan dari level pertama.','Pengguna sudah masuk.','Progres game direset dan XP dihitung ulang.',[
('Pengguna','Memilih Mulai ulang petualangan'),('PyTurtle','Menampilkan dialog konfirmasi'),('Pengguna','Menyetujui pengulangan?',('Tidak','Tutup dialog tanpa perubahan',None)),('PyTurtle','Menghapus progres level, progres game dan submission'),('PyTurtle','Menaikkan versi petualangan dan menetapkan level pertama'),('PyTurtle','Menghitung ulang XP'),('PyTurtle','Menampilkan daftar Maze Quest')],
'Akun, materi yang selesai, hasil Code Match dan pengaturan dipertahankan. Antrean penyimpanan dari versi petualangan lama ditolak.','GameProgressController::restart; /game/restart')
uc(14,'Menyelesaikan Uji Logika','Pengguna terdaftar','Menguji pernyataan tentang Turtle dan menjelaskan contoh bantahannya.','Pengguna sudah masuk.','Penyelesaian tantangan tercatat setelah contoh dan alasan benar.',[
('Pengguna','Memilih tantangan dan membaca pernyataan'),('Pengguna','Memilih arah serta jarak lalu menguji'),('PyTurtle','Menjalankan simulasi dan mencatat eksperimen'),('PyTurtle','Hasil membantah pernyataan?',('Tidak','Tampilkan hasil dan minta kondisi lain',1)),('Pengguna','Memilih alasan lalu mengirim jawaban'),('PyTurtle','Eksperimen milik pengguna dan alasan benar?',('Tidak','Tolak data atau tampilkan koreksi alasan',4)),('PyTurtle','Mencatat penyelesaian dan menampilkan penjelasan')],
'Enam tantangan tersedia. Arah pilihan 0, 90, 180 atau 270 derajat dan jarak 1 sampai 4. Alasan hanya diterima setelah eksperimen menjadi contoh bantahan. Penyelesaian Uji Logika tidak menambah XP pada implementasi saat ini.','LogicChallengeController; LogicChallengeService; /uji-logika')
uc(15,'Melihat progres belajar','Pengguna terdaftar','Memantau kemajuan materi dan petualangan pribadi.','Pengguna sudah masuk.','Progres materi dan tiap level ditampilkan.',[
('Pengguna','Membuka Progres'),('PyTurtle','Mengambil level dan progres milik pengguna'),('PyTurtle','Menghitung materi selesai dan total materi'),('PyTurtle','Menampilkan ringkasan progres serta skor level'),('Pengguna','Meninjau kemajuan belajar')],
'Pengguna yang belum belajar melihat kondisi awal. Data progres berasal dari akun yang sedang masuk.','ProgressController; /progress')
uc(16,'Melihat leaderboard','Pengguna terdaftar','Membandingkan pencapaian melalui peringkat XP.','Pengguna sudah masuk.','Daftar peringkat dan badge tersedia.',[
('Pengguna','Membuka Leaderboard'),('PyTurtle','Mengambil nama tampilan, XP dan status badge'),('PyTurtle','Mengurutkan XP menurun lalu ID pengguna'),('PyTurtle','Menampilkan peringkat dengan paginasi'),('Pengguna','Membaca peringkat atau memilih halaman lain')],
'Setiap halaman memuat sampai 20 pengguna. Email tidak ditampilkan. Permintaan halaman berikutnya mengulangi pengambilan data sesuai halaman yang dipilih.','LeaderboardController; /leaderboard')
uc(17,'Memperbarui profil','Pengguna terdaftar','Mengubah nama tampilan akun.','Pengguna sudah masuk.','Nama tampilan baru tersimpan.',[
('Pengguna','Membuka Profil saya'),('PyTurtle','Menampilkan profil saat ini'),('Pengguna','Mengubah nama tampilan dan menyimpan'),('PyTurtle','Nama terisi dan maksimal 40 karakter?',('Tidak','Tampilkan kesalahan isian',2)),('PyTurtle','Menyimpan nama tampilan'),('PyTurtle','Menampilkan konfirmasi profil diperbarui')],
'Perubahan profil yang tersedia pada formulir ini adalah nama tampilan.','SettingsController::profile/updateProfile; /profile')
uc(18,'Menautkan akun Google','Pengguna terdaftar dan Google','Menghubungkan identitas Google ke akun PyTurtle yang sedang digunakan.','Sudah masuk dan integrasi Google telah dikonfigurasi.','Identitas Google tertaut ke akun yang sama.',[
('Pengguna','Memilih Hubungkan Google pada Profil saya'),('PyTurtle','Mengarahkan permintaan penautan'),('Google','Meminta pemilihan akun dan persetujuan'),('Google','Mengembalikan identitas terverifikasi'),('PyTurtle','Sesi, email dan kepemilikan tautan sesuai?',('Tidak','Tolak penautan dan tampilkan pesan',None)),('PyTurtle','Menyimpan identitas Google pada akun aktif'),('PyTurtle','Membuat ulang sesi dan menampilkan konfirmasi')],
'Email Google harus sama dengan email akun aktif. Akun Google yang telah tertaut ke pengguna lain, identitas pengganti yang bertentangan, pembatalan atau sesi kedaluwarsa menyebabkan penolakan. Progres dan XP tetap melekat pada akun.','GoogleAuthController; GoogleAccountService; POST /profile/google')
uc(19,'Mengubah pengaturan','Pengguna terdaftar','Menyesuaikan musik, suara dan pengurangan animasi.','Pengguna sudah masuk.','Preferensi tersimpan pada akun.',[
('Pengguna','Membuka Pengaturan'),('PyTurtle','Menampilkan preferensi saat ini'),('Pengguna','Mengubah musik, suara atau pengurangan animasi'),('Pengguna','Memilih Simpan'),('PyTurtle','Nilai pengaturan valid?',('Tidak','Tampilkan kesalahan pengaturan',2)),('PyTurtle','Membuat atau memperbarui preferensi akun'),('PyTurtle','Menampilkan konfirmasi tersimpan')],
'Preferensi disimpan sebagai nilai aktif atau nonaktif. Pengaturan digunakan pada tampilan permainan.','SettingsController::index/update; /settings')

short_decisions={
'Data valid dan email belum terdaftar?':'Data akun valid?',
'Isian valid dan batas percobaan belum terlampaui?':'Isian dan batas valid?',
'Email dan kata sandi cocok?':'Kredensial cocok?',
'Sesi dan identitas Google valid?':'Identitas valid?',
'Akun Google tertaut atau email masih baru?':'Akun tertaut atau baru?',
'Token, email dan kata sandi baru valid?':'Data pemulihan valid?',
'Materi sudah terbuka?':'Materi terbuka?',
'Jawaban valid dan benar?':'Jawaban benar?',
'Level sudah terbuka?':'Level terbuka?',
'Seluruh checkpoint dan tujuan tercapai?':'Tujuan tercapai?',
'Chapter terakhir sudah selesai?':'Chapter selesai?',
'Akses dan versi petualangan atau penilaian valid?':'Akses dan versi valid?',
'Akses dan versi penilaian valid?':'Akses dan versi valid?',
'Menyetujui pengulangan?':'Setuju mengulang?',
'Hasil membantah pernyataan?':'Ada contoh bantahan?',
'Eksperimen milik pengguna dan alasan benar?':'Data dan alasan valid?',
'Nama terisi dan maksimal 40 karakter?':'Nama tampilan valid?',
'Sesi, email dan kepemilikan tautan sesuai?':'Data penautan valid?',
'Nilai pengaturan valid?':'Pengaturan valid?'
}
for u in U:
    u['nodes']=[(node[0],short_decisions.get(node[1],node[1]),*node[2:]) for node in u['nodes']]

def wrapped(draw,s,font,width):
    out=[]
    for para in s.split('\n'):
        line=''
        for w in para.split():
            trial=(line+' '+w).strip()
            if draw.textlength(trial,font=font)>width and line: out.append(line); line=w
            else: line=trial
        out.append(line)
    return out
def label(d,box,s,size=29,b=False):
    x1,y1,x2,y2=box; ft=F(size,b); lines=wrapped(d,s,ft,x2-x1-24); lh=size+7
    while len(lines)*lh > y2-y1-4 and size>18:
        size-=1; ft=F(size,b); lines=wrapped(d,s,ft,x2-x1-24); lh=size+7
    assert len(lines)*lh <= y2-y1-4, (s,box,lines)
    for k,l in enumerate(lines):
        d.text(((x1+x2-d.textlength(l,font=ft))/2,(y1+y2-len(lines)*lh)/2+k*lh),l,font=ft,fill='black')
def arrow(d,pts,dashed=False):
    if dashed:
        for a,b in zip(pts,pts[1:]):
            dist=math.dist(a,b)
            for k in range(0,int(dist),15):
                q=min(k+8,dist); d.line([(a[0]+(b[0]-a[0])*k/dist,a[1]+(b[1]-a[1])*k/dist),(a[0]+(b[0]-a[0])*q/dist,a[1]+(b[1]-a[1])*q/dist)],fill='#555555',width=3)
    else: d.line(pts,fill='#333333',width=3)
    a,b=pts[-2:]; t=math.atan2(b[1]-a[1],b[0]-a[0]); L=13
    d.polygon([b,(b[0]-L*math.cos(t-.45),b[1]-L*math.sin(t-.45)),(b[0]-L*math.cos(t+.45),b[1]-L*math.sin(t+.45))],fill='#333333')

def activity(u):
    nodes=u['nodes']; google='Google' in u['actor']; lanes=['Pengunjung' if u['id'] in ['UC01','UC02','UC03','UC04'] else 'Pengguna','PyTurtle']+(['Google'] if google else [])
    W=1650; laneW=1160/len(lanes); xs=[50+laneW*(k+.5) for k in range(len(lanes))]; n=len(nodes); H=250+145*n
    im=Image.new('RGB',(W,H),'white'); d=ImageDraw.Draw(im)
    for k,l in enumerate(lanes):
        x=50+k*laneW; d.rectangle((x,40,x+laneW,H-35),outline='#b5b5b5',width=2); d.rectangle((x,40,x+laneW,108),fill='#edf1f4',outline='#b5b5b5',width=2); label(d,(x,42,x+laneW,105),l,30,True)
    label(d,(1235,42,1630,105),'Alur alternatif',28,True)
    centers=[]; boxes=[]
    for k,node in enumerate(nodes):
        lane=0 if node[0] in ['Pengunjung','Pengguna'] else lanes.index(node[0]); x=xs[lane]; y=215+k*145
        decision=len(node)>2; bw=laneW-65; bh=120 if decision else 110
        box=(x-bw/2,y-bh/2,x+bw/2,y+bh/2); centers.append((x,y)); boxes.append(box)
    start=(centers[0][0],143); d.ellipse((start[0]-13,130,start[0]+13,156),fill='black'); arrow(d,[(start[0],156),(start[0],boxes[0][1])])
    for k,node in enumerate(nodes):
        x,y=centers[k]; box=boxes[k]; decision=len(node)>2
        if decision:
            x1,y1,x2,y2=box; d.polygon([(x,y1),(x2,y),(x,y2),(x1,y)],fill='#f5f7f8',outline='black',width=2)
            label(d,(x1+60,y1+26,x2-60,y2-26),node[1],23)
        else:
            d.rounded_rectangle(box,radius=19,fill='white',outline='black',width=2); label(d,box,node[1],27)
        if k<n-1:
            nx,ny=centers[k+1]; bottom=box[3]; top=boxes[k+1][1]; mid=(bottom+top)/2
            arrow(d,[(x,bottom),(x,mid),(nx,mid),(nx,top)])
            if decision:
                main='Tidak' if node[2][0]=='Ya' else 'Ya'; d.text((x+12,bottom+3),main,font=F(21),fill='black')
        if decision:
            guard,act,target=node[2]; alt=(1280,y-50,1615,y+50)
            d.rounded_rectangle(alt,16,fill='#fafafa',outline='#777777',width=2); label(d,alt,act,25)
            # Side connectors route along the row gap to avoid action boxes in other lanes.
            cy=y+65; arrow(d,[(box[2],y),(box[2]+16,y),(box[2]+16,cy),(1250,cy),(1250,y),(1280,y)])
            d.text((1225,cy+3),'['+guard+']',font=F(21),fill='black')
            if target is None:
                d.ellipse((1625,y-9,1643,y+9),outline='black',width=2); d.ellipse((1629,y-5,1639,y+5),fill='black'); arrow(d,[(1615,y),(1625,y)])
            else:
                tx,ty=centers[target]; returnY=boxes[target][1]-23
                # Return path runs on the alternative side; arrival is above the target activity.
                spine=1640 if k%2==0 else 1624
                arrow(d,[(1448,y+50),(1448,y+64),(spine,y+64),(spine,returnY),(tx,returnY),(tx,boxes[target][1])])
    x,y=centers[-1]; endy=H-65; arrow(d,[(x,boxes[-1][3]),(x,endy-16)])
    d.ellipse((x-16,endy-16,x+16,endy+16),outline='black',width=3); d.ellipse((x-10,endy-10,x+10,endy+10),fill='black')
    p=ASSET/f"{u['id']}-activity.png"; im.save(p)
    # Editable PlantUML follows the exact same node graph as the document image.
    lines=['@startuml',f"title {u['id']} {u['name']}", 'skinparam monochrome true']
    for i,node in enumerate(nodes):
        ln='Pengunjung' if node[0]=='Pengunjung' else node[0]
        lines.append(f'partition "{ln}" {{')
        lines.append((f'diamond "{node[1]}" as N{i}' if len(node)>2 else f'rectangle "{node[1]}" as N{i} <<activity>>'))
        lines.append('}')
    # State-style activity syntax keeps explicit back edges and lane partitions editable.
    lines=['@startuml',f"title {u['id']} {u['name']}",'skinparam monochrome true']
    for i,node in enumerate(nodes):
        lane=node[0]
        lines += [f'partition "{lane}" {{', f'(*) --> "{node[1]}"' if i==0 else f'"{nodes[i-1][1]}" --> "{node[1]}"', '}']
    # Supply a canonical structured graph beside source for faithful future editing.
    # Modern PlantUML activity beta supports labels and backward gotos for the real branches.
    lines=['@startuml',f"title {u['id']} {u['name']}",'skinparam monochrome true',f'|{nodes[0][0]}|','start']
    for i,node in enumerate(nodes):
        lines += [f'|{node[0]}|',f'label N{i}']
        if len(node)>2:
            guard,act,target=node[2]; main='Tidak' if guard=='Ya' else 'Ya'
            lines += [f'if ({node[1]}) then ({guard})',f':{act};']
            if target is None: lines+=['stop']
            else: lines += [f'goto N{target}']
            lines += [f'else ({main})','endif']
        else: lines += [f':{node[1]};']
    lines += ['stop','@enduml']; (ASSET/f"{u['id']}-activity.puml").write_text('\n'.join(lines),encoding='utf-8')
    return p

def actor(d,x,y,name):
    d.ellipse((x-21,y-70,x+21,y-28),outline='black',width=3); d.line([(x,y-28),(x,y+39)],fill='black',width=3)
    d.line([(x-39,y-4),(x+39,y-4)],fill='black',width=3); d.line([(x,y+39),(x-30,y+84)],fill='black',width=3); d.line([(x,y+39),(x+30,y+84)],fill='black',width=3)
    label(d,(x-145,y+96,x+145,y+171),name,28,True)
def usecases(name,title,ids,person):
    W=1650; H=1530 if len(ids)>4 else 1120; im=Image.new('RGB',(W,H),'white'); d=ImageDraw.Draw(im)
    d.rectangle((360,75,1350,H-65),outline='black',width=3); label(d,(370,80,1340,145),'Sistem PyTurtle',32,True)
    ay=H/2-60; actor(d,170,ay,person)
    google=any(i in [3,18] for i in ids)
    if google: actor(d,1510,ay,'Google')
    top=210; gap=(H-340)/len(ids); centers={}
    for k,i in enumerate(ids):
        u=U[i-1]; y=top+gap*k+gap/2; box=(545,y-58,1220,y+58); centers[i]=(882,y)
        # Shared association trunk avoids crossings and does not imply data flow.
        d.line([(210,ay),(302,ay)],fill='#666666',width=2)
        d.line([(302,ay),(302,y),(545,y)],fill='#666666',width=2)
        d.ellipse(box,fill='white',outline='black',width=2); label(d,(570,y-51,1195,y+51),f"{u['id']}  {u['name']}",28)
        if i in [3,18]: d.line([(1220,y),(1400,y),(1400,ay),(1471,ay)],fill='#555555',width=2)
    # Hint is an optional extension; label the relation without passing through other ellipses.
    if 11 in ids:
        y9=centers[9][1]; y11=centers[11][1]
        arrow(d,[(1220,y11),(1300,y11),(1300,y9),(1220,y9)],True)
        # Put the relationship label beside its vertical segment, clear of UC10.
        d.rectangle((1218,y9+62,1336,y9+95),fill='white')
        d.text((1220,y9+65),'<<extend>>',font=F(17),fill='black')
    p=ASSET/f'{name}.png'; im.save(p)
    src=['@startuml','left to right direction','skinparam monochrome true',f'actor "{person}" as A']
    if google: src+=['actor Google as G']
    src+=['rectangle "Sistem PyTurtle" {']+[f'usecase "{U[i-1]["id"]}\\n{U[i-1]["name"]}" as UC{i:02}' for i in ids]+['}']+[f'A -- UC{i:02}' for i in ids]
    src += [f'G -- UC{i:02}' for i in ids if i in [3,18]]
    if 11 in ids: src+=['UC11 ..> UC09 : <<extend>>\nSaat membutuhkan petunjuk']
    src+=['@enduml']; (ASSET/f'{name}.puml').write_text('\n'.join(src),encoding='utf-8')
    return p

imgs={u['id']:activity(u) for u in U}
ucimgs=[usecases('usecase-pengunjung','Pengunjung',[1,2,3,4],'Pengunjung'),usecases('usecase-belajar','Belajar',[7,8,9,10,11,12,13,14],'Pengguna terdaftar'),usecases('usecase-akun','Akun',[5,6,15,16,17,18,19],'Pengguna terdaftar')]
(ROOT/'_work'/'model.json').write_text(json.dumps(U,ensure_ascii=False,indent=2),encoding='utf-8')

# Content is shared by native Word and PDF builders.
pages=[]
def page(title,blocks): pages.append((title,blocks))
def P(s): return ('p',s)
def H(s): return ('h',s)
def IMG(p,maxh=430): return ('img',(str(p),maxh))
page('Functional Modeling',[
('subtitle','PyTurtle'),P('Dokumen proyek Rekayasa Perangkat Lunak'),
P('Nama kelompok: ____________________    Kelas: ____________________'),P('Anggota dan NIM: __________________________________________________'),P('__________________________________________________________________'),
H('1 Pendahuluan'),P('PyTurtle adalah aplikasi web untuk belajar Python Turtle melalui materi dan kuis, Code Match, Maze Quest, serta Uji Logika. Functional modeling ini menjelaskan tujuan pengguna dan alur aktivitas sistem untuk mendukung tujuan tersebut.'),
H('2 Aktor dan batas sistem'),P('Pengunjung adalah orang yang belum masuk. Pengguna terdaftar adalah pemilik akun yang sudah masuk. Google adalah sistem eksternal yang menyediakan autentikasi serta identitas untuk penautan akun.'),
P('Batas sistem mencakup antarmuka web, autentikasi, penilaian, penyimpanan progres dan preferensi PyTurtle. Basis data, parser Turtle dan proses perhitungan skor merupakan bagian internal sistem. Cakupan model tidak mencakup dashboard admin atau pengelolaan materi melalui antarmuka.'),
H('3 Panduan pembacaan'),P('Elips menunjukkan use case; garis tanpa panah menunjukkan hubungan aktor dengan use case. Relasi <<extend>> menunjukkan perilaku opsional. Login menjadi prasyarat fitur akun, sehingga tidak digambar sebagai <<include>> pada setiap fitur.'),
P('Activity diagram memakai lingkaran awal, lingkaran akhir, aktivitas bersudut membulat, decision berbentuk belah ketupat, serta panah aliran kontrol. Swimlane memisahkan tanggung jawab Pengunjung atau Pengguna, PyTurtle dan Google. Kolom Alur alternatif menampung cabang pengecualian, bukan aktor tambahan.'),
P('Model terdiri atas 19 use case dan 19 activity diagram. Setiap ID pada daftar use case memiliki activity diagram dengan ID yang sama.')])
for j,subset in enumerate([U[:10],U[10:]]):
    page(f'4 Daftar use case bagian {j+1}',[P('Daftar ini menjadi matriks keterlacakan antara tujuan pengguna, aktor dan activity diagram. ID aktivitas sama dengan ID use case.'),('table', [['ID','Use case','Aktor','Diagram']]+[[u['id'],u['name'],u['actor'],u['id']+' Activity'] for u in subset]),P('Use case pemulihan kata sandi mencakup permintaan tautan dan penggantian kata sandi. Use case materi mencakup membaca materi serta menyelesaikan kuis. Uji Logika mencakup eksperimen dan pemilihan alasan.')])
page('5 Use Case Diagram pengunjung',[P('Pengunjung mengakses pendaftaran, login email, login Google dan pemulihan kata sandi. Google berpartisipasi pada autentikasi Google sebagai aktor pendukung.'),IMG(ucimgs[0],420),P('Gambar 1 Use case untuk aktor Pengunjung dan Google.')])
page('6 Use Case Diagram pengguna dalam belajar',[P('Pengguna terdaftar mengakses aktivitas belajar dan permainan. UC11 Meminta hint memperluas UC09 Bermain Maze Quest ketika pengguna membutuhkan petunjuk saat mengerjakan kode.'),IMG(ucimgs[1],470),P('Gambar 2 Use case belajar dan petualangan untuk Pengguna terdaftar.'),P('Autosave, validasi kode, perhitungan XP, pemberian badge dan pembukaan level merupakan aktivitas internal pada use case terkait.')])
page('7 Use Case Diagram pengguna dalam akun',[P('Pengguna terdaftar dapat meninjau informasi pencapaian, memperbarui profil, mengubah pengaturan, menautkan Google dan keluar. Google berpartisipasi pada UC18.'),IMG(ucimgs[2],470),P('Gambar 3 Use case akun dan informasi untuk Pengguna terdaftar.'),P('Gambar 2 dan Gambar 3 merupakan dua tampilan dari aktor pengguna yang sama. Pemisahan bertujuan menjaga keterbacaan diagram.')])
for idx,u in enumerate(U):
    page(f"8 {u['id']} {u['name']}",[
    P('Tujuan: '+u['goal']),P('Aktor: '+u['actor']+'. Prasyarat: '+u['pre']),P('Pascakondisi sukses: '+u['post']),IMG(imgs[u['id']],420),P(f"Gambar {idx+4} Activity diagram {u['id']}."),P('Aturan dan alur alternatif: '+u['note'])])
page('9 Keterlacakan implementasi',[
P('Rujukan berikut menunjukkan bagian proyek yang menjadi dasar pemodelan. Rute tersedia pada routes/web.php. Controller berada pada app/Http/Controllers dan layanan berada pada app/Services.'),
('table',[['Use case','Rujukan utama']]+[[u['id'],u['source']] for u in U[:10]]),
P('Diagram dipisahkan berdasarkan aktor dan area penggunaan. Perilaku yang terjadi otomatis dimasukkan ke activity diagram, sedangkan tujuan pengguna tetap menjadi unit use case.')])
page('10 Keterlacakan dan aturan lintas fitur',[
('table',[['Use case','Rujukan utama']]+[[u['id'],u['source']] for u in U[10:]]),
H('Aturan lintas fitur'),P('Semua fitur belajar, permainan, progres, leaderboard dan pengaturan memerlukan sesi masuk. Sesi yang berakhir mengharuskan pengguna masuk kembali. Isian yang tidak valid ditolak dan ditampilkan sebagai kesalahan yang dapat diperbaiki.'),
P('Skor dan status penyelesaian dihitung di server. Pengiriman ulang data dengan identitas permintaan yang sama tidak menggandakan reward pada fitur yang mendukungnya. Materi dan level memiliki pemeriksaan akses sesuai progres masing-masing.'),
P('Kegagalan jaringan pada permainan ditangani melalui antrean penyimpanan di browser saat halaman telah dimuat. Navigasi penuh tanpa internet tidak termasuk kemampuan PWA. Versi petualangan dan penilaian mencegah data lama mengubah progres baru.'),
H('Kelengkapan model'),P('UC01 sampai UC19 masing-masing memiliki satu activity diagram. Google dimodelkan pada UC03 dan UC18. Tidak ada aktor admin pada cakupan aplikasi saat ini.')])

doc=Document(); sec=doc.sections[0]; sec.page_width=Inches(8.2677); sec.page_height=Inches(11.6929); sec.top_margin=Inches(.62); sec.bottom_margin=Inches(.6); sec.left_margin=Inches(.7); sec.right_margin=Inches(.7)
for nm in ['Normal','Title','Subtitle','Heading 1','Heading 2']:
    st=doc.styles[nm]; st.font.name='Arial'; st.font.color.rgb=RGBColor(0,0,0)
doc.styles['Normal'].font.size=Pt(10); doc.styles['Normal'].paragraph_format.space_after=Pt(5)
doc.styles['Normal'].paragraph_format.line_spacing=1.08
doc.styles['Title'].font.size=Pt(25); doc.styles['Heading 1'].font.size=Pt(17); doc.styles['Heading 2'].font.size=Pt(12)
foot=sec.footer.paragraphs[0]; foot.alignment=2; rr=foot.add_run(); fld=OxmlElement('w:fldSimple'); fld.set(qn('w:instr'),'PAGE'); rr._r.addnext(fld)
doc.core_properties.title='Functional Modeling'; doc.core_properties.subject='PyTurtle'; doc.core_properties.author='Kelompok Proyek PyTurtle'
for pi,(title,blocks) in enumerate(pages):
    if pi: doc.add_page_break()
    doc.add_paragraph(title,'Title' if pi==0 else 'Heading 1')
    for typ,val in blocks:
        if typ=='p': doc.add_paragraph(val)
        elif typ=='h': doc.add_paragraph(val,'Heading 2')
        elif typ=='subtitle': doc.add_paragraph(val,'Subtitle')
        elif typ=='img':
            path,mh=val; wi,hi=Image.open(path).size; w=min(6.65,mh/72*wi/hi); p=doc.add_paragraph(); p.alignment=1; r=p.add_run(); r.add_picture(path,width=Inches(w)); inline=r._r.xpath('.//wp:docPr'); inline[0].set('descr',Path(path).stem.replace('-',' '))
        elif typ=='table':
            tb=doc.add_table(rows=0, cols=len(val[0])); tb.autofit=False
            widths=[.62,3.15,2.15,.9] if len(val[0])==4 else [.7,6.05]
            for rownum,row in enumerate(val):
                cells=tb.add_row().cells
                for col,(cell,txt) in enumerate(zip(cells,row)):
                    cell.width=Inches(widths[col]); cell.text=txt; cell.vertical_alignment=1
                    for p in cell.paragraphs:
                        p.paragraph_format.space_after=Pt(5); p.paragraph_format.space_before=Pt(5)
                        for run in p.runs: run.font.size=Pt(9); run.bold=rownum==0
                    pr=cell._tc.get_or_add_tcPr(); borders=OxmlElement('w:tcBorders')
                    for side in ['top','left','bottom','right']:
                        el=OxmlElement('w:'+side); el.set(qn('w:val'),'single'); el.set(qn('w:sz'),'4'); el.set(qn('w:color'),'D9D9D9'); borders.append(el)
                    pr.append(borders)
                    if rownum==0:
                        shade=OxmlElement('w:shd'); shade.set(qn('w:fill'),'E8EDF1'); pr.append(shade)
                if rownum==0: trpr=tb.rows[0]._tr.get_or_add_trPr(); repeat=OxmlElement('w:tblHeader'); trpr.append(repeat)
doc.save(ROOT/'Functional-Modeling-PyTurtle.docx')

PW,PH=A4; pdf=canvas.Canvas(str(ROOT/'Functional-Modeling-PyTurtle.pdf'),pagesize=A4)
pdf.setTitle('Functional Modeling PyTurtle'); pdf.setAuthor('Kelompok Proyek PyTurtle')
LEFT=48; RIGHT=PW-48; y=0
def lines_pdf(s,font,size,width):
    result=[]
    for pp in s.split('\n'):
        ln=''
        for w in pp.split():
            t=(ln+' '+w).strip()
            if pdfmetrics.stringWidth(t,font,size)>width and ln: result.append(ln); ln=w
            else: ln=t
        result.append(ln)
    return result
def pdftext(s,size=10,font='Arial',space=6):
    global y
    for line in lines_pdf(s,font,size,RIGHT-LEFT):
        pdf.setFont(font,size); pdf.setFillColorRGB(0,0,0); pdf.drawString(LEFT,y,line); y-=size*1.32
    y-=space
for pi,(title,blocks) in enumerate(pages):
    y=PH-48
    pdftext(title,25 if pi==0 else 16,'ArialB',12)
    for typ,val in blocks:
        if typ=='p': pdftext(val)
        elif typ=='h': y-=4; pdftext(val,12,'ArialB',6)
        elif typ=='subtitle': pdftext(val,17,'Arial',13)
        elif typ=='img':
            path,mh=val; wi,hi=Image.open(path).size; w=min(RIGHT-LEFT,mh*wi/hi); h=w*hi/wi
            pdf.drawImage(path,(PW-w)/2,y-h,width=w,height=h); y-=h+10
        elif typ=='table':
            rows=val; widths=[43,207,153,96] if len(rows[0])==4 else [48,451]
            for rn,row in enumerate(rows):
                txts=[lines_pdf(t,'ArialB' if rn==0 else 'Arial',9,widths[k]-14) for k,t in enumerate(row)]
                rh=max(len(ls) for ls in txts)*12+16; x=LEFT
                for k,ls in enumerate(txts):
                    pdf.setFillColorRGB(*((.91,.93,.95) if rn==0 else (1,1,1))); pdf.setStrokeColorRGB(.85,.85,.85); pdf.rect(x,y-rh,widths[k],rh,fill=1,stroke=1)
                    pdf.setFont('ArialB' if rn==0 else 'Arial',9); pdf.setFillColorRGB(0,0,0)
                    for li,l in enumerate(ls): pdf.drawString(x+7,y-15-li*12,l)
                    x+=widths[k]
                y-=rh
            y-=12
    assert y>=40,(pi+1,title,y)
    pdf.setFont('Arial',8); pdf.setFillColorRGB(.35,.35,.35); pdf.drawRightString(RIGHT,25,str(pi+1)); pdf.showPage()
pdf.save()
readme='''# Functional Modeling PyTurtle\n\nDokumen berisi 19 use case dan 19 activity diagram, dengan tiga tampilan Use Case Diagram.\n\n- Functional-Modeling-PyTurtle.docx: dokumen Word.\n- Functional-Modeling-PyTurtle.pdf: dokumen PDF.\n- diagram/*.puml: sumber PlantUML yang dapat diedit.\n- diagram/*.png: gambar resolusi tinggi yang digunakan dalam dokumen.\n\nLengkapi nama kelompok, kelas, anggota dan NIM pada dokumen sebelum pengumpulan.\nDiagram mencerminkan kode aplikasi utama pada 20 Oktober 2026, bukan salinan proyek di output.\n'''
(ROOT/'README.md').write_text(readme,encoding='utf-8')
with zipfile.ZipFile(ROOT/'Sumber-Diagram-PyTurtle.zip','w',zipfile.ZIP_DEFLATED) as z:
    z.write(ROOT/'README.md','README.md')
    for f in ASSET.iterdir(): z.write(f,'diagram/'+f.name)
print(json.dumps({'pages':len(pages),'use_cases':len(U),'activity_diagrams':len(imgs),'use_case_views':len(ucimgs),'root':str(ROOT)}))
