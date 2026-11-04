@if (session('pesan'))
    <div class="pesan" role="status">
        <span>{{ session('pesan') }}</span>
        <button type="button" data-tutup-pesan>Tutup</button>
    </div>
@endif
@if (session('galat'))
    <div class="pesan pesan--galat" role="alert">
        <span>{{ session('galat') }}</span>
        <button type="button" data-tutup-pesan>Tutup</button>
    </div>
@endif
