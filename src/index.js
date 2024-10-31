console.log('A placeholder file for hooking optty inline scripts after');

jQuery(document).on('updated_cart_totals', function () {
    var total = jQuery('#cart-total').val();
    var elementExists = document.getElementById('mw');
    var widgetName = 'cart-box-widget';
    if (!elementExists || widgetName === 'cart-box-widget') {
        window[window['Optty-Widget-SDK']]['q'] = window[window['Optty-Widget-SDK']]['q'].splice(0, 1);
        (function (w, d, s, o, f, js, fjs) {
            w['Optty-Widget-SDK'] = o;
            w[o] = w[o] || function () {
                (w[o].q = w[o].q || []).push(arguments)
            };
            js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
            js.id = o;
            js.src = f;
            js.async = 1;
            fjs.parentNode.insertBefore(js, fjs);
        }(window, document, 'script', 'mw', script_vars.widget_url));
    }
    window.mw('cart-box-widget', {amount: total});
})