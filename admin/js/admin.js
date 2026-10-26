/* Umay Çerez Yöneticisi: yönetim paneli. Bağımlılık yok (logo seçimi için wp.media). */
(function (w, d) {
    'use strict';

    var A = w.kvkkcAdmin || {};
    var $ = function (sel, root) { return (root || d).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); };

    function post(data) {
        var body = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            Object.keys(data).forEach(function (key) { body.append(key, data[key]); });
        }
        body.set('_ajax_nonce', A.nonce);
        return fetch(A.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) {
            return r.json();
        });
    }

    function markDirty(form) {
        var flag = $('[data-kvkkc-dirty]', form);
        if (flag) {
            flag.hidden = false;
        }
        form.kvkkcDirty = true;
    }

    function guardUnload(form) {
        form.addEventListener('input', function () { markDirty(form); });
        form.addEventListener('change', function () { markDirty(form); });
        form.addEventListener('submit', function () { form.kvkkcDirty = false; });
        w.addEventListener('beforeunload', function (event) {
            if (form.kvkkcDirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    /* ---------------------------------------------------------------- *
     * Sekmeler
     * ---------------------------------------------------------------- */
    function initTabs() {
        var tabs = $$('.kvkkc-tab');
        if (!tabs.length) {
            return;
        }
        var referer = $('input[name="_wp_http_referer"]');

        function select(tab, focus) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                d.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
            if (focus) {
                tab.focus();
            }
            var id = tab.getAttribute('data-tab');
            try {
                w.sessionStorage.setItem('kvkkc-tab', id);
            } catch (e) {
                // Depolama kapalıysa sekme hatırlanmaz.
            }
            // Kaydettikten sonra aynı sekmeye dönülsün.
            if (referer) {
                referer.value = referer.value.split('#')[0].replace(/&settings-updated=true/, '') + '#' + id;
            }
        }

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(tab, false); });
            tab.addEventListener('keydown', function (event) {
                var next = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[event.key];
                if (next) {
                    event.preventDefault();
                    select(tabs[(i + next + tabs.length) % tabs.length], true);
                }
            });
        });

        var wanted = w.location.hash.slice(1);
        try {
            wanted = wanted || w.sessionStorage.getItem('kvkkc-tab');
        } catch (e) {
            // yok say
        }
        var initial = tabs.filter(function (t) { return t.getAttribute('data-tab') === wanted; })[0];
        select(initial || tabs[0], false);
    }

    /* ---------------------------------------------------------------- *
     * Alanlar
     * ---------------------------------------------------------------- */
    function initFields(form) {
        function radioValue(name) {
            var checked = $('input[name="kvkkc_settings[' + name + ']"]:checked', form);
            return checked ? checked.value : '';
        }

        function conditions() {
            $$('[data-show-if]', form).forEach(function (row) {
                var rule = row.getAttribute('data-show-if').split(':');
                row.hidden = radioValue(rule[0]) !== rule[1];
            });
        }

        form.addEventListener('change', conditions);
        conditions();

        form.addEventListener('input', function (event) {
            var input = event.target;
            if (input.type === 'range') {
                var out = input.parentNode.querySelector('output');
                out.textContent = input.value + (out.textContent.match(/[^\d]+$/) || [''])[0];
            }
            if (input.type === 'color') {
                input.parentNode.querySelector('[data-kvkkc-color-value]').textContent = input.value;
            }
        });

        $$('[data-kvkkc-media]', form).forEach(function (box) {
            var input = $('input', box);
            var thumb = $('.kvkkc-media-thumb', box);
            var clear = $('[data-kvkkc-media-clear]', box);
            var frame;

            $('[data-kvkkc-media-pick]', box).addEventListener('click', function () {
                if (!w.wp || !w.wp.media) {
                    return;
                }
                frame = frame || w.wp.media({ title: A.i18n.chooseLogo, button: { text: A.i18n.useLogo }, library: { type: 'image' }, multiple: false });
                frame.off('select').on('select', function () {
                    var item = frame.state().get('selection').first().toJSON();
                    input.value = item.id;
                    $('img', thumb).src = (item.sizes && item.sizes.medium ? item.sizes.medium : item).url;
                    thumb.hidden = false;
                    clear.hidden = false;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                frame.open();
            });

            clear.addEventListener('click', function () {
                input.value = '0';
                thumb.hidden = true;
                clear.hidden = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        $$('[data-kvkkc-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var text = button.getAttribute('data-kvkkc-copy');
                var done = function () {
                    button.classList.add('is-copied');
                    button.setAttribute('title', A.i18n.copied);
                    w.setTimeout(function () { button.classList.remove('is-copied'); }, 1400);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(done);
                }
            });
        });
    }

    /* ---------------------------------------------------------------- *
     * Canlı önizleme ve uyum çizelgesi
     * ---------------------------------------------------------------- */
    function initPreview(form) {
        var frame = $('#kvkkc-preview-frame');
        if (!frame) {
            return;
        }
        var device = $('[data-kvkkc-device-frame]');
        var view = $('[data-kvkkc-view]');
        var preview = $('.kvkkc-preview');
        var expand = $('[data-kvkkc-expand]');
        var SIZES = { desktop: [1280, 800], mobile: [375, 720] };
        var layer = 'banner';
        var config = null;
        var timer;
        var seq = 0;

        function esc(text) {
            return String(text).replace(/[&<>"]/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
            });
        }

        // Önizleme sitedeki bandın aynısıdır: aynı CSS ve JS dosyası, sunucuda temizlenmiş aynı yapılandırma.
        function render() {
            if (!config) {
                return;
            }
            var json = JSON.stringify(config).replace(/</g, '\\u003c');
            var page = ''
                + '<header class="pv-top"><strong>' + esc(A.siteName || '') + '</strong><span></span><span></span><span></span></header>'
                + '<main class="pv-main"><i class="pv-l pv-h"></i><i class="pv-l"></i><i class="pv-l pv-s"></i>'
                + '<iframe class="kvkkc-blocked" data-kvkkc-cat="' + esc(config.yt || 'pazarlama') + '" width="16" height="9" title="video"></iframe>'
                + '<i class="pv-l"></i><i class="pv-l pv-s"></i></main>';

            frame.srcdoc = '<!doctype html><html lang="' + esc(d.documentElement.lang || 'tr') + '"><head><meta charset="utf-8">'
                + '<meta name="viewport" content="width=device-width,initial-scale=1">'
                + '<link rel="stylesheet" href="' + esc(A.bannerCss) + '">'
                + '<style>html,body{overflow:hidden}body{margin:0;font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#fff;color:#1d2433}'
                + '.pv-top{display:flex;gap:14px;align-items:center;padding:14px 20px;border-bottom:1px solid #eceef3}'
                + '.pv-top span{width:44px;height:8px;border-radius:4px;background:#e6e8ee}.pv-top strong{flex:1;font-size:13px}'
                + '.pv-main{display:grid;gap:12px;padding:24px 20px 200px;max-width:560px}'
                + '.pv-l{display:block;height:10px;border-radius:5px;background:#eceef3}.pv-h{height:22px;width:70%}.pv-s{width:55%}</style>'
                + '</head><body>' + page
                + '<script>window.kvkkcGuard={v:' + config.v + ',state:{ok:false,t:0,id:"",c:[]},allow:function(){}};<\/script>'
                + '<script src="' + esc(A.bannerJs) + '"><\/script>'
                + '<script>KVKKC.init(' + json + ',{layer:"' + layer + '"});<\/script>'
                + '</body></html>';
        }

        function renderChecks(checks) {
            var list = $('[data-kvkkc-checks]');
            var ledger = $('[data-kvkkc-ledger]');
            var passed = 0;
            list.textContent = '';
            checks.forEach(function (check) {
                passed += check.state === 'ok' ? 1 : 0;
                var li = d.createElement('li');
                li.className = 'is-' + check.state;
                li.innerHTML = '<span class="kvkkc-check-text"></span><span class="kvkkc-check-ref"></span>';
                li.firstChild.textContent = check.text;
                li.lastChild.textContent = check.ref;
                list.appendChild(li);
            });
            $('[data-kvkkc-score]').textContent = passed + '/' + checks.length;
            ledger.classList.toggle('is-sealed', passed === checks.length);
        }

        function refresh() {
            var data = new FormData(form);
            var mine = ++seq;
            data.set('action', 'kvkkc_preview');
            post(data).then(function (res) {
                if (mine !== seq || !res || !res.success) {
                    return;
                }
                config = res.data.config;
                render();
                renderChecks(res.data.checks);
            });
        }

        function schedule() {
            w.clearTimeout(timer);
            timer = w.setTimeout(refresh, 250);
        }

        // Çerçeve gerçek ekran genişliğinde çizilir, kaba sığacak kadar küçültülür.
        function fit() {
            var size = SIZES[device.getAttribute('data-kvkkc-device-frame')];
            var expanded = preview.classList.contains('is-expanded');
            if (expanded) {
                view.style.height = '';
            }
            var scale = Math.min(1, view.clientWidth / size[0]);
            if (expanded) {
                scale = Math.min(scale, view.clientHeight / size[1]);
            }
            frame.style.width = size[0] + 'px';
            frame.style.height = size[1] + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            if (!expanded) {
                view.style.height = Math.round(size[1] * scale) + 'px';
            }
        }

        if (w.ResizeObserver) {
            new w.ResizeObserver(fit).observe(view);
        }

        function setExpanded(on) {
            preview.classList.toggle('is-expanded', on);
            d.body.classList.toggle('kvkkc-preview-open', on);
            expand.setAttribute('aria-pressed', on ? 'true' : 'false');
            fit();
        }
        expand.addEventListener('click', function () {
            setExpanded(!preview.classList.contains('is-expanded'));
        });
        d.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && preview.classList.contains('is-expanded')) {
                setExpanded(false);
                expand.focus();
            }
        });

        form.addEventListener('input', schedule);
        form.addEventListener('change', schedule);

        $$('[data-kvkkc-layer]').forEach(function (button) {
            button.addEventListener('click', function () {
                layer = button.getAttribute('data-kvkkc-layer');
                $$('[data-kvkkc-layer]').forEach(function (b) { b.setAttribute('aria-pressed', b === button ? 'true' : 'false'); });
                render();
            });
        });

        $$('[data-kvkkc-device]').forEach(function (button) {
            button.addEventListener('click', function () {
                device.setAttribute('data-kvkkc-device-frame', button.getAttribute('data-kvkkc-device'));
                $$('[data-kvkkc-device]').forEach(function (b) { b.setAttribute('aria-pressed', b === button ? 'true' : 'false'); });
                fit();
            });
        });

        fit();
        refresh();
    }

    /* ---------------------------------------------------------------- *
     * Politika sürümü
     * ---------------------------------------------------------------- */
    function initBump() {
        var button = $('[data-kvkkc-bump]');
        if (!button) {
            return;
        }
        button.addEventListener('click', function () {
            if (!w.confirm(A.i18n.bumpConfirm)) {
                return;
            }
            button.disabled = true;
            post({ action: 'kvkkc_bump_version' }).then(function (res) {
                button.disabled = false;
                if (res && res.success) {
                    $('[data-kvkkc-version-number]').textContent = 'v' + res.data.version;
                }
            });
        });
    }

    /* ---------------------------------------------------------------- *
     * Envanter
     * ---------------------------------------------------------------- */
    function initInventory() {
        var form = $('[data-kvkkc-inventory]');
        if (!form) {
            return;
        }
        var template = $('#kvkkc-row-template');
        guardUnload(form);

        form.addEventListener('click', function (event) {
            var target = event.target;

            if (target.matches('[data-kvkkc-row-add]')) {
                var body = target.closest('.kvkkc-svc-body').querySelector('[data-kvkkc-rows]');
                var base = body.getAttribute('data-kvkkc-rows') + '[cookies][n' + Date.now() + ']';
                var row = template.content.firstElementChild.cloneNode(true);
                $$('input', row).forEach(function (input) {
                    input.name = base + '[' + input.getAttribute('data-name') + ']';
                });
                body.appendChild(row);
                $('input', row).focus();
                markDirty(form);
            } else if (target.matches('[data-kvkkc-row-remove]')) {
                target.closest('tr').remove();
                markDirty(form);
            } else if (target.matches('[data-kvkkc-service-remove]')) {
                if (w.confirm(A.i18n.removeConfirm)) {
                    target.closest('[data-kvkkc-service]').remove();
                    markDirty(form);
                }
            }
        });
    }

    /* ---------------------------------------------------------------- *
     * Tarama
     * ---------------------------------------------------------------- */
    function initScan() {
        var box = $('[data-kvkkc-scan]');
        if (!box) {
            return;
        }
        var start = $('[data-kvkkc-scan-start]', box);
        var progress = $('[data-kvkkc-progress]', box);
        var bar = $('[data-kvkkc-progress-bar]', box);
        var text = $('[data-kvkkc-progress-text]', box);

        function format(done, total) {
            return A.i18n.scanning.replace('%1$d', done).replace('%2$d', total);
        }

        start.addEventListener('click', function () {
            start.disabled = true;
            progress.hidden = false;
            bar.style.width = '0';
            text.textContent = A.i18n.scanStart;

            post({ action: 'kvkkc_scan_start', limit: $('#kvkkc-scan-limit').value }).then(function (res) {
                if (!res || !res.success) {
                    throw new Error('start');
                }
                var urls = res.data.urls;
                var done = 0;

                // Sayfalar sırayla taranır; sunucuyu yormamak için aynı anda tek istek.
                return urls.reduce(function (chain, url) {
                    return chain.then(function () {
                        return post({ action: 'kvkkc_scan_url', url: url }).catch(function () { return null; }).then(function () {
                            done++;
                            bar.style.width = Math.round(done / urls.length * 100) + '%';
                            text.textContent = format(done, urls.length);
                        });
                    });
                }, Promise.resolve());
            }).then(function () {
                text.textContent = A.i18n.scanDone;
                w.location.reload();
            }).catch(function () {
                text.textContent = A.i18n.scanFailed;
                start.disabled = false;
            });
        });
    }

    d.addEventListener('DOMContentLoaded', function () {
        var settings = $('#kvkkc-settings-form');
        if (settings) {
            guardUnload(settings);
            initTabs();
            initFields(settings);
            initPreview(settings);
        }
        initBump();
        initInventory();
        initScan();
    });
})(window, document);
