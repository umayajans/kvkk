/* Umay Çerez Yöneticisi: çerez bandı ve tercihler penceresi. Bağımlılık yok. */
(function (w, d) {
    'use strict';

    var OPTIONAL = ['islevsel', 'analitik', 'pazarlama'];
    var FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])';

    var G = w.kvkkcGuard || {};
    var KVKKC = w.KVKKC = w.KVKKC || {};
    var cfg, opts, root, banner, wrap, modal, floatBtn, live, receipt, lastFocus, styleEl, bound;

    if (!G.state) {
        G.state = { ok: false, t: 0, id: '', c: [] };
        G.allow = function (cats) { G.state.c = cats.slice(); };
    }

    /**
     * Küçük DOM yardımcısı.
     */
    function h(tag, props, kids) {
        var node = d.createElement(tag);
        var key, value;
        for (key in props || {}) {
            value = props[key];
            if (value === null || value === undefined || value === false) {
                continue;
            }
            if (key === 'text') {
                node.textContent = value;
            } else if (key === 'html') {
                node.innerHTML = value;
            } else if (key.indexOf('on') === 0) {
                node.addEventListener(key.slice(2), value);
            } else {
                node.setAttribute(key === 'cls' ? 'class' : key, value === true ? '' : value);
            }
        }
        (kids || []).forEach(function (kid) {
            if (kid) {
                node.appendChild(typeof kid === 'string' ? d.createTextNode(kid) : kid);
            }
        });
        return node;
    }

    function button(label, cls, onclick, extra) {
        var props = { type: 'button', cls: 'kvkkc-btn ' + (cls || ''), onclick: onclick, text: label };
        for (var key in extra || {}) {
            props[key] = extra[key];
        }
        return h('button', props);
    }

    function catName(key) {
        for (var i = 0; i < cfg.cats.length; i++) {
            if (cfg.cats[i].key === key) {
                return cfg.cats[i].name;
            }
        }
        return key;
    }

    function links(all) {
        var items = cfg.links.filter(function (link) { return all || link[2]; });
        if (!items.length) {
            return null;
        }
        return h('p', { cls: 'kvkkc-links' }, items.map(function (link) {
            return h('a', { href: link[1], text: link[0] });
        }));
    }

    function chosen() {
        return G.state.ok ? G.state.c.slice() : [];
    }

    /* ---------------------------------------------------------------- *
     * Kurulum
     * ---------------------------------------------------------------- */

    KVKKC.init = function (config, options) {
        if (root) {
            KVKKC.destroy();
        }
        cfg = config;
        opts = options || {};

        build();
        bindOnce();

        if (cfg.preview) {
            placeholders();
            if (opts.layer === 'prefs') {
                KVKKC.open('prefs');
            } else if (opts.layer === 'float') {
                showFloat();
            } else {
                showBanner();
            }
            return;
        }

        if (G.state.ok) {
            apply(G.state.c, false);
            showFloat();
        } else {
            showBanner();
        }

        placeholders();

        if (w.location.hash === '#kvkkc-ayarlar') {
            KVKKC.open('prefs');
        }
    };

    KVKKC.destroy = function () {
        if (root) {
            root.remove();
        }
        if (styleEl) {
            styleEl.remove();
        }
        d.documentElement.classList.remove('kvkkc-locked');
        root = banner = wrap = modal = floatBtn = live = receipt = styleEl = null;
    };

    function build() {
        var classes = [
            'kvkkc',
            'kvkkc--' + cfg.layout,
            'kvkkc--pos-' + cfg.pos,
            'kvkkc--anim-' + cfg.anim,
            'kvkkc--font-' + cfg.font,
            'kvkkc--prefs-' + cfg.prefsStyle,
            'kvkkc--float-' + cfg.float.pos,
            cfg.shadow ? 'kvkkc--shadow' : '',
            cfg.preview ? 'kvkkc--preview' : ''
        ];

        // Renk değişkenleri :root'a yazılır; böylece içerik içindeki yer tutucular da aynı paleti kullanır.
        var vars = [];
        for (var key in cfg.vars) {
            vars.push('--kvkkc-' + key + ':' + cfg.vars[key]);
        }
        vars.push('--kvkkc-z:' + cfg.z);
        styleEl = h('style', { id: 'kvkkc-vars', text: ':root{' + vars.join(';') + '}' + (cfg.css || '') });
        d.documentElement.classList.toggle('kvkkc-dark-auto', !!cfg.autoDark);

        root = h('div', { id: 'kvkkc-root', cls: classes.join(' ').trim() });
        live = h('div', { cls: 'kvkkc-sr', role: 'status', 'aria-live': 'polite' });

        buildBanner();
        buildModal();

        floatBtn = h('button', {
            type: 'button',
            cls: 'kvkkc-float',
            'aria-label': cfg.text.floatLabel,
            title: cfg.text.floatLabel,
            html: cfg.float.icon,
            hidden: true,
            onclick: function () { KVKKC.open('prefs'); }
        });

        root.appendChild(banner);
        root.appendChild(wrap);
        root.appendChild(floatBtn);
        root.appendChild(live);

        // İlk sekme odağı banda gelsin diye gövdenin başına eklenir.
        d.body.insertBefore(root, d.body.firstChild);
        d.body.appendChild(styleEl);
    }

    function buildBanner() {
        var t = cfg.text;
        var modalLayout = cfg.layout === 'modal';

        var accept = button(t.accept, 'kvkkc-btn--accept', function () { decide('accept_all', OPTIONAL); });
        var reject = button(t.reject, 'kvkkc-btn--reject', function () { decide('reject_all', []); });
        var third = cfg.quick
            ? button(t.save, 'kvkkc-btn--save', function () { decide('custom', quickValues()); })
            : button(t.prefs, 'kvkkc-btn--prefs', function () { KVKKC.open('prefs'); }, { 'aria-haspopup': 'dialog' });
        var buttons = cfg.order === 'reject_first' ? [reject, accept, third] : [accept, reject, third];

        var quick = null;
        if (cfg.quick) {
            quick = h('div', { cls: 'kvkkc-quick', role: 'group', 'aria-label': t.prefsTitle }, OPTIONAL.map(function (key) {
                return h('label', { cls: 'kvkkc-quick-item' }, [
                    h('input', { type: 'checkbox', value: key }),
                    h('span', { text: catName(key) })
                ]);
            }));
        }

        banner = h('section', {
            cls: 'kvkkc-banner',
            role: modalLayout ? 'dialog' : 'region',
            'aria-modal': modalLayout ? 'true' : null,
            'aria-label': modalLayout ? null : t.region,
            'aria-labelledby': 'kvkkc-title',
            'aria-describedby': 'kvkkc-message',
            hidden: true
        }, [
            cfg.overlay !== 'none' || modalLayout ? h('div', { cls: 'kvkkc-overlay kvkkc-overlay--' + (cfg.overlay === 'none' ? 'dim' : cfg.overlay) }) : null,
            h('div', { cls: 'kvkkc-banner-box' }, [
                h('div', { cls: 'kvkkc-banner-body' }, [
                    cfg.logo ? h('img', { cls: 'kvkkc-logo', src: cfg.logo, alt: '' }) : null,
                    h('h2', { id: 'kvkkc-title', cls: 'kvkkc-title', text: t.title }),
                    h('div', { id: 'kvkkc-message', cls: 'kvkkc-message', html: t.message }),
                    links(false)
                ]),
                quick,
                h('div', { cls: 'kvkkc-actions' }, buttons),
                cfg.quick ? h('button', { type: 'button', cls: 'kvkkc-textlink', text: t.prefs, onclick: function () { KVKKC.open('prefs'); } }) : null
            ])
        ]);
    }

    function quickValues() {
        var values = [];
        banner.querySelectorAll('.kvkkc-quick input:checked').forEach(function (input) {
            values.push(input.value);
        });
        return values;
    }

    function buildModal() {
        var t = cfg.text;

        var list = h('div', { cls: 'kvkkc-cats' }, cfg.cats.map(function (cat) {
            var required = cat.key === 'zorunlu';
            var nameId = 'kvkkc-cat-' + cat.key;
            var tableId = 'kvkkc-cookies-' + cat.key;
            var control = required
                ? h('span', { cls: 'kvkkc-always', text: t.always })
                : h('button', {
                    type: 'button',
                    cls: 'kvkkc-switch',
                    role: 'switch',
                    'aria-checked': 'false',
                    'aria-labelledby': nameId,
                    'data-cat': cat.key,
                    onclick: function () {
                        this.setAttribute('aria-checked', this.getAttribute('aria-checked') === 'true' ? 'false' : 'true');
                    }
                }, [h('span', { cls: 'kvkkc-switch-knob' })]);

            var toggle = h('button', {
                type: 'button',
                cls: 'kvkkc-more',
                'aria-expanded': 'false',
                'aria-controls': tableId,
                onclick: function () {
                    var open = this.getAttribute('aria-expanded') !== 'true';
                    this.setAttribute('aria-expanded', open ? 'true' : 'false');
                    d.getElementById(tableId).hidden = !open;
                }
            }, [h('span', { text: t.showCookies }), h('span', { cls: 'kvkkc-count', text: String(cat.cookies.length) })]);

            return h('div', { cls: 'kvkkc-cat kvkkc-cat--' + cat.key }, [
                h('div', { cls: 'kvkkc-cat-head' }, [
                    h('h3', { id: nameId, cls: 'kvkkc-cat-name', text: cat.name }),
                    control
                ]),
                h('p', { cls: 'kvkkc-cat-desc', text: cat.desc }),
                cat.cookies.length ? toggle : h('p', { cls: 'kvkkc-empty', text: t.noCookies }),
                cat.cookies.length ? h('div', { id: tableId, cls: 'kvkkc-cookies', hidden: true }, [cookieTable(cat.cookies)]) : null
            ]);
        }));

        receipt = h('p', { cls: 'kvkkc-receipt', hidden: true });

        modal = h('div', {
            cls: 'kvkkc-modal',
            role: 'dialog',
            'aria-modal': 'true',
            'aria-labelledby': 'kvkkc-modal-title',
            'aria-describedby': 'kvkkc-modal-intro',
            tabindex: '-1'
        }, [
            h('div', { cls: 'kvkkc-modal-head' }, [
                h('h2', { id: 'kvkkc-modal-title', cls: 'kvkkc-title', text: t.prefsTitle }),
                h('button', {
                    type: 'button',
                    cls: 'kvkkc-close',
                    'aria-label': t.close,
                    html: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
                    onclick: closePrefs
                })
            ]),
            h('div', { cls: 'kvkkc-modal-body' }, [
                h('div', { id: 'kvkkc-modal-intro', cls: 'kvkkc-message', html: t.prefsIntro }),
                list,
                links(true),
                receipt
            ]),
            h('div', { cls: 'kvkkc-modal-foot' }, [
                button(t.reject, 'kvkkc-btn--reject', function () { decide('reject_all', []); }),
                button(t.save, 'kvkkc-btn--save', function () { decide('custom', switchValues()); }),
                button(t.accept, 'kvkkc-btn--accept', function () { decide('accept_all', OPTIONAL); })
            ])
        ]);

        wrap = h('div', { cls: 'kvkkc-modal-wrap', hidden: true }, [
            h('div', { cls: 'kvkkc-modal-backdrop', onclick: closePrefs }),
            modal
        ]);
    }

    function cookieTable(rows) {
        var t = cfg.text;
        var labels = [t.colName, t.colProvider, t.colPurpose, t.colDuration];
        return h('table', { cls: 'kvkkc-table' }, [
            h('thead', {}, [h('tr', {}, labels.map(function (label) { return h('th', { scope: 'col', text: label }); }))]),
            h('tbody', {}, rows.map(function (row) {
                var name = row.s ? h('span', { text: row.n }) : h('code', { text: row.n });
                return h('tr', {}, [
                    h('td', { 'data-label': labels[0] }, [name]),
                    h('td', { 'data-label': labels[1], text: row.p }),
                    h('td', { 'data-label': labels[2], text: row.u }),
                    h('td', { 'data-label': labels[3], text: row.d || '–' })
                ]);
            }))
        ]);
    }

    function switchValues() {
        var values = [];
        modal.querySelectorAll('.kvkkc-switch[aria-checked="true"]').forEach(function (sw) {
            values.push(sw.getAttribute('data-cat'));
        });
        return values;
    }

    /* ---------------------------------------------------------------- *
     * Görünürlük ve odak
     * ---------------------------------------------------------------- */

    function reveal(node) {
        node.hidden = false;
        node.classList.remove('kvkkc-in');
        // Bir kare bekleyip geçiş sınıfı eklenir; hareketi azaltma tercihinde CSS geçişi kapatır.
        w.requestAnimationFrame(function () {
            w.requestAnimationFrame(function () { node.classList.add('kvkkc-in'); });
        });
    }

    function showBanner() {
        floatBtn.hidden = true;
        reveal(banner);
        if (cfg.lock && !cfg.preview) {
            d.documentElement.classList.add('kvkkc-locked');
        }
        if (cfg.layout === 'modal' && !cfg.preview) {
            focusFirst(banner);
        }
    }

    function hideBanner() {
        banner.hidden = true;
        d.documentElement.classList.remove('kvkkc-locked');
    }

    function showFloat() {
        floatBtn.hidden = !cfg.float.on;
    }

    function focusFirst(scope) {
        var target = scope.querySelector('.kvkkc-actions .kvkkc-btn, .kvkkc-modal-foot .kvkkc-btn') || scope.querySelector(FOCUSABLE);
        if (target) {
            target.focus();
        }
    }

    KVKKC.open = function (layer) {
        if (layer !== 'prefs' || !root) {
            return;
        }
        var current = chosen();
        modal.querySelectorAll('.kvkkc-switch').forEach(function (sw) {
            sw.setAttribute('aria-checked', current.indexOf(sw.getAttribute('data-cat')) > -1 ? 'true' : 'false');
        });
        updateReceipt();
        lastFocus = d.activeElement;
        floatBtn.hidden = true;
        reveal(wrap);
        if (!cfg.preview) {
            d.documentElement.classList.add('kvkkc-locked');
            modal.focus();
        }
    };

    function closePrefs() {
        var bannerOpen = !banner.hidden;
        wrap.hidden = true;
        if (!(bannerOpen && cfg.lock)) {
            d.documentElement.classList.remove('kvkkc-locked');
        }
        if (!bannerOpen && (G.state.ok || cfg.preview)) {
            showFloat();
        }
        if (lastFocus && lastFocus.focus && !cfg.preview) {
            lastFocus.focus();
        }
    }

    function trap(event, scope) {
        var items = Array.prototype.filter.call(scope.querySelectorAll(FOCUSABLE), function (node) {
            return node.offsetParent !== null;
        });
        if (!items.length) {
            return;
        }
        var first = items[0];
        var last = items[items.length - 1];
        if (event.shiftKey && (d.activeElement === first || d.activeElement === modal)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && d.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function bindOnce() {
        if (bound) {
            return;
        }
        bound = true;

        d.addEventListener('keydown', function (event) {
            if (!root) {
                return;
            }
            var prefsOpen = !wrap.hidden;
            if (event.key === 'Escape' && prefsOpen) {
                // Pencereyi kapatmak bir seçim sayılmaz; ilk katman açıksa ekranda kalır.
                closePrefs();
            } else if (event.key === 'Tab' && prefsOpen) {
                trap(event, modal);
            } else if (event.key === 'Tab' && cfg.layout === 'modal' && !banner.hidden) {
                trap(event, banner);
            }
        });

        // [kvkk_cerez_ayarlari] kısa kodu ve menüdeki "#kvkkc-ayarlar" bağlantıları.
        d.addEventListener('click', function (event) {
            var link = event.target.closest && event.target.closest('a[href$="#kvkkc-ayarlar"], .kvkkc-open-prefs');
            if (link && root) {
                event.preventDefault();
                KVKKC.open('prefs');
            }
        });

        d.addEventListener('kvkkc:blocked', function () {
            if (root && !cfg.preview) {
                placeholders();
            }
        });
    }

    function updateReceipt() {
        if (!G.state.ok || !G.state.id) {
            receipt.hidden = true;
            return;
        }
        var date = new Date(G.state.t * 1000);
        receipt.textContent = cfg.text.consentId + ': ' + G.state.id + ' · ' + cfg.text.consentDate + ': ' + date.toLocaleString(d.documentElement.lang || undefined);
        receipt.hidden = false;
    }

    /* ---------------------------------------------------------------- *
     * Karar ve uygulama
     * ---------------------------------------------------------------- */

    function writeCookie(cats) {
        var value = [cfg.v, G.state.t, G.state.id, cats.join('-')].join('.');
        var secure = w.location.protocol === 'https:' ? ';secure' : '';
        d.cookie = 'kvkkc_consent=' + value + ';path=/;max-age=' + cfg.days * 86400 + ';samesite=lax' + secure;
    }

    function decide(action, cats) {
        if (cfg.preview) {
            return;
        }

        var previous = chosen();
        cats = OPTIONAL.filter(function (key) { return cats.indexOf(key) > -1; });
        var withdrawn = previous.filter(function (key) { return cats.indexOf(key) < 0; });

        if (action === 'custom' && withdrawn.length) {
            action = 'withdraw';
        }

        G.state.ok = true;
        G.state.t = Math.floor(Date.now() / 1000);
        G.state.c = cats;
        writeCookie(cats);

        var request = send(action, cats);

        hideBanner();
        wrap.hidden = true;
        d.documentElement.classList.remove('kvkkc-locked');
        showFloat();
        live.textContent = cfg.text.saved;

        if (withdrawn.length) {
            clearCookies(withdrawn);
            if (cfg.reload) {
                // Çalışmaya başlamış kodlar ancak yenilemeyle durur; kayıt isteğinin gitmesi beklenir.
                var reload = function () { w.location.reload(); };
                request.then(reload, reload);
                w.setTimeout(reload, 1500);
                return;
            }
        }

        apply(cats, true);
    }

    function send(action, cats) {
        if (!w.fetch) {
            return Promise.resolve();
        }
        return w.fetch(cfg.rest, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: action,
                categories: cats,
                version: cfg.v,
                page: w.location.pathname,
                id: G.state.id || ''
            })
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            if (data && data.id) {
                G.state.id = data.id;
                G.state.t = data.time || G.state.t;
                updateReceipt();
            }
        });
    }

    function apply(cats, changed) {
        G.allow(cats);

        if (changed && cfg.cm && G.gtag) {
            G.gtag('consent', 'update', G.modeState(cats));
        }

        unblock(cats);

        if (cfg.gtm) {
            w.dataLayer = w.dataLayer || [];
            w.dataLayer.push({
                event: 'kvkkc_consent',
                kvkkc_zorunlu: true,
                kvkkc_islevsel: cats.indexOf('islevsel') > -1,
                kvkkc_analitik: cats.indexOf('analitik') > -1,
                kvkkc_pazarlama: cats.indexOf('pazarlama') > -1
            });
        }

        // WP Consent API (Site Kit, WooCommerce gibi eklentiler bu kategorileri okur).
        if (typeof w.wp_set_consent === 'function') {
            w.wp_set_consent('functional', 'allow');
            w.wp_set_consent('preferences', cats.indexOf('islevsel') > -1 ? 'allow' : 'deny');
            w.wp_set_consent('statistics', cats.indexOf('analitik') > -1 ? 'allow' : 'deny');
            w.wp_set_consent('statistics-anonymous', cats.indexOf('analitik') > -1 ? 'allow' : 'deny');
            w.wp_set_consent('marketing', cats.indexOf('pazarlama') > -1 ? 'allow' : 'deny');
        }

        d.dispatchEvent(new CustomEvent('kvkkc:consent', { detail: { categories: cats.slice(), changed: changed } }));
    }

    /**
     * İzin verilen kategorilerin engelini kaldırır. Script'ler yeni öğe olarak, belgedeki
     * sırayla eklenir (tipi değiştirilen bir script tarayıcıda çalışmaz).
     */
    function unblock(cats) {
        d.querySelectorAll('[data-kvkkc-cat]').forEach(function (node) {
            if (cats.indexOf(node.getAttribute('data-kvkkc-cat')) < 0) {
                return;
            }
            var src = node.getAttribute('data-kvkkc-src');

            if (node.tagName === 'SCRIPT') {
                var script = d.createElement('script');
                Array.prototype.forEach.call(node.attributes, function (attr) {
                    if (attr.name !== 'type' && attr.name.indexOf('data-kvkkc-') !== 0) {
                        script.setAttribute(attr.name, attr.value);
                    }
                });
                if (node.getAttribute('data-kvkkc-type')) {
                    script.type = node.getAttribute('data-kvkkc-type');
                }
                if (src) {
                    script.async = node.hasAttribute('async');
                    script.src = src;
                } else {
                    script.text = node.text;
                }
                node.parentNode.replaceChild(script, node);
                return;
            }

            if (src) {
                node.removeAttribute('data-kvkkc-src');
                node.removeAttribute('data-kvkkc-cat');
                node.classList.remove('kvkkc-blocked');
                node.src = src;
            }
        });

        d.querySelectorAll('.kvkkc-ph').forEach(function (ph) {
            if (cats.indexOf(ph.getAttribute('data-kvkkc-cat')) > -1) {
                ph.remove();
            }
        });
    }

    function clearCookies(cats) {
        var patterns = [];
        cats.forEach(function (key) {
            patterns = patterns.concat(cfg.clear[key] || []);
        });
        var matches = function (name) {
            return patterns.some(function (pattern) {
                return pattern.slice(-1) === '*' ? name.indexOf(pattern.slice(0, -1)) === 0 : name === pattern;
            });
        };

        var host = w.location.hostname;
        var parts = host.split('.');
        var domains = ['', host, '.' + host];
        for (var i = 1; i < parts.length - 1; i++) {
            domains.push('.' + parts.slice(i).join('.'));
        }

        d.cookie.split(';').forEach(function (pair) {
            var name = pair.split('=')[0].trim();
            if (name && matches(name)) {
                domains.forEach(function (domain) {
                    d.cookie = name + '=;max-age=0;path=/' + (domain ? ';domain=' + domain : '');
                });
            }
        });

        try {
            Object.keys(w.localStorage).forEach(function (key) {
                if (matches(key)) {
                    w.localStorage.removeItem(key);
                }
            });
        } catch (e) {
            // Depolama kapalıysa yapılacak bir şey yok.
        }
    }

    /* ---------------------------------------------------------------- *
     * Engellenen içerik için yer tutucular
     * ---------------------------------------------------------------- */

    function placeholder(cat) {
        var text = cfg.text.phText.replace('{kategori}', catName(cat));
        return h('div', { cls: 'kvkkc-ph', 'data-kvkkc-cat': cat }, [
            h('div', { cls: 'kvkkc-ph-inner' }, [
                h('span', { cls: 'kvkkc-ph-icon', html: cfg.float.icon }),
                h('p', { text: text }),
                button(cfg.text.phButton, 'kvkkc-ph-btn', function () {
                    var cats = chosen();
                    if (cats.indexOf(cat) < 0) {
                        cats.push(cat);
                    }
                    decide('custom', cats);
                })
            ])
        ]);
    }

    function placeholders() {
        var allowed = G.state.c;

        d.querySelectorAll('iframe.kvkkc-blocked').forEach(function (frame) {
            var cat = frame.getAttribute('data-kvkkc-cat');
            if (!cat || allowed.indexOf(cat) > -1 || (frame.previousElementSibling && frame.previousElementSibling.classList.contains('kvkkc-ph'))) {
                return;
            }
            var ph = placeholder(cat);
            var width = parseInt(frame.getAttribute('width'), 10);
            var height = parseInt(frame.getAttribute('height'), 10);
            if (width > 1 && height > 1) {
                ph.style.aspectRatio = width + ' / ' + height;
            }
            frame.parentNode.insertBefore(ph, frame);
        });

        // Elementor YouTube videoları iframe'i tarayıcıda kurar; sunucuda yalnızca boş bir kutu vardır.
        if (cfg.yt && allowed.indexOf(cfg.yt) < 0) {
            d.querySelectorAll('.elementor-widget-video .elementor-wrapper').forEach(function (box) {
                var widget = box.closest('.elementor-widget-video');
                var settings = widget ? widget.getAttribute('data-settings') || '' : '';
                if (settings.indexOf('youtube') < 0 || box.querySelector('iframe:not(.kvkkc-blocked), .kvkkc-ph')) {
                    return;
                }
                if (w.getComputedStyle(box).position === 'static') {
                    box.style.position = 'relative';
                }
                box.appendChild(placeholder(cfg.yt));
            });
        }
    }

    if (w.kvkkcConfig && !w.kvkkcConfig.preview) {
        if (d.readyState === 'loading') {
            d.addEventListener('DOMContentLoaded', function () { KVKKC.init(w.kvkkcConfig); });
        } else {
            KVKKC.init(w.kvkkcConfig);
        }
    }
})(window, document);
