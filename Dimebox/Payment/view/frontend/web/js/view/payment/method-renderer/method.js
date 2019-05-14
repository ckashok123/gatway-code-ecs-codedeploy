/**
 * Dimebox JS component 
 *
 * @category    Dimebox
 * @author      Chetu India Team
 */

define(
        [
            'jquery',
            'Magento_Payment/js/view/payment/cc-form',
            'Magento_Checkout/js/action/place-order',
            'Magento_Checkout/js/model/quote',
            'Magento_Customer/js/model/customer',
            'Magento_Checkout/js/model/payment/additional-validators',
            'mage/url',
            'Magento_Checkout/js/model/full-screen-loader',
            window.checkoutConfig.environmentUrl,
            'Dimebox_Payment/js/view/payment/data',
            'ko'


        ],
        function (
                $,
                Component,
                placeOrderAction,
                quote,
                customer,
                additionalValidators,
                url,
                fullScreenLoader,
                jsclient,
                ko
                ) {
            'use strict';
            return Component.extend({

                defaults: {
                    template: 'Dimebox_Payment/payment/form'
                },
                getIframeUrl: function () {
                    return window.checkoutConfig.baseUrl + 'dimebox/iframe/form';
                },
                getTermUrl: function () {
                    return window.checkoutConfig.baseUrl + 'dimebox/iframe/saveorder';
                },
                getActionUrl: function () {
                    return window.checkoutConfig.actionUrl;
                },
                getPareq: function () {
                    return window.checkoutConfig.pareq;
                },
                getMailingAddress: function () {
                    return window.checkoutConfig.payment.checkmo.mailingAddress;
                },
                setPlaceOrderHandler: function (handler) {
                    this.placeOrderHandler = handler;
                },
                context: function () {
                    return this;
                },
                isShowLegend: function () {
                    return true;
                },
                getCode: function () {
                    return 'dimebox_payment';
                },
                isActive: function () {
                    return true;
                }

            });
        }
);


