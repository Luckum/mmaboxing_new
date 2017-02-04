/*
Magnific Popup Core JS file
*/


/*
Private static constants
*/


(function() {
  var AFTER_CLOSE_EVENT, AJAX_NS, BEFORE_APPEND_EVENT, BEFORE_CLOSE_EVENT, CHANGE_EVENT, CLOSE_EVENT, EVENT_NS, IFRAME_NS, INLINE_NS, MARKUP_PARSE_EVENT, MagnificPopup, NS, OPEN_EVENT, PREVENT_CLOSE_CLASS, READY_CLASS, REMOVING_CLASS, RETINA_NS, getHasMozTransform, hasMozTransform, mfp, supportsTransitions, _ajaxCur, _body, _checkInstance, _currPopupType, _destroyAjaxRequest, _document, _emptyPage, _fixIframeBugs, _getCloseBtn, _getEl, _getTitle, _hiddenClass, _imgInterval, _inlinePlaceholder, _isJQ, _lastInlineElement, _mfpOn, _mfpTrigger, _prevContentType, _prevStatus, _putInlineElementsBack, _removeAjaxCursor, _window, _wrapClasses;

  CLOSE_EVENT = "Close";

  BEFORE_CLOSE_EVENT = "BeforeClose";

  AFTER_CLOSE_EVENT = "AfterClose";

  BEFORE_APPEND_EVENT = "BeforeAppend";

  MARKUP_PARSE_EVENT = "MarkupParse";

  OPEN_EVENT = "Open";

  CHANGE_EVENT = "Change";

  NS = "tpl-block-mfp";

  EVENT_NS = "." + NS;

  READY_CLASS = "tpl-block-mfp-ready";

  REMOVING_CLASS = "tpl-block-mfp-removing";

  PREVENT_CLOSE_CLASS = "tpl-block-mfp-prevent-close";

  /*
  Private vars
  */


  mfp = void 0;

  MagnificPopup = function() {};

  _isJQ = !!window.jQuery;

  _prevStatus = void 0;

  _window = $(window);

  _body = void 0;

  _document = void 0;

  _prevContentType = void 0;

  _wrapClasses = void 0;

  _currPopupType = void 0;

  /*
  Private functions
  */


  _mfpOn = function(name, f) {
    return mfp.ev.on(NS + name + EVENT_NS, f);
  };

  _getEl = function(className, appendTo, html, raw) {
    var el;
    el = document.createElement("div");
    el.className = "tpl-block-mfp-" + className;
    if (html) {
      el.innerHTML = html;
    }
    if (!raw) {
      el = $(el);
      if (appendTo) {
        el.appendTo(appendTo);
      }
    } else {
      if (appendTo) {
        appendTo.appendChild(el);
      }
    }
    return el;
  };

  _mfpTrigger = function(e, data) {
    mfp.ev.triggerHandler(NS + e, data);
    if (mfp.st.callbacks) {
      e = e.charAt(0).toLowerCase() + e.slice(1);
      if (mfp.st.callbacks[e]) {
        return mfp.st.callbacks[e].apply(mfp, ($.isArray(data) ? data : [data]));
      }
    }
  };

  _getCloseBtn = function(type) {
    if (type !== _currPopupType || !mfp.currTemplate.closeBtn) {
      mfp.currTemplate.closeBtn = $(mfp.st.closeMarkup.replace("%title%", mfp.st.tClose));
      _currPopupType = type;
    }
    return mfp.currTemplate.closeBtn;
  };

  _checkInstance = function() {
    if (!$.magnificPopup.instance) {
      mfp = new MagnificPopup();
      mfp.init();
      return $.magnificPopup.instance = mfp;
    }
  };

  supportsTransitions = function() {
    var s, v;
    s = document.createElement("p").style;
    v = ["ms", "O", "Moz", "Webkit"];
    if (s["transition"] !== undefined) {
      return true;
    }
    if ((function() {
      var _results;
      _results = [];
      while (v.length) {
        _results.push(v.pop() + "Transition" in s);
      }
      return _results;
    })()) {
      return true;
    }
    return false;
  };

  /*
  Public functions
  */


  MagnificPopup.prototype = {
    constructor: MagnificPopup,
    /*
    Initializes Magnific Popup plugin.
    This function is triggered only once when $.fn.magnificPopup or $.magnificPopup is executed
    */

    init: function() {
      var appVersion;
      appVersion = navigator.appVersion;
      mfp.isIE7 = appVersion.indexOf("MSIE 7.") !== -1;
      mfp.isIE8 = appVersion.indexOf("MSIE 8.") !== -1;
      mfp.isLowIE = mfp.isIE7 || mfp.isIE8;
      mfp.isAndroid = /android/g.test(appVersion);
      mfp.isIOS = /iphone|ipad|ipod/g.test(appVersion);
      mfp.supportsTransition = supportsTransitions();
      mfp.probablyMobile = mfp.isAndroid || mfp.isIOS || /(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent);
      _document = $(document);
      return mfp.popupsCache = {};
    },
    /*
    Opens popup
    @param  data [description]
    */

    open: function(data) {
      var classesToadd, i, item, items, modules, n, s, windowHeight, windowStyles;
      if (!_body) {
        _body = $(document.body);
      }
      i = void 0;
      if (data.isObj === false) {
        mfp.items = data.items.toArray();
        mfp.index = 0;
        items = data.items;
        item = void 0;
        i = 0;
        while (i < items.length) {
          item = items[i];
          if (item.parsed) {
            item = item.el[0];
          }
          if (item === data.el[0]) {
            mfp.index = i;
            break;
          }
          i++;
        }
      } else {
        mfp.items = ($.isArray(data.items) ? data.items : [data.items]);
        mfp.index = data.index || 0;
      }
      if (mfp.isOpen) {
        mfp.updateItemHTML();
        return;
      }
      mfp.types = [];
      _wrapClasses = "";
      if (data.mainEl && data.mainEl.length) {
        mfp.ev = data.mainEl.eq(0);
      } else {
        mfp.ev = _document;
      }
      if (data.key) {
        if (!mfp.popupsCache[data.key]) {
          mfp.popupsCache[data.key] = {};
        }
        mfp.currTemplate = mfp.popupsCache[data.key];
      } else {
        mfp.currTemplate = {};
      }
      mfp.st = $.extend(true, {}, $.magnificPopup.defaults, data);
      mfp.fixedContentPos = (mfp.st.fixedContentPos === "auto" ? !mfp.probablyMobile : mfp.st.fixedContentPos);
      if (mfp.st.modal) {
        mfp.st.closeOnContentClick = false;
        mfp.st.closeOnBgClick = false;
        mfp.st.showCloseBtn = false;
        mfp.st.enableEscapeKey = false;
      }
      if (!mfp.bgOverlay) {
        mfp.bgOverlay = _getEl("bg").on("click" + EVENT_NS, function() {
          return mfp.close();
        });
        mfp.wrap = _getEl("wrap").attr("tabindex", -1).on("click" + EVENT_NS, function(e) {
          if (mfp._checkIfClose(e.target)) {
            return mfp.close();
          }
        });
        mfp.container = _getEl("container", mfp.wrap);
      }
      mfp.contentContainer = _getEl("content");
      if (mfp.st.preloader) {
        mfp.preloader = _getEl("preloader", mfp.container, mfp.st.tLoading);
      }
      modules = $.magnificPopup.modules;
      i = 0;
      while (i < modules.length) {
        n = modules[i];
        n = n.charAt(0).toUpperCase() + n.slice(1);
        mfp["init" + n].call(mfp);
        i++;
      }
      _mfpTrigger("BeforeOpen");
      if (mfp.st.showCloseBtn) {
        if (!mfp.st.closeBtnInside) {
          mfp.wrap.append(_getCloseBtn());
        } else {
          _mfpOn(MARKUP_PARSE_EVENT, function(e, template, values, item) {
            return values.close_replaceWith = _getCloseBtn(item.type);
          });
          _wrapClasses += " tpl-block-mfp-close-btn-in";
        }
      }
      if (mfp.st.alignTop) {
        _wrapClasses += " tpl-block-mfp-align-top";
      }
      if (mfp.fixedContentPos) {
        mfp.wrap.css({
          overflow: mfp.st.overflowY,
          overflowX: "hidden",
          overflowY: mfp.st.overflowY
        });
      } else {
        mfp.wrap.css({
          top: _window.scrollTop(),
          position: "absolute"
        });
      }
      if (mfp.st.fixedBgPos === false || (mfp.st.fixedBgPos === "auto" && !mfp.fixedContentPos)) {
        mfp.bgOverlay.css({
          height: _document.height(),
          position: "absolute"
        });
      }
      if (mfp.st.enableEscapeKey) {
        _document.on("keyup" + EVENT_NS, function(e) {
          if (e.keyCode === 27) {
            return mfp.close();
          }
        });
      }
      _window.on("resize" + EVENT_NS, function() {
        return mfp.updateSize();
      });
      if (!mfp.st.closeOnContentClick) {
        _wrapClasses += " tpl-block-mfp-auto-cursor";
      }
      if (_wrapClasses) {
        mfp.wrap.addClass(_wrapClasses);
      }
      windowHeight = mfp.wH = _window.height();
      windowStyles = {};
      if (mfp.fixedContentPos) {
        if (mfp._hasScrollBar(windowHeight)) {
          s = mfp._getScrollbarSize();
          if (s) {
            windowStyles.marginRight = s;
          }
        }
      }
      if (mfp.fixedContentPos) {
        if (!mfp.isIE7) {
          windowStyles.overflow = "hidden";
        } else {
          $("body, html").css("overflow", "hidden");
        }
      }
      classesToadd = mfp.st.mainClass;
      if (mfp.isIE7) {
        classesToadd += " tpl-block-mfp-ie7";
      }
      if (classesToadd) {
        mfp._addClassToMFP(classesToadd);
      }
      mfp.updateItemHTML();
      _mfpTrigger("BuildControls");
      $("html").css(windowStyles);
      mfp.bgOverlay.add(mfp.wrap).prependTo(mfp.st.prependTo || _body);
      mfp._lastFocusedEl = document.activeElement;
      setTimeout((function() {
        if (mfp.content) {
          mfp._addClassToMFP(READY_CLASS);
          mfp._setFocus();
        } else {
          mfp.bgOverlay.addClass(READY_CLASS);
        }
        return _document.on("focusin" + EVENT_NS, mfp._onFocusIn);
      }), 16);
      mfp.isOpen = true;
      mfp.updateSize(windowHeight);
      _mfpTrigger(OPEN_EVENT);
      return data;
    },
    /*
    Closes the popup
    */

    close: function() {
      if (!mfp.isOpen) {
        return;
      }
      _mfpTrigger(BEFORE_CLOSE_EVENT);
      mfp.isOpen = false;
      if (mfp.st.removalDelay && !mfp.isLowIE && mfp.supportsTransition) {
        mfp._addClassToMFP(REMOVING_CLASS);
        return setTimeout((function() {
          return mfp._close();
        }), mfp.st.removalDelay);
      } else {
        return mfp._close();
      }
    },
    /*
    Helper for close() function
    */

    _close: function() {
      var classesToRemove, windowStyles;
      _mfpTrigger(CLOSE_EVENT);
      classesToRemove = REMOVING_CLASS + " " + READY_CLASS + " ";
      mfp.bgOverlay.detach();
      mfp.wrap.detach();
      mfp.container.empty();
      if (mfp.st.mainClass) {
        classesToRemove += mfp.st.mainClass + " ";
      }
      mfp._removeClassFromMFP(classesToRemove);
      if (mfp.fixedContentPos) {
        windowStyles = {
          marginRight: ""
        };
        if (mfp.isIE7) {
          $("body, html").css("overflow", "");
        } else {
          windowStyles.overflow = "";
        }
        $("html").css(windowStyles);
      }
      _document.off("keyup" + EVENT_NS + " focusin" + EVENT_NS);
      mfp.ev.off(EVENT_NS);
      mfp.wrap.attr("class", "tpl-block-mfp-wrap").removeAttr("style");
      mfp.bgOverlay.attr("class", "tpl-block-mfp-bg");
      mfp.container.attr("class", "tpl-block-mfp-container");
      if (mfp.st.showCloseBtn && (!mfp.st.closeBtnInside || mfp.currTemplate[mfp.currItem.type] === true) ? mfp.currTemplate.closeBtn : void 0) {
        mfp.currTemplate.closeBtn.detach();
      }
      if (mfp._lastFocusedEl) {
        $(mfp._lastFocusedEl).focus();
      }
      mfp.currItem = null;
      mfp.content = null;
      mfp.currTemplate = null;
      mfp.prevHeight = 0;
      return _mfpTrigger(AFTER_CLOSE_EVENT);
    },
    updateSize: function(winHeight) {
      var height, zoomLevel;
      if (mfp.isIOS) {
        zoomLevel = document.documentElement.clientWidth / window.innerWidth;
        height = window.innerHeight * zoomLevel;
        mfp.wrap.css("height", height);
        mfp.wH = height;
      } else {
        mfp.wH = winHeight || _window.height();
      }
      if (!mfp.fixedContentPos) {
        mfp.wrap.css("height", mfp.wH);
      }
      return _mfpTrigger("Resize");
    },
    /*
    Set content of popup based on current index
    */

    updateItemHTML: function() {
      var item, markup, newContent, type;
      item = mfp.items[mfp.index];
      mfp.contentContainer.detach();
      if (mfp.content) {
        mfp.content.detach();
      }
      if (!item.parsed) {
        item = mfp.parseEl(mfp.index);
      }
      type = item.type;
      _mfpTrigger("BeforeChange", [(mfp.currItem ? mfp.currItem.type : ""), type]);
      mfp.currItem = item;
      if (!mfp.currTemplate[type]) {
        markup = (mfp.st[type] ? mfp.st[type].markup : false);
        _mfpTrigger("FirstMarkupParse", markup);
        if (markup) {
          mfp.currTemplate[type] = $(markup);
        } else {
          mfp.currTemplate[type] = true;
        }
      }
      if (_prevContentType && _prevContentType !== item.type) {
        mfp.container.removeClass("tpl-block-mfp-" + _prevContentType + "-holder");
      }
      newContent = mfp["get" + type.charAt(0).toUpperCase() + type.slice(1)](item, mfp.currTemplate[type]);
      mfp.appendContent(newContent, type);
      item.preloaded = true;
      _mfpTrigger(CHANGE_EVENT, item);
      _prevContentType = item.type;
      mfp.container.prepend(mfp.contentContainer);
      return _mfpTrigger("AfterChange");
    },
    /*
    Set HTML content of popup
    */

    appendContent: function(newContent, type) {
      mfp.content = newContent;
      if (newContent) {
        if (mfp.st.showCloseBtn && mfp.st.closeBtnInside && mfp.currTemplate[type] === true) {
          if (!mfp.content.find(".tpl-block-mfp-close").length) {
            mfp.content.append(_getCloseBtn());
          }
        } else {
          mfp.content = newContent;
        }
      } else {
        mfp.content = "";
      }
      _mfpTrigger(BEFORE_APPEND_EVENT);
      mfp.container.addClass("tpl-block-mfp-" + type + "-holder");
      return mfp.contentContainer.append(mfp.content);
    },
    /*
    Creates Magnific Popup data object based on given data
    @param  {int} index Index of item to parse
    */

    parseEl: function(index) {
      var i, item, type, types;
      item = mfp.items[index];
      type = item.type;
      if (item.tagName) {
        item = {
          el: $(item)
        };
      } else {
        item = {
          data: item,
          src: item.src
        };
      }
      if (item.el) {
        types = mfp.types;
        i = 0;
        while (i < types.length) {
          if (item.el.hasClass("tpl-block-mfp-" + types[i])) {
            type = types[i];
            break;
          }
          i++;
        }
        item.src = item.el.attr("data-mfp-src");
        if (!item.src) {
          item.src = item.el.attr("href");
        }
      }
      item.type = type || mfp.st.type || "inline";
      item.index = index;
      item.parsed = true;
      mfp.items[index] = item;
      _mfpTrigger("ElementParse", item);
      return mfp.items[index];
    },
    /*
    Initializes single popup or a group of popups
    */

    addGroup: function(el, options) {
      var eHandler, eName;
      eHandler = function(e) {
        e.mfpEl = this;
        return mfp._openClick(e, el, options);
      };
      if (!options) {
        options = {};
      }
      eName = "click.magnificPopup";
      options.mainEl = el;
      if (options.items) {
        options.isObj = true;
        return el.off(eName).on(eName, eHandler);
      } else {
        options.isObj = false;
        if (options.delegate) {
          return el.off(eName).on(eName, options.delegate, eHandler);
        } else {
          options.items = el;
          return el.off(eName).on(eName, eHandler);
        }
      }
    },
    _openClick: function(e, el, options) {
      var disableOn, midClick;
      midClick = (options.midClick !== undefined ? options.midClick : $.magnificPopup.defaults.midClick);
      if (!midClick && (e.which === 2 || e.ctrlKey || e.metaKey)) {
        return;
      }
      disableOn = (options.disableOn !== undefined ? options.disableOn : $.magnificPopup.defaults.disableOn);
      if (disableOn) {
        if ($.isFunction(disableOn)) {
          if (!disableOn.call(mfp)) {
            return true;
          }
        } else {
          if (_window.width() < disableOn) {
            return true;
          }
        }
      }
      if (e.type) {
        e.preventDefault();
        if (mfp.isOpen) {
          e.stopPropagation();
        }
      }
      options.el = $(e.mfpEl);
      if (options.delegate) {
        options.items = el.find(options.delegate);
      }
      return mfp.open(options);
    },
    /*
    Updates text on preloader
    */

    updateStatus: function(status, text) {
      var data;
      if (mfp.preloader) {
        if (_prevStatus !== status) {
          mfp.container.removeClass("tpl-block-mfp-s-" + _prevStatus);
        }
        if (!text && status === "loading") {
          text = mfp.st.tLoading;
        }
        data = {
          status: status,
          text: text
        };
        _mfpTrigger("UpdateStatus", data);
        status = data.status;
        text = data.text;
        mfp.preloader.html(text);
        mfp.preloader.find("a").on("click", function(e) {
          return e.stopImmediatePropagation();
        });
        mfp.container.addClass("tpl-block-mfp-s-" + status);
        return _prevStatus = status;
      }
    },
    _checkIfClose: function(target) {
      var closeOnBg, closeOnContent;
      if ($(target).hasClass(PREVENT_CLOSE_CLASS)) {
        return;
      }
      closeOnContent = mfp.st.closeOnContentClick;
      closeOnBg = mfp.st.closeOnBgClick;
      if (closeOnContent && closeOnBg) {
        return true;
      } else {
        if (!mfp.content || $(target).hasClass("tpl-block-mfp-close") || (mfp.preloader && target === mfp.preloader[0])) {
          return true;
        }
        if (target !== mfp.content[0] && !$.contains(mfp.content[0], target)) {
          if (closeOnBg ? $.contains(document, target) : void 0) {
            return true;
          }
        } else {
          if (closeOnContent) {
            return true;
          }
        }
      }
      return false;
    },
    _addClassToMFP: function(cName) {
      mfp.bgOverlay.addClass(cName);
      return mfp.wrap.addClass(cName);
    },
    _removeClassFromMFP: function(cName) {
      this.bgOverlay.removeClass(cName);
      return mfp.wrap.removeClass(cName);
    },
    _hasScrollBar: function(winHeight) {
      return (mfp.isIE7 ? _document.height() : document.body.scrollHeight) > (winHeight || _window.height());
    },
    _setFocus: function() {
      return (mfp.st.focus ? mfp.content.find(mfp.st.focus).eq(0) : mfp.wrap).focus();
    },
    _onFocusIn: function(e) {
      if (e.target !== mfp.wrap[0] && !$.contains(mfp.wrap[0], e.target)) {
        mfp._setFocus();
        return false;
      }
    },
    _parseMarkup: function(template, values, item) {
      var arr;
      arr = void 0;
      if (item.data) {
        values = $.extend(item.data, values);
      }
      _mfpTrigger(MARKUP_PARSE_EVENT, [template, values, item]);
      return $.each(values, function(key, value) {
        var attr, el;
        if (value === undefined || value === false) {
          return true;
        }
        arr = key.split("_");
        if (arr.length > 1) {
          el = template.find(EVENT_NS + "-" + arr[0]);
          if (el.length > 0) {
            attr = arr[1];
            if (attr === "replaceWith") {
              if (el[0] !== value[0]) {
                return el.replaceWith(value);
              }
            } else if (attr === "img") {
              if (el.is("img")) {
                return el.attr("src", value);
              } else {
                return el.replaceWith("<img src=\"" + value + "\" class=\"" + el.attr("class") + "\" />");
              }
            } else {
              return el.attr(arr[1], value);
            }
          }
        } else {
          return template.find(EVENT_NS + "-" + key).html(value);
        }
      });
    },
    _getScrollbarSize: function() {
      var scrollDiv;
      if (mfp.scrollbarSize === undefined) {
        scrollDiv = document.createElement("div");
        scrollDiv.id = "tpl-block-mfp-sbm";
        scrollDiv.style.cssText = "width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;";
        document.body.appendChild(scrollDiv);
        mfp.scrollbarSize = scrollDiv.offsetWidth - scrollDiv.clientWidth;
        document.body.removeChild(scrollDiv);
      }
      return mfp.scrollbarSize;
    }
  };

  /*
  Public static functions
  */


  $.magnificPopup = {
    instance: null,
    proto: MagnificPopup.prototype,
    modules: [],
    open: function(options, index) {
      _checkInstance();
      if (!options) {
        options = {};
      } else {
        options = $.extend(true, {}, options);
      }
      options.isObj = true;
      options.index = index || 0;
      return this.instance.open(options);
    },
    close: function() {
      return $.magnificPopup.instance && $.magnificPopup.instance.close();
    },
    registerModule: function(name, module) {
      if (module.options) {
        $.magnificPopup.defaults[name] = module.options;
      }
      $.extend(this.proto, module.proto);
      return this.modules.push(name);
    },
    defaults: {
      disableOn: 0,
      key: null,
      midClick: false,
      mainClass: "",
      preloader: true,
      focus: "",
      closeOnContentClick: false,
      closeOnBgClick: true,
      closeBtnInside: true,
      showCloseBtn: true,
      enableEscapeKey: true,
      modal: false,
      alignTop: false,
      removalDelay: 0,
      prependTo: null,
      fixedContentPos: "auto",
      fixedBgPos: "auto",
      overflowY: "auto",
      closeMarkup: "<button title=\"%title%\" type=\"button\" class=\"tpl-block-mfp-close\">&times;</button>",
      tClose: "Close (Esc)",
      tLoading: "Loading..."
    }
  };

  $.fn.magnificPopup = function(options) {
    var index, itemOpts, items, jqEl;
    _checkInstance();
    jqEl = $(this);
    if (typeof options === "string") {
      if (options === "open") {
        items = void 0;
        itemOpts = (_isJQ ? jqEl.data("magnificPopup") : jqEl[0].magnificPopup);
        index = parseInt(arguments_[1], 10) || 0;
        if (itemOpts.items) {
          items = itemOpts.items[index];
        } else {
          items = jqEl;
          if (itemOpts.delegate) {
            items = items.find(itemOpts.delegate);
          }
          items = items.eq(index);
        }
        mfp._openClick({
          mfpEl: items
        }, jqEl, itemOpts);
      } else {
        if (mfp.isOpen) {
          mfp[options].apply(mfp, Array.prototype.slice.call(arguments_, 1));
        }
      }
    } else {
      options = $.extend(true, {}, options);
      if (_isJQ) {
        jqEl.data("magnificPopup", options);
      } else {
        jqEl[0].magnificPopup = options;
      }
      mfp.addGroup(jqEl, options);
    }
    return jqEl;
  };

  INLINE_NS = "inline";

  _hiddenClass = void 0;

  _inlinePlaceholder = void 0;

  _lastInlineElement = void 0;

  _putInlineElementsBack = function() {
    if (_lastInlineElement) {
      _inlinePlaceholder.after(_lastInlineElement.addClass(_hiddenClass)).detach();
      return _lastInlineElement = null;
    }
  };

  $.magnificPopup.registerModule(INLINE_NS, {
    options: {
      hiddenClass: "hide",
      markup: "",
      tNotFound: "Content not found"
    },
    proto: {
      initInline: function() {
        mfp.types.push(INLINE_NS);
        return _mfpOn(CLOSE_EVENT + "." + INLINE_NS, function() {
          return _putInlineElementsBack();
        });
      },
      getInline: function(item, template) {
        var el, inlineSt, parent;
        _putInlineElementsBack();
        if (item.src) {
          inlineSt = mfp.st.inline;
          el = $(item.src);
          if (el.length) {
            parent = el[0].parentNode;
            if (parent && parent.tagName) {
              if (!_inlinePlaceholder) {
                _hiddenClass = inlineSt.hiddenClass;
                _inlinePlaceholder = _getEl(_hiddenClass);
                _hiddenClass = "tpl-block-mfp-" + _hiddenClass;
              }
              _lastInlineElement = el.after(_inlinePlaceholder).detach().removeClass(_hiddenClass);
            }
            mfp.updateStatus("ready");
          } else {
            mfp.updateStatus("error", inlineSt.tNotFound);
            el = $("<div>");
          }
          item.inlineElement = el;
          return el;
        }
        mfp.updateStatus("ready");
        mfp._parseMarkup(template, {}, item);
        return template;
      }
    }
  });

  AJAX_NS = "ajax";

  _ajaxCur = void 0;

  _removeAjaxCursor = function() {
    if (_ajaxCur) {
      return _body.removeClass(_ajaxCur);
    }
  };

  _destroyAjaxRequest = function() {
    _removeAjaxCursor();
    if (mfp.req) {
      return mfp.req.abort();
    }
  };

  $.magnificPopup.registerModule(AJAX_NS, {
    options: {
      settings: null,
      cursor: "tpl-block-mfp-ajax-cur",
      tError: "<a href=\"%url%\">The content</a> could not be loaded."
    },
    proto: {
      initAjax: function() {
        mfp.types.push(AJAX_NS);
        _ajaxCur = mfp.st.ajax.cursor;
        _mfpOn(CLOSE_EVENT + "." + AJAX_NS, _destroyAjaxRequest);
        return _mfpOn("BeforeChange." + AJAX_NS, _destroyAjaxRequest);
      },
      getAjax: function(item) {
        var opts;
        if (_ajaxCur) {
          _body.addClass(_ajaxCur);
        }
        mfp.updateStatus("loading");
        opts = $.extend({
          url: item.src,
          success: function(data, textStatus, jqXHR) {
            var temp;
            temp = {
              data: data,
              xhr: jqXHR
            };
            _mfpTrigger("ParseAjax", temp);
            mfp.appendContent($(temp.data), AJAX_NS);
            item.finished = true;
            _removeAjaxCursor();
            mfp._setFocus();
            setTimeout((function() {
              return mfp.wrap.addClass(READY_CLASS);
            }), 16);
            mfp.updateStatus("ready");
            return _mfpTrigger("AjaxContentAdded");
          },
          error: function() {
            _removeAjaxCursor();
            item.finished = item.loadError = true;
            return mfp.updateStatus("error", mfp.st.ajax.tError.replace("%url%", item.src));
          }
        }, mfp.st.ajax.settings);
        mfp.req = $.ajax(opts);
        return "";
      }
    }
  });

  _imgInterval = void 0;

  _getTitle = function(item) {
    var src;
    if (item.data && item.data.title !== undefined) {
      return item.data.title;
    }
    src = mfp.st.image.titleSrc;
    if (src) {
      if ($.isFunction(src)) {
        return src.call(mfp, item);
      } else {
        if (item.el) {
          return item.el.attr(src) || "";
        }
      }
    }
    return "";
  };

  $.magnificPopup.registerModule("image", {
    options: {
      markup: "<div class=\"tpl-block-mfp-figure\">" + "<div class=\"tpl-block-mfp-close\"></div>" + "<figure>" + "<div class=\"tpl-block-mfp-img\"></div>" + "<figcaption>" + "<div class=\"tpl-block-mfp-bottom-bar\">" + "<div class=\"tpl-block-mfp-title\"></div>" + "<div class=\"tpl-block-mfp-counter\"></div>" + "</div>" + "</figcaption>" + "</figure>" + "</div>",
      cursor: "tpl-block-mfp-zoom-out-cur",
      titleSrc: "title",
      verticalFit: true,
      tError: "<a href=\"%url%\">The image</a> could not be loaded."
    },
    proto: {
      initImage: function() {
        var imgSt, ns;
        imgSt = mfp.st.image;
        ns = ".image";
        mfp.types.push("image");
        _mfpOn(OPEN_EVENT + ns, function() {
          if (mfp.currItem.type === "image" && imgSt.cursor) {
            return _body.addClass(imgSt.cursor);
          }
        });
        _mfpOn(CLOSE_EVENT + ns, function() {
          if (imgSt.cursor) {
            _body.removeClass(imgSt.cursor);
          }
          return _window.off("resize" + EVENT_NS);
        });
        _mfpOn("Resize" + ns, mfp.resizeImage);
        if (mfp.isLowIE) {
          return _mfpOn("AfterChange", mfp.resizeImage);
        }
      },
      resizeImage: function() {
        var decr, item;
        item = mfp.currItem;
        if (!item || !item.img) {
          return;
        }
        if (mfp.st.image.verticalFit) {
          decr = 0;
          if (mfp.isLowIE) {
            decr = parseInt(item.img.css("padding-top"), 10) + parseInt(item.img.css("padding-bottom"), 10);
          }
          return item.img.css("max-height", mfp.wH - decr);
        }
      },
      _onImageHasSize: function(item) {
        if (item.img) {
          item.hasSize = true;
          if (_imgInterval) {
            clearInterval(_imgInterval);
          }
          item.isCheckingImgSize = false;
          _mfpTrigger("ImageHasSize", item);
          if (item.imgHidden) {
            if (mfp.content) {
              mfp.content.removeClass("tpl-block-mfp-loading");
            }
            return item.imgHidden = false;
          }
        }
      },
      /*
      Function that loops until the image has size to display elements that rely on it asap
      */

      findImageSize: function(item) {
        var counter, img, mfpSetInterval;
        counter = 0;
        img = item.img[0];
        mfpSetInterval = function(delay) {
          if (_imgInterval) {
            clearInterval(_imgInterval);
          }
          return _imgInterval = setInterval(function() {
            if (img.naturalWidth > 0) {
              mfp._onImageHasSize(item);
              return;
            }
            if (counter > 200) {
              clearInterval(_imgInterval);
            }
            counter++;
            if (counter === 3) {
              return mfpSetInterval(10);
            } else if (counter === 40) {
              return mfpSetInterval(50);
            } else {
              if (counter === 100) {
                return mfpSetInterval(500);
              }
            }
          }, delay);
        };
        return mfpSetInterval(1);
      },
      getImage: function(item, template) {
        var el, guard, img, imgSt, onLoadComplete, onLoadError;
        guard = 0;
        onLoadComplete = function() {
          if (item) {
            if (item.img[0].complete) {
              item.img.off(".mfploader");
              if (item === mfp.currItem) {
                mfp._onImageHasSize(item);
                mfp.updateStatus("ready");
              }
              item.hasSize = true;
              item.loaded = true;
              return _mfpTrigger("ImageLoadComplete");
            } else {
              guard++;
              if (guard < 200) {
                return setTimeout(onLoadComplete, 100);
              } else {
                return onLoadError();
              }
            }
          }
        };
        onLoadError = function() {
          if (item) {
            item.img.off(".mfploader");
            if (item === mfp.currItem) {
              mfp._onImageHasSize(item);
              mfp.updateStatus("error", imgSt.tError.replace("%url%", item.src));
            }
            item.hasSize = true;
            item.loaded = true;
            return item.loadError = true;
          }
        };
        imgSt = mfp.st.image;
        el = template.find(".tpl-block-mfp-img");
        if (el.length) {
          img = document.createElement("img");
          img.className = "tpl-block-mfp-img";
          item.img = $(img).on("load.mfploader", onLoadComplete).on("error.mfploader", onLoadError);
          img.src = item.src;
          if (el.is("img")) {
            item.img = item.img.clone();
          }
          img = item.img[0];
          if (img.naturalWidth > 0) {
            item.hasSize = true;
          } else {
            if (!img.width) {
              item.hasSize = false;
            }
          }
        }
        mfp._parseMarkup(template, {
          title: _getTitle(item),
          img_replaceWith: item.img
        }, item);
        mfp.resizeImage();
        if (item.hasSize) {
          if (_imgInterval) {
            clearInterval(_imgInterval);
          }
          if (item.loadError) {
            template.addClass("tpl-block-mfp-loading");
            mfp.updateStatus("error", imgSt.tError.replace("%url%", item.src));
          } else {
            template.removeClass("tpl-block-mfp-loading");
            mfp.updateStatus("ready");
          }
          return template;
        }
        mfp.updateStatus("loading");
        item.loading = true;
        if (!item.hasSize) {
          item.imgHidden = true;
          template.addClass("tpl-block-mfp-loading");
          mfp.findImageSize(item);
        }
        return template;
      }
    }
  });

  hasMozTransform = void 0;

  getHasMozTransform = function() {
    if (hasMozTransform === undefined) {
      hasMozTransform = document.createElement("p").style.MozTransform !== undefined;
    }
    return hasMozTransform;
  };

  $.magnificPopup.registerModule("zoom", {
    options: {
      enabled: false,
      easing: "ease-in-out",
      duration: 300,
      opener: function(element) {
        if (element.is("img")) {
          return element;
        } else {
          return element.find("img");
        }
      }
    },
    proto: {
      initZoom: function() {
        var animatedImg, duration, getElToAnimate, image, ns, openTimeout, showMainContent, zoomSt;
        zoomSt = mfp.st.zoom;
        ns = ".zoom";
        image = void 0;
        if (!zoomSt.enabled || !mfp.supportsTransition) {
          return;
        }
        duration = zoomSt.duration;
        getElToAnimate = function(image) {
          var cssObj, newImg, t, transition;
          newImg = image.clone().removeAttr("style").removeAttr("class").addClass("tpl-block-mfp-animated-image");
          transition = "all " + (zoomSt.duration / 1000) + "s " + zoomSt.easing;
          cssObj = {
            position: "fixed",
            zIndex: 9999,
            left: 0,
            top: 0,
            "-webkit-backface-visibility": "hidden"
          };
          t = "transition";
          cssObj["-webkit-" + t] = cssObj["-moz-" + t] = cssObj["-o-" + t] = cssObj[t] = transition;
          newImg.css(cssObj);
          return newImg;
        };
        showMainContent = function() {
          return mfp.content.css("visibility", "visible");
        };
        openTimeout = void 0;
        animatedImg = void 0;
        _mfpOn("BuildControls" + ns, function() {
          if (mfp._allowZoom()) {
            clearTimeout(openTimeout);
            mfp.content.css("visibility", "hidden");
            image = mfp._getItemToZoom();
            if (!image) {
              showMainContent();
              return;
            }
            animatedImg = getElToAnimate(image);
            animatedImg.css(mfp._getOffset());
            mfp.wrap.append(animatedImg);
            return openTimeout = setTimeout(function() {
              animatedImg.css(mfp._getOffset(true));
              return openTimeout = setTimeout(function() {
                showMainContent();
                return setTimeout((function() {
                  animatedImg.remove();
                  image = animatedImg = null;
                  return _mfpTrigger("ZoomAnimationEnded");
                }), 16);
              }, duration);
            }, 16);
          }
        });
        _mfpOn(BEFORE_CLOSE_EVENT + ns, function() {
          if (mfp._allowZoom()) {
            clearTimeout(openTimeout);
            mfp.st.removalDelay = duration;
            if (!image) {
              image = mfp._getItemToZoom();
              if (!image) {
                return;
              }
              animatedImg = getElToAnimate(image);
            }
            animatedImg.css(mfp._getOffset(true));
            mfp.wrap.append(animatedImg);
            mfp.content.css("visibility", "hidden");
            return setTimeout((function() {
              return animatedImg.css(mfp._getOffset());
            }), 16);
          }
        });
        return _mfpOn(CLOSE_EVENT + ns, function() {
          if (mfp._allowZoom()) {
            showMainContent();
            if (animatedImg) {
              animatedImg.remove();
            }
            return image = null;
          }
        });
      },
      _allowZoom: function() {
        return mfp.currItem.type === "image";
      },
      _getItemToZoom: function() {
        if (mfp.currItem.hasSize) {
          return mfp.currItem.img;
        } else {
          return false;
        }
      },
      _getOffset: function(isLarge) {
        var el, obj, offset, paddingBottom, paddingTop;
        el = void 0;
        if (isLarge) {
          el = mfp.currItem.img;
        } else {
          el = mfp.st.zoom.opener(mfp.currItem.el || mfp.currItem);
        }
        offset = el.offset();
        paddingTop = parseInt(el.css("padding-top"), 10);
        paddingBottom = parseInt(el.css("padding-bottom"), 10);
        offset.top -= $(window).scrollTop() - paddingTop;
        obj = {
          width: el.width(),
          height: (_isJQ ? el.innerHeight() : el[0].offsetHeight) - paddingBottom - paddingTop
        };
        if (getHasMozTransform()) {
          obj["-moz-transform"] = obj["transform"] = "translate(" + offset.left + "px," + offset.top + "px)";
        } else {
          obj.left = offset.left;
          obj.top = offset.top;
        }
        return obj;
      }
    }
  });

  IFRAME_NS = "iframe";

  _emptyPage = "//about:blank";

  _fixIframeBugs = function(isShowing) {
    var el;
    if (mfp.currTemplate[IFRAME_NS]) {
      el = mfp.currTemplate[IFRAME_NS].find("iframe");
      if (el.length) {
        if (!isShowing) {
          el[0].src = _emptyPage;
        }
        if (mfp.isIE8) {
          return el.css("display", (isShowing ? "block" : "none"));
        }
      }
    }
  };

  $.magnificPopup.registerModule(IFRAME_NS, {
    options: {
      markup: "<div class=\"tpl-block-mfp-iframe-scaler\">" + "<div class=\"tpl-block-mfp-close\"></div>" + "<iframe class=\"tpl-block-mfp-iframe\" src=\"//about:blank\" frameborder=\"0\" allowfullscreen></iframe>" + "</div>",
      srcAction: "iframe_src",
      patterns: {
        youtube: {
          index: "youtube.com",
          id: "v=",
          src: "//www.youtube.com/embed/%id%?autoplay=1"
        },
        vimeo: {
          index: "vimeo.com/",
          id: "/",
          src: "//player.vimeo.com/video/%id%?autoplay=1"
        },
        gmaps: {
          index: "//maps.google.",
          src: "%id%&output=embed"
        }
      }
    },
    proto: {
      initIframe: function() {
        mfp.types.push(IFRAME_NS);
        _mfpOn("BeforeChange", function(e, prevType, newType) {
          if (prevType !== newType) {
            if (prevType === IFRAME_NS) {
              return _fixIframeBugs();
            } else {
              if (newType === IFRAME_NS) {
                return _fixIframeBugs(true);
              }
            }
          }
        });
        return _mfpOn(CLOSE_EVENT + "." + IFRAME_NS, function() {
          return _fixIframeBugs();
        });
      },
      getIframe: function(item, template) {
        var dataObj, embedSrc, iframeSt;
        embedSrc = item.src;
        iframeSt = mfp.st.iframe;
        $.each(iframeSt.patterns, function() {
          if (embedSrc.indexOf(this.index) > -1) {
            if (this.id) {
              if (typeof this.id === "string") {
                embedSrc = embedSrc.substr(embedSrc.lastIndexOf(this.id) + this.id.length, embedSrc.length);
              } else {
                embedSrc = this.id.call(this, embedSrc);
              }
            }
            embedSrc = this.src.replace("%id%", embedSrc);
            return false;
          }
        });
        dataObj = {};
        if (iframeSt.srcAction) {
          dataObj[iframeSt.srcAction] = embedSrc;
        }
        mfp._parseMarkup(template, dataObj, item);
        mfp.updateStatus("ready");
        return template;
      }
    }
  });

  RETINA_NS = "retina";

  $.magnificPopup.registerModule(RETINA_NS, {
    options: {
      replaceSrc: function(item) {
        return item.src.replace(/\.\w+$/, function(m) {
          return "@2x" + m;
        });
      },
      ratio: 1
    },
    proto: {
      initRetina: function() {
        var ratio, st;
        if (window.devicePixelRatio > 1) {
          st = mfp.st.retina;
          ratio = st.ratio;
          ratio = (!isNaN(ratio) ? ratio : ratio());
          if (ratio > 1) {
            _mfpOn("ImageHasSize" + "." + RETINA_NS, function(e, item) {
              return item.img.css({
                "max-width": item.img[0].naturalWidth / ratio,
                width: "100%"
              });
            });
            return _mfpOn("ElementParse" + "." + RETINA_NS, function(e, item) {
              return item.src = st.replaceSrc(item, ratio);
            });
          }
        }
      }
    }
  });

  _checkInstance();

}).call(this);
