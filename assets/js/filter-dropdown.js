(function() {
    'use strict';

    function initFilterDropdowns() {
        document.querySelectorAll('.filter-dropdown select').forEach(function(select) {
            if (select.dataset.fdReady) return;
            select.dataset.fdReady = '1';

            var wrap = select.parentNode;
            var placeholder = select.options[select.selectedIndex]
                ? select.options[select.selectedIndex].textContent
                : select.options[0].textContent;

            var trigger = document.createElement('div');
            trigger.className = 'filter-dropdown-trigger';
            trigger.textContent = placeholder;

            var list = document.createElement('div');
            list.className = 'filter-dropdown-list';

            var items = [];
            var activeIdx = -1;

            Array.from(select.options).forEach(function(opt) {
                var item = document.createElement('div');
                item.className = 'filter-dropdown-item';
                if (opt.selected) item.classList.add('selected');
                item.textContent = opt.textContent;
                item.dataset.value = opt.value;

                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    select.value = this.dataset.value;
                    trigger.textContent = this.textContent;
                    items.forEach(function(o) {
                        o.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    close();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });

                item.addEventListener('mouseenter', function() {
                    var idx = items.indexOf(this);
                    if (idx !== activeIdx) {
                        items.forEach(function(o) { o.classList.remove('active'); });
                        this.classList.add('active');
                        activeIdx = idx;
                    }
                });

                items.push(item);
                list.appendChild(item);
            });

            function open() {
                closeAll();
                positionList(list, trigger);
                list.classList.add('show');
                trigger.classList.add('open');
                var sel = items.findIndex(function(o) { return o.classList.contains('selected'); });
                activeIdx = sel >= 0 ? sel : 0;
                items.forEach(function(o) { o.classList.remove('active'); });
                if (items[activeIdx]) items[activeIdx].classList.add('active');
            }

            function close() {
                list.classList.remove('show');
                trigger.classList.remove('open');
                items.forEach(function(o) { o.classList.remove('active'); });
            }

            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                if (list.classList.contains('show')) { close(); return; }
                open();
            });

            trigger.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (list.classList.contains('show')) {
                        items[activeIdx] && items[activeIdx].click();
                    } else {
                        open();
                    }
                }
                if (e.key === 'Escape') { close(); trigger.focus(); }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!list.classList.contains('show')) { open(); return; }
                    activeIdx = Math.min(activeIdx + 1, items.length - 1);
                    items.forEach(function(o) { o.classList.remove('active'); });
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!list.classList.contains('show')) { open(); return; }
                    activeIdx = Math.max(activeIdx - 1, 0);
                    items.forEach(function(o) { o.classList.remove('active'); });
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                }
            });

            select.addEventListener('change', function() {
                trigger.textContent = select.options[select.selectedIndex].textContent;
                items.forEach(function(o, i) {
                    var sel = o.dataset.value === select.value;
                    o.classList.toggle('selected', sel);
                });
            });

            wrap.appendChild(trigger);
            wrap.appendChild(list);
        });
    }

    function positionList(list, trigger) {
        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom - 8;
        var spaceAbove = rect.top - 8;
        list.style.position = 'fixed';
        list.style.left = rect.left + 'px';
        list.style.width = rect.width + 'px';
        list.style.top = '';
        list.style.bottom = '';
        list.style.maxHeight = '';
        if (spaceBelow >= 120 || spaceBelow >= spaceAbove) {
            list.style.top = (rect.bottom + 4) + 'px';
            list.style.maxHeight = Math.max(80, Math.min(200, spaceBelow - 4)) + 'px';
        } else {
            list.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            list.style.maxHeight = Math.max(80, Math.min(200, spaceAbove - 4)) + 'px';
        }
    }

    function closeAll() {
        document.querySelectorAll('.filter-dropdown-list.show').forEach(function(l) {
            l.classList.remove('show');
            l.style.position = '';
            l.style.left = '';
            l.style.width = '';
            l.style.top = '';
            l.style.bottom = '';
            l.style.maxHeight = '';
        });
        document.querySelectorAll('.filter-dropdown-trigger.open').forEach(function(t) {
            t.classList.remove('open');
        });
    }

    document.addEventListener('click', closeAll);
    window.addEventListener('scroll', closeAll, true);
    window.addEventListener('resize', closeAll);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilterDropdowns);
    } else {
        initFilterDropdowns();
    }
})();
