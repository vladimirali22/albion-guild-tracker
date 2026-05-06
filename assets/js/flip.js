/* ═══════════════════════════════════════════════════════════
   ALBION GUILD TRACKER — Flip Calculator Engine
   flip.js — Live calculations, autocomplete, save system
═══════════════════════════════════════════════════════════ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── DOM refs ──
    const itemNameInput    = document.getElementById('itemName');
    const autocompleteList = document.getElementById('autocompleteList');
    const addItemHint      = document.getElementById('addItemHint');
    const addItemNameSpan  = document.getElementById('addItemName');
    const addItemBtn       = document.getElementById('addItemBtn');

    const tierSelect    = document.getElementById('tier');
    const enchantSelect = document.getElementById('enchant');
    const tierBadge     = document.getElementById('tierBadge');

    const buyPriceInput  = document.getElementById('buyPrice');
    const sellPriceInput = document.getElementById('sellPrice');
    const quantityInput  = document.getElementById('quantity');
    const premiumCheck   = document.getElementById('premium');
    const taxInfo        = document.getElementById('taxInfo');

    const qtyMinus    = document.getElementById('qtyMinus');
    const qtyPlus     = document.getElementById('qtyPlus');
    const qtyPresets  = document.querySelectorAll('.qty-preset');

    const radioCards  = document.querySelectorAll('.radio-card');

    // Result elements
    const resultEmpty   = document.getElementById('resultEmpty');
    const resultContent = document.getElementById('resultContent');
    const resultCard    = document.getElementById('resultCard');
    const quickStats    = document.getElementById('quickStatsCard');
    const saveTradeBtn  = document.getElementById('saveTradeBtn');
    const saveFeedback  = document.getElementById('saveFeedback');

    // ── State ──
    let selectedItem = null;
    let lastCalc     = null;
    let autocompleteTimeout = null;
    let selectedAutocompleteIdx = -1;
    let autocompleteItems = [];

    // ══════════════════════════════════════
    //  TIER BADGE
    // ══════════════════════════════════════

    function updateTierBadge() {
        const t = tierSelect.value;
        const e = enchantSelect.value;
        tierBadge.textContent = `${t}.${e}`;

        const colors = { T4: '#9ca3af', T5: '#60a5fa', T6: '#a78bfa', T7: '#fbbf24', T8: '#f97316' };
        tierBadge.style.setProperty('--tier-color', colors[t] || '#d4a853');
    }

    tierSelect.addEventListener('change', updateTierBadge);
    enchantSelect.addEventListener('change', updateTierBadge);
    updateTierBadge();

    // ══════════════════════════════════════
    //  BUY METHOD RADIO CARDS
    // ══════════════════════════════════════

    radioCards.forEach(card => {
        card.addEventListener('click', () => {
            radioCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            card.querySelector('input[type="radio"]').checked = true;
            calculate();
        });
    });

    // ══════════════════════════════════════
    //  PREMIUM TOGGLE
    // ══════════════════════════════════════

    premiumCheck.addEventListener('change', () => {
        taxInfo.textContent = premiumCheck.checked ? '4% transaction tax' : '8% transaction tax';
        calculate();
    });

    // ══════════════════════════════════════
    //  QUANTITY CONTROLS
    // ══════════════════════════════════════

    qtyMinus.addEventListener('click', () => {
        const v = Math.max(1, parseInt(quantityInput.value || 1) - 1);
        quantityInput.value = v;
        calculate();
    });

    qtyPlus.addEventListener('click', () => {
        const v = parseInt(quantityInput.value || 1) + 1;
        quantityInput.value = v;
        calculate();
    });

    qtyPresets.forEach(btn => {
        btn.addEventListener('click', () => {
            quantityInput.value = parseInt(btn.dataset.qty);
            qtyPresets.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            calculate();
        });
    });

    // ══════════════════════════════════════
    //  LIVE INPUT CALCULATION TRIGGERS
    // ══════════════════════════════════════

    [buyPriceInput, sellPriceInput, quantityInput].forEach(el => {
        el.addEventListener('input', () => calculate());
    });

    // ══════════════════════════════════════
    //  CALCULATION ENGINE
    // ══════════════════════════════════════

    function calculate() {
        const buyPrice  = parseFloat(buyPriceInput.value)   || 0;
        const sellPrice = parseFloat(sellPriceInput.value)  || 0;
        const quantity  = Math.max(1, parseInt(quantityInput.value) || 1);
        const isPremium = premiumCheck.checked;
        const isBuyOrder = document.querySelector('input[name="buyMethod"]:checked')?.value === 'buy_order';

        if (buyPrice <= 0 || sellPrice <= 0) {
            showEmpty();
            return;
        }

        // ── Fee calculations ──
        const buyTotal   = buyPrice * quantity;
        const sellTotal  = sellPrice * quantity;
        const buySetup   = isBuyOrder ? (buyPrice * 0.025 * quantity) : 0;
        const sellSetup  = sellPrice * 0.025 * quantity;
        const taxRate    = isPremium ? 0.04 : 0.08;
        const taxTotal   = sellTotal * taxRate;
        const totalFees  = buySetup + sellSetup + taxTotal;
        const netProfit  = sellTotal - buyTotal - totalFees;
        const profitPer  = netProfit / quantity;
        const roi        = buyTotal > 0 ? (netProfit / buyTotal) * 100 : 0;
        const margin     = sellTotal > 0 ? (netProfit / sellTotal) * 100 : 0;
        const breakEven  = buyPrice > 0
            ? Math.ceil(buyPrice / (1 - 0.025 - taxRate))
            : 0;

        lastCalc = {
            buyPrice, sellPrice, quantity,
            buyTotal, sellTotal, buySetup, sellSetup,
            taxTotal, totalFees, netProfit, profitPer,
            roi, margin, breakEven,
            isPremium, isBuyOrder
        };

        renderBreakdown(lastCalc);
        renderResult(lastCalc);
    }

    // ── Breakdown Table ──
    function renderBreakdown(c) {
        setEl('brBuyTotal',  fmt(c.buyTotal));
        setEl('brBuySetup',  c.isBuyOrder ? `-${fmt(c.buySetup)}` : 'N/A (Instant Buy)');
        setEl('brSellTotal', fmt(c.sellTotal));
        setEl('brSellSetup', `-${fmt(c.sellSetup)}`);
        setEl('brTax',       `-${fmt(c.taxTotal)}`);
        setEl('brTaxRate',   `(${c.isPremium ? '4' : '8'}%)`);
        setEl('brTotalFees', `-${fmt(c.totalFees)}`);

        const brBuySetupEl = document.getElementById('brBuySetup');
        if (brBuySetupEl) {
            brBuySetupEl.style.opacity = c.isBuyOrder ? '1' : '0.4';
        }
    }

    // ── Result Panel ──
    function renderResult(c) {
        resultEmpty.style.display   = 'none';
        resultContent.style.display = 'block';
        quickStats.style.display    = 'block';

        const profitClass = c.netProfit > 0 ? 'profit-pos' : c.netProfit < 0 ? 'profit-neg' : 'profit-zero';
        const profitSign  = c.netProfit > 0 ? '+' : '';

        // Main result
        const perItemEl = document.getElementById('resultPerItem');
        perItemEl.textContent = `${profitSign}${fmt(c.profitPer)}`;
        perItemEl.className = `result-value ${profitClass}`;

        const marginEl = document.getElementById('resultMargin');
        marginEl.textContent = `${c.margin.toFixed(1)}% margin · ROI ${c.roi.toFixed(1)}%`;
        marginEl.className = `result-margin ${profitClass}`;

        // Grid values
        const gridData = [
            { id: 'rg1',     qty: 1 },
            { id: 'rg10',    qty: 10 },
            { id: 'rg20',    qty: 20 },
            { id: 'rgCustom', qty: c.quantity },
        ];

        gridData.forEach(({ id, qty }) => {
            const val = c.profitPer * qty;
            const el  = document.getElementById(id);
            if (!el) return;
            el.textContent = `${val >= 0 ? '+' : ''}${fmt(val)}`;
            el.className = `rg-value ${val > 0 ? 'profit-pos' : val < 0 ? 'profit-neg' : 'profit-zero'}`;
        });

        // Custom qty label
        const customLabel = document.getElementById('rgCustomQtyLabel');
        if (customLabel) customLabel.textContent = `×${c.quantity}`;

        // Verdict
        const verdictEl = document.getElementById('resultVerdict');
        if (c.netProfit > 0) {
            verdictEl.innerHTML = `<span class="verdict-good">✓ Profitable Trade</span> — You gain <strong>${fmt(c.profitPer)}</strong> per item after all fees.`;
        } else if (c.netProfit < 0) {
            verdictEl.innerHTML = `<span class="verdict-bad">✗ Losing Trade</span> — You lose <strong>${fmt(Math.abs(c.profitPer))}</strong> per item. Adjust your prices.`;
        } else {
            verdictEl.innerHTML = `<span class="verdict-neutral">◆ Break Even</span> — No profit, no loss.`;
        }

        // Quick stats
        setEl('qsROI',       `${c.roi.toFixed(2)}%`);
        setEl('qsMargin',    `${c.margin.toFixed(2)}%`);
        setEl('qsFees',      `-${fmt(c.totalFees)}`);
        setEl('qsBreakEven', fmt(c.breakEven));

        // Color ROI
        const roiEl = document.getElementById('qsROI');
        if (roiEl) roiEl.className = `qs-value ${c.roi > 0 ? 'profit-pos' : c.roi < 0 ? 'profit-neg' : ''}`;

        // Show save button only if item is filled
        saveTradeBtn.style.display = itemNameInput.value.trim() ? 'block' : 'none';

        // Animate result card
        resultCard.classList.remove('result-bounce');
        void resultCard.offsetWidth;
        resultCard.classList.add('result-bounce');
    }

    function showEmpty() {
        resultEmpty.style.display   = 'flex';
        resultContent.style.display = 'none';
        quickStats.style.display    = 'none';
        saveTradeBtn.style.display  = 'none';
        clearBreakdown();
    }

    function clearBreakdown() {
        ['brBuyTotal','brBuySetup','brSellTotal','brSellSetup','brTax','brTotalFees'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '—';
        });
    }

    // ══════════════════════════════════════
    //  AUTOCOMPLETE
    // ══════════════════════════════════════

    itemNameInput.addEventListener('input', () => {
        const q = itemNameInput.value.trim();
        clearTimeout(autocompleteTimeout);
        selectedAutocompleteIdx = -1;

        if (q.length < 1) {
            hideAutocomplete();
            addItemHint.style.display = 'none';
            selectedItem = null;
            saveTradeBtn.style.display = 'none';
            return;
        }

        autocompleteTimeout = setTimeout(() => searchItems(q), 200);
    });

    async function searchItems(q) {
        try {
            const res  = await fetch(`api/items.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();
            autocompleteItems = data.items || [];
            renderAutocomplete(autocompleteItems, q);
        } catch (e) {
            hideAutocomplete();
        }
    }

    function renderAutocomplete(items, q) {
        autocompleteList.innerHTML = '';

        if (items.length === 0) {
            // Show add-to-db hint
            addItemNameSpan.textContent = q;
            addItemHint.style.display = 'block';
            hideAutocomplete();
            return;
        }

        addItemHint.style.display = 'none';

        items.forEach((item, i) => {
            const li = document.createElement('div');
            li.className = 'ac-item';
            li.dataset.idx = i;

            // Highlight matching part
            const regex = new RegExp(`(${escapeRegex(q)})`, 'gi');
            li.innerHTML = item.name.replace(regex, '<mark>$1</mark>');

            li.addEventListener('click', () => selectItem(item));
            li.addEventListener('mouseenter', () => {
                setActiveAC(i);
            });

            autocompleteList.appendChild(li);
        });

        autocompleteList.style.display = 'block';
    }

    function selectItem(item) {
        selectedItem = item;
        itemNameInput.value = item.name;
        hideAutocomplete();
        addItemHint.style.display = 'none';
        if (lastCalc) saveTradeBtn.style.display = 'block';
        calculate();
    }

    function hideAutocomplete() {
        autocompleteList.style.display = 'none';
        autocompleteList.innerHTML = '';
        selectedAutocompleteIdx = -1;
    }

    function setActiveAC(idx) {
        selectedAutocompleteIdx = idx;
        document.querySelectorAll('.ac-item').forEach((el, i) => {
            el.classList.toggle('ac-active', i === idx);
        });
    }

    // Keyboard navigation for autocomplete
    itemNameInput.addEventListener('keydown', e => {
        const items = document.querySelectorAll('.ac-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveAC(Math.min(selectedAutocompleteIdx + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveAC(Math.max(selectedAutocompleteIdx - 1, 0));
        } else if (e.key === 'Enter' && selectedAutocompleteIdx >= 0) {
            e.preventDefault();
            selectItem(autocompleteItems[selectedAutocompleteIdx]);
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });

    // Close autocomplete on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('.autocomplete-wrapper') && !e.target.closest('.add-item-hint')) {
            hideAutocomplete();
        }
    });

    // ── Add new item ──
    addItemBtn.addEventListener('click', async () => {
        const name = itemNameInput.value.trim();
        if (!name) return;

        addItemBtn.textContent = '⏳ Adding...';
        addItemBtn.disabled = true;

        try {
            const res  = await fetch('api/items.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const data = await res.json();
            if (data.success) {
                selectedItem = data.item;
                addItemHint.style.display = 'none';
                showToast(`✓ "${name}" added to database!`, 'success');
                if (lastCalc) saveTradeBtn.style.display = 'block';
            } else {
                showToast(data.error || 'Failed to add item.', 'error');
            }
        } catch (e) {
            showToast('Network error.', 'error');
        }

        addItemBtn.disabled = false;
        addItemBtn.innerHTML = `<span>＋</span> Add "<span id="addItemName">${name}</span>" to database`;
    });

    // ══════════════════════════════════════
    //  SAVE TRADE
    // ══════════════════════════════════════

    saveTradeBtn.addEventListener('click', async () => {
        if (!lastCalc) return;

        const itemName = itemNameInput.value.trim();
        if (!itemName) {
            showToast('Please enter an item name.', 'error');
            return;
        }

        saveTradeBtn.disabled = true;
        const btnText = saveTradeBtn.querySelector('.btn-text');
        btnText.textContent = '⏳ Saving...';

        const payload = {
            item_name:  itemName,
            tier:       tierSelect.value,
            enchant:    parseInt(enchantSelect.value),
            buy_price:  parseInt(buyPriceInput.value)  || 0,
            sell_price: parseInt(sellPriceInput.value) || 0,
            quantity:   parseInt(quantityInput.value)  || 1,
            profit:     Math.round(lastCalc.netProfit),
        };

        try {
            const res  = await fetch('api/save_trade.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                showSaveFeedback(`✓ Trade saved! (ID #${data.trade_id})`, 'success');
                showToast('Trade saved to your records!', 'success');
                saveTradeBtn.querySelector('.btn-text').textContent = '✓ Trade Saved!';
                setTimeout(() => {
                    saveTradeBtn.querySelector('.btn-text').textContent = '💾 Save This Trade';
                    saveTradeBtn.disabled = false;
                }, 2500);

                // Refresh recent trades
                addRecentTrade({
                    item_name:  itemName,
                    tier:       tierSelect.value,
                    enchant:    parseInt(enchantSelect.value),
                    profit:     Math.round(lastCalc.netProfit),
                });
            } else {
                showSaveFeedback(data.error || 'Save failed.', 'error');
                saveTradeBtn.disabled = false;
                btnText.textContent = '💾 Save This Trade';
            }
        } catch (e) {
            showSaveFeedback('Network error. Check your connection.', 'error');
            saveTradeBtn.disabled = false;
            btnText.textContent = '💾 Save This Trade';
        }
    });

    function showSaveFeedback(msg, type) {
        saveFeedback.textContent = msg;
        saveFeedback.className = `save-feedback save-feedback-${type}`;
        setTimeout(() => { saveFeedback.textContent = ''; saveFeedback.className = 'save-feedback'; }, 4000);
    }

    function addRecentTrade(trade) {
        const container = document.querySelector('.recent-list') || (() => {
            // Replace "no trades" with a list
            const noTrades = document.querySelector('.no-trades');
            if (noTrades) {
                const list = document.createElement('div');
                list.className = 'recent-list';
                noTrades.replaceWith(list);
                return list;
            }
            return null;
        })();

        if (!container) return;

        const profitClass = trade.profit >= 0 ? 'profit-pos' : 'profit-neg';
        const profitStr   = (trade.profit >= 0 ? '+' : '') + trade.profit.toLocaleString();
        const now         = new Date();
        const dateStr     = now.toLocaleDateString('en', { month: 'short', day: 'numeric' });

        const item = document.createElement('div');
        item.className = 'recent-item recent-item-new';
        item.innerHTML = `
            <div class="recent-item-left">
                <div class="recent-item-name">${escapeHtml(trade.item_name)}</div>
                <div class="recent-item-meta">${trade.tier}.${trade.enchant} · ${dateStr}</div>
            </div>
            <div class="recent-item-profit ${profitClass}">${profitStr}</div>
        `;
        container.insertBefore(item, container.firstChild);

        // Remove oldest if > 5
        const items = container.querySelectorAll('.recent-item');
        if (items.length > 5) items[items.length - 1].remove();
    }

    // ══════════════════════════════════════
    //  TOAST NOTIFICATIONS
    // ══════════════════════════════════════

    function showToast(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `flip-toast flip-toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
            <span>${escapeHtml(msg)}</span>
        `;
        document.body.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('toast-visible'));
        setTimeout(() => {
            toast.classList.remove('toast-visible');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // ══════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════

    function fmt(n) {
        if (isNaN(n)) return '0';
        const abs = Math.abs(n);
        const sign = n < 0 ? '-' : '';
        if (abs >= 1e9) return `${sign}${(abs / 1e9).toFixed(2)}B`;
        if (abs >= 1e6) return `${sign}${(abs / 1e6).toFixed(2)}M`;
        if (abs >= 1e3) return `${sign}${(abs / 1e3).toFixed(1)}K`;
        return `${sign}${Math.round(abs).toLocaleString()}`;
    }

    function setEl(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function escapeRegex(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Init: clear fields & show empty state ──
    showEmpty();
});
