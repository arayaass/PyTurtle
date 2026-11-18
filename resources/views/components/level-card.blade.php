@props(['level', 'progress' => null, 'unlocked' => false, 'lockMessage' => null])
<article class="card level-card {{ !$unlocked?'locked':'' }}">
    <div class="level-art level-art-{{ $level->id }}">
        <span class="level-number">0{{ $level->id }}</span
        ><img src="/assets/nature/forest-daylight.svg" alt="" loading="lazy" />
    </div>
    <div class="card-body">
        <div class="card-meta">
            <span>CHAPTER 0{{ $level->id }}</span
            ><span>{{ $progress?->status==='completed'?'✓ Selesai':($unlocked?'Terbuka':'Terkunci') }}</span>
        </div>
        <h3>{{ $level->title }}</h3>
        <p>{{ $level->definition['subtitle'] }}</p>
        <div class="level-bottom">
            <span>{{ $progress?->best_score ?? 0 }} <small>/ 100 poin</small></span>
            @if ($unlocked)
                <a class="text-link" href="/maze/{{ $level->id }}"
                    >{{ $progress?->status==='completed'?'Main lagi':'Jelajahi' }} <span aria-hidden="true">→</span></a>
            @else
                <span class="muted small">{{ $lockMessage ?? 'Selesaikan level sebelumnya' }}</span>
            @endif
        </div>
    </div>
</article>
