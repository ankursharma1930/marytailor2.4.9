/**
 * e
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
document.querySelectorAll('[data-mf-ytw-youtube-link]').forEach(item => {
    item.addEventListener('click', event => {
        let url = event.target.closest('[data-mf-ytw-youtube-link]').getAttribute('data-mf-ytw-youtube-link');
        if (!url) return;

        let rg = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        let id = url.match(rg);
        if (!id || id.length < 3) return;
        let element = event.target.closest('[data-mf-ytw-youtube-link]');
        event.stopImmediatePropagation();

        let popup = '<div class="custom-modal-popup">'
            + '<div class="custom-modal-inner-wrap">'
            + '<header class="modal-header">'
            + '<div class="modal-title"></div>'
            + '<button class="action-close" id="close-popup" data-role="closeBtn" type="button">'
            + '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">'
            + '<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>'
            + '</svg>'
            + '</button>'
            + '</header>'
            + '<div class="modal-content" data-role="content">'
            + '<div style="width: 100%;">'
            + '</div>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '<div class="custom-modals-overlay" style="z-index: 901;"></div>';

        let style = '<style>\n' +
            '    .custom-modal-popup {position: fixed;top: 0;right: 0;bottom: 0;left: 0;z-index: 902;min-width: 0;visibility: hidden;opacity: 0;-webkit-transition: visibility 0s .3s,opacity .3s ease;transition: visibility 0s .3s,opacity .3s ease;pointer-events: none;overflow-y: auto;}\n' +
            '    .custom-modal-popup._show {visibility: visible;opacity: 1;-webkit-transition: opacity .3s ease;transition: opacity .3s ease;}\n' +
            '    .custom-modal-inner-wrap {min-width: 350px; margin: 5rem auto;width: 75%;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-flex-direction: column;-ms-flex-direction: column;flex-direction: column;box-sizing: border-box;height: auto;left: 0;position: absolute;right: 0;-webkit-transform: translateY(-200%);transform: translateY(-200%);-webkit-transition: -webkit-transform .2s ease;transition: transform .2s ease;background: 0 0;box-shadow: none;opacity: 1;pointer-events: auto;}\n' +
            '    .custom-modal-popup._show .custom-modal-inner-wrap {-webkit-transform: translateY(0);transform: translateY(0);}\n' +
            '    .custom-modal-popup .modal-header {padding: 1.2rem 0 1.2rem;}\n' +
            '    .custom-modal-popup .modal-header .modal-title {color: #fff;font-weight: 600;padding: 0 3rem;}\n' +
            '    .custom-modal-popup .modal-header .action-close {display: block;position: absolute;top: -20px;right: -20px;padding: 15px;background: none;background: 0 0;-moz-box-sizing: content-box;border: 0;box-shadow: none;margin: 0;text-decoration: none;}\n' +
            '    .custom-modal-popup .modal-content {padding: 0;margin: 0;border: none;}\n' +
            '    body._show-modal {height: 100%;overflow: hidden;width: 100%;}\n' +
            '    body .custom-modals-overlay {opacity: 0;background: none;position: static;}\n' +
            '    body._show-modal .custom-modals-overlay {background-color: rgba(51,51,51,.55);bottom: 0;left: 0;position: fixed;right: 0;top: 0;opacity: 1;}\n' +
            '</style>'

        url = 'https://www.youtube.com/embed/' + id[2] + '?start=1&amp;rel=0&amp;showinfo=0&amp;autoplay=1&modestbranding=1';
        if (element.getAttribute('mf-ytw-start-at')) {
            url += '&start=' + element.getAttribute('mf-ytw-start-at')
        }

        var width = element.getAttribute('mf-ytw-width');
        var height = '500px';
        if (element.getAttribute('mf-ytw-height')) {
            height = element.getAttribute('mf-ytw-height');
        }

        if (!document.querySelector('.custom-modal-popup')){
            document.getElementById('maincontent').insertAdjacentHTML('beforeend', popup);
            document.querySelector('head').insertAdjacentHTML('beforeend', style);

            document.getElementById('close-popup').addEventListener('click', event => {
                popup = event.target.closest('.custom-modal-popup');
                closePopup(popup);
            });

            document.querySelector('.custom-modals-overlay').addEventListener('click', event => {
                popup = document.querySelector('.custom-modal-popup');
                closePopup(popup);
            })
        }

        function closePopup(popup){
            document.body.classList.remove('_show-modal');
            popup.classList.remove('_show');
            popup.querySelector('.modal-content div').innerHTML = '';
        }

        document.body.classList.add('_show-modal');
        document.querySelector('.custom-modal-popup').classList.add('_show');
        document.querySelector('.custom-modal-popup .modal-content div').insertAdjacentHTML('beforeend', '' +
            '<iframe width="100%" height="' + height + '" frameBorder="0" allow="autoplay" ' +
            ( element.getAttribute('mf-ytw-allowfullscreen') ? ' allowFullScreen=""' : '') +
            ( element.getAttribute('mf-ytw-youtube-name') ? ' title="' + element.getAttribute('mf-ytw-youtube-name') + '"' : '')
            + 'src="' + url + '"'
            + '></iframe>');

        if (width && window.innerWidth >= 1024) {
            setTimeout(function () {
                document.querySelector('.custom-modal-popup').style.width = width;
            }, 500);
        }
        return false;
    })
})
