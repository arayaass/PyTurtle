<div class="dialogue" x-show="dialogOpen" x-cloak role="dialog" aria-modal="true" aria-label="Cerita Shiha" x-trap.inert.noscroll="dialogOpen">
<div class="dialogue-inner"><img src="/assets/characters/shiha/portrait.svg" alt="Shiha"><div><span class="eyebrow">SHIHA · PENJELAJAH MUDA</span><p x-text="dialogText"></p>
<div class="spread"><span class="small muted" x-text="(dialogIndex+1)+' / '+dialogLines.length"></span><div class="button-row"><button class="text-link" @click="skipTyping()">Tampilkan teks</button><button class="primary" @click="nextDialogue()" x-text="dialogIndex===dialogLines.length-1?'Lanjutkan':'Berikutnya →'"></button></div></div>
</div></div></div>
