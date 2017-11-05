<style type="text/css">
        /* http://meyerweb.com/eric/tools/css/reset/
           v2.0 | 20110126
           License: none (public domain)
        */

    html, body, div, span, applet, object, iframe,
    h1, h2, h3, h4, h5, h6, p, blockquote, pre,
    a, abbr, acronym, address, big, cite, code,
    del, dfn, em, img, ins, kbd, q, s, samp,
    small, strike, strong, sub, sup, tt, var,
    b, u, i, center,
    dl, dt, dd, ol, ul, li,
    fieldset, form, label, legend,
    table, caption, tbody, tfoot, thead, tr, th, td,
    article, aside, canvas, details, embed,
    figure, figcaption, footer, header, hgroup,
    menu, nav, output, ruby, section, summary,
    time, mark, audio, video { margin: 0; padding: 0; border: 0; font-size: 100%; font: inherit; vertical-align: baseline; }
    article, aside, details, figcaption, figure,
    footer, header, hgroup, menu, nav, section { display: block; }
    body { line-height: 1; }
    ol, ul { list-style: none; }
    table { border-collapse: collapse; border-spacing: 0; }

    BODY { font: italic 18px "Times New Roman"; }
    IMG.form1 { position: absolute; top: 0; left: 0; }
    IMG.form2 { position: absolute; top: 1414px; left: 0; }
    .field { position: absolute; overflow: visible; }
    .field.cash-on-delivery-int { left: 740px; top: 345px; width: 64px; text-align: center; }
    .field.cash-on-delivery-dec { left: 830px; top: 345px; width: 27px; text-align: center; }
    .field.cash-on-delivery-text { left: 400px; top: 370px; height: 20px; width: 490px; font-size: 14px; text-align: center; }
    .field.from-fullname { left: 435px; top: 400px; width: 450px; }
    .field.from-address-line1 { left: 430px; top: 445px; width: 460px; }
    .field.from-address-line2 { left: 400px; top: 465px; width: 490px; }
    .field.from-zipcode { left: 815px; top: 484px; font-size: 18px; letter-spacing: 3px; }
    .field.from-inn { left: 433px; top: 525px; font-size: 16px; letter-spacing: 3px; font-family: monospace; }
    .field.from-corr { left: 643px; top: 525px; font-size: 17px; letter-spacing: 2px; font-family: monospace; }
    .field.from-account { left: 467px; top: 567px; font-size: 17px; letter-spacing: 2px; font-family: monospace; }
    .field.from-bik { left: 777px; top: 567px; font-size: 17px; letter-spacing: 2px; font-family: monospace; }
    .field.from-bank { left: 540px; top: 545px; width: 345px; }
    .field.to-fullname { left: 460px; top: 710px; width: 430px; }
    .field.to-address-line1 { left: 540px; top: 752px; width: 350px; }
    .field.to-address-line2 { left: 400px; top: 775px; width: 490px; }
    .field.to-zipcode { left: 816px; top: 794px; font-size: 18px; letter-spacing: 3px; }
</style>
<img class="form1" src="<?php echo $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>modules/netshop/forms/templates/russianpost_f113_page1.png" width="1000" alt=""/>
<img class="form2" src="<?php echo $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>modules/netshop/forms/templates/russianpost_f113_page2.png" width="1000" alt=""/>
<div class="field cash-on-delivery-int"><?php echo $cash_on_delivery_int; ?></div>
<div class="field cash-on-delivery-dec"><?php echo $cash_on_delivery_dec; ?></div>
<div class="field cash-on-delivery-text"><?php echo $cash_on_delivery_text; ?></div>
<div class="field from-fullname"><?php echo $from_fullname; ?></div>
<div class="field from-address-line1"><?php echo $from_address_line1; ?></div>
<div class="field from-address-line2"><?php echo $from_address_line2; ?></div>
<div class="field from-zipcode"><?php echo $from_zipcode; ?></div>
<div class="field from-inn"><?php echo $receiver_inn; ?></div>
<div class="field from-corr"><?php echo $receiver_corr; ?></div>
<div class="field from-account"><?php echo $receiver_account; ?></div>
<div class="field from-bank"><?php echo $receiver_bank; ?></div>
<div class="field from-bik"><?php echo $receiver_bik; ?></div>
<div class="field to-fullname"><?php echo $to_fullname; ?></div>
<div class="field to-address-line1"><?php echo $to_address_line1; ?></div>
<div class="field to-address-line2"><?php echo $to_address_line2; ?></div>
<div class="field to-zipcode"><?php echo $to_zipcode; ?></div>