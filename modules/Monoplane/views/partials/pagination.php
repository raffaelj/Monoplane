<?php $dropdown_limit = $dropdown_limit ?? 5; ?>

@if($pagination['pages'] > 1)
            <nav class="pagination {{ $pagination['pages'] > $dropdown_limit ? 'dropdown' : '' }}">
                <span class="pagination_label">@lang('Page')</span>
                <ul>
                @if($pagination['pages'] > $dropdown_limit && $pagination['page'] > 1)
                    <li class="pagination_first"><a href="@base($pagination['slug'])" title="@lang('first')">&laquo;</a></li>
                @endif
                @if($pagination['page'] == 2)
                    <li class="pagination_previous"><a href="@base($pagination['slug'])" title="@lang('previous')">&lt;</a></li>
                @endif
                @if($pagination['page'] > 2)
                    <li class="pagination_previous"><a href="@base($pagination['slug'].'/'.($pagination['page']-1))" title="@lang('previous')">&lt;</a></li>
                @endif
                @if($pagination['pages'] > $dropdown_limit)
                    <li class="pagination_dropdown_headline" tabindex="0" title="@lang('Page') {{ $pagination['page'] }} @lang('of') {{ $pagination['pages'] }}"><span>{{ $pagination['page'] }}</span><ul>
                @endif
                @for($i = 1; $i <= $pagination['pages']; $i++)
                    <li class="pagination_item {{ $i == $pagination['page'] ? 'active' : '' }}">
                    @if($i == $pagination['page'])
                        <span title="@lang('Page') {{ $i }} @lang('of') {{ $pagination['pages'] }}">{{ $i }}</span>
                    @else
                        <a href="@base($pagination['slug'].($i == 1 ? '' : '/'.$i))" title="@lang('Page') {{ $i }} @lang('of') {{ $pagination['pages'] }}">{{ $i }}</a>
                    @endif
                    </li>
                @endfor
                @if($pagination['pages'] > $dropdown_limit)
                    </ul></li>
                @endif
                @if($pagination['page'] < $pagination['pages'])
                    <li class="pagination_next"><a href="@base($pagination['slug'].'/'.($pagination['page']+1))" title="@lang('next')">&gt;</a></li>
                @endif
                @if($pagination['pages'] > $dropdown_limit && $pagination['page'] < $pagination['pages'])
                    <li class="pagination_last"><a href="@base($pagination['slug'].'/'.$pagination['pages'])" title="@lang('last')">&raquo;</a></li>
                @endif
                </ul>
            </nav>
@endif
