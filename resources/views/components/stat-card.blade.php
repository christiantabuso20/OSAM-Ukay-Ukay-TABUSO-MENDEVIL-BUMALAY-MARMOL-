@props(['icon', 'label', 'value', 'hint' => null, 'tone' => 'berry'])

<div class="stat-card">
    <div class="stat-icon tone-{{ $tone }}"><i class="fa-solid {{ $icon }}"></i></div>
    <div>
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
        @if ($hint)<div class="stat-hint">{{ $hint }}</div>@endif
    </div>
</div>
