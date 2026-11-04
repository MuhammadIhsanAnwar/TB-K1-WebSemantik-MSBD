@props(['n'])
<span class="bintang" role="img" aria-label="{{ $n }} dari 5 bintang">@for ($i = 1; $i <= 5; $i++)<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.6L10 14.7l-5.1 2.7 1-5.6-4.1-4 5.7-.8z" fill="{{ $i <= $n ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>@endfor</span>
