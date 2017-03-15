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

    BODY { font: 11px "Times New Roman"; }
    .field { position: absolute; overflow: visible; }
    .field.from-legal-entity { left: 280px; top: 226px; width: 240px; height: 13px; }
    .field.from-fullname { left: 266px; top: 238px; width: 254px; height: 13px; }
    .field.from-phone { left: 235px; top: 253px; width: 285px; height: 13px; }
    .field.from-street { left: 280px; top: 268px; width: 240px; height: 13px; }
    .field.from-house { left: 200px; top: 283px; width: 30px; height: 13px; }
    .field.from-block { left: 258px; top: 283px; width: 27px; height: 13px; }
    .field.from-floor { left: 303px; top: 283px; width: 25px; height: 13px; }
    .field.from-apartment { left: 365px; top: 283px; width: 43px; height: 13px; }
    .field.from-city { left: 285px; top: 298px; width: 235px; height: 13px; }
    .field.from-region { left: 220px; top: 312px; width: 300px; height: 13px; }
    .field.from-zipcode { left: 225px; top: 325px; height: 13px; letter-spacing: 7px; }
    .field.to-legal-entity { left: 625px; top: 226px; width: 240px; height: 13px; }
    .field.to-fullname { left: 605px; top: 239px; width: 260px; height: 13px; }
    .field.to-phone { left: 575px; top: 253px; width: 290px; height: 13px; }
    .field.to-street { left: 625px; top: 268px; width: 260px; height: 13px; }
    .field.to-house { left: 545px; top: 283px; width: 30px; height: 13px; }
    .field.to-block { left: 605px; top: 283px; width: 27px; height: 13px; }
    .field.to-floor { left: 650px; top: 283px; width: 25px; height: 13px; }
    .field.to-apartment { left: 710px; top: 283px; width: 43px; height: 13px; }
    .field.to-city { left: 630px; top: 298px; width: 235px; height: 13px; }
    .field.to-region { left: 565px; top: 312px; width: 300px; height: 13px; }
    .field.to-zipcode { left: 569px; top: 325px; height: 13px; letter-spacing: 7px; }
    .field.description { left: 190px; top: 360px; height: 13px; width: 325px; text-indent: 95px; }
    .field.valuation { left: 625px; top: 382px; height: 13px; letter-spacing: 4px; font-size: 10px; }
    .field.valuation-text { left: 570px; top: 395px; height: 13px; width: 300px; font-size: 9px; }
    .field.cash-on-delivery-int { left: 623px; top: 407px; height: 13px; letter-spacing: 4px; font-size: 10px; }
    .field.cash-on-delivery-dec { left: 697px; top: 407px; height: 13px; letter-spacing: 4px; font-size: 10px; }
    .field.cash-on-delivery-text { left: 570px; top: 423px; height: 13px; width: 300px; font-size: 8px; }
    .field.weight-int { left: 259px; top: 505px; height: 15px; letter-spacing: 3px; font-size: 14px; }
    .field.weight-dec { left: 298px; top: 505px; height: 15px; letter-spacing: 3px; font-size: 14px; }
</style>
<div class="field from-legal-entity"><?= $from_legal_entity; ?></div>
<div class="field from-fullname"><?= $from_fullname; ?></div>
<div class="field from-phone"><?= $from_phone; ?></div>
<div class="field from-street"><?= $from_street; ?></div>
<div class="field from-house"><?= $from_house; ?></div>
<div class="field from-block"><?= $from_block; ?></div>
<div class="field from-floor"><?= $from_floor; ?></div>
<div class="field from-apartment"><?= $from_apartment; ?></div>
<div class="field from-city"><?= $from_city; ?></div>
<div class="field from-region"><?= $from_region; ?></div>
<div class="field from-zipcode"><?= $from_zipcode; ?></div>
<div class="field to-legal-entity"><?= $to_legal_entity; ?></div>
<div class="field to-fullname"><?= $to_fullname; ?></div>
<div class="field to-phone"><?= $to_phone; ?></div>
<div class="field to-street"><?= $to_street; ?></div>
<div class="field to-house"><?= $to_house; ?></div>
<div class="field to-block"><?= $to_block; ?></div>
<div class="field to-floor"><?= $to_floor; ?></div>
<div class="field to-apartment"><?= $to_apartment; ?></div>
<div class="field to-city"><?= $to_city; ?></div>
<div class="field to-region"><?= $to_region; ?></div>
<div class="field to-zipcode"><?= $to_zipcode; ?></div>
<div class="field description"><?= $description; ?></div>
<div class="field valuation"><?= $valuation; ?></div>
<div class="field valuation-text"><?= $valuation_text; ?></div>
<div class="field cash-on-delivery-int"><?= $cash_on_delivery_int; ?></div>
<div class="field cash-on-delivery-dec"><?= $cash_on_delivery_dec; ?></div>
<div class="field cash-on-delivery-text"><?= $cash_on_delivery_text; ?></div>
<div class="field weight-int"><?= $weight_int; ?></div>
<div class="field weight-dec"><?= $weight_dec; ?></div>