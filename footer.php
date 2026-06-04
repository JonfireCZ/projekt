</main>

<footer class="bg-dark text-white text-center py-3 mt-auto">
    <p class="mb-1">&copy; <?= date('Y') ?> LockerRoom</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle order collapse toggles: update hash and scroll into view
document.addEventListener('click', function(e){
    var el = e.target.closest('[data-bs-toggle="collapse"]');
    if (!el) return;

    // target id from href or data-bs-target
    var target = el.getAttribute('href') || el.getAttribute('data-bs-target') || '';
    if (!target.startsWith('#orderDetails-')) return; // only handle order details

    e.preventDefault();
    var id = target.slice(1);
    var targetEl = document.getElementById(id);
    if (!targetEl) return;

    // get or create bootstrap collapse instance
    var bsCollapse = bootstrap.Collapse.getInstance(targetEl) || new bootstrap.Collapse(targetEl, {toggle:false});
    if (targetEl.classList.contains('show')) {
        bsCollapse.hide();
        try { history.replaceState(null, '', location.pathname + location.search); } catch(e) {}
    } else {
        bsCollapse.show();
        setTimeout(function(){ targetEl.scrollIntoView({behavior:'smooth', block:'start'}); }, 200);
        try { history.replaceState(null, '', '#' + id); } catch(e) {}
    }
});

// On page load, if URL contains #orderDetails-*, open it and activate Orders tab
document.addEventListener('DOMContentLoaded', function(){
    var hash = location.hash || '';
    if (!hash.startsWith('#orderDetails-')) return;
    var id = hash.slice(1);
    var targetEl = document.getElementById(id);
    if (!targetEl) return;

    // activate Orders tab if present
    var ordersTabTrigger = document.querySelector('[data-bs-target="#orders"], a[href="#orders"]');
    if (ordersTabTrigger) {
        try {
            var tab = bootstrap.Tab.getInstance(ordersTabTrigger) || new bootstrap.Tab(ordersTabTrigger);
            tab.show();
        } catch(e) {}
    }

    var bsCollapse = bootstrap.Collapse.getInstance(targetEl) || new bootstrap.Collapse(targetEl, {toggle:false});
    bsCollapse.show();
    setTimeout(function(){ targetEl.scrollIntoView({behavior:'smooth', block:'start'}); }, 200);
});

// Mobile search toggle: open/close and focus input
document.addEventListener('click', function(e){
    var openBtn = e.target.closest('#mobileSearchBtn');
    if (openBtn) {
        var ms = document.getElementById('mobileSearch');
        if (!ms) return;
        var bs = bootstrap.Collapse.getInstance(ms) || new bootstrap.Collapse(ms, {toggle:false});
        bs.show();
        setTimeout(function(){
            var input = ms.querySelector('input[name="search"]');
            if (input) input.focus();
        }, 100);
        return;
    }

    var closeBtn = e.target.closest('#mobileSearchClose');
    if (closeBtn) {
        var ms = document.getElementById('mobileSearch');
        if (!ms) return;
        var bs = bootstrap.Collapse.getInstance(ms) || new bootstrap.Collapse(ms, {toggle:false});
        bs.hide();
        return;
    }
});

// Desktop search toggle: focus input when opened
document.addEventListener('click', function(e){
    var openBtn = e.target.closest('#desktopSearchBtn');
    if (openBtn) {
        var ds = document.getElementById('desktopSearch');
        if (!ds) return;
        var bs = bootstrap.Collapse.getInstance(ds) || new bootstrap.Collapse(ds, {toggle:false});
        bs.show();
        setTimeout(function(){
            var input = ds.querySelector('input[name="search"]');
            if (input) input.focus();
        }, 100);
    }
});
</script>
</body>
</html>
