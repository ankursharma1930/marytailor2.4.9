/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
function mfYtwIframe() {
    /* Iframe */
    document.querySelectorAll('.mf-ytw-wrapper').forEach(function (mfYtwWrapper) {
        mfYtwWrapper.addEventListener('click', function (e) {
            if (e.target.classList.contains('mf-ytw-play-button')) {
                mfYtwWrapper.click();
            } else {
                if (e.target.getAttribute('data-iframe-created')) return;
                e.target.setAttribute('data-iframe-created', 1);

                var eh = mfYtwWrapper.clientWidth / 1.777777;
                var maxH = parseInt(mfYtwWrapper.getAttribute('data-height'));
                if (eh > maxH) {
                    eh = maxH;
                }

                var iframe = document.createElement("iframe");
                iframe.setAttribute("width", "100%");
                iframe.setAttribute("height", eh);
                iframe.setAttribute("frameborder", "0");
                iframe.setAttribute("allow", "autoplay");

                if (mfYtwWrapper.getAttribute('shorts')){
                    iframe.setAttribute("allow", "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share");
                }

                if (e.target.getAttribute('data-iframe-title')) {
                    iframe.setAttribute('title', mfYtwWrapper.getAttribute('data-iframe-title'));
                }
                if (mfYtwWrapper.getAttribute('data-allowfullscreen')) {
                    iframe.setAttribute("allowfullscreen", "");
                }
                iframe.setAttribute("src", mfYtwWrapper.getAttribute('data-iframe-src'));
                e.target.innerHTML = '';
                e.target.appendChild(iframe);
            }

        });
    });


    function resize() {
        document.querySelectorAll('.mf-ytw-wrapper').forEach(function (el) {
            var w = el.getAttribute('data-width');
            if (w.indexOf('px') != -1) {
                w = parseInt(w);
                var pw = el.parentElement.getAttribute('width');
                var ew = (pw < w) ? pw : w;
                el.setAttribute('width', ew  + 'px');
            }
            var eh = el.clientWidth / 1.777777;
            var maxH = parseInt(el.getAttribute('data-height'));
            if (eh > maxH) {
                eh = maxH;
            }

            if (el.querySelector('.mf-ytw-youtube')) {
                el.querySelector('.mf-ytw-youtube').style.height = eh + 'px';
            }

            if (el.querySelector('iframe')) {
                el.querySelector('iframe').style.height = eh + 'px';
            }
        });
    }

    resize();
    setTimeout(function(){
        resize();
    }, 1000);

    window.addEventListener("resize", resize);

    /* Lazy Load */

    if (!document.querySelectorAll('.mf-ytw-youtube').length) {
        return;
    }
    var w = window;

    function getOffcet(elem)
    {
        var rect = elem.getBoundingClientRect();
        // Make sure element is not hidden (display: none) or disconnected
        if ( rect.width || rect.height || elem.getClientRects().length ) {
            doc = elem.ownerDocument;
            win = window;
            docElem = doc.documentElement;

            return {
                top: rect.top + win.pageYOffset - docElem.clientTop,
                left: rect.left + win.pageXOffset - docElem.clientLeft
            };
        }

        return {top:0, left: 0};
    }

    function lazyload() {
        var inview = document.querySelectorAll('.mf-ytw-youtube');
        let inviewArray = Array.from(inview);
        var inviewFilter = inviewArray.filter(function (e) {
            var th = 300;
            const stylesE = w.getComputedStyle(e);
            if (stylesE.display === 'none' || stylesE.visibility === 'hidden') return;

            var wt = w.scrollY,
                wb = wt + w.innerHeight,
                et = getOffcet(e).top,
                eb = et + e.getAttribute('height');

            return eb >= wt - th && et <= wb + th;
        });

        inviewFilter.forEach(function (e) {
            var a = ['background', 'background-size'];
            for (var i=0; i<a.length; i++) {
                let aDataName = 'data-' + a[i];
                let aName = a[i];
                if (e.getAttribute(aDataName)) {
                    e.style[aName] = e.getAttribute(aDataName);
                    e.setAttribute(aDataName, false);
                }
            }
        });
    }

    lazyload();
    w.addEventListener("scroll", lazyload);
    w.addEventListener("resize", lazyload);

    /* On product tabs click */
    var productInfoDetailedImgs = document.querySelectorAll('.product.info.detailed .data.item.title a');
    if (productInfoDetailedImgs) {
        for (var i=0;i<productInfoDetailedImgs.length; i++) {
            productInfoDetailedImgs[i].addEventListener('click', function(){
                setTimeout(resize, 100);
                setTimeout(lazyload, 100);
            });
        }
    }
}

if (document.readyState === "complete"
    || document.readyState === "loaded"
    || document.readyState === "interactive") {
    mfYtwIframe();
} else {
    document.addEventListener("DOMContentLoaded", mfYtwIframe);
}
