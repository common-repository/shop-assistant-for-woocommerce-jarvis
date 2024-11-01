jQuery(function ($) {
    


    $(document).ready(function () {
        $(".jarvis_mode option").each(function () {
            $(this).siblings("[value='" + this.value + "']").remove();
        });

        [].slice.call(document.querySelectorAll('.tabs-jarvis')).forEach(function (el) {
            new CBPFWTabs(el);
        });
        //Support Quickball on Jarvis
        if($('#support_quickball').is(':checked')){
            $('#support_quickball_email_contianer').show();
        }else{
            $('#support_quickball_email_contianer').hide();
        }

        $(document).on('change','#support_quickball',function () {
            if($(this).is(':checked')){
                $('#support_quickball_email_contianer').show();
            }else{
                $('#support_quickball_email_contianer').hide();
            }
        })

        //Custom quickball for jarvis.
        if($('#custom_quickball').is(':checked')){
            $('#custom_quickball_link_contianer').show();
            $('.custom_quickball_icon_contianer').show();
        }else{
            $('#custom_quickball_link_contianer').hide();
            $('.custom_quickball_icon_contianer').hide();
        }

        $(document).on('change','#custom_quickball',function () {
            if($(this).is(':checked')){
                $('#custom_quickball_link_contianer').show();
                $('.custom_quickball_icon_contianer').show();
            }else{
                $('#custom_quickball_link_contianer').hide();
                $('.custom_quickball_icon_contianer').hide();
            }
        })


        $(".jarvis_select_two").select2({width: '100%', height: '500px', dropdownCssClass: "bigdrop"});

        if ($(".jarvis_sentence_builder").length) {

            $(".jarvis_sentence_builder .repeatable").repeatable({
                addTrigger: ".jarvis_sentence_builder .add",
                deleteTrigger: ".jarvis_sentence_builder .delete",
                template: "#jarvis_sentence_builder",
                startWith: 1,
                onDelete: function () {
                    build_search_phrase();
                }
            });

            $(".jarvis_sentence_builder").on("change", ".qc-jarvis-filter", function () {
                if ($(this).val() == "price") {
                    $(this).parents(".field-group:eq(0)").find(".price-set").removeClass('qc-jarvis-hide');
                    $(this).parents(".field-group:eq(0)").find(".label-set").addClass('qc-jarvis-hide');
                } else {
                    $(this).parents(".field-group:eq(0)").find(".price-set").addClass('qc-jarvis-hide');
                    $(this).parents(".field-group:eq(0)").find(".label-set").removeClass('qc-jarvis-hide');
                }
            });

            $(".jarvis_sentence_builder .repeatable").sortable({
                items: "> div.field-group", cursor: "move", stop: function (event, ui) {
                    build_search_phrase();
                }
            });
            $(".jarvis_sentence_builder .repeatable");

        }


        // Setup the tips
        jQuery(".tips, .help_tip").tipTip({
            attribute: "data-tip",
            fadeIn: 50,
            fadeOut: 50,
            delay: 200,
            defaultPosition: "top"
        });

        build_search_phrase();

        $(".jarvis_sentence_builder .repeatable").on("keyup", ".field-group .qc-jarvis-text", build_search_phrase);
        $(".jarvis_sentence_builder .repeatable").on("change", ".field-group .qc-jarvis-filter", build_search_phrase);
        $(".jarvis_sentence_builder .repeatable").on("keyup", ".field-group .jarvis-priceone", build_search_phrase);
        $(".jarvis_sentence_builder .repeatable").on("keyup", ".field-group .jarvis-pricetwo", build_search_phrase);
        $(".jarvis_sentence_builder .repeatable").on("keyup", ".field-group .qc-jarvis-label", build_search_phrase);
        $(".shop-jarvis-button-text").on("keyup", build_search_phrase);

    });

    function build_search_phrase() {
        var output = "";

        $(".jarvis_sentence_builder .repeatable .field-group").each(function () {

            var text = $(this).find(".qc-jarvis-text").val();

            if (text != "") {
                output += text + " ";
            }

            var filter = $(this).find(".qc-jarvis-filter").val();


            if (filter != "") {

                var priceone = $(this).find(".jarvis-priceone").val();
                var pricetwo = $(this).find(".jarvis-pricetwo").val();
                var label = $(this).find(".qc-jarvis-label").val();

                if (filter == "price") {

                    output += '<span class="phrase-example-filter">' + priceone + '</span> & <span class="phrase-example-filter">' + pricetwo + '</span> ';

                } else {

                    output += '<span class="phrase-example-filter">' + label + "</span> ";

                }

            }

        });

        if (output != "") {
            output = output.substring(0, output.length - 1);

            var button_text = $(".jarvis-search-button-text").val();

            if (button_text != "") {
                output += '. <span class="phrase-example-button">' + button_text + '</span>';
            } else {
                output += '. <span class="phrase-example-button"></span>';
            }

            $(".search-phrase").html(output);
            $(".search-phrase").show();
            $(".pre-search-phrase").hide();
        } else {
            $(".pre-search-phrase").show();
            $(".search-phrase").hide();
        }

    }
    //Dynamic Order Notification settings
    $("#add-more-block-inner").on('click',function () {
        //var firsBlock=$(".block-section").find('.block-inner').first().clone(true);
        //var firsBlock=$(".block-section").find('.block-inner').first().html();
        //$(".block-section").append(firsBlock);

        var data = {
            'action': 'get_admin_order_notification_item',
            'security': ajax_object.ajax_nonce
        };
        jQuery.post(ajax_object.ajax_url, data, function (response) {
            $(".block-section").append(response);
            $('.qcld-remove-on-item').on('click',function () {
               if(confirm('Are you sure to delete order notification block?')){
                   $(this).parent().parent().remove();
               }


            });
        });
    })
    //Removing item with out ajax loading
    $('.qcld-remove-on-item').on('click',function () {
        if(confirm('Are you sure to delete order notification block?')){
            $(this).parent().parent().remove();
        }
    });
    //Save into hidden field all notification data.
    $('#save-order-notification').on('click',function () {
       var inner_blocks=$('.block-inner'), single;
        var inner_blocks_vals=[];
        $.each(inner_blocks, function(i, obj) {
            single = {
                'product_id': $(obj).find('select').val(),
                'customer_name': $(obj).find('.customer-name').val(),
                'customer_address': $(obj).find('.customer-address').val(),
                'notification_duration': $(obj).find('.notification-duration').val(),
            };

            inner_blocks_vals.push(single);
        });
        $("#artificial-orders-val").val(JSON.stringify(inner_blocks_vals));
        if(inner_blocks_vals.length>0){
            alert('Order Notificaion has been saved');
        }else{
            alert('No Order details');
        }
    });
    //Reset to defualt all options
    $('#jarvis-reset-options-default').on('click',function () {
        var $this   = $(this);
        var returnDefualt = confirm("Are you sure you want to reset all options to Default? Resetting Will Delete All Saved Settings, Custom Messages, Languages etc.");
        if (returnDefualt == true) {

            $this.addClass('spinning');
            $this.prop("disabled", true);

            var data = {
                'action': 'jarvis_delete_all_options_for_uninstall',
                'security': ajax_object.ajax_nonce
            };
            jQuery.post(ajax_object.ajax_url, data, function (response) {
                alert(response);
                $this.prop("disabled", false);
                $this.removeClass('spinning');
                window.location.reload();
            });
        }
    });

});



