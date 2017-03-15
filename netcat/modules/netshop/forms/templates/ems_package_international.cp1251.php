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

    BODY { font: 16px "Times New Roman"; }
    .field { position: absolute; overflow: visible; }
    .field.from-fullname { left: 190px; top: 300px; width: 295px; height: 18px; }
    .field.from-address-line1 { left: 190px; top: 324px; width: 295px; height: 18px; }
    .field.from-address-line2 { left: 190px; top: 348px; width: 295px; height: 18px; }
    .field.from-phone { left: 255px; top: 370px; height: 18px; }
    .field.russia { left: 405px; top: 370px; height: 18px; font-size: 18px; }
    .field.to-fullname { left: 525px; top: 300px; width: 320px; height: 18px; }
    .field.to-address-line1 { left: 525px; top: 324px; width: 320px; height: 18px; }
    .field.to-address-line2 { left: 525px; top: 348px; width: 320px; height: 18px; }
    .field.to-phone { left: 585px; top: 370px; height: 18px; }
    .field.description { left: 181px; top: 436px; height: 13px; width: 215px; line-height: 23px; }
    .field.valuation { left: 310px; top: 578px; height: 18px; width: 80px; font-size: 14px; }
    .field.weight { left: 217px; top: 580px; height: 18px; }
</style>
<div class="field from-fullname"><?php echo $from_fullname; ?></div>
<div class="field from-address-line1"><?php echo $from_address_line1; ?></div>
<div class="field from-address-line2"><?php echo $from_address_line2; ?></div>
<div class="field from-phone"><?php echo $from_phone; ?></div>
<div class="field russia">Russia</div>
<div class="field to-fullname"><?php echo $to_fullname; ?></div>
<div class="field to-address-line1"><?php echo $to_address_line1; ?></div>
<div class="field to-address-line2"><?php echo $to_address_line2; ?></div>
<div class="field to-phone"><?php echo $to_phone; ?></div>
<div class="field description"><?php echo $description; ?></div>
<div class="field valuation"><?php echo $valuation; ?></div>
<div class="field weight"><?php echo $weight; ?></div>