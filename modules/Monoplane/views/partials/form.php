
<form id="contact" method="post" action="{{ MP_BASE_URL . '/forms/submit/' . $form }}">

    <fieldset>
        <legend>
        @if(!empty($options['title']))
            @lang($options['title'])
        @else
            @lang('Contact Me')
        @endif
        </legend>

        @if($error)
        <p class="message error alarm">
            <strong>@lang('Something went wrong').</strong><br>
            {{ $error }}
        </p>
        @endif

        @if(!empty($message['success']))
        <p class="message success">@lang($message['success'])</p>
        @endif

        @if(!empty($message['notice']))
        <p class="message error">@lang($message['notice'])</p>
        @endif

        {{ $fields }}

        <div><input name="submit" type="submit" value="@lang('Send')" /></div>
    </fieldset>

</form>
