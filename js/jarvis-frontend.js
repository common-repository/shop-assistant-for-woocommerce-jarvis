/******************************
 Metarial preloader plugin start
 ******************************/
(function (e) {


    var t = {
        position: "bottom",
        height: "5px",
        col_1: "#3b78e7",
        col_2: "#da4733",
        col_3: "#fdba2c",
        col_4: "#159756",
        fadeIn: 200,
        fadeOut: 200
    };
    e.materialPreloader = function (n) {
        var r = e.extend({}, t, n);
        $template = "<div id='materialPreloader' class='load-bar' style='height:" + r.height + ";display:none;" + r.position + ":0px'><div class='load-bar-container'><div class='load-bar-base base1' style='background:" + r.col_1 + "'><div class='color red' style='background:" + r.col_2 + "'></div><div class='color blue' style='background:" + r.col_3 + "'></div><div class='color yellow' style='background:" + r.col_4 + "'></div><div class='color green' style='background:" + r.col_1 + "'></div></div></div> <div class='load-bar-container'><div class='load-bar-base base2' style='background:" + r.col_1 + "'><div class='color red' style='background:" + r.col_2 + "'></div><div class='color blue' style='background:" + r.col_3 + "'></div><div class='color yellow' style='background:" + r.col_4 + "'></div> <div class='color green' style='background:" + r.col_1 + "'></div> </div> </div> </div>";
        e("#jarvis-pinball-box").prepend($template);
        this.on = function () {
            e("#materialPreloader").fadeIn(r.fadeIn)
        };
        this.off = function () {
            e("#materialPreloader").fadeOut(r.fadeOut)
        }
    }
})(jQuery);
/******************************
 Metarial preloader plugin end
 ******************************/

jQuery(document).ready(function () {
    //Position override for jarvis icon
    var jarvisVal = qc_jarvis_params;
    jQuery("#jarvis-icon-container").css({
        'right': jarvisVal.position_x + 'px',
        'bottom': jarvisVal.position_y + 'px'
    });
    //Disable Jarvis icon Animation
    if (jarvisVal.disable_jarvis_icon_animation == 1) {
        jQuery('.jarvis-ball').addClass('quickball-animation-deactive');
    } else {
        jQuery('.jarvis-ball').addClass('quickball-animation-active');
    }

});

jQuery(function ($) {



    //console.log('some : '+qc_jarvis_params.disable_jarvis_icon_animation);
    //Initialize the metarial preloader
    qcld_jarvis_preloader = new $.materialPreloader({
        position: 'top',
        height: '8px',
        col_1: '#159756',
        col_2: '#da4733',
        col_3: '#3b78e7',
        col_4: '#fdba2c',
        fadeIn: 200,
        fadeOut: 200
    });
    /* $(" .add_to_cart_button").click(function () {
     $("#qcld_cart_prod_table").addClass("loading");
     setTimeout(function () {
     showQcldCart();
     }, 1000);
     //showQcldCart();
     });*/

    function showQcldCart() {
        var data = {
            'action': 'get_cart_products',
            'security': ajax_object.ajax_nonce
        };

        jQuery.post(ajax_object.ajax_url, data, function (response) {
            $(".qcld_cart_prod_table_body").html(response);
            // console.log(response);
            $("#qcld_cart_prod_table").removeClass("loading");
            //console.log('Got this from the server: ' + response);
        });
    }

    function showQcldCartItem() {
        var data = {
            'action': 'remove_item_numbers_genie_cart',
            'security': ajax_object.ajax_nonce
        };

        jQuery.post(ajax_object.ajax_url, data, function (response) {
            $('.genie-cart-items').html(response);
            jQuery("[name='update_cart']").removeAttr('disabled');
            jQuery('[name="update_cart"]').trigger( 'click' );
        });
    }

    $(document.body).on('added_to_cart', function() {   
        showQcldCart();
        showQcldCartItem();
        // Update Cart on added to card
        jQuery("[name='update_cart']").removeAttr('disabled');
        jQuery('[name="update_cart"]').trigger( 'click' );
    });



    $(function () {
        $('ul.jarvis_product_list').slimScroll({});
        $('.qcld_cart_prod_table_body').slimScroll({});

    });
    function update_search_link(jarvis) {
        var current_shop_url = qc_jarvis_params.shop_url;
        var query = "";
        var product_cats = "";
        var product_tags = "";
        var product_atts = "";

        var query = "?";
        if (current_shop_url.indexOf("?") != -1) {
            query = "&";
        }

        // Product Categories
        if (jarvis.find('.jarvis-field-type-product-category').length) {
            jarvis.find('.jarvis-field-type-product-category').each(function () {
                if ($(this).find(".jarvis-select li.selected").length) {
                    if ($(this).find(".jarvis-select li:not(:first).selected a").attr("data-value")) {
                        product_cats += $(this).find(".jarvis-select li.selected a").attr("data-value") + ",";
                    }
                }
            });
            if (product_cats != "") {
                product_cats = product_cats.substring(0, product_cats.length - 1);
                query += "sa_product_cat=" + product_cats + "&";
            }
        }

        // Product Tags
        if (jarvis.find('.jarvis-field-type-product-tag').length) {
            jarvis.find('.jarvis-field-type-product-tag').each(function () {
                if ($(this).find(".jarvis-select li:not(:first).selected").length) {
                    product_tags += $(this).find(".jarvis-select li.selected a").attr("data-value") + ",";
                }
            });
            if (product_tags != "") {
                product_tags = product_tags.substring(0, product_tags.length - 1);
                query += "sa_product_tag=" + product_tags + "&";
            }
        }

        // Product Min Price
        if (jarvis.find('input[name="sa_min_price"]').length) {
            var sa_min_price = jarvis.find('input[name="sa_min_price"]').val();
            query += "sa_min_price=" + sa_min_price + "&";
        }

        // Product Max Price
        if (jarvis.find('input[name="sa_max_price"]').length) {
            var sa_max_price = jarvis.find('input[name="sa_max_price"]').val();
            query += "sa_max_price=" + sa_max_price + "&";
        }

        // Product Attributes
        if (jarvis.find('.jarvis-field-type-attribute').length) {
            jarvis.find('.jarvis-field-type-attribute').each(function () {
                if ($(this).find(".jarvis-select li:not(:first).selected").length) {
                    var attribute_name = $(this).find(".jarvis-select").attr("data-name");
                    var attribute_value = $(this).find(".jarvis-select li.selected a").attr("data-value");
                    query += attribute_name + "=" + attribute_value + "&";
                }
            });
        }

        if (( query != "?" ) && ( query != "&" )) {
            query = query.substring(0, query.length - 1);
            current_shop_url += query;
            //console.log(current_shop_url);
            window.location = current_shop_url;
        } else {
            return false;
        }

    }

    /***************** Cod by Tareq Start******************/
    //Ajax based Search start here.
    $(document).on("click", '.jarvis-find-ajax', function (event) {
        event.preventDefault();
        $(this).addClass('loading');
        var jarvis = $(this).parents('.woocommerce-jarvis:eq(0)');
        jarvis_find_ajax_forming(jarvis);
    });
    function jarvis_find_ajax_forming(jarvis) {
        var query = "";
        var product_cats = "";
        var product_tags = "";
        // var product_atts = "";

        // Product Categories
        if (jarvis.find('.jarvis-field-type-product-category').length) {
            jarvis.find('.jarvis-field-type-product-category').each(function () {
                if ($(this).find(".jarvis-select li.selected").length) {
                    if ($(this).find(".jarvis-select li:not(:first).selected a").attr("data-value")) {
                        product_cats += $(this).find(".jarvis-select li.selected a").attr("data-value") + ",";
                    }
                }
            });
            if (product_cats != "") {
                product_cats = product_cats.substring(0, product_cats.length - 1);
                query += "sa_product_cat=" + product_cats + "&";
            }
        }

        // Product Tags
        if (jarvis.find('.jarvis-field-type-product-tag').length) {
            jarvis.find('.jarvis-field-type-product-tag').each(function () {
                if ($(this).find(".jarvis-select li:not(:first).selected").length) {
                    product_tags += $(this).find(".jarvis-select li.selected a").attr("data-value") + ",";
                }
            });
            if (product_tags != "") {
                product_tags = product_tags.substring(0, product_tags.length - 1);
                query += "sa_product_tag=" + product_tags + "&";
            }
        }

        // Product Min Price
        if (jarvis.find('input[name="sa_min_price"]').length) {
            var sa_min_price = jarvis.find('input[name="sa_min_price"]').val();
            query += "sa_min_price=" + sa_min_price + "&";
        }

        // Product Max Price
        if (jarvis.find('input[name="sa_max_price"]').length) {
            var sa_max_price = jarvis.find('input[name="sa_max_price"]').val();
            query += "sa_max_price=" + sa_max_price + "&";
        }

        // Product Attributes
        if (jarvis.find('.jarvis-field-type-attribute').length) {
            jarvis.find('.jarvis-field-type-attribute').each(function () {
                if ($(this).find(".jarvis-select li:not(:first).selected").length) {
                    var attribute_name = $(this).find(".jarvis-select").attr("data-name");
                    var attribute_value = $(this).find(".jarvis-select li.selected a").attr("data-value");
                    query += attribute_name + "=" + attribute_value + "&";
                }
            });
        }

        if (query.indexOf("&") != -1) {
            //Removing White space from the query.
            query = query.replace(/ /g, '');
            var data = {
                'action': 'get_jarvis_ajax_search_products',
                //'security': ajax_object.ajax_nonce
            };
            //console.log(ajax_object.ajax_url+'?action='+data.action+'&'+query);

            jQuery.get(ajax_object.ajax_url + '?action=' + data.action + '&' + query, function (response) {
                //console.log(JSON.stringify(response));
                if (response.products_num > 0) {
                    $('.jarvis-find-ajax').removeClass('loading');
                    $("#jarvis_ajax_search_products").html('');
                    $("#jarvis_ajax_search_products").html(response.html);
                    // $(".jarvis-featured-products").html(response.html);
                } else {
                    $("#jarvis_ajax_search_products").html("<p class='ajax-search-message' style='border:1px red solid;'>Oops! Nothing matches your exact criteria. How about selecting some different options?</p>");
                }
                $('.jarvis-find-ajax').removeClass('loading');
                $("#jarvis_ajax_search_products").html('');
                $("#jarvis_ajax_search_products").html(response.html);
            });
        } else {
            return false;
        }

    }

    //Genie cart numbers and title notification.
    $(document).on('click', '.add_to_cart_button', function (event) {
        if (!$(this).hasClass('product_type_variable')) {
            event.preventDefault();
            var currentUrl = window.location.href;
            if (currentUrl.indexOf('product') != -1) {
                var product_id = $("[name='add-to-cart']").val();
                var qnty = $("[name='quantity']").val();
            } else {
                var product_id = $(this).attr('data-product_id');
                var qnty = 1;
            }
            var data = {
                'action': 'qcld_jarvis_add_to_cart',
                'product_id': product_id,
                'quantity': qnty,
                'security': ajax_object.ajax_nonce
            };
            jQuery.post(ajax_object.ajax_url, data, function (response) {
                if (response == 'simple') {
                    show_cart_item_number(product_id);
                }
            });

        }
    });
    function show_cart_item_number(product_id) {
        var data = {
            'action': 'get_cart_items_num_product_title',
            'product_id': product_id,
            'security': ajax_object.ajax_nonce
        };
        jQuery.post(ajax_object.ajax_url, data, function (response) {
            //Show message tooltip && insert title.
            $("#qcld_jarvis_tooltip_genie_cart").addClass("active");
            $("#genie-cart-added-message").html(response.title);
            //Display cart circle
            $("#genie-cart").css({'display': 'block'});
            //$('.genie-cart-items').html(response.items_number);
            showQcldCart();
            //Now hide the tooltip
            setTimeout(function () {
                $("#qcld_jarvis_tooltip_genie_cart").removeClass("active");
            }, 3000);

        });
    }

    //Removing cart item
    //$(document).on('click', '.product-remove a,.qcld-cart-item-remove', function (event) {
    $(document).on('click', '.product-remove a', function (event) {
        event.preventDefault();
        setTimeout(function () {
            var data = {
                'action': 'remove_item_numbers_genie_cart',
                'security': ajax_object.ajax_nonce
            };

            jQuery.post(ajax_object.ajax_url, data, function (response) {
                //Now hide the tooltip
                showQcldCartItem();
                $('.genie-cart-items').html(response);
            });
        }, 3000);
    });
    //remove the cart item from global cart.
    $(document).on("click", ".qcld-cart-item-remove", function () {
        qcld_jarvis_preloader.on();
        var item = $(this).attr('jarvis-cart-item');

        var data = {
            'action': 'jarvis_cart_item_remove',
            'cart_item': item,
            'security': ajax_object.ajax_nonce
        };
        jQuery.post(ajax_object.ajax_url, data, function (response) {
            showQcldCart();
            showQcldCartItem();
            qcld_jarvis_preloader.off();
        });
    });
    /***************** Cod by Tareq End******************/

    //Fade in button
    jQuery(".woocommerce-jarvis").hover(
        function (event) {
            var this_jarvis = jQuery(this);
            var find_button = jQuery(this).find(".jarvis-find");

            if (!this_jarvis.hasClass('jarvis-has-activated')) {

                // Animate the find button once
                find_button.stop(true, true).animate({
                    top: "-8px",
                    opacity: 0,
                }, 100, 'easeInOutQuad').animate({top: "8px", opacity: 0}, 1).delay(100).animate({
                    top: "0px",
                    opacity: 1
                }, 100, 'easeInOutQuad');

                this_jarvis.addClass('jarvis-has-activated');
            }
            event.preventDefault();
        },
        function (event) {
            event.preventDefault();
        }
    );


    //Set Width's of DropDowns
    jQuery(".jarvis-field ul").each(function () {
        jQuery(this).css({marginLeft: -jQuery(this).width() / 2});
    });


    //Easing Functions
    jQuery.easing.easeInQuad = function (x, t, b, c, d) {
        return c * (t /= d) * t + b;
    };
    jQuery.easing.easeOutQuad = function (x, t, b, c, d) {
        return -c * (t /= d) * (t - 2) + b;
    };
    jQuery.easing.easeInOutQuad = function (x, t, b, c, d) {
        if ((t /= d / 2) < 1) return c / 2 * t * t + b;
        return -c / 2 * ((--t) * (t - 2) - 1) + b;
    };


    //DropDown Open
    jQuery(document).on('click', '.jarvis-field > a', function (event) {
        event.preventDefault();
        var ullist = jQuery(this).parent().children('ul:first');

        ullist.slideDown(500, 'easeInOutQuad').animate({top: "-20px"}, {
            queue: false,
            duration: 500,
            easing: 'easeInOutQuad'
        });

        jarvis_close_fields(jQuery(".jarvis-field").not(jQuery(event.target).parents(".jarvis-field")));

        event.stopPropagation();
    });

    //DropDown Select
    jQuery(document).on('click', '.jarvis-field li >a', function (event) {
        event.preventDefault();

        var current_jarvis = jQuery(this).parents('.woocommerce-jarvis');
        var current_field = jQuery(this).parents('.jarvis-field');
        var current_field_display_text = current_field.find('a:first');
        var clicked_element = jQuery(this).parent('li');
        var other_elements = jQuery(this).parents("ul").find('li').not(clicked_element);
        var reset_element = current_jarvis.find(".jarvis-reset");

        //Set the class of the clicked element
        clicked_element.addClass("selected");
        other_elements.removeClass("selected");

        current_field.attr('data-new-value', clicked_element.find('a').html());

        //Do the lock in animation
        current_field_display_text.delay(250).animate({top: "-10px", opacity: 0}, 150).animate({
            top: "10px",
            opacity: 0
        }, 1, function () {

            //Set the value of the top-most visible text
            current_field_display_text.html(current_field.attr('data-new-value'));

            //Set the value of the dropdown
            current_value = clicked_element.find('a').attr("data-value");
            if (current_value === "" || current_value == "any" || current_value === undefined)
                current_field.find('select').val("");
            else
                current_field.find('select').val(current_value);

        }).animate({top: "0px", opacity: 1}, 150);

        //Close all fields on select of something
        jarvis_close_fields(jQuery('.jarvis-field'));

        //switch on the reset button
        current_jarvis.addClass("jarvis-active");

        event.stopPropagation();
    });

    //Close all on click outside
    jQuery(document).click(function (event) {
        //Close the feilds of all the jarviss except the current one
        jarvis_close_fields(jQuery(".jarvis-field").not(jQuery(event.target).parents(".jarvis-field")));
    });

    //Function to close all
    function jarvis_close_fields(elements) {
        elements.each(function () {
            jQuery(this).find('ul').slideUp(300, 'easeInOutQuad').animate({top: "0"}, {
                queue: false,
                duration: 300,
                easing: 'easeInOutQuad'
            });
        });
    }

    //Clear Button Click
    jQuery('.woocommerce-jarvis .jarvis-reset').on('click', function (event) {
        //For Ajax search result by tareq remove products
        $("#jarvis_ajax_search_products").html("");
        //For Ajax search result by tareq remove products

        var current_jarvis = jQuery(this).parents('.woocommerce-jarvis');
        var reset_element = current_jarvis.find("jarvis-reset");

        current_jarvis.find('.jarvis-field').each(function () {

            if (jQuery(this).hasClass('jarvis-field-select')) {

                if (jQuery(this).find('ul li.original').length) {
                    jQuery(this).find('ul li.original').find('a').click();
                }
                else {
                    jQuery(this).find('ul li').first().find('a').click();
                    jQuery(this).attr("data-new-value", jQuery(this).attr('data-original-value'));
                }
            }
            else if (jQuery(this).hasClass('jarvis-field-input')) {

                jQuery(this).find('input').each(function () {

                    reset_value = jQuery(this).attr('data-original-value');
                    jQuery(this).val(reset_value);

                });

            }

        });

        //switch on the reset button
        current_jarvis.removeClass("jarvis-active");

        event.stopPropagation();
    });


    /*$(".qcld_woojarvis .add_to_cart_button").each(function(index, element) {
     var prodlink = $(this).parent().find(".woocommerce-LoopProduct-link").attr('href');
     $(this).attr("href",prodlink);
     $(this).removeClass("ajax_add_to_cart");
     $(this).text("View Detail");

     });*/

    $("#genie-lamp,#genie-cart").animatedModal({
        modalTarget: 'genie-target',
        animatedIn: qc_jarvis_params.jarvis_pop_up_form_effect,
        animatedOut: 'bounceOutDown',
        color: '#eef5f9',
        // Callbacks
        beforeOpen: function () {
            $(".woocommerce-jarvis .jarvis_title_init").hide();
            $(".jarvis_title_msg_typed, .typed-cursor").remove();
            var titleFontSize = $(".woocommerce-jarvis .jarvis_title_init").attr('data-fontsize');
            $(".woocommerce-jarvis .jarvis_title_init").after('<h2 class="jarvis_title_msg_typed jarvis_title" style="font-size:' + titleFontSize + 'px"></h2>');
            $(".jarvis_title_msg_typed").typed({
                stringsElement: $('.woocommerce-jarvis .jarvis_title_init'),
                typeSpeed: 30,
                backDelay: 500,
                loop: true,
                contentType: 'text', // or text
                // defaults to false for infinite loop
                loopCount: 1,
                //callback: function(){ foo(); },
                resetCallback: function () {
                    newTyped();
                }
            });
        },
        afterOpen: function () {
            // console.log("The animation is completed");
        },
        beforeClose: function () {
            // console.log("The animation was called");
        },
        afterClose: function () {
            //console.log("The animation is completed");
        }
    });

    $("#genie-lamp").hover(function () {
        $("#qcld_jarvis_msg li").removeClass("active");
        $("#qcld_jarvis_msg li:first-child").addClass("active");
        $("#qcld_jarvis_tooltip").addClass("active");
    }, function () {
        $("#qcld_jarvis_tooltip").removeClass("active");
        //hideJarvisMessage();

    });


    //setInterval(function(){ alert("Hello"); }, 3000);

    /**######### Message display and display start ###############*/
    var msgTotal = $("#qcld_jarvis_msg > li.jarvisMsgItem").size() ? $("#qcld_jarvis_msg > li.jarvisMsgItem").size() : 0;
    var globalTimer = $("#qcld_jarvis_msg").attr("data-global-timer");
    var msgLoop = $("#qcld_jarvis_tooltip").attr("data-loop");

    if (globalTimer < 1) {
        globalTimer = 8;
    }

    var runningMsg = 0;
    //Handling notification message delay and display start
    setTimeout(function () {
        var firstMsGTime = $("#qcld_jarvis_msg > li.jarvisMsgItem").first().attr('data-timer');
        message_interval();
    }, 2000);


    function message_interval() {
        runningMsg++;
        // console.log("message display ");

        var activeMsgFromCookey = parseInt($.cookie('activeMsg'));

        if (msgLoop == 0 && activeMsgFromCookey == (msgTotal)) {
            //clearInterval(showMessages)
            $("#qcld_jarvis_tooltip").removeClass("active");

            //$("#qcld_jarvis_msg > li.jarvisMsgItem").removeClass("active");
            //$("#qcld_jarvis_msg > li.jarvisMsgItem").eq(1).addClass("active");
        } else {
            message_display();
        }
    }

    function message_display() {
        var activeMsgFromCookey = parseInt($.cookie('activeMsg'));
        //activeMsgFromCookey = parseInt(activeMsgFromCookey+1);
        var activeIndex, nextIndex;

        var msgTotal = $("#qcld_jarvis_msg > li.jarvisMsgItem").size() ? $("#qcld_jarvis_msg > li.jarvisMsgItem").size() :0;
        var globalTimer = $("#qcld_jarvis_msg").attr("data-global-timer");
        var msgLoop = $("#qcld_jarvis_tooltip").attr("data-loop");
        $("#qcld_jarvis_msg > li.jarvisMsgItem").eq(activeMsgFromCookey).addClass("active");
        //$(".qcld_jarvis_tooltip").addClass("active");

        if(isNaN(activeMsgFromCookey)){
            $(".qcld_jarvis_tooltip").addClass("active", globalTimer * 1000);
        }else{
            $(".qcld_jarvis_tooltip").addClass("active",500);
        }

        activeIndex = $("#qcld_jarvis_msg > li.jarvisMsgItem.active").index("#qcld_jarvis_msg > li");
        var displayTimer = $("#qcld_jarvis_msg > li.jarvisMsgItem.active").attr('data-timer');
        if (activeIndex == msgTotal - 1 && msgLoop == 1) {
            nextIndex = 0;
        } else {

            nextIndex = parseInt(activeIndex + 1);
        }


        if (msgLoop == 0 && activeMsgFromCookey == msgTotal) {
            //console.log("all complete");
            nextIndex = msgTotal;
            $("#qcld_jarvis_tooltip").removeClass("active");
            return false;
        }

        //Display till the data-timer
        setTimeout(function () {
            //console.log("message hide ");
            $(".qcld_jarvis_tooltip").removeClass("active");
            $("#qcld_jarvis_msg > li.jarvisMsgItem").removeClass("active");
            $("#qcld_jarvis_msg > li.jarvisMsgItem").eq(nextIndex).addClass("active");

            $.cookie('activeMsg', nextIndex, {path: '/'});
            //Delay as global timer
            setTimeout(function () {
                message_interval();
            }, globalTimer * 1000);

        }, displayTimer * 1000);
    }

    //Handling notification message delay and display end
    //Pinball effect start here
    $(document).on('click', '.jarvis-ball-style', function (event) {
        if ($(this).attr('data-ball-style') != 5) {
            $('.jarvis-ball-style').removeClass('active');
            $(this).addClass('active');
            var ballStyle = $(this).attr('data-ball-style');
            $("#jarvis-pinball-box").show();
            $(".jarvis-ball-inner").html('<div class="jarvis-tab-loader"><img src="' + ajax_object.image_path + 'preloader.gif" style="" /></div>');
            var data = {
                'action': 'pin_ball_mode',
                'style': ballStyle,
                'security': ajax_object.ajax_nonce
            };

            jQuery.post(ajax_object.ajax_url, data, function (response) {
                $("#jarvis-pinball-box").removeClass(qc_jarvis_params.jarvis_pop_up_form_effect);
                $("#jarvis-pinball-box").addClass(qc_jarvis_params.jarvis_pop_up_form_effect);
                //Now show the coresponding output
                $(".jarvis-ball-inner").html(response);
                $(".jarvis-ball-inner .animated").addClass(qc_jarvis_params.jarvis_pop_up_form_effect);

                if (ballStyle == 4) {
                    $(".jarvis-ball-inner").append('<div id="jarvis_ajax_search_products"></div>');
                    $('.jarvis-ball-inner').slimScroll({height: '60hv'});

                } else if (ballStyle == 3) {
                    $('.qcld_cart_prod_table_body').slimScroll({});
                } else if (ballStyle == 2) {
                    $('.jarvis-ball-inner').slimScroll({height: '60hv'});
                } else if (ballStyle == 1) {
                    $('ul.jarvis_product_list').slimScroll({});
                } else if (ballStyle == 6) {
                    jarvisCaptchaCode();
                }
                //
            });
        }
    });
    //Hide the pinball box if click on outsite
    $(document).on('click', function (e) {
        var container = $("#jarvis-pinball-box");
        var rejectContainer = $(".jarvis-ball-style");
        if (!rejectContainer.is(e.target) && rejectContainer.has(e.target).length === 0) {
            if (!container.is(e.target) && container.has(e.target).length === 0) {
                $("#jarvis-pinball-box").removeClass(qc_jarvis_params.jarvis_pop_up_form_effect);
                if ($('.jarvis-support-form-container').length == 0) {
                    container.fadeOut(500);
                }

            }
        }
    });
    //Support form
    $(document).on('click', '#jarvis-support-form-submit', function (event) {
        $("#jarvis-support-form-validation").html("")
        var validate = "";
        var fullName = $('#jarvis_support_fullname').val();
        var email = $('#jarvis_support_email').val();
        var message = $('#jarvis_support_Message').val();
        var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
        var captcha = $("#javis-captcha").val();
        var captchaVal = $("#javis-captcha-val").val();

        if (fullName == '') {
            validate += "<p>Full Name is required. </p>";
        }
        if (email == '') {
            validate += "<p>Email is required. </p>";
        }
        if (email != '' && re.test(email) != true) {
            validate += "<p>Sorry, Email address is not valid! please give a valid email. </p>";
        }

        if (captcha == '') {
            validate += "<p>Captcha code is required. </p>";
        }
        if (captcha != '' && captcha != captchaVal) {
            validate += "<p>Sorry, Captcha code does not match </p>";
        }
        if (validate == "") {
            var data = {
                'action': 'pin_ball_support',
                'name': fullName,
                'email': email,
                'message': message,
                'security': ajax_object.ajax_nonce
            };

            jQuery.post(ajax_object.ajax_url, data, function (response) {
                var res = JSON.parse(response);
                if (res.status == 'success') {
                    $(".jarvis-support-form-container").html(res.message);
                }
                if (res.status == 'fail') {
                    $("#jarvis-support-form-validation").html(res.message);
                }
            });

        } else {
            $("#jarvis-support-form-validation").html(validate);
        }
    });
    /****
     * reCaptcha Start
     */
    function jarvisCaptchaCode() {
        var Numb1, Numb2, Numb3, Numb4, Code;
        Numb1 = (Math.ceil(Math.random() * 10) - 1).toString();
        Numb2 = (Math.ceil(Math.random() * 10) - 1).toString();
        Numb3 = (Math.ceil(Math.random() * 10) - 1).toString();
        Numb4 = (Math.ceil(Math.random() * 10) - 1).toString();

        Code = Numb1 + Numb2 + Numb3 + Numb4;
        $("#jarvis-captcha span").remove();
        $("#jarvis-captcha button").remove();
        $("#jarvis-captcha").append("<span id='jarvis-captcha-code'>" + Code + "</span><button type='button' id='javis-captcha-refresh'>Refresh</button><input type='hidden' id='javis-captcha-val' value='" + Code + "'>");
    }

    $(document).on('click', '#javis-captcha-refresh', function (event) {
        event.preventDefault();
        jarvisCaptchaCode();
    });
    /*
     //Draggable features
     $(document).ready(function () {
     $("#jarvis-ball").draggable(
     {
     drag: function(event){
     var rightP=parseInt($(window).width() - event.pageX);
     var bottomP=parseInt($(window).height() - event.pageY);
     // console.log("right: " + ($(window).width() - event.pageX) + ", bottom: " + (event.pageY/2)+'height :'+$(window).height() );
     //var rightP=($(window).width()-leftP)
     // var bottomP=($(window).height()-topP)
     // console.log('right : '+rightP+' bottom: '+bottomP);
     $("#jarvis-icon-container").removeAttr("style");
     $("#jarvis-icon-container").css({
     'right': rightP + 'px',
     'bottom': bottomP+ 'px'
     });
     }
     }
     );
     })*/
    //Pinball effect end here


});