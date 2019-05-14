/**
 * Dimebox JS to place order with Iframe  
 *
 * @category    Dimebox
 * @author      Chetu India Team
 */

window.dimeboxPay = function () {
    var intervalid = setInterval(function () {
        if (jQuery('#dimebox_payment-iframe').contents().find('#_submit_button').length == 1) {

            // script to apply custom css to page
            var iFrameDOM = document.getElementsByTagName('iframe')[0]
            console.log('iFrameDOM', iFrameDOM);
            var cssLink = document.createElement("link");
            //load bootstrap
            cssLink.href = "https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css";
            cssLink.rel = "stylesheet"; 
            cssLink.type = "text/css";
            iFrameDOM.contentWindow.document.head.appendChild(cssLink);
            var cssLink2 = document.createElement("link");
            //load custom CSS from magento Bergstromes theme (needs to have URL updated)
            // cssLink2.href = "http://127.0.0.1:8888/magento2-2.2/pub/static/version1551974243/frontend/Dimebox/bergstroms/en_CA/css/styles-m.css";
            cssLink2.href = "http://bergstroms.dimebox.com/pub/static/version1551974243/frontend/Dimebox/bergstroms/en_CA/css/styles-m.css";
	    cssLink2.rel = "stylesheet"; 
            cssLink2.type = "text/css";
            iFrameDOM.contentWindow.document.head.appendChild(cssLink2);

            jQuery('#dimebox_payment-iframe').contents().find('#_card_number').keyup(function () {
                var cc_num = jQuery('#dimebox_payment-iframe').contents().find('#_card_number').val();
                jQuery('#dimebox_payment-iframe').contents().find('#dimebox_cc_number').val(cc_num);
                jQuery('#dimebox_payment_cc_number').val(cc_num);
                jQuery('#dimebox_payment_cc_number').keyup();
            });
            jQuery('#dimebox_payment-iframe').contents().find('#_expiry').keyup(function () {
                var str = jQuery(this).val();
                var card_mon = str.slice(0, 2);
                var card_mon = card_mon.replace(/^0+/, '');
                var card_yr = 20 + str.slice(3, 5);
                jQuery('#dimebox_payment_expiration').val(card_mon);
                jQuery('#dimebox_payment-iframe').contents().find('#dimebox_cc_month').val(card_mon);
                jQuery('#dimebox_payment_expiration').trigger('change');
                jQuery('#dimebox_payment_expiration_yr').val(card_yr);
                jQuery('#dimebox_payment-iframe').contents().find('#dimebox_cc_year').val(card_yr);
                jQuery('#dimebox_payment_expiration_yr').trigger('change');
            });
            jQuery('#dimebox_payment-iframe').contents().find('#_cvv').keyup(function () {
                jQuery('#dimebox_payment_cc_cid').val(jQuery('#dimebox_payment-iframe').contents().find('#_cvv').val());
                jQuery('#dimebox_payment-iframe').contents().find('#dimebox_cc_cvv').val(jQuery('#dimebox_payment-iframe').contents().find('#_cvv').val());
                jQuery('#dimebox_payment_cc_cid').trigger('change');
                jQuery('#dimebox_payment-iframe').contents().find('#dimebox_cc_type').val(jQuery('#dimebox_payment_cc_type').val());
            });
            jQuery('#dimebox_payment-iframe').contents().find('form').submit(function (e) {
				console.log(jQuery('#customer-email').val());
				document.cookie='customer_email='+jQuery('#customer-email').val()+';path=/;';
                if (jQuery('#dimebox_payment-iframe').contents().find('form #_alert_container').find('ul li').length > 0 || jQuery('#dimebox_payment-iframe').contents().find('form #_alert_container').find('span').length > 0) {
                    return false;
                } else {
                    jQuery('.loading-mask').css('display', 'block');
					window.onmessage = function (e) {
						form_obj = JSON.parse(e.data);
						jQuery('#PaReq').val(form_obj.pareq);
						jQuery('#threeDSecureForm').attr('action', form_obj.actionUrl);
						if(window.checkoutConfig.status == null){
							window.checkoutConfig.status = form_obj.status;
						}
						
						if (window.checkoutConfig.secureCard == 1 && window.checkoutConfig.status == "Y") {
							console.log('Going for 3D');
							var secure_form = setInterval(function () {
							jQuery('#threeDSecureForm').submit();
							clearInterval(secure_form);
							}, 5000);
						} else {
							console.log('normal card');
							var place_order = setInterval(function () {
							jQuery('#dimebox-place-order').trigger('click');
							clearInterval(place_order);
                            }, 3000);
                            window.dimeboxPay();
						}
						
					};
                   
                }

            });

            clearInterval(intervalid);
        }
    }, 1000);
};
