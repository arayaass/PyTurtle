<header class="site-header" x-data="{open:false}">
<div class="nav-inner"><a class="brand" href="/home"><span class="brand-mark">p<span>y</span></span>PyTurtle<span class="brand-dot">.</span></a>
<button class="mobile-menu secondary" @click="open=!open" :aria-expanded="open" aria-controls="navigation">Menu</button>
<nav id="navigation" aria-label="Navigasi utama" :class="{'is-open':open}">
@foreach(['home'=>'Home','modules'=>'Modules','maze'=>'Maze Quest','code-match'=>'Code Match','uji-logika'=>'Uji Logika','leaderboard'=>'Leaderboard','progress'=>'Progress'] as $route=>$label)
<a href="{{ route($route) }}" class="{{ request()->is($route,$route.'/*') ? 'active' : '' }}" @if(request()->is($route,$route.'/*')) aria-current="page" @endif>{{ $label }}</a>
@endforeach
</nav>
<div class="user-menu" x-data="{show:false}" @click.outside="show=false" @keydown.escape.window="show=false">
<button @click="show=!show" :aria-expanded="show" class="user-toggle"><span class="avatar">{{ mb_substr(auth()->user()->display_name ?? auth()->user()->name,0,1) }}</span><span class="user-name">{{ auth()->user()->display_name ?? auth()->user()->name }}</span><span aria-hidden="true">⌄</span></button>
<div class="dropdown" x-show="show" x-cloak><a href="/profile">Profil saya</a><a href="/settings">Pengaturan</a><form action="/logout" method="POST">@csrf<button type="submit">Keluar</button></form></div>
</div></div></header>
