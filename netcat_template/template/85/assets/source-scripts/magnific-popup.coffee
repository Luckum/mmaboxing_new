#! Magnific Popup - v0.9.9 - 2013-12-04
#* http://dimsemenov.com/plugins/magnific-popup/
#* Copyright (c) 2013 Dmitry Semenov;


#>>core

###
Magnific Popup Core JS file
###

###
Private static constants
###
CLOSE_EVENT = "Close"
BEFORE_CLOSE_EVENT = "BeforeClose"
AFTER_CLOSE_EVENT = "AfterClose"
BEFORE_APPEND_EVENT = "BeforeAppend"
MARKUP_PARSE_EVENT = "MarkupParse"
OPEN_EVENT = "Open"
CHANGE_EVENT = "Change"
NS = "tpl-block-mfp"
EVENT_NS = "." + NS
READY_CLASS = "tpl-block-mfp-ready"
REMOVING_CLASS = "tpl-block-mfp-removing"
PREVENT_CLOSE_CLASS = "tpl-block-mfp-prevent-close"

###
Private vars
###
mfp = undefined # As we have only one instance of MagnificPopup object, we define it locally to not to use 'this'
MagnificPopup = ->

_isJQ = !!(window.jQuery)
_prevStatus = undefined
_window = $(window)
_body = undefined
_document = undefined
_prevContentType = undefined
_wrapClasses = undefined
_currPopupType = undefined

###
Private functions
###
_mfpOn = (name, f) ->
  mfp.ev.on NS + name + EVENT_NS, f

_getEl = (className, appendTo, html, raw) ->
  el = document.createElement("div")
  el.className = "tpl-block-mfp-" + className
  el.innerHTML = html  if html
  unless raw
    el = $(el)
    el.appendTo appendTo  if appendTo
  else appendTo.appendChild el  if appendTo
  el

_mfpTrigger = (e, data) ->
  mfp.ev.triggerHandler NS + e, data
  if mfp.st.callbacks

    # converts "mfpEventName" to "eventName" callback and triggers it if it's present
    e = e.charAt(0).toLowerCase() + e.slice(1)
    mfp.st.callbacks[e].apply mfp, (if $.isArray(data) then data else [data])  if mfp.st.callbacks[e]

_getCloseBtn = (type) ->
  if type isnt _currPopupType or not mfp.currTemplate.closeBtn
    mfp.currTemplate.closeBtn = $(mfp.st.closeMarkup.replace("%title%", mfp.st.tClose))
    _currPopupType = type
  mfp.currTemplate.closeBtn


# Initialize Magnific Popup only when called at least once
_checkInstance = ->
  unless $.magnificPopup.instance
    mfp = new MagnificPopup()
    mfp.init()
    $.magnificPopup.instance = mfp


# CSS transition detection, http://stackoverflow.com/questions/7264899/detect-css-transitions-using-javascript-and-without-modernizr
supportsTransitions = ->
  s = document.createElement("p").style # 's' for style. better to create an element if body yet to exist
  v = ["ms", "O", "Moz", "Webkit"] # 'v' for vendor
  return true  if s["transition"] isnt `undefined`
  return true  if v.pop() + "Transition" of s  while v.length
  false


###
Public functions
###
MagnificPopup:: =
  constructor: MagnificPopup

  ###
  Initializes Magnific Popup plugin.
  This function is triggered only once when $.fn.magnificPopup or $.magnificPopup is executed
  ###
  init: ->
    appVersion = navigator.appVersion
    mfp.isIE7 = appVersion.indexOf("MSIE 7.") isnt -1
    mfp.isIE8 = appVersion.indexOf("MSIE 8.") isnt -1
    mfp.isLowIE = mfp.isIE7 or mfp.isIE8
    mfp.isAndroid = (/android/g).test(appVersion)
    mfp.isIOS = (/iphone|ipad|ipod/g).test(appVersion)
    mfp.supportsTransition = supportsTransitions()

    # We disable fixed positioned lightbox on devices that don't handle it nicely.
    # If you know a better way of detecting this - let me know.
    mfp.probablyMobile = (mfp.isAndroid or mfp.isIOS or /(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent))
    _document = $(document)
    mfp.popupsCache = {}


  ###
  Opens popup
  @param  data [description]
  ###
  open: (data) ->
    _body = $(document.body)  unless _body
    i = undefined
    if data.isObj is false

      # convert jQuery collection to array to avoid conflicts later
      mfp.items = data.items.toArray()
      mfp.index = 0
      items = data.items
      item = undefined
      i = 0
      while i < items.length
        item = items[i]
        item = item.el[0]  if item.parsed
        if item is data.el[0]
          mfp.index = i
          break
        i++
    else
      mfp.items = (if $.isArray(data.items) then data.items else [data.items])
      mfp.index = data.index or 0

    # if popup is already opened - we just update the content
    if mfp.isOpen
      mfp.updateItemHTML()
      return
    mfp.types = []
    _wrapClasses = ""
    if data.mainEl and data.mainEl.length
      mfp.ev = data.mainEl.eq(0)
    else
      mfp.ev = _document
    if data.key
      mfp.popupsCache[data.key] = {}  unless mfp.popupsCache[data.key]
      mfp.currTemplate = mfp.popupsCache[data.key]
    else
      mfp.currTemplate = {}
    mfp.st = $.extend(true, {}, $.magnificPopup.defaults, data)
    mfp.fixedContentPos = (if mfp.st.fixedContentPos is "auto" then not mfp.probablyMobile else mfp.st.fixedContentPos)
    if mfp.st.modal
      mfp.st.closeOnContentClick = false
      mfp.st.closeOnBgClick = false
      mfp.st.showCloseBtn = false
      mfp.st.enableEscapeKey = false

    # Building markup
    # main containers are created only once
    unless mfp.bgOverlay

      # Dark overlay
      mfp.bgOverlay = _getEl("bg").on("click" + EVENT_NS, ->
        mfp.close()
      )
      mfp.wrap = _getEl("wrap").attr("tabindex", -1).on("click" + EVENT_NS, (e) ->
        mfp.close()  if mfp._checkIfClose(e.target)
      )
      mfp.container = _getEl("container", mfp.wrap)
    mfp.contentContainer = _getEl("content")
    mfp.preloader = _getEl("preloader", mfp.container, mfp.st.tLoading)  if mfp.st.preloader

    # Initializing modules
    modules = $.magnificPopup.modules
    i = 0
    while i < modules.length
      n = modules[i]
      n = n.charAt(0).toUpperCase() + n.slice(1)
      mfp["init" + n].call mfp
      i++
    _mfpTrigger "BeforeOpen"
    if mfp.st.showCloseBtn

      # Close button
      unless mfp.st.closeBtnInside
        mfp.wrap.append _getCloseBtn()
      else
        _mfpOn MARKUP_PARSE_EVENT, (e, template, values, item) ->
          values.close_replaceWith = _getCloseBtn(item.type)

        _wrapClasses += " tpl-block-mfp-close-btn-in"
    _wrapClasses += " tpl-block-mfp-align-top"  if mfp.st.alignTop
    if mfp.fixedContentPos
      mfp.wrap.css
        overflow: mfp.st.overflowY
        overflowX: "hidden"
        overflowY: mfp.st.overflowY

    else
      mfp.wrap.css
        top: _window.scrollTop()
        position: "absolute"

    if mfp.st.fixedBgPos is false or (mfp.st.fixedBgPos is "auto" and not mfp.fixedContentPos)
      mfp.bgOverlay.css
        height: _document.height()
        position: "absolute"

    if mfp.st.enableEscapeKey

      # Close on ESC key
      _document.on "keyup" + EVENT_NS, (e) ->
        mfp.close()  if e.keyCode is 27

    _window.on "resize" + EVENT_NS, ->
      mfp.updateSize()

    _wrapClasses += " tpl-block-mfp-auto-cursor"  unless mfp.st.closeOnContentClick
    mfp.wrap.addClass _wrapClasses  if _wrapClasses

    # this triggers recalculation of layout, so we get it once to not to trigger twice
    windowHeight = mfp.wH = _window.height()
    windowStyles = {}
    if mfp.fixedContentPos
      if mfp._hasScrollBar(windowHeight)
        s = mfp._getScrollbarSize()
        windowStyles.marginRight = s  if s
    if mfp.fixedContentPos
      unless mfp.isIE7
        windowStyles.overflow = "hidden"
      else

        # ie7 double-scroll bug
        $("body, html").css "overflow", "hidden"
    classesToadd = mfp.st.mainClass
    classesToadd += " tpl-block-mfp-ie7"  if mfp.isIE7
    mfp._addClassToMFP classesToadd  if classesToadd

    # add content
    mfp.updateItemHTML()
    _mfpTrigger "BuildControls"

    # remove scrollbar, add margin e.t.c
    $("html").css windowStyles

    # add everything to DOM
    mfp.bgOverlay.add(mfp.wrap).prependTo mfp.st.prependTo or _body

    # Save last focused element
    mfp._lastFocusedEl = document.activeElement

    # Wait for next cycle to allow CSS transition
    setTimeout (->
      if mfp.content
        mfp._addClassToMFP READY_CLASS
        mfp._setFocus()
      else

        # if content is not defined (not loaded e.t.c) we add class only for BG
        mfp.bgOverlay.addClass READY_CLASS

      # Trap the focus in popup
      _document.on "focusin" + EVENT_NS, mfp._onFocusIn
    ), 16
    mfp.isOpen = true
    mfp.updateSize windowHeight
    _mfpTrigger OPEN_EVENT
    data


  ###
  Closes the popup
  ###
  close: ->
    return  unless mfp.isOpen
    _mfpTrigger BEFORE_CLOSE_EVENT
    mfp.isOpen = false

    # for CSS3 animation
    if mfp.st.removalDelay and not mfp.isLowIE and mfp.supportsTransition
      mfp._addClassToMFP REMOVING_CLASS
      setTimeout (->
        mfp._close()
      ), mfp.st.removalDelay
    else
      mfp._close()


  ###
  Helper for close() function
  ###
  _close: ->
    _mfpTrigger CLOSE_EVENT
    classesToRemove = REMOVING_CLASS + " " + READY_CLASS + " "
    mfp.bgOverlay.detach()
    mfp.wrap.detach()
    mfp.container.empty()
    classesToRemove += mfp.st.mainClass + " "  if mfp.st.mainClass
    mfp._removeClassFromMFP classesToRemove
    if mfp.fixedContentPos
      windowStyles = marginRight: ""
      if mfp.isIE7
        $("body, html").css "overflow", ""
      else
        windowStyles.overflow = ""
      $("html").css windowStyles
    _document.off "keyup" + EVENT_NS + " focusin" + EVENT_NS
    mfp.ev.off EVENT_NS

    # clean up DOM elements that aren't removed
    mfp.wrap.attr("class", "tpl-block-mfp-wrap").removeAttr "style"
    mfp.bgOverlay.attr "class", "tpl-block-mfp-bg"
    mfp.container.attr "class", "tpl-block-mfp-container"

    # remove close button from target element
    mfp.currTemplate.closeBtn.detach()  if mfp.currTemplate.closeBtn  if mfp.st.showCloseBtn and (not mfp.st.closeBtnInside or mfp.currTemplate[mfp.currItem.type] is true)
    $(mfp._lastFocusedEl).focus()  if mfp._lastFocusedEl # put tab focus back
    mfp.currItem = null
    mfp.content = null
    mfp.currTemplate = null
    mfp.prevHeight = 0
    _mfpTrigger AFTER_CLOSE_EVENT

  updateSize: (winHeight) ->
    if mfp.isIOS

      # fixes iOS nav bars https://github.com/dimsemenov/Magnific-Popup/issues/2
      zoomLevel = document.documentElement.clientWidth / window.innerWidth
      height = window.innerHeight * zoomLevel
      mfp.wrap.css "height", height
      mfp.wH = height
    else
      mfp.wH = winHeight or _window.height()

    # Fixes #84: popup incorrectly positioned with position:relative on body
    mfp.wrap.css "height", mfp.wH  unless mfp.fixedContentPos
    _mfpTrigger "Resize"


  ###
  Set content of popup based on current index
  ###
  updateItemHTML: ->
    item = mfp.items[mfp.index]

    # Detach and perform modifications
    mfp.contentContainer.detach()
    mfp.content.detach()  if mfp.content
    item = mfp.parseEl(mfp.index)  unless item.parsed
    type = item.type
    _mfpTrigger "BeforeChange", [(if mfp.currItem then mfp.currItem.type else ""), type]

    # BeforeChange event works like so:
    # _mfpOn('BeforeChange', function(e, prevType, newType) { });
    mfp.currItem = item
    unless mfp.currTemplate[type]
      markup = (if mfp.st[type] then mfp.st[type].markup else false)

      # allows to modify markup
      _mfpTrigger "FirstMarkupParse", markup
      if markup
        mfp.currTemplate[type] = $(markup)
      else

        # if there is no markup found we just define that template is parsed
        mfp.currTemplate[type] = true
    mfp.container.removeClass "tpl-block-mfp-" + _prevContentType + "-holder"  if _prevContentType and _prevContentType isnt item.type
    newContent = mfp["get" + type.charAt(0).toUpperCase() + type.slice(1)](item, mfp.currTemplate[type])
    mfp.appendContent newContent, type
    item.preloaded = true
    _mfpTrigger CHANGE_EVENT, item
    _prevContentType = item.type

    # Append container back after its content changed
    mfp.container.prepend mfp.contentContainer
    _mfpTrigger "AfterChange"


  ###
  Set HTML content of popup
  ###
  appendContent: (newContent, type) ->
    mfp.content = newContent
    if newContent
      if mfp.st.showCloseBtn and mfp.st.closeBtnInside and mfp.currTemplate[type] is true

        # if there is no markup, we just append close button element inside
        mfp.content.append _getCloseBtn()  unless mfp.content.find(".tpl-block-mfp-close").length
      else
        mfp.content = newContent
    else
      mfp.content = ""
    _mfpTrigger BEFORE_APPEND_EVENT
    mfp.container.addClass "tpl-block-mfp-" + type + "-holder"
    mfp.contentContainer.append mfp.content


  ###
  Creates Magnific Popup data object based on given data
  @param  {int} index Index of item to parse
  ###
  parseEl: (index) ->
    item = mfp.items[index]
    type = item.type
    if item.tagName
      item = el: $(item)
    else
      item =
        data: item
        src: item.src
    if item.el
      types = mfp.types

      # check for 'mfp-TYPE' class
      i = 0

      while i < types.length
        if item.el.hasClass("tpl-block-mfp-" + types[i])
          type = types[i]
          break
        i++
      item.src = item.el.attr("data-mfp-src")
      item.src = item.el.attr("href")  unless item.src
    item.type = type or mfp.st.type or "inline"
    item.index = index
    item.parsed = true
    mfp.items[index] = item
    _mfpTrigger "ElementParse", item
    mfp.items[index]


  ###
  Initializes single popup or a group of popups
  ###
  addGroup: (el, options) ->
    eHandler = (e) ->
      e.mfpEl = this
      mfp._openClick e, el, options

    options = {}  unless options
    eName = "click.magnificPopup"
    options.mainEl = el
    if options.items
      options.isObj = true
      el.off(eName).on eName, eHandler
    else
      options.isObj = false
      if options.delegate
        el.off(eName).on eName, options.delegate, eHandler
      else
        options.items = el
        el.off(eName).on eName, eHandler

  _openClick: (e, el, options) ->
    midClick = (if options.midClick isnt `undefined` then options.midClick else $.magnificPopup.defaults.midClick)
    return  if not midClick and (e.which is 2 or e.ctrlKey or e.metaKey)
    disableOn = (if options.disableOn isnt `undefined` then options.disableOn else $.magnificPopup.defaults.disableOn)
    if disableOn
      if $.isFunction(disableOn)
        return true  unless disableOn.call(mfp)
      else # else it's number
        return true  if _window.width() < disableOn
    if e.type
      e.preventDefault()

      # This will prevent popup from closing if element is inside and popup is already opened
      e.stopPropagation()  if mfp.isOpen
    options.el = $(e.mfpEl)
    options.items = el.find(options.delegate)  if options.delegate
    mfp.open options


  ###
  Updates text on preloader
  ###
  updateStatus: (status, text) ->
    if mfp.preloader
      mfp.container.removeClass "tpl-block-mfp-s-" + _prevStatus  if _prevStatus isnt status
      text = mfp.st.tLoading  if not text and status is "loading"
      data =
        status: status
        text: text


      # allows to modify status
      _mfpTrigger "UpdateStatus", data
      status = data.status
      text = data.text
      mfp.preloader.html text
      mfp.preloader.find("a").on "click", (e) ->
        e.stopImmediatePropagation()

      mfp.container.addClass "tpl-block-mfp-s-" + status
      _prevStatus = status


  #
  #   "Private" helpers that aren't private at all
  #

  # Check to close popup or not
  # "target" is an element that was clicked
  _checkIfClose: (target) ->
    return  if $(target).hasClass(PREVENT_CLOSE_CLASS)
    closeOnContent = mfp.st.closeOnContentClick
    closeOnBg = mfp.st.closeOnBgClick
    if closeOnContent and closeOnBg
      return true
    else

      # We close the popup if click is on close button or on preloader. Or if there is no content.
      return true  if not mfp.content or $(target).hasClass("tpl-block-mfp-close") or (mfp.preloader and target is mfp.preloader[0])

      # if click is outside the content
      if target isnt mfp.content[0] and not $.contains(mfp.content[0], target)

        # last check, if the clicked element is in DOM, (in case it's removed onclick)
        return true  if $.contains(document, target)  if closeOnBg
      else return true  if closeOnContent
    false

  _addClassToMFP: (cName) ->
    mfp.bgOverlay.addClass cName
    mfp.wrap.addClass cName

  _removeClassFromMFP: (cName) ->
    @bgOverlay.removeClass cName
    mfp.wrap.removeClass cName

  _hasScrollBar: (winHeight) ->
    ((if mfp.isIE7 then _document.height() else document.body.scrollHeight)) > (winHeight or _window.height())

  _setFocus: ->
    ((if mfp.st.focus then mfp.content.find(mfp.st.focus).eq(0) else mfp.wrap)).focus()

  _onFocusIn: (e) ->
    if e.target isnt mfp.wrap[0] and not $.contains(mfp.wrap[0], e.target)
      mfp._setFocus()
      false

  _parseMarkup: (template, values, item) ->
    arr = undefined
    values = $.extend(item.data, values)  if item.data
    _mfpTrigger MARKUP_PARSE_EVENT, [template, values, item]
    $.each values, (key, value) ->
      return true  if value is `undefined` or value is false
      arr = key.split("_")
      if arr.length > 1
        el = template.find(EVENT_NS + "-" + arr[0])
        if el.length > 0
          attr = arr[1]
          if attr is "replaceWith"
            el.replaceWith value  if el[0] isnt value[0]
          else if attr is "img"
            if el.is("img")
              el.attr "src", value
            else
              el.replaceWith "<img src=\"" + value + "\" class=\"" + el.attr("class") + "\" />"
          else
            el.attr arr[1], value
      else
        template.find(EVENT_NS + "-" + key).html value


  _getScrollbarSize: ->

    # thx David
    if mfp.scrollbarSize is `undefined`
      scrollDiv = document.createElement("div")
      scrollDiv.id = "tpl-block-mfp-sbm"
      scrollDiv.style.cssText = "width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;"
      document.body.appendChild scrollDiv
      mfp.scrollbarSize = scrollDiv.offsetWidth - scrollDiv.clientWidth
      document.body.removeChild scrollDiv
    mfp.scrollbarSize

# MagnificPopup core prototype end

###
Public static functions
###
$.magnificPopup =
  instance: null
  proto: MagnificPopup::
  modules: []
  open: (options, index) ->
    _checkInstance()
    unless options
      options = {}
    else
      options = $.extend(true, {}, options)
    options.isObj = true
    options.index = index or 0
    @instance.open options

  close: ->
    $.magnificPopup.instance and $.magnificPopup.instance.close()

  registerModule: (name, module) ->
    $.magnificPopup.defaults[name] = module.options  if module.options
    $.extend @proto, module.proto
    @modules.push name

  defaults:

    # Info about options is in docs:
    # http://dimsemenov.com/plugins/magnific-popup/documentation.html#options
    disableOn: 0
    key: null
    midClick: false
    mainClass: ""
    preloader: true
    focus: "" # CSS selector of input to focus after popup is opened
    closeOnContentClick: false
    closeOnBgClick: true
    closeBtnInside: true
    showCloseBtn: true
    enableEscapeKey: true
    modal: false
    alignTop: false
    removalDelay: 0
    prependTo: null
    fixedContentPos: "auto"
    fixedBgPos: "auto"
    overflowY: "auto"
    closeMarkup: "<button title=\"%title%\" type=\"button\" class=\"tpl-block-mfp-close\">&times;</button>"
    tClose: "Close (Esc)"
    tLoading: "Loading..."

$.fn.magnificPopup = (options) ->
  _checkInstance()
  jqEl = $(this)

  # We call some API method of first param is a string
  if typeof options is "string"
    if options is "open"
      items = undefined
      itemOpts = (if _isJQ then jqEl.data("magnificPopup") else jqEl[0].magnificPopup)
      index = parseInt(arguments_[1], 10) or 0
      if itemOpts.items
        items = itemOpts.items[index]
      else
        items = jqEl
        items = items.find(itemOpts.delegate)  if itemOpts.delegate
        items = items.eq(index)
      mfp._openClick
        mfpEl: items
      , jqEl, itemOpts
    else
      mfp[options].apply mfp, Array::slice.call(arguments_, 1)  if mfp.isOpen
  else

    # clone options obj
    options = $.extend(true, {}, options)

    #
    #    * As Zepto doesn't support .data() method for objects
    #    * and it works only in normal browsers
    #    * we assign "options" object directly to the DOM element. FTW!
    #
    if _isJQ
      jqEl.data "magnificPopup", options
    else
      jqEl[0].magnificPopup = options
    mfp.addGroup jqEl, options
  jqEl


#Quick benchmark
#
#var start = performance.now(),
# i,
# rounds = 1000;
#
#for(i = 0; i < rounds; i++) {
#
#}
#console.log('Test #1:', performance.now() - start);
#
#start = performance.now();
#for(i = 0; i < rounds; i++) {
#
#}
#console.log('Test #2:', performance.now() - start);
#

#>>core

#>>inline
INLINE_NS = "inline"
_hiddenClass = undefined
_inlinePlaceholder = undefined
_lastInlineElement = undefined
_putInlineElementsBack = ->
  if _lastInlineElement
    _inlinePlaceholder.after(_lastInlineElement.addClass(_hiddenClass)).detach()
    _lastInlineElement = null

$.magnificPopup.registerModule INLINE_NS,
  options:
    hiddenClass: "hide" # will be appended with `tpl-block-mfp-` prefix
    markup: ""
    tNotFound: "Content not found"

  proto:
    initInline: ->
      mfp.types.push INLINE_NS
      _mfpOn CLOSE_EVENT + "." + INLINE_NS, ->
        _putInlineElementsBack()


    getInline: (item, template) ->
      _putInlineElementsBack()
      if item.src
        inlineSt = mfp.st.inline
        el = $(item.src)
        if el.length

          # If target element has parent - we replace it with placeholder and put it back after popup is closed
          parent = el[0].parentNode
          if parent and parent.tagName
            unless _inlinePlaceholder
              _hiddenClass = inlineSt.hiddenClass
              _inlinePlaceholder = _getEl(_hiddenClass)
              _hiddenClass = "tpl-block-mfp-" + _hiddenClass

            # replace target inline element with placeholder
            _lastInlineElement = el.after(_inlinePlaceholder).detach().removeClass(_hiddenClass)
          mfp.updateStatus "ready"
        else
          mfp.updateStatus "error", inlineSt.tNotFound
          el = $("<div>")
        item.inlineElement = el
        return el
      mfp.updateStatus "ready"
      mfp._parseMarkup template, {}, item
      template


#>>inline

#>>ajax
AJAX_NS = "ajax"
_ajaxCur = undefined
_removeAjaxCursor = ->
  _body.removeClass _ajaxCur  if _ajaxCur

_destroyAjaxRequest = ->
  _removeAjaxCursor()
  mfp.req.abort()  if mfp.req

$.magnificPopup.registerModule AJAX_NS,
  options:
    settings: null
    cursor: "tpl-block-mfp-ajax-cur"
    tError: "<a href=\"%url%\">The content</a> could not be loaded."

  proto:
    initAjax: ->
      mfp.types.push AJAX_NS
      _ajaxCur = mfp.st.ajax.cursor
      _mfpOn CLOSE_EVENT + "." + AJAX_NS, _destroyAjaxRequest
      _mfpOn "BeforeChange." + AJAX_NS, _destroyAjaxRequest

    getAjax: (item) ->
      _body.addClass _ajaxCur  if _ajaxCur
      mfp.updateStatus "loading"
      opts = $.extend(
        url: item.src
        success: (data, textStatus, jqXHR) ->
          temp =
            data: data
            xhr: jqXHR

          _mfpTrigger "ParseAjax", temp
          mfp.appendContent $(temp.data), AJAX_NS
          item.finished = true
          _removeAjaxCursor()
          mfp._setFocus()
          setTimeout (->
            mfp.wrap.addClass READY_CLASS
          ), 16
          mfp.updateStatus "ready"
          _mfpTrigger "AjaxContentAdded"

        error: ->
          _removeAjaxCursor()
          item.finished = item.loadError = true
          mfp.updateStatus "error", mfp.st.ajax.tError.replace("%url%", item.src)
      , mfp.st.ajax.settings)
      mfp.req = $.ajax(opts)
      ""


#>>ajax

#>>image
_imgInterval = undefined
_getTitle = (item) ->
  return item.data.title  if item.data and item.data.title isnt `undefined`
  src = mfp.st.image.titleSrc
  if src
    if $.isFunction(src)
      return src.call(mfp, item)
    else return item.el.attr(src) or ""  if item.el
  ""

$.magnificPopup.registerModule "image",
  options:
    markup: "<div class=\"tpl-block-mfp-figure\">" + "<div class=\"tpl-block-mfp-close\"></div>" + "<figure>" + "<div class=\"tpl-block-mfp-img\"></div>" + "<figcaption>" + "<div class=\"tpl-block-mfp-bottom-bar\">" + "<div class=\"tpl-block-mfp-title\"></div>" + "<div class=\"tpl-block-mfp-counter\"></div>" + "</div>" + "</figcaption>" + "</figure>" + "</div>"
    cursor: "tpl-block-mfp-zoom-out-cur"
    titleSrc: "title"
    verticalFit: true
    tError: "<a href=\"%url%\">The image</a> could not be loaded."

  proto:
    initImage: ->
      imgSt = mfp.st.image
      ns = ".image"
      mfp.types.push "image"
      _mfpOn OPEN_EVENT + ns, ->
        _body.addClass imgSt.cursor  if mfp.currItem.type is "image" and imgSt.cursor

      _mfpOn CLOSE_EVENT + ns, ->
        _body.removeClass imgSt.cursor  if imgSt.cursor
        _window.off "resize" + EVENT_NS

      _mfpOn "Resize" + ns, mfp.resizeImage
      _mfpOn "AfterChange", mfp.resizeImage  if mfp.isLowIE

    resizeImage: ->
      item = mfp.currItem
      return  if not item or not item.img
      if mfp.st.image.verticalFit
        decr = 0

        # fix box-sizing in ie7/8
        decr = parseInt(item.img.css("padding-top"), 10) + parseInt(item.img.css("padding-bottom"), 10)  if mfp.isLowIE
        item.img.css "max-height", mfp.wH - decr

    _onImageHasSize: (item) ->
      if item.img
        item.hasSize = true
        clearInterval _imgInterval  if _imgInterval
        item.isCheckingImgSize = false
        _mfpTrigger "ImageHasSize", item
        if item.imgHidden
          mfp.content.removeClass "tpl-block-mfp-loading"  if mfp.content
          item.imgHidden = false


    ###
    Function that loops until the image has size to display elements that rely on it asap
    ###
    findImageSize: (item) ->
      counter = 0
      img = item.img[0]
      mfpSetInterval = (delay) ->
        clearInterval _imgInterval  if _imgInterval

        # decelerating interval that checks for size of an image
        _imgInterval = setInterval(->
          if img.naturalWidth > 0
            mfp._onImageHasSize item
            return
          clearInterval _imgInterval  if counter > 200
          counter++
          if counter is 3
            mfpSetInterval 10
          else if counter is 40
            mfpSetInterval 50
          else mfpSetInterval 500  if counter is 100
        , delay)

      mfpSetInterval 1

    getImage: (item, template) ->
      guard = 0

      # image load complete handler
      onLoadComplete = ->
        if item
          if item.img[0].complete
            item.img.off ".mfploader"
            if item is mfp.currItem
              mfp._onImageHasSize item
              mfp.updateStatus "ready"
            item.hasSize = true
            item.loaded = true
            _mfpTrigger "ImageLoadComplete"
          else

            # if image complete check fails 200 times (20 sec), we assume that there was an error.
            guard++
            if guard < 200
              setTimeout onLoadComplete, 100
            else
              onLoadError()


      # image error handler
      onLoadError = ->
        if item
          item.img.off ".mfploader"
          if item is mfp.currItem
            mfp._onImageHasSize item
            mfp.updateStatus "error", imgSt.tError.replace("%url%", item.src)
          item.hasSize = true
          item.loaded = true
          item.loadError = true

      imgSt = mfp.st.image
      el = template.find(".tpl-block-mfp-img")
      if el.length
        img = document.createElement("img")
        img.className = "tpl-block-mfp-img"
        item.img = $(img).on("load.mfploader", onLoadComplete).on("error.mfploader", onLoadError)
        img.src = item.src

        # without clone() "error" event is not firing when IMG is replaced by new IMG
        # TODO: find a way to avoid such cloning
        item.img = item.img.clone()  if el.is("img")
        img = item.img[0]
        if img.naturalWidth > 0
          item.hasSize = true
        else item.hasSize = false  unless img.width
      mfp._parseMarkup template,
        title: _getTitle(item)
        img_replaceWith: item.img
      , item
      mfp.resizeImage()
      if item.hasSize
        clearInterval _imgInterval  if _imgInterval
        if item.loadError
          template.addClass "tpl-block-mfp-loading"
          mfp.updateStatus "error", imgSt.tError.replace("%url%", item.src)
        else
          template.removeClass "tpl-block-mfp-loading"
          mfp.updateStatus "ready"
        return template
      mfp.updateStatus "loading"
      item.loading = true
      unless item.hasSize
        item.imgHidden = true
        template.addClass "tpl-block-mfp-loading"
        mfp.findImageSize item
      template


#>>image

#>>zoom
hasMozTransform = undefined
getHasMozTransform = ->
  hasMozTransform = document.createElement("p").style.MozTransform isnt `undefined`  if hasMozTransform is `undefined`
  hasMozTransform

$.magnificPopup.registerModule "zoom",
  options:
    enabled: false
    easing: "ease-in-out"
    duration: 300
    opener: (element) ->
      (if element.is("img") then element else element.find("img"))

  proto:
    initZoom: ->
      zoomSt = mfp.st.zoom
      ns = ".zoom"
      image = undefined
      return  if not zoomSt.enabled or not mfp.supportsTransition
      duration = zoomSt.duration
      getElToAnimate = (image) ->
        newImg = image.clone().removeAttr("style").removeAttr("class").addClass("tpl-block-mfp-animated-image")
        transition = "all " + (zoomSt.duration / 1000) + "s " + zoomSt.easing
        cssObj =
          position: "fixed"
          zIndex: 9999
          left: 0
          top: 0
          "-webkit-backface-visibility": "hidden"

        t = "transition"
        cssObj["-webkit-" + t] = cssObj["-moz-" + t] = cssObj["-o-" + t] = cssObj[t] = transition
        newImg.css cssObj
        newImg

      showMainContent = ->
        mfp.content.css "visibility", "visible"

      openTimeout = undefined
      animatedImg = undefined
      _mfpOn "BuildControls" + ns, ->
        if mfp._allowZoom()
          clearTimeout openTimeout
          mfp.content.css "visibility", "hidden"

          # Basically, all code below does is clones existing image, puts in on top of the current one and animated it
          image = mfp._getItemToZoom()
          unless image
            showMainContent()
            return
          animatedImg = getElToAnimate(image)
          animatedImg.css mfp._getOffset()
          mfp.wrap.append animatedImg
          openTimeout = setTimeout(->
            animatedImg.css mfp._getOffset(true)
            openTimeout = setTimeout(->
              showMainContent()
              setTimeout (->
                animatedImg.remove()
                image = animatedImg = null
                _mfpTrigger "ZoomAnimationEnded"
              ), 16 # avoid blink when switching images
            , duration) # this timeout equals animation duration
          , 16) # by adding this timeout we avoid short glitch at the beginning of animation


      # Lots of timeouts...
      _mfpOn BEFORE_CLOSE_EVENT + ns, ->
        if mfp._allowZoom()
          clearTimeout openTimeout
          mfp.st.removalDelay = duration
          unless image
            image = mfp._getItemToZoom()
            return  unless image
            animatedImg = getElToAnimate(image)
          animatedImg.css mfp._getOffset(true)
          mfp.wrap.append animatedImg
          mfp.content.css "visibility", "hidden"
          setTimeout (->
            animatedImg.css mfp._getOffset()
          ), 16

      _mfpOn CLOSE_EVENT + ns, ->
        if mfp._allowZoom()
          showMainContent()
          animatedImg.remove()  if animatedImg
          image = null


    _allowZoom: ->
      mfp.currItem.type is "image"

    _getItemToZoom: ->
      if mfp.currItem.hasSize
        mfp.currItem.img
      else
        false


    # Get element postion relative to viewport
    _getOffset: (isLarge) ->
      el = undefined
      if isLarge
        el = mfp.currItem.img
      else
        el = mfp.st.zoom.opener(mfp.currItem.el or mfp.currItem)
      offset = el.offset()
      paddingTop = parseInt(el.css("padding-top"), 10)
      paddingBottom = parseInt(el.css("padding-bottom"), 10)
      offset.top -= ($(window).scrollTop() - paddingTop)

      #
      #
      #     Animating left + top + width/height looks glitchy in Firefox, but perfect in Chrome. And vice-versa.
      #
      #
      obj =
        width: el.width()

        # fix Zepto height+padding issue
        height: ((if _isJQ then el.innerHeight() else el[0].offsetHeight)) - paddingBottom - paddingTop


      # I hate to do this, but there is no another option
      if getHasMozTransform()
        obj["-moz-transform"] = obj["transform"] = "translate(" + offset.left + "px," + offset.top + "px)"
      else
        obj.left = offset.left
        obj.top = offset.top
      obj


#>>zoom

#>>iframe
IFRAME_NS = "iframe"
_emptyPage = "//about:blank"
_fixIframeBugs = (isShowing) ->
  if mfp.currTemplate[IFRAME_NS]
    el = mfp.currTemplate[IFRAME_NS].find("iframe")
    if el.length

      # reset src after the popup is closed to avoid "video keeps playing after popup is closed" bug
      el[0].src = _emptyPage  unless isShowing

      # IE8 black screen bug fix
      el.css "display", (if isShowing then "block" else "none")  if mfp.isIE8

$.magnificPopup.registerModule IFRAME_NS,
  options:
    markup: "<div class=\"tpl-block-mfp-iframe-scaler\">" + "<div class=\"tpl-block-mfp-close\"></div>" + "<iframe class=\"tpl-block-mfp-iframe\" src=\"//about:blank\" frameborder=\"0\" allowfullscreen></iframe>" + "</div>"
    srcAction: "iframe_src"

    # we don't care and support only one default type of URL by default
    patterns:
      youtube:
        index: "youtube.com"
        id: "v="
        src: "//www.youtube.com/embed/%id%?autoplay=1"

      vimeo:
        index: "vimeo.com/"
        id: "/"
        src: "//player.vimeo.com/video/%id%?autoplay=1"

      gmaps:
        index: "//maps.google."
        src: "%id%&output=embed"

  proto:
    initIframe: ->
      mfp.types.push IFRAME_NS
      _mfpOn "BeforeChange", (e, prevType, newType) ->
        if prevType isnt newType
          if prevType is IFRAME_NS
            _fixIframeBugs() # iframe if removed
          else _fixIframeBugs true  if newType is IFRAME_NS # iframe is showing

      # else {
      # iframe source is switched, don't do anything
      #}
      _mfpOn CLOSE_EVENT + "." + IFRAME_NS, ->
        _fixIframeBugs()


    getIframe: (item, template) ->
      embedSrc = item.src
      iframeSt = mfp.st.iframe
      $.each iframeSt.patterns, ->
        if embedSrc.indexOf(@index) > -1
          if @id
            if typeof @id is "string"
              embedSrc = embedSrc.substr(embedSrc.lastIndexOf(@id) + @id.length, embedSrc.length)
            else
              embedSrc = @id.call(this, embedSrc)
          embedSrc = @src.replace("%id%", embedSrc)
          false # break;

      dataObj = {}
      dataObj[iframeSt.srcAction] = embedSrc  if iframeSt.srcAction
      mfp._parseMarkup template, dataObj, item
      mfp.updateStatus "ready"
      template


#>>iframe

#>>retina
RETINA_NS = "retina"
$.magnificPopup.registerModule RETINA_NS,
  options:
    replaceSrc: (item) ->
      item.src.replace /\.\w+$/, (m) ->
        "@2x" + m


    ratio: 1 # Function or number.  Set to 1 to disable.

  proto:
    initRetina: ->
      if window.devicePixelRatio > 1
        st = mfp.st.retina
        ratio = st.ratio
        ratio = (if not isNaN(ratio) then ratio else ratio())
        if ratio > 1
          _mfpOn "ImageHasSize" + "." + RETINA_NS, (e, item) ->
            item.img.css
              "max-width": item.img[0].naturalWidth / ratio
              width: "100%"


          _mfpOn "ElementParse" + "." + RETINA_NS, (e, item) ->
            item.src = st.replaceSrc(item, ratio)



#>>retina
_checkInstance()