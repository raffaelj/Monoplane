<?php
if (isset($mp['privacy_notice']['route_'.$app('i18n')->locale])) {
    $route = $mp['privacy_notice']['route_'.$app('i18n')->locale];
} elseif (isset($mp['privacy_notice']['route'])) {
    $route = $mp['privacy_notice']['route'];
} else {
    $route = '';
}
?>

<aside id="privacy-notice">

    <h2>@lang('Privacy notice')</h2>

    <p>@lang('To watch embedded videos on this page, they have to be loaded from a third party. At this moment, the platform owners will collect personal data about you.') @lang('To prevent you from accepting this message over and over again, a so-called cookie will be set.')
    <br>
    @lang('You can find details in my') <a href="@base($route)">@lang('privacy notice')</a>.</p>

    <form id="privacy-notice-form" action="{{ $app['route'] }}" method="post">

        <input id="loadExternalVideos" name="loadExternalVideos" type="checkbox" value="1" checked />
        <label for="loadExternalVideos">
            @lang('Allow cookie usage and to load external videos from YouTube and Vimeo')
        </label>

        <button id="privacy-notice-submit" type="submit">@lang('Yes')</button>
        <button id="privacy-notice-cancel" type="reset">@lang('No')</button>
    </form>

</aside>
