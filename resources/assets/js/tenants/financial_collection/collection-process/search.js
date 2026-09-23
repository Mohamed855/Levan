document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('customerSearch');
    const searchResults = document.getElementById('searchResults');
    const searchForm = searchInput?.closest('form');
    let searchTimeout;

    if (!searchInput || !searchForm) {
        console.error('❌ Search elements not found');
        return;
    }

    //  منع submit النموذج
    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        return false;
    });

    //  البحث فقط عند الكتابة
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchResults.innerHTML =
            '<div style="padding: 1rem; text-align: center;"><i class="ti ti-loader ti-spin"></i> جاري البحث...</div>';
        searchResults.style.display = 'block';

        searchTimeout = setTimeout(() => {
            fetch(window.routes.search, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ query: query })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        searchResults.innerHTML = data.data.map(c => `
                            <div class="search-result-item" data-customer-id="${c.id}">
                                <div>
                                    <div class="search-result-name">${escapeHtml(c.name)}</div>
                                    <div class="d-flex gap-2">
                                        <div class="search-result-details">
                                            ${c.account_number ? escapeHtml(c.account_number) : ''}
                                        </div>
                                        ${c.account_number && c.identity_number ? ' - ' : ''}
                                        <div class="search-result-details">
                                            ${c.identity_number ? escapeHtml(c.identity_number) : ''}
                                        </div>
                                    </div>
                                </div>
                                <i class="ti ti-arrow-left"></i>
                            </div>
                        `).join('');

                        //  أضف click listeners فقط للنتائج
                        document.querySelectorAll('.search-result-item').forEach(item => {
                            item.addEventListener('click', function () {
                                const customerId = this.getAttribute('data-customer-id');
                                navigateToCustomer(customerId);
                            });
                        });
                    } else {
                        searchResults.innerHTML =
                            '<div style="padding: 1rem; text-align: center; color: #6c757d;">لم يتم العثور على نتائج</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    searchResults.innerHTML =
                        '<div style="padding: 1rem; text-align: center; color: #dc3545;">حدث خطأ</div>';
                });
        }, 300);
    });

    //  منع Enter من submit النموذج
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });

    //  منع Enter و Ctrl+Enter
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || (e.ctrlKey && e.key === 'Enter')) {
            e.preventDefault();
            return false;
        }
    });

    //  إغلاق النتائج عند الضغط خارجها
    document.addEventListener('click', function (e) {
        // إذا ضغط خارج النموذج واستطلاع النتائج
        if (!searchForm.contains(e.target)) {
            searchResults.style.display = 'none';
        }
        // أو إذا ضغط على عنصر بحث (لا تغلق)
        if (e.target.closest('.search-result-item')) {
            // سيتم الانتقال بواسطة click handler
        }
    });

    //  دالة الانتقال للعميل
    window.navigateToCustomer = function (customerId) {
        window.location.href = window.routes.index + '?customer_id=' + customerId;
    };

    //  دالة escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
