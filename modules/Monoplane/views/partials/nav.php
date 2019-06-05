<?php if (empty($mp['nav'])) return ''; ?>

        <nav>
            <ul>
@foreach($mp['nav'] as $n)
@if(empty($type) || isset($n['navigation']) && ((is_string($n['navigation']) && $n['navigation'] == $type) || (is_array($n['navigation']) && in_array($type, $n['navigation']))))
                <li><a class="{{ $mp['_id'] == $n[$mp['id']] ? 'active' : '' }}" href="{{ strpos($n[$mp['id']], 'http') === 0 ? $n[$mp['id']] : $app->baseUrl($n[$mp['id']]) }}">{{ $n['title'] }}</a></li>
@endif
@endforeach
            </ul>
        </nav>
