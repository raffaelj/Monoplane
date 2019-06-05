(function(g, d) {

    // for relative paths - MP_BASE_URL should be declared in the head of your
    // template file: <script>var MP_BASE_URL = '{{ MP_BASE_URL }}';</script>
    if (!MP_BASE_URL) MP_BASE_URL = '';

    var MP = {

        // source: http://youmightnotneedjquery.com/#ready
        ready: function (fn) {
            if (d.readyState != 'loading'){
                fn();
            } else if (d.addEventListener) {
                d.addEventListener('DOMContentLoaded', fn);
            } else {
                d.attachEvent('onreadystatechange', function() {
                    if (d.readyState != 'loading')
                        fn();
                });
            }
        },

        convertVideoLinksToIframes: function() {

            var $this = this;

            var video_links = d.querySelectorAll('a[data-video-id]');

            Array.prototype.forEach.call(video_links, function(el, i){

                var id       = el.getAttribute('data-video-id');
                var provider = el.getAttribute('data-video-provider');
                var asset    = el.getAttribute('data-video-thumb');
                var width    = 480;
                var height   = 370;

                if ((data_width = el.getAttribute('data-video-width'))
                    && (data_height = el.getAttribute('data-video-height'))) {

                    // reassign aspect ratio
                    height = width * (data_height / data_width);

                }

                var thumb = MP_BASE_URL + '/getImage?src=' + asset + '&w=480&o=1';

                if (provider == 'youtube') {
                    var src = 'https://www.youtube-nocookie.com/embed/'
                        + id + '?rel=0&showinfo=0&autoplay=1';
                }

                if (provider == 'vimeo') {
                    var src = 'https://player.vimeo.com/video/'
                        + id + '?color=ffffff&title=0&byline=0&portrait=0&autoplay=1';
                }

                var container = d.createElement('div');

                container.setAttribute('class', 'video_embed_container');

                var iframe = d.createElement('iframe');

                iframe.setAttribute('class', 'video_embed');
                iframe.setAttribute('width', width);
                iframe.setAttribute('height', height);
                iframe.setAttribute('src', 'about:blank');
                iframe.setAttribute('data-src', src);
                iframe.setAttribute('src', 'about:blank');
                iframe.setAttribute('allowfullscreen', '');
                iframe.style.width = width+'px';
                iframe.style.height = height+'px';
                iframe.style['background-image'] = 'url(' + thumb + ')';

                container.appendChild(iframe);

                var play_button = d.createElement('span');
                play_button.setAttribute('class', 'play_button');

                container.appendChild(play_button);

                el.parentNode.insertBefore(container, el);
                el.parentNode.style['text-align'] = 'center';

                play_button.addEventListener('click', function(e) {

                    if (Cookie.get('loadExternalVideos') == '1') {
                        iframe.setAttribute('src', iframe.getAttribute('data-src'));
                        iframe.style['background-image'] = '';
                    }
                    else {
                        $this.displayPrivacyNotice(iframe);
                    }

                });

            });

        },

        displayPrivacyNotice: function (target) {

            var banner = d.getElementById('privacy-notice');
            banner.style.display = 'block';

            var form = d.getElementById('privacy-notice-form');

            form.addEventListener('submit', function(e) {

                if (e) e.preventDefault();

                var data = new FormData(form);

                var loadExternalVideos = data.get('loadExternalVideos');

                // Cookie won't be set, if loadExternalVideos == null
                Cookie.set('loadExternalVideos', loadExternalVideos);

                if (loadExternalVideos) {
                    target.setAttribute('src', target.getAttribute('data-src'));
                    target.style['background-image'] = '';
                }

                // hide banner
                banner.style.display = '';

            });

            form.addEventListener('reset', function(e) {

                banner.style.display = '';

            });

        },

    };

    var Cookie = {

        lifeTime: '30', // cookie life time in days

        set: function(key, value, lifeTime) {

            if (!key || (!value && value != 0)) return;
            if (!lifeTime && lifeTime != 0) lifeTime = this.lifeTime;

            var expirationDate = new Date();
            expirationDate.setTime(expirationDate.getTime() + lifeTime * 86400000)

            d.cookie = key + '=' + value + ';expires=' + expirationDate.toUTCString() + '; path=/';

        },

        get: function(key) {

            if (d.cookie == '') return;

            // source: https://stackoverflow.com/a/42578414
            // the shorter version failed in IE: https://stackoverflow.com/a/50452780
            var cockie = d.cookie.split('; ').reduce(function(result, pairStr) {
                var arr = pairStr.split('=');
                if (arr.length === 2) { result[arr[0]] = arr[1]; }
                return result;
            }, {});

            return key ? cockie[key] : cockie;

        },

        destroy: function(key) {

            this.set(key, '', 0);

        }

    };

    MP.Cookie = Cookie;
    g.MP = MP;

})(this, document);

MP.ready(function() {

    MP.convertVideoLinksToIframes();

});
