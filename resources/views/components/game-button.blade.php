{{-- A button cut from the design, placed in the design's own pixels. `cap`: a 3D key whose top really sinks into its rim. --}}
@props(['image', 'x', 'y', 'w', 'h', 'label', 'cap' => false])

<button type="button" ontouchstart=""
        {{ $attributes->class(['game-btn', 'st', 'game-btn--cap' => $cap]) }}
        style="--x:{{ $x }};--y:{{ $y }};--w:{{ $w }};--h:{{ $h }};--img:url('{{ asset('images/game/stage/buttons/'.$image.'.png') }}')"
        aria-label="{{ $label }}">
    @if ($cap)
        <span class="game-btn-rim"></span>
    @endif
    <span class="game-btn-face"></span>
</button>
