/* KVKK Çerez Yöneticisi: sayfa başı koruması. Head'e satır içi basılır, window.kvkkcGuard verisini kullanır. */
(function (w, d) {
    'use strict';

    var G = w.kvkkcGuard;
    if (!G || G.ready) {
        return;
    }
    G.ready = true;

    var OPTIONAL = ['islevsel', 'analitik', 'pazarlama'];
    var MODE = {
        islevsel: ['functionality_storage', 'personalization_storage'],
        analitik: ['analytics_storage'],
        pazarlama: ['ad_storage', 'ad_user_data', 'ad_personalization']
    };

    // Rıza çerezi: "sürüm.zaman.rızaNo.kategori-kategori". Sürüm eskiyse rıza yok sayılır.
    var match = d.cookie.match(/(?:^|;\s*)kvkkc_consent=([^;]*)/);
    var parts = match ? decodeURIComponent(match[1]).split('.') : [];
    var valid = parts.length === 4 && /^\d+$/.test(parts[0]) && +parts[0] === G.v;

    G.state = {
        ok: valid,
        t: valid ? +parts[1] : 0,
        id: valid ? parts[2] : '',
        c: valid && parts[3] ? parts[3].split('-').filter(function (c) { return OPTIONAL.indexOf(c) > -1; }) : []
    };

    G.allow = function (cats) {
        G.state.c = cats.slice();
    };

    G.modeState = function (cats) {
        var state = { security_storage: 'granted' };
        for (var key in MODE) {
            for (var i = 0; i < MODE[key].length; i++) {
                state[MODE[key][i]] = cats.indexOf(key) > -1 ? 'granted' : 'denied';
            }
        }
        return state;
    };

    // Google İzin Modu: varsayılan her şey reddedildi, kayıtlı tercih varsa hemen güncellenir.
    w.dataLayer = w.dataLayer || [];
    G.gtag = function () {
        w.dataLayer.push(arguments);
    };
    if (G.cm) {
        var defaults = G.modeState([]);
        defaults.wait_for_update = 500;
        G.gtag('consent', 'default', defaults);
        G.gtag('set', 'ads_data_redaction', true);
        if (G.state.ok) {
            G.gtag('consent', 'update', G.modeState(G.state.c));
        }
    }

    // WP Consent API kullanan eklentiler (Site Kit, WooCommerce) için rıza tipi.
    w.wp_consent_type = 'optin';

    /**
     * Adresin kategorisi. Sunucudaki KVKKC_Services::match_url ile aynı kurallar: önce elle
     * kurallar, sonra en uzun alan adı/yol eşleşmesi.
     */
    G.category = function (url) {
        url = String(url || '');
        if (!url) {
            return '';
        }
        var i, low = url.toLowerCase();
        for (i = 0; i < G.m.length; i++) {
            if (low.indexOf(G.m[i][0].toLowerCase()) > -1) {
                return G.m[i][1];
            }
        }
        var loc;
        try {
            loc = new URL(url, d.baseURI);
        } catch (e) {
            return '';
        }
        var host = loc.hostname.toLowerCase();
        var tail = loc.pathname + loc.search;
        var best = '';
        var score = -1;
        for (i = 0; i < G.r.length; i++) {
            var rule = G.r[i];
            var sameHost = host === rule[0] || host.slice(-rule[0].length - 1) === '.' + rule[0];
            if (sameHost && (!rule[1] || tail.indexOf(rule[1]) === 0) && rule[0].length + rule[1].length > score) {
                score = rule[0].length + rule[1].length;
                best = rule[2];
            }
        }
        return best;
    };

    if (!G.b) {
        return;
    }

    var setAttr = Element.prototype.setAttribute;

    /**
     * İzin verilmemişse öğeyi engeller: adres data-kvkkc-src'de saklanır, script çalışmaz.
     *
     * @return {boolean} Engellendi mi?
     */
    function stop(el, url) {
        var cat = G.category(url);
        if (!cat || cat === 'zorunlu' || G.state.c.indexOf(cat) > -1) {
            return false;
        }
        setAttr.call(el, 'data-kvkkc-src', url);
        setAttr.call(el, 'data-kvkkc-cat', cat);
        if (el.tagName === 'SCRIPT') {
            if (el.type && el.type !== 'text/plain') {
                setAttr.call(el, 'data-kvkkc-type', el.type);
            }
            setAttr.call(el, 'type', 'text/plain');
        } else {
            el.classList.add('kvkkc-blocked');
        }
        return true;
    }

    var protos = { script: w.HTMLScriptElement && HTMLScriptElement.prototype, iframe: w.HTMLIFrameElement && HTMLIFrameElement.prototype };

    // Sonradan oluşturulan script ve iframe'lerin src ataması yakalanır (yükleme hiç başlamaz).
    function watch(el, tag) {
        var desc = Object.getOwnPropertyDescriptor(protos[tag], 'src');
        if (!desc || !desc.set) {
            return;
        }
        Object.defineProperty(el, 'src', {
            configurable: true,
            get: function () {
                return el.getAttribute('data-kvkkc-src') || desc.get.call(el);
            },
            set: function (value) {
                if (!stop(el, value)) {
                    desc.set.call(el, value);
                }
            }
        });
        el.setAttribute = function (name, value) {
            if (String(name).toLowerCase() === 'src' && stop(el, value)) {
                return;
            }
            return setAttr.apply(el, arguments);
        };
    }

    var create = d.createElement;
    d.createElement = function (tag) {
        var el = create.apply(this, arguments);
        var name = String(tag).toLowerCase();
        if (protos[name]) {
            watch(el, name);
        }
        return el;
    };

    // innerHTML ile eklenen iframe'ler için yedek kontrol: yükleme başlamış olabilir, about:blank ile kesilir.
    // Not: sonradan eklenen satır içi script'ler bu yolla yakalanamaz.
    function inspect(node) {
        if (node.nodeType !== 1) {
            return;
        }
        var frames = node.tagName === 'IFRAME' ? [node] : node.getElementsByTagName('iframe');
        for (var i = 0; i < frames.length; i++) {
            var frame = frames[i];
            if (frame.classList.contains('kvkkc-blocked')) {
                frame.dispatchEvent(new CustomEvent('kvkkc:blocked', { bubbles: true }));
            } else if (frame.getAttribute('src') && stop(frame, frame.getAttribute('src'))) {
                setAttr.call(frame, 'src', 'about:blank');
                frame.dispatchEvent(new CustomEvent('kvkkc:blocked', { bubbles: true }));
            }
        }
    }

    if (w.MutationObserver) {
        new MutationObserver(function (records) {
            for (var i = 0; i < records.length; i++) {
                for (var j = 0; j < records[i].addedNodes.length; j++) {
                    inspect(records[i].addedNodes[j]);
                }
            }
        }).observe(d.documentElement, { childList: true, subtree: true });
    }
})(window, document);
