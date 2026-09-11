// Article rich-text editor — loads Trix from a CDN on demand and wires image
// uploads to /publish/media. Degrades to a plain <textarea> if the CDN is blocked.
(function () {
    'use strict';

    var TRIX_VERSION = '2.1.15';
    var CDN = 'https://cdnjs.cloudflare.com/ajax/libs/trix/' + TRIX_VERSION + '/';

    var form = document.querySelector('form[data-media-endpoint]');
    var editor = document.querySelector('trix-editor');
    var hidden = document.getElementById('article-body');
    if (!form || !editor || !hidden) { return; }

    var endpoint = form.getAttribute('data-media-endpoint');
    var token = form.getAttribute('data-token') || '';

    // fallbackToTextarea can be reached from more than one place (a failed
    // script load, and the safety-net timeout below) — guard so a second call
    // is a no-op instead of touching an element already removed from the DOM.
    var fellBack = false;
    function fallbackToTextarea() {
        if (fellBack) { return; }
        fellBack = true;
        clearTimeout(fallbackTimer);

        var ta = document.createElement('textarea');
        ta.className = 'field__control';
        ta.rows = 18;
        ta.value = hidden.value;
        ta.addEventListener('input', function () { hidden.value = ta.value; });
        editor.parentNode.replaceChild(ta, editor);
    }

    // Trix mirrors its HTML into the hidden input automatically, but make sure
    // the latest value is there on submit.
    form.addEventListener('submit', function () {
        if (!fellBack && editor.editor) { hidden.value = editor.value; }
    });

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = CDN + 'trix.min.css';
    document.head.appendChild(css);

    var script = document.createElement('script');
    script.src = CDN + 'trix.umd.min.js';
    script.async = true;
    script.onerror = fallbackToTextarea;
    script.onload = wire;
    document.head.appendChild(script);

    // Safety net: if Trix hasn't initialised in a few seconds (slow or
    // silently-blocked CDN), fall back.
    var initialised = false;
    document.addEventListener('trix-initialize', function () { initialised = true; });
    var fallbackTimer = setTimeout(function () {
        if (!initialised && !window.Trix) { fallbackToTextarea(); }
    }, 6000);

    function wire() {
        if (!window.Trix) { fallbackToTextarea(); return; }

        // Only accept image files as attachments.
        document.addEventListener('trix-file-accept', function (event) {
            var file = event.file;
            if (!file || file.type.indexOf('image/') !== 0) {
                event.preventDefault();
                window.alert('Only image files can be added to a story.');
            } else if (file.size > 3 * 1024 * 1024) {
                event.preventDefault();
                window.alert('Images must be 3 MB or smaller.');
            }
        });

        document.addEventListener('trix-attachment-add', function (event) {
            var attachment = event.attachment;
            if (!attachment.file) { return; }
            upload(attachment);
        });
    }

    function upload(attachment) {
        var data = new FormData();
        data.append('file', attachment.file);
        data.append('_token', token);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint, true);
        xhr.setRequestHeader('X-CSRF-Token', token);

        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                attachment.setUploadProgress(Math.round((e.loaded / e.total) * 100));
            }
        });

        xhr.addEventListener('load', function () {
            var res = {};
            try { res = JSON.parse(xhr.responseText); } catch (e) {}
            if (xhr.status >= 200 && xhr.status < 300 && res.url) {
                attachment.setAttributes({ url: res.url, href: res.url });
            } else {
                window.alert(res.error || 'The image could not be uploaded.');
                attachment.remove();
            }
        });
        xhr.addEventListener('error', function () {
            window.alert('The image could not be uploaded.');
            attachment.remove();
        });

        xhr.send(data);
    }
}());
