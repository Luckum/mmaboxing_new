$nc(function() {

    // default values and 'constants'
    var path = window.location.pathname.replace(/[^/]+$/, ''),
        getUserFilterResultsUrl = path + "filter/userfilter_json.php",
        getUserFilterListUrl = path + "filter/userfilter_html.php",
        previewMessageUrl = path + "coupon/preview_message.php",
        generateBatchUrl = path + "coupon/generate_batch.php",
        checkCodeUrl = "coupon/check.php",
        maxCodeLengthWithPrefix = 64,
        defaultLength = 10;

    var form = $nc("#nc_netshop_promotion_coupons_generate_form"),
        catalogueId = form.find("input[name=catalogue_id]").val(),
        generationBatchSize = parseInt(form.find("input[name=batch_size]").val(), 10),

        multipleCodeOptions = form.find(".multiple-codes"),
        singleCodeOptions = form.find(".single-code"),
        codeOptions = form.find(".code-options"),
        mailOptions = form.find(".mail-options"),

        numCodesInput = form.find("input[name='num_codes']"),
        prefixInput = form.find("input[name='coupon_options[code_prefix]']"),
        symbolsInput = form.find("input[name='coupon_options[code_symbols]']"),
        lengthSelect = form.find("select[name='coupon_options[code_length]']"),

        sendToUsersCheckbox = form.find("input[name='send_to_users']"),
        userEmailFieldSelect = form.find("select[name='mail_options[user_email_field]']"),
        userIdsInput = form.find("input[name='user_ids']"),
        filterResultsContainer = form.find(".filter-results"),
        numSelectedUsersSpan = form.find(".filter-results-count");

        // --- Handlers for changing number of codes and prefix ---

        // Set minimum possible length of the variable (generated) part of the code
        // depending on the desired number of codes and number of symbols:
    var setPossibleLengths = function() {
            var numCodes = Number(numCodesInput.val()),
                numSymbols = symbolsInput.val().length,
                minLength = 1,
                maxLength = maxCodeLengthWithPrefix - prefixInput.val().length,
                i;

            for (i = minLength; i <= maxLength; i++) { // suboptimal...
                if (Math.pow(numSymbols, i) > numCodes) {
                    minLength = i;
                    break;
                }
            }

            var previousLengthSelectState = lengthSelect.data('minAndMaxLength'),
                newState = minLength + ".." + maxLength;
            if (previousLengthSelectState != newState) {
                var options = [],
                    selectValue = lengthSelect.val();
                if (!previousLengthSelectState) {
                    selectValue = lengthSelect.data('selected') || defaultLength;
                }
                else if (Number(selectValue) < minLength) {
                    selectValue = minLength;
                }
                else if (Number(selectValue) > maxLength) {
                    selectValue = maxLength;
                }

                for (i = minLength; i <= maxLength; i++) {
                    options.push('<option value="' + i + '">' + i + "</option>");
                }
                lengthSelect.html(options.join('')).val(selectValue);
                lengthSelect.data('minAndMaxLength', newState);
            }
        },

        // show or hide multiple code options
        numCodesChangeHandler = function() {
            var numCodes = Number(numCodesInput.val());
            codeOptions.show();
            if (numCodes < 1 || isNaN(numCodes)) {
                multipleCodeOptions.hide();
                singleCodeOptions.hide();
                codeOptions.hide();
            }
            else if (numCodes == 1) {
                multipleCodeOptions.hide();
                singleCodeOptions.show();
            }
            else {
                singleCodeOptions.hide();
                multipleCodeOptions.show();
                setPossibleLengths();
            }
        };

    // assign event handlers
    numCodesInput.change(numCodesChangeHandler)
                 .keyup(numCodesChangeHandler)
                 .change();

    symbolsInput.change(setPossibleLengths).keyup(setPossibleLengths);
    prefixInput.change(setPossibleLengths).keyup(setPossibleLengths);

    // --- Handler for 'expires' input change ---
    var validTillInput = form.find("input[name='coupon_options[valid_till]']").hide();
    form.find("select[name='coupon_options[expires]']").change(function() {
        if ($nc(this).val() == 1) { validTillInput.show(); }
                             else { validTillInput.hide(); }
    }).change();

    // --- Check single code ---
    var statusContainer = form.find(".single-code .code-status"),
        singleCodeInput = form.find("input[name='coupon_options[code]']"),
        hideAllCodeStatuses = function() { statusContainer.children().hide(); },

        checkRequest = null,
        checkCodeResultCache = {},
        setCheckStatus = function(result) {
            hideAllCodeStatuses();
            if (result.is_ok) {
                statusContainer.find(".code-status-ok").show();
            }
            else {
                statusContainer.find(".code-status-error")
                    .show()
                    .find(".code-error-message").html(result.error_message);
            }
            checkCodeResultCache[result.code] = result;
        },

        checkCode = function() {
            var couponCode = singleCodeInput.val();
            if (couponCode.length == 0) {
                hideAllCodeStatuses();
            }
            else {
                if (checkCodeResultCache[couponCode]) {
                    setCheckStatus(checkCodeResultCache[couponCode]);
                }
                else {
                    // if there’s already a request for that code in progress, do nothing
                    if (checkRequest === null || (checkRequest !== null && checkRequest._couponCode != couponCode)) {
                        if (checkRequest !== null) { checkRequest.abort(); }
                        checkRequest = $nc.getJSON(checkCodeUrl, { 
                            code: couponCode,
                            catalogue_id: catalogueId
                        }, setCheckStatus);
                        checkRequest._couponCode = couponCode;
                    }
                }
            }
        };

    // --- init condition editor ---
    // condition editor cannot be initialized while it’s container is hidden
    // because 'chosen' selects won’t initialize properly...
    var conditionEditor,
        showConditionEditor = function() {
            if (!conditionEditor) {
                // init the condition editor
                conditionEditor = new nc_netshop_condition_editor(
                    "#nc_netshop_promotion_coupons_form_user_conditions",
                    'user_conditions',
                    form.find("input[name=submitted_user_conditions]").val() || null,
                    catalogueId,
                    ['GROUP_USER', 'GROUP_ORDERS', 'GROUP_EXTENSION']
                );
                getUserFilterResults();
            }
            numCodesInput.val(numSelectedUsersSpan.html());
            numCodesChangeHandler();
         };

    // --- "Send to users" checkbox ---
    var toggleUserFilter = function() {
            var numCodesRow = form.find(".num-codes-row"),
                userFilterRow = form.find(".user-filter-row");

            if (sendToUsersCheckbox.prop('checked')) {
                numCodesRow.hide();
                userFilterRow.show();
                mailOptions.show();
                showConditionEditor();
            }
            else {
                userFilterRow.hide();
                mailOptions.hide();
                numCodesRow.show();
            }
        },

        userFilterRequest = null,
        setUserFilterResults = function(result) {
            numSelectedUsersSpan.html(result.count);
            numCodesInput.val(result.count);
            numCodesChangeHandler();
            userIdsInput.val(result.user_ids);
            updateFilterStatus('.loaded');
        },
        updateFilterStatus = function(classToShow) {
            filterResultsContainer.children().hide().filter(classToShow).show();
        },
        getUserFilterResults = function(event) {
            event && event.preventDefault();
            updateFilterStatus('.loading');
            var conditions = conditionEditor.save();
            if (userFilterRequest) { userFilterRequest.abort(); }
            userFilterRequest = $nc.getJSON(getUserFilterResultsUrl, {
                conditions: conditions ? JSON.stringify(conditions) : '',
                catalogue_id: catalogueId,
                user_email_field: userEmailFieldSelect.val()
            }, setUserFilterResults);
        },

        userFilterResultDialogId = "nc_netshop_coupon_user_filter_result_dialog",
        // set the correct height for the dialog content
        resizeDialogContent = function(dialogContainer) {
            $nc(dialogContainer).find(".nc_admin_form_body > .content")
                .height(1)
                .height(dialogContainer.height())
                .css('overflow-y', 'scroll');
        },
        // show dialog with the info about selected users
        showUserFilterResultDialog = function(event) {
            event && event.preventDefault();
            var $top = window.top.$nc,
                dialog = $top($nc('#' + userFilterResultDialogId + "_template").clone())
                             .attr('id', userFilterResultDialogId)
                             .data("onResize", resizeDialogContent);

            dialog.find(".nc_admin_metro_button_cancel").click(function() {
                $top.modal.close();
                $top('#' + userFilterResultDialogId).remove(); // sic, no closure for dialog
            });

            dialog.find(".nc_admin_form_body > .content")
                  .load(getUserFilterListUrl, {
                      conditions: form.find("input[name=user_conditions]").val(),
                      catalogue_id: catalogueId,
                      user_email_field: userEmailFieldSelect.val()
                  });

            $top('body').append(dialog);
            $top.modal(dialog);

            window.top.nc_register_modal_resize_handler();
            $top(window.top).resize(); // init the content div height
        };

    sendToUsersCheckbox.change(toggleUserFilter);
    form.find(".apply-filter-button").click(getUserFilterResults);
    numSelectedUsersSpan.click(showUserFilterResultDialog);

    // mailer template editor
    nc_netshop_mailer_template_editor('nc_netshop_promotion_coupons_generate_form_mail_body');

    // preview message button
    form.find(".preview-message-button").click(function(event) {
        nc_netshop_mailer_template_open_preview(form, previewMessageUrl);
        event.preventDefault();
        return false;
    });

    // assign event handlers and initial state
    hideAllCodeStatuses();
    checkCode();
    singleCodeInput.keyup(checkCode);

    // --- BATCH COUPON GENERATION ---
    var batchId = 0,
        batchGenerationModalId = "nc_netshop_coupon_batch_generation_modal",
        showBatchGenerationModal = function() {
            var $top = window.top.$nc,
                modal = $top($nc('#' + batchGenerationModalId + "_template").clone())
                             .attr('id', batchGenerationModalId);
            $top('body').append(modal);
            $top.modal(modal, {
                close: false,
                escClose: false,
                containerCss: { width: '550px' },
                onClose: function() {
                    $top.modal.close();
                    $top('#' + batchGenerationModalId).remove();
                }
            });
            $top('#simplemodal-container').addClass("simplemodal-container-fixed-size");
        },

        calculateProgressPercent = function(a, b) {
            return Math.min(100, Math.ceil(a / b * 100));
        },

        makeBatchGenerationRequest = function(data) {
            $nc.ajax({
                method: 'post',
                url: generateBatchUrl,
                data: data,
                dataType: 'json',
                success: processBatchGenerationResponse,
                error: processBatchGenerationError
            });
        },

        requestNextBatchGeneration = function() {
            makeBatchGenerationRequest({ batch_id: batchId, batch_size: generationBatchSize });
        },

        processBatchGenerationResponse = function(response) {
            var $top = window.top.$nc,
                dialog = $top('#simplemodal-container');

            if (!response.success) {
                dialog.find('.error').show().find('.message').html(response.error_message);
                return;
            }
            batchId = response.batch_id; // declared in the parent scope

            var percent, stepDiv;

            if (response.current_step == 'generate_codes') {
                percent = calculateProgressPercent(response.num_codes_generated, response.num_codes_total);
                stepDiv = dialog.find(".step1");
            }
            else if (response.current_step == 'send_codes') {
                percent = calculateProgressPercent(response.num_codes_sent, response.num_codes_total);
                stepDiv = dialog.find(".step2");
            }

            if (stepDiv) {
                stepDiv.show().find('.nc-progress-bar').width(percent + "%");
                if (percent == 100) {
                    stepDiv.find('.nc-progress').hide();
                    stepDiv.find('.done').show();
                }
            }

            if (response.finished) {
                $top.modal.close();
                var redirect_url = form.find("input[name=redirect_url]").val();
                if (redirect_url) {
                    window.location = redirect_url;
                }
                else {
                    top.window.location.hash = "#module.netshop.promotion.coupon(" +
                        form.find("input[name=deal_type]").val() + "," +
                        form.find("input[name=deal_id]").val() + ")";
                }
            }
            else {
                requestNextBatchGeneration();
            }
        },

        processBatchGenerationError = function(error) {
            window.top.$nc('#simplemodal-container .error').show().find('.message').html(error.responseText);
        },

        startBatchGeneration = function() {
            showBatchGenerationModal();
            makeBatchGenerationRequest(form.serializeArray());
        };

    form[0].onsubmit = function() { // this handler has to be attached this way
        if (conditionEditor) { conditionEditor.save(); }

        // decide whether batch generation is required
        var maxBatchSize = generationBatchSize;
        if (sendToUsersCheckbox.prop('checked')) { maxBatchSize /= 2; }
        if (parseInt(numCodesInput.val(), 10) > maxBatchSize) {
            startBatchGeneration();
            return false;
        }
        return true;
    };

    // --- READY ---
    form.show();

    // this might call showUserFilter() which in turn will initialize 'chosen'
    // selects and that requires the select container to be visible
    toggleUserFilter();

});