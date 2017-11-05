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
    .field.from-fullname { left: 240px; top: 493px; width: 380px; height: 20px; }
    .field.from-address-line1 { left: 240px; top: 517px; width: 380px; height: 20px; }
    .field.from-address-line2 { left: 200px; top: 545px; width: 420px; height: 20px; }
    .field.from-zipcode { left: 485px; top: 570px; font-size: 30px; letter-spacing: 8px; }
    .field.to-fullname { left: 250px; top: 618px; width: 540px; }
    .field.to-address-line1 { left: 240px; top: 650px; width: 550px; }
    .field.to-address-line2 { left: 200px; top: 675px; width: 450px; }
    .field.to-zipcode { left: 660px; top: 664px; font-size: 30px; letter-spacing: 8px; }
    .field.bottom-to-fullname { left: 238px; top: 1029px; width: 560px; }
    .field.bottom-to-address-line1 { left: 243px; top: 1066px; width: 560px; }
    .field.bottom-to-address-line2 { left: 200px; top: 1103px; width: 450px; }
    .field.bottom-to-zipcode { left: 658px; top: 1090px; font-size: 30px; letter-spacing: 8px; }
    .field.valuation { left: 260px; top: 990px; width: 180px; text-align: center; }
    .field.valuation-text { left: 200px; top: 422px; height: 20px; width: 420px; font-size: 14px; text-align: center; }
    .field.cash-on-delivery { left: 580px; top: 990px; width: 170px; text-align: center; }
    .field.cash-on-delivery-text { left: 200px; top: 460px; height: 20px; width: 420px; font-size: 14px; text-align: center; }
    .field.weight1 { left: 685px; top: 440px; width: 130px; text-align: center; }
    .field.weight2 { left: 285px; top: 910px; width: 160px; text-align: center; }
</style>
<img class="form1" src="<?php echo $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>modules/netshop/forms/templates/russianpost_f116_page1.png" width="1000" alt=""/>
<img class="form2" src="<?php echo $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH ?>modules/netshop/forms/templates/russianpost_f116_page2.png" width="1000" alt=""/>
<div class="field from-fullname"><?php echo $from_fullname; ?></div>
<div class="field from-address-line1"><?php echo $from_address_line1; ?></div>
<div class="field from-address-line2"><?php echo $from_address_line2; ?></div>
<div class="field from-zipcode"><?php echo $from_zipcode; ?></div>
<div class="field to-fullname"><?php echo $to_fullname; ?></div>
<div class="field to-address-line1"><?php echo $to_address_line1; ?></div>
<div class="field to-address-line2"><?php echo $to_address_line2; ?></div>
<div class="field to-zipcode"><?php echo $to_zipcode; ?></div>
<div class="field valuation-text"><?php echo $valuation_text; ?></div>
<div class="field cash-on-delivery-text"><?php echo $cash_on_delivery_text; ?></div>
<div class="field valuation"><?php echo $valuation; ?></div>
<div class="field cash-on-delivery"><?php echo $cash_on_delivery; ?></div>
<div class="field weight1"><?php echo $weight; ?></div>
<div class="field weight2"><?php echo $weight; ?></div>
<div class="field bottom-to-fullname"><?php echo $from_fullname; ?></div>
<div class="field bottom-to-address-line1"><?php echo $from_address_line1; ?></div>
<div class="field bottom-to-address-line2"><?php echo $from_address_line2; ?></div>
<div class="field bottom-to-zipcode"><?php echo $from_zipcode; ?></div>