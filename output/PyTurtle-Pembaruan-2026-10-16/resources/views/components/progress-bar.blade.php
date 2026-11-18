@props(['value'=>0,'label'=>'Progres'])
<div class="progress-track" role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ max(0,min(100,$value)) }}%"></span></div>
