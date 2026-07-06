const DEBUG = false;
	
(function(){

function getBlocksCartData(){

    /*
     * The subtotal shim is only needed for WooCommerce Blocks.
     * Classic WooCommerce pages fall back gracefully by returning null.
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

async function waitForBlocksStore(){

    return new Promise(resolve => {

        let tries = 0;

        const timer = setInterval(function(){

            tries++;

            if (getBlocksCartData()) {

                clearInterval(timer);

                resolve(true);

                return;
            } // END function waitForBlocksStore()

            if (tries > 50) {

                clearInterval(timer);

                resolve(false);
            } // END if

        }, 300);
    });
} // END function waitForBlocksStore()

async function injectSubtotalRow(){

    const hasStore =
        await waitForBlocksStore();

    if (!hasStore) {
        return;
    } // END if (!hasStore)

    const totalsWrapper =
        document.querySelector(
            '.wc-block-components-totals-wrapper'
        );

    if (!totalsWrapper) {
        return;
    } // END if (!totalsWrapper)

    const totalsRoot =
        totalsWrapper.closest(
            '.wc-block-cart__totals, .wc-block-components-sidebar, .wp-block-woocommerce-cart-order-summary-block, .wc-block-cart'
        )
        || totalsWrapper.parentElement
        || totalsWrapper;

    const customSubtotalRows =
        totalsRoot.querySelectorAll(
            '.tokolariso-subtotal-row'
        );

    customSubtotalRows.forEach(function(row, index){

        if (index > 0) {
            row.remove();
        } // END if
    });

    /*
     * Native subtotal aanwezig?
     */

    const nativeSubtotal =
        Array
            .from(
                totalsRoot.querySelectorAll(
                    '.wc-block-components-totals-item'
                )
            )
            .find(function(row){

                if (
                    row.classList.contains(
                        'tokolariso-subtotal-row'
                    )
                ) {
                    return false;
                } // END if

                const label =
                    row.querySelector(
                        '.wc-block-components-totals-item__label'
                    );

                return (
                    label
                    &&
                    label.textContent
                    &&
                    label.textContent
                        .trim()
                        .toLowerCase() === 'subtotaal'
                );
            })
        ||
        totalsRoot.querySelector(
            '.wc-block-components-totals-item--subtotal'
        );

    /*
     * Custom subtotal aanwezig?
     */

    const customSubtotal =
        totalsRoot.querySelector(
            '.tokolariso-subtotal-row'
        );

    /*
     * Shipping row
     */

let hasFreeShipping = false;

try {

    const cart =
        getBlocksCartData();

    const rates =
        cart?.shippingRates?.[0]
        ?.shipping_rates
        || [];

    hasFreeShipping =
        rates.some(function(rate){

            const price =
                parseInt(
                    rate?.price || 0,
                    10
                );

            return (
    rate?.price === '0'
    ||
    rate?.price === 0
);

        });

} catch(e) {

    if (DEBUG) {

        console.error(
            'FREE SHIPPING DETECTION ERROR',
            e
        );

    } // END if (DEBUG)

} // END try/catch
	
	
    /*
     * GEEN gratis verzending
     */

    if (!hasFreeShipping) {

        /*
         * Cleanup custom subtotal
         */

        customSubtotalRows.forEach(function(row){

            row.remove();

        });

        return;

    } // END if (!hasFreeShipping)

    /*
     * Native subtotal bestaat al
     */

    if (nativeSubtotal) {

        /*
         * Cleanup custom subtotal
         */

        customSubtotalRows.forEach(function(row){

            row.remove();

        });

        return;

    } // END if (nativeSubtotal)


    /*
     * Cart data
     */

    let cart;

    try {

        cart =
            getBlocksCartData();

    } catch(e) {

        if (DEBUG) {

            console.error(
                'SUBTOTAL ERROR',
                e
            );

        } // END if (DEBUG)

        return;

    } // END try/catch

    if (
        !cart
        ||
        !cart.totals
    ) {
        return;
    } // END if (!cart || !cart.totals)

    /*
     * Subtotal incl. BTW
     */

    const subtotalExclRaw =
        parseInt(
            cart.totals.total_items || 0,
            10
        );

    const subtotalTaxRaw =
        parseInt(
            cart.totals.total_items_tax || 0,
            10
        );

    const subtotalInclRaw =
        subtotalExclRaw
        +
        subtotalTaxRaw;

    /*
     * Currency
     */

    const currency =
        cart.totals.currency_symbol || '€';

    const formattedSubtotal =
        currency
        + ' '
        + (
            subtotalInclRaw / 100
        ).toLocaleString(
            'nl-NL',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            } // END if
        );

	/*
 * Custom subtotal bestaat al?
 * Dan updaten
 */

if (customSubtotal) {

    const value =
        customSubtotal.querySelector(
            '.wc-block-components-totals-item__value'
        );

    if (value) {

        value.innerHTML =
            formattedSubtotal;

    } // END if (value)

    return;

} // END if (customSubtotal)
	
    /*
     * Row
     */

    const row =
        document.createElement('div');

    row.className =
        'wc-block-components-totals-item tokolariso-subtotal-row';

    row.style.marginBottom =
        '20px';

    row.innerHTML = `

        <div class="wc-block-components-totals-item__label">
            Subtotaal
        </div>

        <div class="wc-block-components-totals-item__value">
            ${formattedSubtotal}
        </div>
    `;

    /*
     * Shipping row opnieuw ophalen
     */

    const latestShippingRow =
        totalsWrapper.querySelector(
            '.wc-block-components-totals-shipping'
        );

    if (latestShippingRow) {

        latestShippingRow.before(
            row
        );

    } else {

        totalsWrapper.prepend(
            row
        );

    } // END if (latestShippingRow)

} // END async function injectSubtotalRow()

/*
 * Init + observer
 */

window.addEventListener(
    'load',
    function(){

        /*
         * Eerste injectie
         */

        setTimeout(function(){

            injectSubtotalRow();

        }, 1500);

        /*
         * Body observer
         * Woo Blocks rendert async
         */

        const bodyObserver =
            new MutationObserver(function(){

                clearTimeout(
                    window.tokolarisoSubtotalTimer
                );

                window.tokolarisoSubtotalTimer =
                    setTimeout(function(){

                        injectSubtotalRow();

                    }, 300);

            }); /* END MutationObserver */

        bodyObserver.observe(
            document.body,
            {
                childList: true,
                subtree: true
            } // END else
        );

    } /* END function */
); /* END window.addEventListener() */

})();
