@props(['name' => 'password', 'label' => 'Kata sandi', 'hint' => null])
<div class="password-field" x-data="{ visible: false }">
    <label for="{{ $name }}">{{ $label }} @if($hint)<small>{{ $hint }}</small>@endif</label>
    <div class="password-control">
        <input id="{{ $name }}" name="{{ $name }}" type="password" :type="visible ? 'text' : 'password'" {{ $attributes }}>
        <button class="password-toggle" type="button" @click="visible = !visible" :aria-pressed="visible" aria-pressed="false" aria-controls="{{ $name }}" :aria-label="(visible ? 'Sembunyikan ' : 'Tampilkan ') + @js(mb_strtolower($label))" aria-label="Tampilkan {{ mb_strtolower($label) }}" x-text="visible ? 'Sembunyikan' : 'Lihat'">Lihat</button>
    </div>
</div>
