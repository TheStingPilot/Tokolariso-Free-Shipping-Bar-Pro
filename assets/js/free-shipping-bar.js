(function(){

const DEBUG = !!(window.fsb_ajax && window.fsb_ajax.debug);

function debugLog(...args) {

    if (DEBUG) {

        console.log(...args);

    } /* END if (DEBUG) */

} /* END function debugLog() */

function hideBar() {

    const existingBar =
        document.getElementById(
            'free-shipping-bar'
        );

    if (existingBar) {

        existingBar.remove();
    } // END if

    document.documentElement.style.setProperty(
        '--fsb-height',
        '0px'
    );
} // END function hideBar()

function escapeHtml(value) {

    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
} // END function escapeHtml()

function safeImage(value) {

    const url = String(value || '');

    if (
        url.startsWith('http://')
        ||
        url.startsWith('https://')
        ||
        url.startsWith('/')
    ) {
        return url;
    } // END if

    return '';
} // END function safeImage()

function normalizeCartAddress(address) {

    address =
        address || {};

    return {
        country: String(address.country || 'NL').trim() || 'NL',
        postcode: String(address.postcode || '').trim(),
        city: String(address.city || '').trim()
    };
} // END function normalizeCartAddress()

function getCartAddressStorageKey() {

    return fsb_ajax.is_logged_in
        ? 'free_shipment_progressbar_account_address'
        : 'free_shipment_progressbar_checkout_address';
} // END function getCartAddressStorageKey()

function cleanupLegacyStoredAddress() {

    try {
        window.localStorage
            ?.removeItem('free_shipment_progressbar_cart_address');
    } catch(e) {}
} // END function cleanupLegacyStoredAddress()

function getStoredCartAddress() {

    try {

        cleanupLegacyStoredAddress();

        const raw =
            window.localStorage
                ?.getItem(
                    getCartAddressStorageKey()
                );

        if (!raw) {
            return null;
        } // END if

        const address =
            normalizeCartAddress(
                JSON.parse(raw)
            );

        if (
            !address.postcode
            &&
            !address.city
        ) {
            return null;
        } // END if

        return address;

    } catch(e) {
        return null;
    } // END try
} // END function getStoredCartAddress()

function storeCartAddress(address) {

    address =
        normalizeCartAddress(address);

    if (
        !address.postcode
        &&
        !address.city
    ) {
        return;
    } // END if

    try {

        window.localStorage
            ?.setItem(
                getCartAddressStorageKey(),
                JSON.stringify(address)
            );

    } catch(e) {}
} // END function storeCartAddress()

function getVisibleCartItemCount() {

    const cartRoot =
        document.querySelector(
            '.wc-block-cart-items, .woocommerce-cart-form'
        );

    if (!cartRoot) {
        return null;
    } // END if

    const quantityFields =
        cartRoot.querySelectorAll(
            '.wc-block-components-quantity-selector__input, input.qty'
        );

    if (!quantityFields.length) {
        return null;
    } // END if

    let total = 0;

    quantityFields.forEach(function(field){

        const quantity =
            Number(field.value || 0);

        if (!Number.isNaN(quantity)) {
            total += quantity;
        } // END if
    });

    return total;
} // END function getVisibleCartItemCount()

function getCartItemCount(cart) {

    if (isCartVisiblyEmpty()) {
        return 0;
    } // END if

    const visibleCount =
        getVisibleCartItemCount();

    if (
        visibleCount !== null
        &&
        visibleCount > 0
    ) {
        return visibleCount;
    } // END if

    const serverCount =
        Number(window.free_shipment_progressbarServerCartCount);

    if (
        !Number.isNaN(serverCount)
        &&
        serverCount > 0
    ) {
        return serverCount;
    } // END if

    if (Array.isArray(cart?.items)) {

        const itemTotal =
            cart.items.reduce(function(total, item){

            const quantity =
                Number(
                    item?.quantity
                    ?? item?.qty
                    ?? 1
                );

            return total + (
                Number.isNaN(quantity)
                ? 0
                : quantity
            );

        }, 0);

        if (itemTotal > 0) {
            return itemTotal;
        } // END if
    } // END if

    const directCount =
        cart?.itemsCount
        ?? cart?.items_count
        ?? cart?.item_count;

    if (
        directCount !== undefined
        &&
        directCount !== null
        &&
        !Number.isNaN(Number(directCount))
    ) {
        return Number(directCount);
    } // END if

    return 0;
} // END function getCartItemCount()

function isCartVisiblyEmpty() {

    if (
        document.querySelector(
            '.wc-block-cart__empty-cart, .woocommerce-cart .cart-empty, .woocommerce-cart .wc-empty-cart-message'
        )
    ) {
        return true;
    } // END if

    return (
        document.body.classList.contains('woocommerce-cart')
        &&
        document.body.textContent.includes(
            'Your cart is currently empty'
        )
    );
} // END function isCartVisiblyEmpty()

function getBlocksCartData() {

    /*
     * WooCommerce Blocks exposes its cart store through wc.wcBlocksData.
     * Keep this check defensive so classic checkout/cart pages never throw.
     */

    if (
        !window.wc
        ||
        !window.wc.wcBlocksData
        ||
        !window.wp
        ||
        !wp.data
        ||
        !wp.data.select
    ) {
        return null;
    } // END if

    try {

        const cartStore =
            wp.data.select('wc/store/cart');

        if (
            !cartStore
            ||
            typeof cartStore.getCartData !== 'function'
        ) {
            return null;
        } // END if

        return cartStore.getCartData();

    } catch(e) {
        return null;
    } // END try
} // END function getBlocksCartData()

function getBlocksStoreAddress() {

    if (
        !window.wp
        ||
        !wp.data
        ||
        !wp.data.select
    ) {
        return null;
    } // END if

    const candidates = [];

    try {

        const checkoutStore =
            wp.data.select('wc/store/checkout');

        if (checkoutStore) {

            [
                'getShippingAddress',
                'getBillingAddress'
            ].forEach(function(method){

                if (
                    typeof checkoutStore[method]
                    === 'function'
                ) {
                    candidates.push(
                        checkoutStore[method]()
                    );
                } // END if
            });
        } // END if
    } catch(e) {}

    try {

        const cartStore =
            wp.data.select('wc/store/cart');

        if (cartStore) {

            if (
                typeof cartStore.getCartData
                === 'function'
            ) {

                const cartData =
                    cartStore.getCartData();

                candidates.push(
                    cartData?.shippingAddress,
                    cartData?.billingAddress
                );
            } // END if

            if (
                typeof cartStore.getCustomerData
                === 'function'
            ) {

                const customerData =
                    cartStore.getCustomerData();

                candidates.push(
                    customerData?.shippingAddress,
                    customerData?.billingAddress
                );
            } // END if
        } // END if
    } catch(e) {}

    for (const candidate of candidates) {

        if (
            !candidate
            ||
            (
                !candidate.country
                &&
                !candidate.postcode
                &&
                !candidate.city
            )
        ) {
            continue;
        } // END if

        const address =
            normalizeCartAddress(candidate);

        if (
            address.country
            ||
            address.postcode
            ||
            address.city
        ) {
            return address;
        } // END if
    } // END for

    return null;
} // END function getBlocksStoreAddress()

function normalizeCheckoutCountry(value) {

    const country =
        String(value || '').trim();

    const normalized =
        country.toLowerCase();

    const countryMap = {
        nederland: 'NL',
        netherlands: 'NL',
        belgie: 'BE',
        'belgië': 'BE',
        belgium: 'BE',
        belgique: 'BE',
        deutschland: 'DE',
        duitsland: 'DE',
        germany: 'DE',
        frankrijk: 'FR',
        france: 'FR'
    };

    return countryMap[normalized]
        || country;
} // END function normalizeCheckoutCountry()

function getCheckoutFieldValue(selectors) {

    const roots = [
        document.querySelector('.wc-block-checkout'),
        document.querySelector('.wp-block-woocommerce-checkout'),
        document.querySelector('form.woocommerce-checkout'),
        document.body
    ].filter(Boolean);

    for (const root of roots) {

        for (const selector of selectors) {

            const field =
                root.querySelector(selector);

            if (!field) {
                continue;
            } // END if

            const value =
                field.value
                || field.getAttribute('value')
                || field.selectedOptions?.[0]?.value
                || field.selectedOptions?.[0]?.textContent
                || '';

            if (String(value || '').trim()) {
                return String(value).trim();
            } // END if
        } // END for
    } // END for

    return '';
} // END function getCheckoutFieldValue()

function getCheckoutDomAddress() {

    const country =
        normalizeCheckoutCountry(
            getCheckoutFieldValue([
                '#shipping-country',
                '#billing-country',
                '#shipping_country',
                '#billing_country',
                '#calc_shipping_country',
                '[name="shipping-country"]',
                '[name="billing-country"]',
                '[name="shipping_country"]',
                '[name="billing_country"]',
                '[name="country"]',
                '[autocomplete="shipping country"]',
                '[autocomplete="billing country"]'
            ])
        );

    const postcode =
        getCheckoutFieldValue([
            '#shipping-postcode',
            '#billing-postcode',
            '#shipping_postcode',
            '#billing_postcode',
            '#calc_shipping_postcode',
            '[name="shipping-postcode"]',
            '[name="billing-postcode"]',
            '[name="shipping_postcode"]',
            '[name="billing_postcode"]',
            '[name="postcode"]',
            '[autocomplete="shipping postal-code"]',
            '[autocomplete="billing postal-code"]'
        ]);

    const city =
        getCheckoutFieldValue([
            '#shipping-city',
            '#billing-city',
            '#shipping_city',
            '#billing_city',
            '#calc_shipping_city',
            '[name="shipping-city"]',
            '[name="billing-city"]',
            '[name="shipping_city"]',
            '[name="billing_city"]',
            '[name="city"]',
            '[autocomplete="shipping address-level2"]',
            '[autocomplete="billing address-level2"]'
        ]);

    const address =
        normalizeCartAddress({
            country,
            postcode,
            city
        });

    if (
        country
        ||
        postcode
        ||
        city
    ) {
        return address;
    } // END if

    return null;
} // END function getCheckoutDomAddress()

function setCartCounterValue(count) {

    const normalizedCount =
        String(
            Math.max(
                0,
                Number(count) || 0
            )
        );

    const selectors = [
        '.cart-count .count',
        '.cart-count .count-number',
        '.cart-contents .count',
        '.cart-contents .count-number',
        '.site-header-cart .count',
        '.site-header-cart .count-number',
        '.header-cart .count',
        '.header-cart .count-number',
        '.sydney-header-cart .count',
        '.sydney-header-cart .count-number',
        '.menu-item-cart .count',
        '.menu-item-cart .count-number',
        '#site-header-cart .count',
        '#site-header-cart .count-number',
        '#site-header-cart .cart-count .count',
        '#site-header-cart .cart-count .count-number',
        '#site-header-cart .cart-contents .count',
        '#site-header-cart .cart-contents .count-number',
        '.wc-block-mini-cart__badge'
    ];

    const badges =
        document.querySelectorAll(
            selectors.join(',')
        );

    badges.forEach(function(badge){

        if (
            badge.closest(
                '.wishlist, .yith-wcwl, .header-wishlist-icon, [class*="wishlist"], [class*="favorite"], [class*="favourite"]'
            )
        ) {
            return;
        } // END if

        if (
            badge.textContent.trim()
            === normalizedCount
        ) {
            return;
        } // END if

        badge.textContent =
            normalizedCount;

        badge.classList.remove(
            'pulse'
        );

        void badge.offsetWidth;

        badge.classList.add(
            'pulse'
        );
    });
} // END function setCartCounterValue()

	/*
 * Sydney cart counter sync
 */

function syncSydneyCartCounter() {

    if (
        !window.wc ||
        !window.wc.wcBlocksData ||
        !window.wp ||
        !wp.data
    ) {
        return;
    } // END if

    /*
     * Kleine delay zodat
     * Woo Blocks klaar is
     */

    setTimeout(() => {

        const cart =
            getBlocksCartData();

        if (!cart) {
            return;
        } // END if

        /*
         * Aantal producten
         */

const count =
    isCartVisiblyEmpty()
    ? 0
    : getCartItemCount(cart);

        if (
            count <= 0
            &&
            !isCartVisiblyEmpty()
        ) {
            return;
        } // END if

        /*
         * Zoek bestaande badge
         */

        let badge =
            document.querySelector(
                '.cart-count .count'
            );

        /*
         * Sydney fallback:
         * oudere themes
         */

        if (!badge) {

            badge =
                document.querySelector(
                    '.cart-count .count-number'
                );

        } // END if (!badge)

        /*
         * Geen badge gevonden?
         * Dan zelf aanmaken
         */

        if (!badge) {

            const wrapper =
                document.querySelector(
                    '.cart-count'
                );

            if (!wrapper) {
                return;
            } // END if

            badge =
                document.createElement(
                    'span'
                );

            badge.className =
                'count';

            wrapper.appendChild(
                badge
            );

        } // END if (!badge create)

        /*
         * Alleen nummer vervangen
         */

        setCartCounterValue(count);

		if (count <= 0) {

          hideBar();

        } // END if (count <= 0)

        /*
         * Pulse animatie
         */

        badge.classList.remove(
            'pulse'
        );

        void badge.offsetWidth;

        badge.classList.add(
            'pulse'
        );

        /*
         * Debug logging
         */

        if (DEBUG) {

            console.log(
                'COUNTER UPDATED:',
                count
            );

        } // END if (DEBUG)

    }, 500);

    /* END setTimeout */

} // END function syncSydneyCartCounter()
	
function initBar() {

    if (typeof fsb_ajax === 'undefined') {
        return;
    } // END if

    let loading = false;
    let pendingLoad = false;
    let storeSubscribed = false;

    const isCartPage =
        document.body.classList.contains(
            'woocommerce-cart'
        );

    function whenCartStoreReady(callback) {

        let tries = 0;

        const timer =
            setInterval(function(){

                tries++;

                let hasStore = false;

                try {

                    hasStore = !!(
                        window.wc
                        &&
                        window.wc.wcBlocksData
                        &&
                        window.wp
                        &&
                        wp.data
                        &&
                        wp.data.select
                        &&
                        wp.data.select('wc/store/cart')
                    );

                } catch(e) {}

                if (hasStore) {

                    clearInterval(timer);
                    callback();
                    return;
                } // END if

                if (tries >= 50) {
                    clearInterval(timer);
                } // END if
            }, 200);
    } // END function whenCartStoreReady()

    function showBar(message, percent, upsells = [], showProgress = true) {

        let bar =
            document.getElementById(
                'free-shipping-bar'
            );

        /*
         * Nog niet aanwezig?
         */

        if (!bar) {

            bar =
                document.createElement(
                    'div'
                );

            bar.id =
                'free-shipping-bar';

bar.innerHTML = `

    <div class="msg"></div>

    <div class="progress">
        <div class="bar"></div>
    </div>

    <div class="upsells-wrapper">

        <button
            class="upsells-arrow prev"
            type="button"
            aria-label="${escapeHtml(fsb_ajax.i18n.prev)}"
        >
            ‹
        </button>

        <div class="upsells"></div>

        <button
            class="upsells-arrow next"
            type="button"
            aria-label="${escapeHtml(fsb_ajax.i18n.next)}"
        >
            ›
        </button>

    </div>
`;

            /*
             * Voor header plaatsen
             */

            const header =
                document.querySelector(
                    '#masthead'
                );

            if (header) {

                header.before(bar);

            } else {

                document.body.prepend(bar);
            } // END else
        } // END if

function updateBarHeight() {

	/* Bewust 2x dezelfde functie om zeker te weten dat
	 *  - de DOM volledig gerenderd is
     *  - de upsells daadwerkelijk zichtbaar zijn
     *  - CSS/layout/herflow klaar is
     * Daarna pas de hoogte wordt gemeten
     */
	
    requestAnimationFrame(function(){

        requestAnimationFrame(function(){

            const height =
                bar.getBoundingClientRect().height;

            document.documentElement.style.setProperty(
                '--fsb-height',
                height + 'px'
            );

        }); // END requestAnimationFrame()

    }); // END requestAnimationFrame()

} // END function updateBarHeight()

updateBarHeight();

        /*
         * Text
         */

        const msg =
            bar.querySelector('.msg');

        msg.textContent =
            message || 'Berekenen';

        msg.style.display =
            showProgress ? '' : 'none';

        /*
         * Progress
         */

        const progress =
            bar.querySelector('.bar');

        const progressWrapper =
            bar.querySelector('.progress');

        progressWrapper.style.display =
            showProgress ? '' : 'none';
		
		const upsellWrapper =
            bar.querySelector('.upsells');

        percent =
            parseInt(percent || 0);

        progress.style.width =
            percent + '%';
		
		upsellWrapper.innerHTML = '';
		
		let currentIndex = 0;

if (
    Array.isArray(upsells)
    && upsells.length
) {

    upsells.forEach(product => {

        const productName =
            escapeHtml(product.name);

        const productPrice =
            escapeHtml(product.price);

        const productImage =
            escapeHtml(safeImage(product.image));

        const productId =
            parseInt(product.id || 0, 10);

        if (!productId) {
            return;
        } // END if

        const html = `

            <div class="upsell">

                <img
                    src="${productImage}"
                    alt="${productName}"
                >

                <div class="upsell-info">

                    <div class="upsell-title">
                        ${productName}
                    </div>

                    <div class="upsell-price">
                        ${productPrice}
                    </div>

                </div>

<button
    class="upsell-btn"
    data-product-id="${productId}"
>
    ${fsb_ajax.i18n.add_to_cart}
</button>
            </div>
        `;

        upsellWrapper.insertAdjacentHTML(
            'beforeend',
            html
        );
    });
} // END if Array.isArray(upsells) && upsells.length
		
		/*
 * Hoogte opnieuw berekenen
 */

updateBarHeight();

        if (percent >= 100) {

            progress.classList.add(
                'complete'
            );

        } else {

            progress.classList.remove(
                'complete'
            );
        } // END else
		
		const prevButton =
    bar.querySelector('.upsells-arrow.prev');

const nextButton =
    bar.querySelector('.upsells-arrow.next');

function updateSlider() {

    const cards =
        bar.querySelectorAll('.upsell');

    cards.forEach((card, index) => {

        if (
            index >= currentIndex
            &&
            index < currentIndex + 3
        ) {

            card.style.display = 'flex';

        } else {

            card.style.display = 'none';
        } // END else
    });
} // END function updateSlider()

/*
 * Desktop only
 */

if (
    window.innerWidth > 1024
) {

    updateSlider();

    prevButton.onclick = function() {

        currentIndex = Math.max(
            0,
            currentIndex - 1
        );

        updateSlider();
    };

    nextButton.onclick = function() {

        const maxIndex =
            Math.max(
                0,
                upsells.length - 3
            );

        currentIndex = Math.min(
            maxIndex,
            currentIndex + 1
        );

        updateSlider();
    };

} else {

    prevButton.style.display = 'none';
    nextButton.style.display = 'none';

    /*
     * BELANGRIJK:
     * mobiele cards forceren zichtbaar
     */

    const cards =
        bar.querySelectorAll('.upsell');

    cards.forEach(card => {

        card.style.display = 'flex';
    });
} // END else
		
    } // END function showBar()

function pickupSelected() {

    /*
     * Winkelwagenpagina:
     * geen pickup check nodig
     */

    if (isCartPage) {
        return false;
    } // END if

    /*
     * WooCommerce Blocks tabs
     */

    const deliveryOptions = document.querySelectorAll(
        '.wc-block-components-segmented-control__button'
    );

    for (const option of deliveryOptions) {

        const text =
            option.textContent
            ?.toLowerCase()
            ?.trim() || '';

        const active = (

            option.getAttribute(
                'aria-checked'
            ) === 'true'

            ||

            option.classList.contains(
                'is-active'
            )
        );

        if (
            active
            &&
            (
                text.includes('afhalen')
                || text.includes('pickup')
            )
        ) {

            debugLog(
                'PICKUP TAB ACTIVE'
            );

            return true;
        } // END if
    } // END for

    /*
     * Klassieke radios fallback
     */

    const radios = document.querySelectorAll(
        'input[type="radio"]'
    );

    let shippingValue = '';

    radios.forEach(radio => {

        if (
            radio.value.includes('pickup')
            || radio.value.includes('shipping')
            || radio.value.includes('flat_rate')
            || radio.value.includes('local_pickup')
            || radio.value.includes('wbsng')
        ) {

            if (radio.checked) {

                shippingValue =
                    radio.value;
            } // END if
        } // END if
    });

    if (!shippingValue) {
        return false;
    } // END if

    return (
        shippingValue.includes('pickup')
        || shippingValue.includes('local_pickup')
    );
} // END function pickupSelected()

    function getCartAddress() {

        let country = 'NL';
        let postcode = '';
        let city = '';
        const useCustomerAddress =
            Boolean(fsb_ajax.use_customer_address);
        const canUseStoredAddress =
            !useCustomerAddress;
        let hasFreshCheckoutAddress =
            false;

        /*
         * WooCommerce Blocks
         */

        const blocksAddress =
            getBlocksStoreAddress();

        if (blocksAddress) {

            country =
                blocksAddress.country || country;

            postcode =
                blocksAddress.postcode || postcode;

            city =
                blocksAddress.city || city;

            hasFreshCheckoutAddress =
                true;
        } // END if

        /*
         * Checkout DOM fallback. WooCommerce Blocks uses hyphenated field
         * IDs in some themes, while classic checkout uses underscored IDs.
         */

        if (
            !useCustomerAddress
        ) {

            const domAddress =
                getCheckoutDomAddress();

            if (domAddress) {

                country =
                    domAddress.country || country;

                postcode =
                    domAddress.postcode || postcode;

                city =
                    domAddress.city || city;

                hasFreshCheckoutAddress =
                    true;
            } // END if
        } // END if

        if (
            !postcode
            &&
            !city
            &&
            !hasFreshCheckoutAddress
            &&
            canUseStoredAddress
        ) {

            const storedAddress =
                getStoredCartAddress();

            if (storedAddress) {

                country =
                    storedAddress.country || country;

                postcode =
                    storedAddress.postcode || '';

                city =
                    storedAddress.city || '';
            } // END if
        } // END if

        const address =
            normalizeCartAddress({
                country,
                postcode,
                city
            });

        storeCartAddress(address);

        if (DEBUG) {

            console.log(
                'FREE SHIPPING ADDRESS:',
                address
            );
        } // END if

        return address;
    } // END try

	if (getBlocksCartData()) {

    try {

        const cartData =
            getBlocksCartData();

    } catch(e) {

if (DEBUG) {

    console.error(
        'STORE API DEBUG ERROR',
        e
    );
} // END if
    } // END catch
} // END try
	
    function loadBar(force = false) {

        debugLog(
            '[FREE SHIPPING] LOAD BAR',
            {
                force,
                loading
            }
        );

        if (loading) {

            pendingLoad = true;

            return;
        } // END if

        loading = true;

        /*
         * Pickup?
         */

        if (pickupSelected()) {

            hideBar();

            loading = false;

            return;
        } // END if

        const address =
            getCartAddress();

        /*
         * AJAX
         */

        const params =
            new URLSearchParams({

                action:
                    'get_free_shipping_progress',

                security:
                    fsb_ajax.progress_nonce || '',

                country:
                    address.country,

                postcode:
                    address.postcode,

                city:
                    address.city,

                use_customer_address:
                    fsb_ajax.use_customer_address ? '1' : '0',

                t: Date.now()
            });

        fetch(
            fsb_ajax.url
            + '?'
            + params.toString(),
            {
                cache: 'no-store'
            }
        )

        

.then(async res => {

    const text = await res.text();

    /*
     * HTTP error?
     */

    if (!res.ok) {

        console.group(
            'FREE SHIPPING AJAX ERROR'
        );

        console.error(
            'HTTP STATUS:',
            res.status
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'AJAX HTTP ERROR'
        );
    } // END if

    /*
     * Geen JSON?
     */

    if (
        !text.trim().startsWith('{')
        &&
        !text.trim().startsWith('[')
    ) {

        console.group(
            'FREE SHIPPING INVALID JSON'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'INVALID JSON RESPONSE'
        );
    } // END if

    /*
     * Parse JSON
     */

    try {

        return JSON.parse(text);

    } catch(error) {

        console.group(
            'FREE SHIPPING JSON PARSE ERROR'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.error(
            'PARSE ERROR:',
            error
        );

        console.groupEnd();

        throw error;
    } // END catch
})

.then(data => {

    if (
        data
        &&
        data.cart_count !== undefined
        &&
        data.cart_count !== null
        &&
        !Number.isNaN(Number(data.cart_count))
    ) {

        window.free_shipment_progressbarServerCartCount =
            Number(data.cart_count);

        setCartCounterValue(
            window.free_shipment_progressbarServerCartCount
        );
    } // END if

	if (DEBUG) {
    console.group(
        'FREE SHIPPING DEBUG'
    );

    console.log(
        'FULL AJAX RESPONSE:',
        data
    );

    console.log(
        'TOTAL:',
        data.total
    );

    console.log(
        'MINIMUM:',
        data.minimum
    );

    console.log(
        'REMAINING:',
        data.remaining
    );

    console.log(
        'DEBUG SHIPPING DATA:',
        data.debug_shipping_data
    );

    console.log(
        'ADDRESS SOURCE:',
        data.address_source
    );

    console.groupEnd();
	} // END if

            /*
             * Geen free shipping?
             */

            if (
                !data
                || data.show === false
            ) {

                if (
                    data?.empty_cart
                    ||
                    isCartVisiblyEmpty()
                ) {

                    setCartCounterValue(0);
                } // END if

                hideBar();

                return;
            } // END try

showBar(
    data.message,
    data.percent,
    data.upsells || [],
    data.show_progress !== false
);
        })

        .catch(error => {

if (DEBUG) {

    console.error(
        '[FREE SHIPPING]',
        error
    );
} // END if

            hideBar();
        })

        .finally(() => {

            loading = false;

            if (pendingLoad) {

                pendingLoad = false;

                setTimeout(function(){

                    loadBar(true);

                }, 150);
            } // END if
        });
    } // END catch

/*
 * INIT
 */

loadBar(true);
	
	/*
 * FORCE WooCommerce Blocks refresh
 * na page load
 */

whenCartStoreReady(function(){

    setTimeout(function(){

        try {

            wp.data
                .dispatch('wc/store/cart')
                .invalidateResolutionForStore();

            if (DEBUG) {

                console.log(
                    'STORE CACHE INVALIDATED'
                );
            } // END if

        } catch(e) {

            debugLog(
                'STORE INVALIDATE ERROR',
                e
            );
        } // END catch

    }, 1200);
});

/*
 * WooCommerce Blocks store subscribe
 */

whenCartStoreReady(function(){

    if (
        storeSubscribed
        ||
        !wp.data.subscribe
    ) {
        return;
    } // END if

    storeSubscribed = true;

    let previousTotals = '';

    wp.data.subscribe(function(){

        try {

            const cart =
                getBlocksCartData();

            if (!cart?.totals) {
                return;
            } // END if

            const currentTotals =
                JSON.stringify(cart.totals);

            if (
                currentTotals === previousTotals
            ) {
                return;
            } // END try

            previousTotals =
                currentTotals;

            clearTimeout(
                window.fsbStoreTimer
            );

            window.fsbStoreTimer =
                setTimeout(function(){

                    loadBar(true);

                }, 300);

        } catch(e){}
    });
}); // END whenCartStoreReady
	
/*
 * WooCommerce events
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'updated_cart_totals updated_wc_div wc_fragments_refreshed added_to_cart removed_from_cart updated_checkout',

        function(){

            setTimeout(function(){

                loadBar(true);

            }, 500);
        } // END if
    );
} // END if

/*
 * Sync bij normale cart updates
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'updated_cart_totals removed_from_cart added_to_cart updated_wc_div',

        function(){

            setTimeout(function(){

                syncSydneyCartCounter();

            }, 300);
        } // END if
    );
} // END if

/*
 * Alleen WooCommerce fragments observeren
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'wc_fragments_refreshed added_to_cart removed_from_cart updated_checkout',

        function(){

            clearTimeout(
                window.fsbTimer
            );

            window.fsbTimer =
                setTimeout(function(){

                    loadBar(true);

                }, 300); 
        } // END if
    );
} // END if
		
/*
 * Shipping switch
 */

document.body.addEventListener(
    'click',
    function(e){

        /*
         * Zoek shipping methode container
         */

        const shippingWrapper =
            e.target.closest(
                '.wc-block-checkout__shipping-method-option'
            );

        if (!shippingWrapper) {
            return;
        } // END if

        debugLog(
            'SHIPPING CLICK DETECTED'
        );

        /*
         * Kleine delay zodat Blocks
         * checked state kan updaten
         */

        setTimeout(function(){

            const isPickup =
                pickupSelected();

            debugLog(
                'PICKUP:',
                isPickup
            );

            if (isPickup) {

                debugLog(
                    'HIDE BAR'
                );

                hideBar();

            } else {

                debugLog(
                    'SHOW BAR'
                );

                loadBar(true);
            } // END else

        }, 300);
    } // END if
);

	/*
 * ADD TO CART
 */

document.body.addEventListener(
    'click',
    function(e){

        const button =
            e.target.closest('.upsell-btn');

        if (!button) {
            return;
        } // END if

        e.preventDefault();

        const productId =
            button.dataset.productId;

        if (!productId) {
            return;
        } // END if

        button.disabled = true;

        button.innerHTML = '...';

        const formData = new FormData();

        formData.append(
            'action',
            'free_shipment_progressbar_add_upsell_to_cart'
        );

        formData.append(
            'security',
            fsb_ajax.cart_nonce || ''
        );

        formData.append(
            'product_id',
            productId
        );

        fetch(
            fsb_ajax.url,
            {
                method: 'POST',
                body: formData
            } // END if
        )

        .then(async res => {



    const text = await res.text();

    /*
     * HTTP error?
     */

    if (!res.ok) {

        console.group(
            'FREE SHIPPING AJAX ERROR'
        );

        console.error(
            'HTTP STATUS:',
            res.status
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'AJAX HTTP ERROR'
        );
    } // END if

    /*
     * Geen JSON?
     */

    if (
        !text.trim().startsWith('{')
        &&
        !text.trim().startsWith('[')
    ) {

        console.group(
            'FREE SHIPPING INVALID JSON'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'INVALID JSON RESPONSE'
        );
    } // END if

    /*
     * Parse JSON
     */

    try {

        return JSON.parse(text);

    } catch(error) {

        console.group(
            'FREE SHIPPING JSON PARSE ERROR'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.error(
            'PARSE ERROR:',
            error
        );

        console.groupEnd();

        throw error;
    } // END catch
})

        .then(data => {

    if (data.success) {

        button.innerHTML =
            fsb_ajax.i18n.added;

        /*
         * WooCommerce Blocks refresh
         */

        if (
            window.wp
            &&
            wp.data
            &&
            wp.data.dispatch
        ) {

            try {

                wp.data
                    .dispatch('wc/store/cart')
                    .invalidateResolutionForStore();

            } catch(e) {

                console.log(e);
            } // END catch
        } // END try

        /*
         * Classic WooCommerce fallback
         */

/*
 * WooCommerce fragments refresh
 * ZONDER pagina refresh
 */

if (window.jQuery) {

    jQuery.ajax({

        url: fsb_ajax.fragments_url,

        type: 'POST',

        success: function(data) {

            if (
                data
                &&
                data.fragments
            ) {

                Object.keys(data.fragments)
                    .forEach(function(key){

                        jQuery(key).replaceWith(
                            data.fragments[key]
                        );
                    });

                jQuery(document.body)
                    .trigger('wc_fragments_refreshed');
            } // END if
        } // END if
    });
} // END try
		
        /*
         * Free shipping bar refresh
         */

        setTimeout(function(){

            loadBar(true);

        }, 500);
    } // END if
});
});
		
  /*
 * BLOCKS CART OBSERVER
 * Detecteert plus/min updates
 */

function getCartDomSignature() {

    if (isCartVisiblyEmpty()) {
        return 'empty';
    } // END if

    const cartNode =
        document.querySelector(
            '.wc-block-cart-items'
        );

    if (!cartNode) {
        return '';
    } // END if

    const quantities =
        Array.from(
            cartNode.querySelectorAll(
                '.wc-block-components-quantity-selector__input, input.qty'
            )
        )
        .map(function(input){
            return input.value || '';
        });

    return quantities.join('|');
} // END function getCartDomSignature()

const cartObserver = new MutationObserver(function(){

    clearTimeout(window.free_shipment_progressbarCartTimer);

    window.free_shipment_progressbarCartTimer =
        setTimeout(function(){

const cartSignature =
    getCartDomSignature();

if (
    cartSignature
    &&
    cartSignature === window.free_shipment_progressbarLastCartDomSignature
) {
    return;
} // END if

window.free_shipment_progressbarLastCartDomSignature =
    cartSignature;

if (DEBUG) {

    console.log(
        'BLOCKS CART CHANGE DETECTED'
    );

} // END if (DEBUG)

/*
 * Empty cart detectie
 */

const emptyCart =
    document.querySelector(
        '.wc-block-cart__empty-cart'
    );

if (emptyCart) {

    if (DEBUG) {

        console.log(
            'EMPTY CART DETECTED'
        );

    } // END if (DEBUG)

    /*
     * Header reset
     */

    setCartCounterValue(0);

    hideBar();

    return;

} // END if (emptyCart)

            syncSydneyCartCounter();

            loadBar(true);

        }, 400);

}); /* END MutationObserver */

function observeBlocksCartNode() {

    const cartNode =
        document.querySelector(
            '.wc-block-cart-items'
        );

    if (DEBUG) {
        console.log(
            'CART NODE:',
            cartNode
        );
    } // END if

    if (!cartNode) {
        return false;
    } // END if

    window.free_shipment_progressbarLastCartDomSignature =
        getCartDomSignature();

    cartObserver.observe(
        cartNode,
        {
            childList: true,
            subtree: true,
            characterData: true
        } // END if
    );

    return true;
} // END function observeBlocksCartNode()

if (!observeBlocksCartNode()) {

    setTimeout(
        observeBlocksCartNode,
        1000
    );

    setTimeout(
        observeBlocksCartNode,
        2500
    );
} // END if
	
	/*
     * Address changes
     */

    let addressTimeout = null;

    [
        'change',
        'input',
        'blur'
    ]

    .forEach(eventType => {

        document.body.addEventListener(
            eventType,
            function(e){

                const target =
                    e.target;

                if (!target) {
                    return;
                } // END if

                const isAddressField = (

                    target.name?.includes('country')
                    || target.name?.includes('postcode')
                    || target.name?.includes('city')
                    || target.name?.includes('state')

                    || target.id?.includes('country')
                    || target.id?.includes('postcode')
                    || target.id?.includes('city')
                    || target.id?.includes('state')
                );

                if (!isAddressField) {
                    return;
                } // END if

                clearTimeout(
                    addressTimeout
                );

                addressTimeout =
                    setTimeout(function(){

                        loadBar(true);

                    }, 1200);
            } // END if
        );
    });

} // END if

if (
    document.readyState === 'complete'
) {

    initBar();

} else {

    window.addEventListener(
        'load',
        initBar
    );
} // END else

})();
