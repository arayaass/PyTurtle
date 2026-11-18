@props(['command'])
<span class="command-pill" tabindex="0" title="{{ ['forward'=>'Maju mengikuti arah Shiha','backward'=>'Mundur tanpa berputar','left'=>'Berputar berlawanan jarum jam','right'=>'Berputar searah jarum jam','penup'=>'Angkat pena, tanpa garis jejak','pendown'=>'Turunkan pena untuk menggambar'][$command] }}">{{ $command }}({{ in_array($command,['penup','pendown'])?'':'n' }})</span>
