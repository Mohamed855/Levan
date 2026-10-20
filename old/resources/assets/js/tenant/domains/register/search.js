/**
 * Domain Search JS — مسند
 */

$(document).ready(function () {

    /* ─────────────────────────────────────────
       State
    ───────────────────────────────────────── */
    let selectedExtension = '';
    let selectedPrice     = 0;

    /* ─────────────────────────────────────────
       Custom Extension Select Dropdown
    ───────────────────────────────────────── */
    const $header   = $('#dsExtHeader');
    const $dropdown = $('#dsExtDropdown');
    const $search   = $('#dsExtSearch');
    const $options  = $('#dsExtOptions');

    // Toggle — منع الـ form submit لأن الـ button جوه form
    $header.on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const isActive = $dropdown.hasClass('active');
        $header.toggleClass('active', !isActive);
        $dropdown.toggleClass('active', !isActive);
        if (!isActive) setTimeout(function(){ $search.focus(); }, 50);
    });

    // Close on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.ds-ext-select-wrapper').length) {
            $header.removeClass('active');
            $dropdown.removeClass('active');
        }
    });

    // منع الـ dropdown نفسه من إغلاق نفسه لما تضغط جواه
    $dropdown.on('click', function (e) {
        e.stopPropagation();
    });

    // Search filter
    $search.on('input', function () {
        const q = $(this).val().toLowerCase();
        let visible = 0;
        $options.find('.ds-ext-option').each(function () {
            const match = $(this).data('value').toLowerCase().includes(q);
            $(this).toggle(match);
            if (match) visible++;
        });
        $options.find('.ds-ext-empty').remove();
        if (visible === 0) {
            $options.append('<div class="ds-ext-empty">لا توجد نتائج</div>');
        }
    });

    // Select option
    $options.on('click', '.ds-ext-option', function (e) {
        e.stopPropagation();
        const val   = $(this).data('value');
        const price = parseFloat($(this).data('price')) || 0;

        $options.find('.ds-ext-option').removeClass('selected');
        $(this).addClass('selected');

        selectedExtension = val;
        selectedPrice     = price;

        $('#dsExtSelectedText').text(val);
        $header.addClass('has-value');
        $('#dsExtHidden').val(val);

        // Close
        $header.removeClass('active');
        $dropdown.removeClass('active');
        $search.val('');
        $options.find('.ds-ext-option').show();
        $options.find('.ds-ext-empty').remove();

        updatePreview();
    });

    /* ─────────────────────────────────────────
       Domain Preview
    ───────────────────────────────────────── */
    function updatePreview() {
        const name = $('#domainInput').val().trim();
        if (name && selectedExtension) {
            $('#dsPreviewValue').text(name + selectedExtension);
            $('#dsDomainPreview').addClass('show');
        } else {
            $('#dsDomainPreview').removeClass('show');
        }
    }

    $('#domainInput').on('input', updatePreview);

    /* ─────────────────────────────────────────
       TLD Quick Chips
    ───────────────────────────────────────── */
    $(document).on('click', '.ds-tld-chip', function (e) {
        e.preventDefault();
        const tld  = $(this).data('tld');
        const $opt = $options.find('.ds-ext-option[data-value="' + tld + '"]');
        if ($opt.length) {
            $opt.trigger('click');
        } else {
            // Fallback لو الامتداد مش موجود في قاعدة البيانات
            selectedExtension = tld;
            selectedPrice     = 0;
            $('#dsExtSelectedText').text(tld);
            $header.addClass('has-value');
            $('#dsExtHidden').val(tld);
            updatePreview();
        }
    });

    /* ─────────────────────────────────────────
       Form Submit
    ───────────────────────────────────────── */
    $('#domainSearchForm').on('submit', function (e) {
        e.preventDefault();
        searchDomain();
    });

    function searchDomain() {
        const name = $('#domainInput').val().trim();

        if (!name) {
            showInputError('يرجى كتابة اسم النطاق');
            return;
        }
        if (!selectedExtension) {
            showInputError('يرجى اختيار امتداد النطاق');
            $header.trigger('click');
            return;
        }

        const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]$|^[a-zA-Z0-9]$/;
        if (!domainRegex.test(name)) {
            showInputError('اسم النطاق غير صحيح. استخدم حروف وأرقام وشرطة فقط');
            return;
        }

        hideInputError();

        const fullDomain = name + selectedExtension;
        setLoading(true);
        hideResults();

        const url = CHECK_URL.replace('__DOMAIN__', fullDomain);

        $.get(url)
            .done(function (res) {
                setLoading(false);
                if (res.success && res.available === true) {
                    showAvailable(fullDomain);
                } else if (res.success && res.available === false) {
                    showTaken(fullDomain, name);
                } else {
                    showError(fullDomain, res.error || 'تعذّر التحقق من النطاق');
                }
                showResultSection();
            })
            .fail(function (xhr) {
                setLoading(false);
                const msg = xhr.responseJSON?.error || 'حدث خطأ أثناء البحث، حاول مرة أخرى';
                showError(fullDomain, msg);
                showResultSection();
            });
    }

    /* ─────────────────────────────────────────
       Results
    ───────────────────────────────────────── */
    function hideResults() {
        $('#resultSection').attr('hidden', true);
        $('#resultAvailable, #resultTaken, #resultError').attr('hidden', true);
        $('#suggestionsSection').attr('hidden', true);
    }

    function showResultSection() {
        $('#resultSection').removeAttr('hidden');
        $('html, body').animate({
            scrollTop: $('#resultSection').offset().top - 100
        }, 400);
    }

    function showAvailable(domain) {
        $('#availableDomainName').text(domain);
        $('#dsPricingDomain').text(domain);
        $('#dsPricingPrice').text(selectedPrice.toFixed(2) + ' ر.س');
        $('#dsPricingTotal').text(selectedPrice.toFixed(2) + ' ر.س');
        $('#paymentDomainInput').val(domain);
        $('#resultAvailable').removeAttr('hidden');
    }

    function showTaken(domain, name) {
        $('#takenDomainName').text(domain);
        $('#resultTaken').removeAttr('hidden');
        loadSuggestions(name);
    }

    function showError(domain, msg) {
        $('#errorDomainName').text(domain);
        $('#errorMessage').text(msg);
        $('#resultError').removeAttr('hidden');
    }

    /* ─────────────────────────────────────────
       Suggestions
    ───────────────────────────────────────── */
    function loadSuggestions(name) {
        const $grid = $('#suggestionsGrid');
        $grid.html(
            '<span class="ds-suggestions__loading">' +
            '<svg class="ds-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px">' +
            '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>' +
            '</svg> جاري البحث عن بدائل...</span>'
        );
        $('#suggestionsSection').removeAttr('hidden');

        $.get(SUGGEST_URL, { domain_name: name, limit: 8 })
            .done(function (res) {
                if (res.success && res.suggestions && res.suggestions.length > 0) {
                    renderSuggestions(res.suggestions);
                } else {
                    $grid.html('<p style="color:var(--text-muted);font-size:0.85rem;padding:8px 0">لم يتم العثور على اقتراحات متاحة.</p>');
                }
            })
            .fail(function () {
                $grid.html('<p style="color:var(--text-muted);font-size:0.85rem;padding:8px 0">تعذّر تحميل الاقتراحات.</p>');
            });
    }

    function renderSuggestions(suggestions) {
        const $grid = $('#suggestionsGrid');
        $grid.empty();

        suggestions.forEach(function (sugg, i) {
            const priceLabel  = sugg.price ? parseFloat(sugg.price).toFixed(2) + ' ر.س' : '';
            const statusBadge = sugg.available
                ? '<span class="ds-suggestion-item__badge ds-suggestion-item__badge--available">متاح</span>'
                : '<span class="ds-suggestion-item__badge ds-suggestion-item__badge--taken">محجوز</span>';

            const $item = $(
                '<div class="ds-suggestion-item ' + (sugg.available ? 'ds-suggestion-item--available' : 'ds-suggestion-item--taken') + '"' +
                ' style="animation-delay:' + (i * 0.04) + 's"' +
                ' data-domain="' + sugg.domain + '"' +
                ' data-extension="' + sugg.extension + '"' +
                ' data-price="' + (sugg.price || 0) + '">' +
                '<span class="ds-suggestion-item__name">' + sugg.domain + '</span>' +
                '<div class="ds-suggestion-item__info">' + statusBadge +
                (priceLabel ? '<span class="ds-suggestion-item__price">' + priceLabel + '</span>' : '') +
                '</div></div>'
            );

            if (sugg.available) {
                $item.on('click', function () {
                    const ext  = sugg.extension;
                    const name = sugg.domain.replace(ext, '');
                    $('#domainInput').val(name);

                    const $opt = $options.find('.ds-ext-option[data-value="' + ext + '"]');
                    if ($opt.length) {
                        $opt.trigger('click');
                    } else {
                        selectedExtension = ext;
                        selectedPrice     = parseFloat(sugg.price) || 0;
                        $('#dsExtSelectedText').text(ext);
                        $header.addClass('has-value');
                        $('#dsExtHidden').val(ext);
                        updatePreview();
                    }

                    $('html, body').animate({ scrollTop: 0 }, 400);
                    setTimeout(searchDomain, 600);
                });
            }

            $grid.append($item);
        });
    }

    /* ─────────────────────────────────────────
       Try Again
    ───────────────────────────────────────── */
    $('#tryAgainBtn').on('click', function () {
        hideResults();
        $('#domainInput').focus().select();
        $('html, body').animate({ scrollTop: 0 }, 400);
    });

    /* ─────────────────────────────────────────
       Register Button
    ───────────────────────────────────────── */
    $('#registerBtn').on('click', function () {
        $('#paymentForm').trigger('submit');
    });

    /* ─────────────────────────────────────────
       Loading State — FIX: removeAttr بدل attr(null)
    ───────────────────────────────────────── */
    function setLoading(on) {
        var $btn    = $('#searchBtn');
        var $text   = $btn.find('.ds-form__btn-text');
        var $loader = $btn.find('.ds-form__btn-loader');

        $btn.prop('disabled', on);
        if (on) {
            $text.hide();
            $loader.removeAttr('hidden');
        } else {
            $text.show();
            $loader.attr('hidden', true);
        }
    }

    /* ─────────────────────────────────────────
       Input Error
    ───────────────────────────────────────── */
    function showInputError(msg) {
        $('#inputErrorMsg').text(msg);
        $('#inputError').removeAttr('hidden');
        $('#domainInput').focus();
    }

    function hideInputError() {
        $('#inputError').attr('hidden', true);
    }

});
