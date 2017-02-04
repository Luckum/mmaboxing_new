###
 * Скрипт сгенерирован из файла main.coffee, находящегося в папке source-scripts
###

# require.config
#   baseUrl: 'assets/js'

addEvent = (evnt, elem, func) ->
  if (elem.addEventListener)  # W3C DOM
    elem.addEventListener(evnt,func,false);
  else if (elem.attachEvent) # IE DOM
    elem.attachEvent("on"+evnt, func);
  else # No much to do
    elem[evnt] = func;



log = ->
  log.history = log.history || []
  log.history.push arguments
  if this.console
    console.log Array.prototype.slice.call arguments

###!
 * after 1000, -> addSec()
###
after = (ms, fn) ->
  setTimeout(fn, ms)



###!
 * r 'plugin', -> block.plugin()
###
r = (m, c) ->
  cb = ->
    if typeof c is "function" then c.call(this) else true
  if require.defined(m) then cb() else require([m], cb)


###!
 * IOS Hover/Active
 * На случай, если в body не добавят ontouchstart=""
###
addEvent "touchmove", ((event) ->
  # event.preventDefault()
  # touch = event.touches[0]
  # console.log "Touch x:" + touch.pageX + ", y:" + touch.pageY
), false

###!
 * IOS Scale Bug
###
((doc) ->
  fix = ->
    meta.content = "width=320, minimum-scale=" + scales[0] + ", maximum-scale=" + scales[1]
    doc.removeEventListener type, fix, true
  addEvent = "addEventListener"
  type = "gesturestart"
  qsa = "querySelectorAll"
  scales = [0, 1]
  meta = (if qsa of doc then doc[qsa]("meta[name=viewport]") else [])
  if (meta = meta[meta.length - 1]) and addEvent of doc
    fix()
    scales = [.5, 1]
    doc[addEvent] type, fix, true
) document


# ###!
#  * 60fps scrolling using pointer-events: none
#  * http://www.thecssninja.com/javascript/pointer-events-60fps
# ###
# body = document.body
# timer = 0
# window.addEventListener 'scroll', (->
#   clearTimeout timer
#   unless body.classList.contains('tpl-state-disable-hover')
#     body.classList.add 'tpl-state-disable-hover'
#   timer = setTimeout(->
#     body.classList.remove 'tpl-state-disable-hover'
#   , 500)
# ), false


###!
 * После jQuery
###
(($, window) ->

  # $ ->
  #   colors = $('.tpl-block-colors')
  #   if colors[0]
  #     block = '.tpl-block-colors-color.tpl-state-current'
  #     require ['background-check'], ->
  #       BackgroundCheck.init(
  #         targets: block
  #       )

  ###!
   * Общие переменные
  ###
  disabledClass = 'tpl-state-disabled'
  activeClass = 'tpl-state-active'
  currentClass = 'tpl-state-current'
  openedClass = 'tpl-state-opened'
  closedClass = 'tpl-state-closed'
  hiddenClass = 'tpl-state-hidden'
  mediaMid = 1000
  mediaMax = 1850
  loaders = $('.tpl-block-loader')

  ###!
   Узнаём будущую высоту элемента с height:auto
  ###
  getAutoHeight = (el) ->
    startHeight = el.css('height')
    autoHeight = el.css('height', 'auto').css('height')
    el.css('height', startHeight)
    return autoHeight


  ###!
   * Триггеры на разрешения экрана
   * $(window).on 'media-min' → < 320px
   * $(window).on 'media-mid' → > 320px < 1850px
   * $(window).on 'media-max' → > 1850px
   * https://github.com/xoxco/breakpoints
  ###
  $ ->

    w = $(window)
    body = $('body')


    ###!
     * @param  {Number} vw [null, 40, 1200, 1300]
     * @return {String}
    ###
    getMedia = (vw) ->
      unless vw? then vw = body.width()
      if vw >= mediaMid and vw < mediaMax then 'mediaMid'
      else if vw >= mediaMax then 'mediaMax'
      else 'mediaMin'

    ###!
     * @param  {String} sizes ['min', 'mid', 'max', 'mid max']
     * @return {Boolean}
    ###
    isMedia = (sizes) ->
      error = 0
      nowMedia = getMedia()
      $.each sizes.split(' '), (i, size) ->
        unless size is nowMedia then error++
      if error > 0 then return false else true


    lastMedia = getMedia()

    w.on 'resize', ->
      vw = body.width()
      nowMedia = getMedia(vw)
      unless nowMedia is lastMedia
        lastMedia = nowMedia
        $(window).trigger(nowMedia)


    ###!
     * Вверх
    ###
    # $ ->
    #   r 'sticky', ->
    #     contentBlock = $('.tpl-block-content')
    #     contentLayout = contentBlock.find('.tpl-block-layoutwidth').first()
    #     totop = $('<div class="tpl-block-totop">Вверх</div>').prependTo(contentLayout)

    #     bottom = $('body').outerHeight() - contentBlock.outerHeight() - contentBlock.offset().top

    #     totop.sticky
    #       bottomSpacing: bottom
    #       topSpacing: $(window).height()-100


    $('.tpl-block-rating').each () ->
        index = $(this).index('.tpl-block-rating');
        $(this).addClass('tpl-state-index' + index);
        return true;

    ###!
     * Слайдеры карточек
     * http://www.idangero.us/sliders/swiper
    ###
    $ ->
      swipers = $('.tpl-block-swiper')
      if swipers[0]
        r 'swiper', ->
          c = 'tpl-block-swiper'
          cd = false

          # Убираем тени
          removeShadows = (e) ->
            after 300, ->
              unless cd
                $(e).removeClass(c+'-container--grabbing')
              else removeShadows(e)

          # Добавляем тени
          addShadows = (e) ->
            cd = true
            $(e).addClass(c+'-container--grabbing')
            after 500, ->
              cd = false

          # Модификация свайпера
          isMod = (node, mod) ->
            return node.hasClass(c+'--'+mod)

          # Навесить свайпер
          swipeIt = (id, defaultOptions, localOptions) ->
            options = $.extend {},
              defaultOptions, localOptions
            slider = new Swiper '.'+id, options
            $('.' + id).data('slider', slider)
            return slider

          # Опции по-умолчанию
          defaultOptions =
            speed: 200
            autoplay: ''
            mode: 'horizontal'
            loop: false  # "true" - crash opera 12 and IE 11 (?10)
            slidesPerView: 4 # переопределяется
            calculateHeight: false
            updateOnImagesReady: false
            DOMAnimation: false

            grabCursor: true

            autoResize: true

            resizeReInit: false

            cssWidthAndHeight: false #!

            wrapperClass: c+'-wrapper'
            slideClass: c+'-item'
            slideActiveClass: c+'-item--active'
            slideVisibleClass: c+'-item--visible'
            slideElement: 'div'
            noSwipingClass: c+'--no-swiping'
            onSlideChangeStart: (e, a) ->
              addShadows(e.container)
            onSlideChangeEnd: (e, a) ->
              removeShadows(e.container)
            onTouchStart: (e, a) ->
              addShadows(e.container)
            onSlideReset: (e, a) ->
              removeShadows(e.container)
            onFirstInit: ->
              $(window).trigger('swiper-first-init')



          ###!
           * Проходимся по всем свайперам по очереди
          ###
          swipers.each ->

            node = $(this)
            id = c+'-container--id'+swipers.index(node)
            container = node.find('.'+c+'-container').addClass(id)
            localOptions = defaultOptions


            ###!
             * Заморачиваемся с высотами
            ###
            setHeight = (newHeight) ->
              unless newHeight?
                newHeight = container.find('.tpl-block-cardbox').first().outerHeight()
              container.animate({height: newHeight+'px'}, 200)
              node.data('prevHeight', newHeight)
            setHeight()
            ###!
             * При ресайзе, раз в 100мс проверяет, не поменялась ли высота
             * первой карточки, и, если нужно, отправляет на изменение высоты враппера
            ###
            $(window).on 'resize.swiperHeight', ->
              clearTimeout(timeoutResize)
              timeoutResize = setTimeout ->
                prevHeight = node.data('prevHeight')
                nowHeight = container.find('.tpl-block-cardbox').first().outerHeight()
                unless node.data('prevHeight') is nowHeight
                  log node[0].className, 'newHeight!'
                  setHeight(nowHeight)
              ,100


            ###!
             * Определяем, сколько карточек может показаться
             * на текущем брекпоинте
            ###
            # getAmount = ->
            #   card = container.find('.tpl-block-cardbox').first()
            #   return Math.floor(node.outerWidth()/card.outerWidth())
            # node.data('prevAmount', getAmount())
            # defaultOptions.slidesPerView = node.data('prevAmount')
            getAmount = ->
              media = getMedia()
              if isMod(node, '1or2or3')
                switch media
                  when 'mediaMin' then 1
                  when 'mediaMid' then 2
                  when 'mediaMax' then 3
              else if isMod(node, '1or2or4')
                switch media
                  when 'mediaMin' then 1
                  when 'mediaMid' then 2
                  when 'mediaMax' then 4
              else if isMod(node, '1or3or4')
                switch media
                  when 'mediaMin' then 1
                  when 'mediaMid' then 3
                  when 'mediaMax' then 4
              else
                if media is 'min' then 1 else 4


            amount = getAmount()
            node.data('prevAmount', amount)
            localOptions.slidesPerView = amount


            ###!
             * Когда меняется брекпоинт, то, если нужно,
             * пересчитываем количетво карточек и их высоты
            ###
            $(window).on 'mediaMin mediaMid mediaMax', ->
              nowAmount = getAmount()
              prevAmount = node.data('prevAmount')
              # log node[0].className, getMedia(), 'prev:'+prevAmount, 'now:'+nowAmount
              unless nowAmount is prevAmount

                node.data('prevAmount', nowAmount)
                slider.params.slidesPerView = nowAmount
                slider.reInit()





            ###!
             * Опции для свайпера со скроллбаром
            ###
            if isMod(node, 'with-scrollbar')
              localOptions =
                scrollContainer: false
                loop: false
                onFirstInit: ->
                  # Если нужно, открываем табы в подвале после инициализации
                  after 1000, ->
                    w.trigger 'openFooterTabsBySwiper'
                scrollbar:
                  container: '.'+c+'-scrollbar'
                  hide: false
                  draggable: true
                  snapOnRelease: true



            ###!
             * Запускаем текущий свайпер с нужными опциями
            ###
            slider = swipeIt(id, defaultOptions, localOptions)

            ###!
             * Управление кнопками
            ###
            buttonClass = 'tpl-block-prevnext-button'
            prevClass = buttonClass + '--prev'
            nextClass = buttonClass + '--next'
            buttons = node.find('.'+buttonClass)
            buttons.on 'click', ->
              button = $(this)
              if button.hasClass(prevClass)
                slider.swipePrev()
              else
                slider.swipeNext()



    ###!
     * Табы
    ###
    $ ->
      c = 'tpl-block-tabs'
      q = '.' + c
      if $(q)[0]
        $(q).each ->
          node = $(this)

          tabs    = node.find(q+'-tab')
          content = node.find(q+'-content')
          wrapper = node.find(q+'-wrapper')
          switcher = node.find(q+'-switcher')
          isFooterTabs = if node.hasClass(c+'--footer') then true else false

          if isFooterTabs
            trigger = node.find(q+'-trigger')

          # Подвальные карточки
          if isFooterTabs
            w.on 'openFooterTabs', ->
              wrapperAutoHeight = getAutoHeight(wrapper)
              wrapper.removeClass(hiddenClass)
              trigger.addClass(activeClass)
              wrapper.stop().animate({height: wrapperAutoHeight}, 200)
            w.on 'closeFooterTabs', ->
              wrapper.addClass(hiddenClass)
              trigger.removeClass(activeClass)
              wrapper.stop().animate({height: 0}, 200)
            # Открываем подвальные карточки автоматически,
            # свайпер триггирует после загрузки
            w.on 'openFooterTabsBySwiper', ->
              if trigger.hasClass(activeClass)
                w.trigger 'openFooterTabs'
            # Скрывает / показывает блок с контентом по клику на стрелку
            trigger.on 'click', ->
              if trigger.hasClass(activeClass)
                w.trigger 'closeFooterTabs'

              else w.trigger 'openFooterTabs'


          # Переключалка табов
          tabs.on 'click', ->
            prevTab = tabs.filter('.'+currentClass)
            nextTab = $(this)
            prevIndex = tabs.index(prevTab)
            nextIndex = tabs.index(nextTab)
            prevContent = content.eq(prevIndex)
            nextContent = content.eq(nextIndex)

            # Если в подвале, то открываем блок при нажатии на любой таб
            # И закрываем при нажатии на активный таб
            if isFooterTabs
              unless trigger.hasClass(activeClass)
                w.trigger 'openFooterTabs'
#              else if nextIndex is prevIndex
#                w.trigger 'closeFooterTabs'

            # Нажатие на активный таб (для аккордионов)
            if nextIndex is prevIndex and not isFooterTabs
              return false
#              nextContent.stop().animate({height: 0}, 200)
#              nextContent.removeClass(currentClass)
#              nextTab.removeClass(currentClass)

            else
              oldHeight = prevContent.css('height')
              newHeight = getAutoHeight(nextContent)

              prevTab.removeClass(currentClass)
              nextTab.addClass(currentClass)

              prevContent.removeClass(currentClass).css('height', 0)
              # prevContent.stop().animate({height: 0}, 200).removeClass(currentClass)
              nextContent.stop().css('height', oldHeight).animate({height: newHeight}, 200).addClass(currentClass)

              tabs.removeClass(currentClass)
              nextTab.addClass(currentClass)

              # oldHeight = content
              # newHeight = content.eq(0).css('height', 'auto').outerHeight()
              # content.stop().animate({height: 0}, 200, ->
              #   $(this).removeClass(currentClass)
              #   content.eq(nextIndex).stop().animate({height: autoHeight}, 200, ->
              #     $(this).addClass(currentClass)
              #   )
              # )

              # content.stop().slideUp(200, ->
              #   tabs.removeClass(currentClass)
              #   $(this).removeClass(currentClass)
              # )
              # content.eq(i).stop().slideDown(200, ->
              #   tab.addClass(currentClass)
              #   $(this).addClass(currentClass)
              # )

            # unless tab.hasClass(currentClass)
            #   # i = tab.index(tabs)
            #   i = tabs.index(tab)
            #   tabs.removeClass(currentClass)
            #   tab.addClass(currentClass)
            #   content.removeClass(currentClass)
            #   content.eq(i).addClass(currentClass)
            #

          unless node.hasClass(c + '--footer')

            # Табы / аккордион
            tabsToAccordion = ->
              tabs.each ->
                nextTab = $(this)
                i = tabs.index(nextTab)
                nextTab.insertBefore(content.eq(nextIndex))

            accordionToTabs = ->
              tabs.appendTo(switcher)

            if isMedia('mediaMin')
              tabsToAccordion()
            $(window).on 'mediaMin', ->
              tabsToAccordion()
            $(window).on 'mediaMid mediaMax', ->
              accordionToTabs()

    ###!
     * Выпадайки с городами и телефонами
    ###
#    $ ->
#      cities = $('.tpl-field-city', '.tpl-block-mobilecity')
#      if cities[0]
#        cities.on 'click', ->
#          city = $(this)
#          drop = city.parents('.tpl-block-mobilecity-drop')
#          cssHeight = drop.css('height')
#          curHeight = drop.height()
#          dropHide = ->
#            drop.stop().animate({height: 150}, 200)
#            drop.removeClass(activeClass)
#          dropShow = ->
#            autoHeight = drop.css('height', 'auto').outerHeight()
#            drop.height(cssHeight).stop().animate({height: autoHeight}, 200)
#            drop.addClass(activeClass)
#          if city.hasClass(currentClass)
#            if drop.hasClass(activeClass) then dropHide() else dropShow()
#          else
#            dropHide()
#            cities.removeClass(currentClass)
#            city.addClass(currentClass)

    ###!
     * Фильтр каталога в телефоне
    ###
    $ ->
      q = '.tpl-block-asidefilter'
      if $(q)[0]
        drop = $(q)
        drop.find(q + '-trigger').first().on 'click', ->
          cssHeight = drop.css('height')
          dropHide = ->
            drop.stop().animate({height: 150}, 200)
            drop.removeClass(activeClass)
          dropShow = ->
            autoHeight = drop.css('height', 'auto').outerHeight()
            drop.height(cssHeight).stop().animate({height: autoHeight}, 200)
            drop.addClass(activeClass)
          if drop.hasClass(activeClass)
            dropHide()
          else
            dropShow()

    ###!
     * Выбор цвета на телефоне
    ###
    $ ->
      q = '.tpl-block-colors'
      if $(q)[0]
        drop = $(q)
        colors = $(q + '-item')
        colors.on 'mousedown', ->
          # console.log($(this).children().eq(1))
          if isMeida('min')
            # console.log 'click'
            color = $(this)
            cssHeight = drop.css('height')
            curHeight = drop.height()
            if drop.hasClass(activeClass)
              drop.stop().animate({height: 85}, 200)
              drop.removeClass(activeClass)
            else
              autoHeight = drop.css('height', 'auto').outerHeight()
              drop.height(cssHeight).stop().animate({height: autoHeight}, 200)
              drop.addClass(activeClass)




    ###!
     * Мобильная корзина
    ###
    $ ->
      q = '.tpl-block-mobilecart'
      if $(q)[0]
        node = $(q)
        node.find(q + '-showimage').on 'click', ->
          showimg = $(this)
          item = showimg.parents(q + '-item')
          image = item.find('.tpl-field-image')
          image.stop().slideToggle(200)
          item.toggleClass(activeClass)
        node.find(q + '-delete').on 'click', ->
          del = $(this)
          item = del.parents(q + '-item')
          item.stop().slideUp(200)



    ###!
     * Мобильный каталог
    ###
    $ ->
      q = '.tpl-block-mobilemenu'
      if $(q)[0]
        menu = $(q)
        titles = menu.find(q + '-title')
        cats = menu.find(q + '-category')
        drops = menu.find(q + '-drop')
        titles.on 'click', ->
          title = $(this)
          cat = title.parents(q + '-category')
          drop = title.siblings(q + '-drop')
          if cat.hasClass(activeClass)
            drop.stop().slideUp(200)
            cat.removeClass(activeClass)
          else
            drops.stop().slideUp(200)
            cats.removeClass(activeClass)
            cat.addClass(activeClass)
            drop.stop().slideDown(200)


    ###!
     * Выпадайки в мобильной навигации
    ###
    $ ->
      menuClass = 'tpl-block-mobilenav'
      dropsSelector = '.tpl-block-headerdrop'
      menuSelector = '.' + menuClass
      itemClass = menuClass + '-item'
      itemSelector = '.' + itemClass
      if $(menuSelector)[0]
        items = $(itemSelector)
        links = $('a', itemSelector)
        drops = $(dropsSelector)
        links.on 'click', (e) ->
          item = $(this).parents(itemSelector)
          unless item.hasClass(itemClass + '--logo')
            e.preventDefault()
            closeDrops = ->
              items.removeClass(activeClass)
              drops.removeClass(activeClass)
              drops.stop().slideUp(200)
            openDrop = (id) ->
              closeDrops()
              drop = $(dropsSelector + '--' + id)
              drop.addClass(activeClass)
              drop.stop().slideDown(200)
              item.addClass(activeClass)
            if item.hasClass(activeClass)
              closeDrops()
            else
              if item.hasClass(itemClass + '--menu') then openDrop('menu')
              if item.hasClass(itemClass + '--search') then openDrop('search')
              if item.hasClass(itemClass + '--cart') then openDrop('cart')
              if item.hasClass(itemClass + '--user') then openDrop('user')



    ###!
     * Счётчик с плюсом/минусом
    ###
    window.tpl_init_qty_buttons = ->
      c = 'tpl-block-amountchoice'
      q = '.' + c
      clickEvent = 'click.qty_buttons'

      if $(q)[0]
        blockSelector = q
        valueSelector = q + '-value span'
        buttonSelector = q + '-button'
        moreClass = c + '-button--more'
        lessClass = c + '-button--less'

        # Пробегаемся по всем счетчикам на наличие единицы в значении
        # Если находим, то дизейблим кнопку уменьшения
        $(valueSelector).each ->
          self = $(this)
          i = parseInt(self.text())
          if i is 1
            block = self.parents(blockSelector)
            block.find('.'+lessClass).addClass(disabledClass)

        $(buttonSelector)
          .off(clickEvent)
          .on clickEvent, ->
            button = $(this)
            unless button.hasClass(disabledClass)
              block = button.parents(blockSelector)
              value = block.find(valueSelector)
              valueInput = value.siblings('INPUT');
              buttons = block.find(buttonSelector)
              i = parseInt(value.text())
              if i > 0
                if button.hasClass(moreClass)
                  value.text(i+1)
                  valueInput.val(i + 1)
                  buttons.removeClass(disabledClass)
                else
                  if i-1 is 1
                    button.addClass(disabledClass)
                  valueInput.val(i - 1)
                  value.text(i-1)

    $ -> window.tpl_init_qty_buttons()

    ###!
     * Изменённые селектбоксы
     * http://harvesthq.github.io/chosen/
    ###
    $ ->
      selects = $('.tpl-block-iselect')
      if selects[0]
        r 'chosen', ->
          selects.chosen
            disable_search_threshold: 10
            width: '100%'
          selects.on 'change', ->
            $(window).trigger('s')
          selects.trigger('chosen:updated')

    # ###!
    # * Форматирование цены в инпутах
    # * https://github.com/flaviosilveira/Jquery-Price-Format
    # ###
    # $ ->
    #   priceInputs = $('.tpl-block-price')
    #   if priceInputs[0]
    #     require ['price-format'], ->
    #       priceInputs.each ->
    #         input = $(this)
    #         input.priceFormat
    #           prefix: ''
    #           suffix: 'р.'


    ###!
    * Слайдеры значений
    * http://refreshless.com/nouislider/
    ###
    $ ->
      c = 'tpl-block-range'
      q = '.' + c
      ranges = $(q)
      if ranges[0]
        r 'range-slider', ->
#          r 'price-format', ->


            ranges.each ->
              range = $(this)
              slider = range.find(q + '-slider').first()
              minInput = range.find(q + '-min').first()
              maxInput = range.find(q + '-max').first()

              min = slider.data('min') or minInput.val().replace(/[^0-9.]/g, '')
              max = slider.data('max') or maxInput.val().replace(/[^0-9.]/g, '')

              startMin = slider.data('startMin') or min
              startMax = slider.data('startMax') or max

#              setFormating = ->
#                minInput.add(maxInput).priceFormat
#                  prefix: ''
#                  suffix: ' руб.'
#                  # centsSeparator: ' '
#                  # thousandsSeparator: ' '

              slider.noUiSlider
                range: [min, max]
                start: [startMin, startMax]
                handles: 2
                # connect: true
                step: slider.data('step')
                serialization:
                  to: [minInput, maxInput]
                  resolution: slider.data('resolution')
#                slide: ->
#                  setFormating()

#              setFormating()

    ###!
     * Каталог
    ###
    $ ->
      c = 'tpl-block-catalog'
      q = '.' + c
      catalog = $(q)
      if catalog[0]
        node = $(q)

        ###!
         * Переключалка список / плитка
        ###
        plateClass = c + '--view_plate'
        listClass = c + '--view_list'
        switchToPlate = $('.tpl-block-platelist-button--plate')
        switchToList = $('.tpl-block-platelist-button--list')
        switchToPlate.on 'click', ->
          switchToList.removeClass(activeClass)
          switchToPlate.addClass(activeClass)
          node.removeClass(listClass).addClass(plateClass)
        switchToList.on 'click', ->
          switchToPlate.removeClass(activeClass)
          switchToList.addClass(activeClass)
          node.removeClass(plateClass).addClass(listClass)

    ###!
     * Галереи
     * http://fotorama.io
    ###
    $ ->
      fotoraming = (galleries) ->
        galleries.each ->
          $(this).fotorama
            nav: 'thumbs'
            arrows: false
            # nav: 'none'
            height: 440
            thumbwidth: 90
            thumbheight: 90
            thumbmargin: 20
            thumbborderwidth: 5


      galleries = $('.tpl-block-gallery')
      if galleries[0]
        r 'fotorama', ->
          fotoraming(galleries)

      $(window).on 'galleryPopup', ->
        galleries = $('.tpl-block-gallery')
        r 'fotorama', ->
          fotoraming(galleries)


    ###!
     * Слайдер на главной
     * http://fotorama.io
    ###
    $ ->
      q = '.tpl-block-hero'
      if $(q)[0]
        hero = $(q)
        slider = hero.find(q + '-slider')
        prev = hero.find('.tpl-block-prevnext-button--prev')
        next = hero.find('.tpl-block-prevnext-button--next')

        getHeight = () ->
          if isMedia('mediaMin') then return 630 else return '100%'
        setHeight = (f, h) ->
          f.resize
            height: h

        r 'fotorama', ->

          slider.on 'fotorama:ready', (e, fotorama, extra) ->
            setHeight(fotorama, getHeight())
            $(window).on 'mediaMin mediaMid mediaMax', ->
              setHeight(slider, getHeight())

          slider.fotorama
            nav: 'none'
            arrows: false
            # click: false
            margin: 20
            height: '100%'
            width: '100%'
            loop: true
            shadows: false

          f = slider.data('fotorama')

          prev.on 'click', ->
            f.show('<')
          next.on 'click', ->
            f.show('>')





    ###!
     * Сравнение
     * http://www.fixedheadertable.com
    ###
    $ ->
      c = 'tpl-block-comparision'
      q = '.' + c
      if $(q)[0]
        node = $(q)
        viewport = node.find(q + '-original')

        doubler = node.find(q + '-doubler')
        doubler = viewport.clone().removeClass(c + '-original').addClass(c + '-doubler')
        doubler.find('td').detach()
        doubler.insertAfter(viewport)

        area = viewport.find('table')

        prev = doubler.find('.tpl-block-prevnext-button--prev').first()
        next = doubler.find('.tpl-block-prevnext-button--next').first()
        pos = 0

        prev.addClass(disabledClass)
        col = area.find('td').first()

        nodeWidth = node.outerWidth()
        areaWidth = area.outerWidth()
        colw = col.outerWidth()

        calc = ->
          nodeWidth = node.outerWidth()
          areaWidth = area.outerWidth()
          colw = col.outerWidth()

        if area.find('tr:first td').length <= 3
          next.addClass(disabledClass)

        $(window).on 'mediaMid mediaMax', ->
          calc()
          viewport.scrollLeft(colw * pos)

        next.on 'click', ->
          l = viewport.scrollLeft()
          if l + colw + nodeWidth >= areaWidth
            next.addClass(disabledClass)
          if l + nodeWidth < areaWidth
            prev.removeClass(disabledClass)
            viewport.animate
              scrollLeft: '+=' + colw
            , 200, ->
              pos++

        prev.on 'click', ->
          l = viewport.scrollLeft()
          if l - colw <= 0
            prev.addClass(disabledClass)
          if l > 0
            next.removeClass(disabledClass)
            viewport.animate
              scrollLeft: '-='+colw
            , 200, ->
              pos--



    ###!
     * Странные штуки на главной странице:
     * по отдельному сайту на разрешение
    ###
    $ ->
      if $('.tpl-block-content--frontpage')[0]
        layout = $('.tpl-block-frontlayout')
        news = $('.tpl-block-frontlayout-news')

        if isMedia('mediaMin')
          news.appendTo(layout)

        $(window).on 'mediaMin', ->
          news.appendTo(layout)

        $(window).on 'mediaMid mediaMax', ->
          news.prependTo(layout)


    ###!
     * Лайтбоксы
     * http://dimsemenov.com/plugins/magnific-popup/
    ###
    $ ->
      r 'magnific-popup', ->

#        $('.tpl-block-catalog--view_plate .tpl-block-cardbox .tpl-block-cardbox-link').magnificPopup
#          midClick: false
#          mainClass: 'tpl-block-mfp-animating'
#          type: 'ajax'
#          tError: '<a href="%url%">Страница</a> не может быть загружена.'
#          tLoading: 'Загрузка...'
#          closeMarkup: '<div class="tpl-block-mfp-close"><i class="icon-popup-close"></i></div>'

        $('.tpl-link-modal').magnificPopup
          midClick: true
          mainClass: 'tpl-block-mfp-animating'
          removalDelay: 200
          type: 'inline'
          closeMarkup: '<div class="tpl-block-mfp-close"><i class="icon-popup-close"></i></div>'


    ###
     * Переход по ссылке «Подробное описание» в полном просмотре товаров
    ###
    $ ->
      $('.tpl-block-cardshortdesc a.tpl-link-more').on 'click', ->
        tabSwitcher = $('.tpl-block-tabs-switcher .tpl-block-tabs-tab').eq(0).click()
        $('HTML, BODY').animate
          scrollTop: tabSwitcher.offset().top
        return false
      return

    ###
     * Добавление/удаление в сравнение и избранное
    ###
    $ ->
      $(document).on 'click', '.tpl-block-button-compare, .tpl-block-button-favorite', ->
        $this = $(this)
        $.ajax
          url: $this.attr('href')
          dataType: 'html'
          success: (data) ->
            container = if $this.is('.tpl-block-button-compare') then 1 else 2
            $data = $('<div/>').html(data);
            selector1 = '.tpl-block-tabs--footer .tpl-block-tabs-wrapper .tpl-block-layoutwidth .tpl-block-swiper-container'
            $(selector1).eq(container).find('.tpl-block-swiper-item').remove();

            $wrapper  = $(selector1).eq(container).find('.tpl-block-swiper-wrapper')
            $newItem = $data.find('.tpl-block-tabs--footer .tpl-block-tabs-wrapper .tpl-block-swiper-container').eq(container).find('.tpl-block-swiper-item')
            $(selector1).eq(container).height(315)
            $wrapper.prepend($newItem)

            selector2 = '.tpl-block-tabs--footer .tpl-block-compare-button-container'

            $(selector2).html($data.find(selector2).html())
            $(selector1).eq(container).data('slider').reInit()

            $('div.tpl-block-tabs-switcher div.tpl-block-tabs-tab.tpl-state-current').click();
            return

        $.magnificPopup.open
          items:
            src: if $this.is('.tpl-block-button-compare') then '#goodslist-compare-added' else '#goodslist-favorite-added'
          mainClass: 'tpl-block-mfp-animating'
          removalDelay: 200
          type: 'inline'
          closeMarkup: '<div class="tpl-block-mfp-close"><i class="icon-popup-close"></i></div>'

        return false

    ###
     * Нажатие на ссылку в всплывающем окне «Добавлено в [список]»
    ###
    $ ->
      $('#goodslist-favorite-added a.tpl-block-link, #goodslist-favorite-removed a.tpl-block-link, #goodslist-compare-added a.tpl-block-link').on 'click', ->
        $this = $(this)
        $parent = $this.closest('.tpl-block-footerpopup')
        tabSwitcher = $('.tpl-block-tabs--footer .tpl-block-tabs-switcher .tpl-block-tabs-tab')

        if ($parent.is('#goodslist-favorite-added') || $parent.is('#goodslist-favorite-removed'))
          tabSwitcher = tabSwitcher.eq(2)
        else if $parent.is('#goodslist-compare-added')
          tabSwitcher = tabSwitcher.eq(1)
        else
          return false

        $.magnificPopup.close()
        tabSwitcher.triggerHandler('click')
        $('html, body').animate
          scrollTop: tabSwitcher.offset().top

        return false
      return

    ###
     * Добавление комментария
    ###
    $ ->
       if window.location.hash == '#comment-added'
         tabSwitcher = $('.tpl-block-tabs-switcher .tpl-block-tabs-tab').eq(2).click()
         $('html, body').animate({ scrollTop: tabSwitcher.offset().top })


    ###
     * Подписка
    ###
    $ ->
      $('FORM.tpl-block-footersubscribe').on 'submit', ->
        $this = $(this)
        email = $this.find('INPUT[name="fields[Email]"]').val()
        regexp = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/

        if regexp.test(email)
          $.post($this.attr('action'), $this.serializeArray())

          $.magnificPopup.open
            items:
              src: '#subscriber-added'
            mainClass: 'tpl-block-mfp-animating'
            removalDelay: 200
            type: 'inline'
            closeMarkup: '<div class="tpl-block-mfp-close"><i class="icon-popup-close"></i></div>'

          return false
      return

    ###
     * Нажатие на звёздочки рейтинга
    ###
    $ ->
      $(document).on 'click', '.tpl-block-rating > A', ->
        $this = $(this)
        $block = $this.closest('.tpl-block-rating')

        responseHandler = (data) ->
          regexp = /tpl-state-index(\d+)/i
          match = regexp.exec($block.attr('class'))
          if match
            index = parseInt(match[1])
            $data = $('<div/>').html(data)
            $block.replaceWith($data.find('.tpl-block-rating').eq(index))
          return

        $.post $this.attr('href'), responseHandler
        return false # return from the click handler

      return

    ###
     * Выпадающие списки
    ###

    $ ->
      close_all_dropdowns = ->
          $('ul.tpl-block-dropdown').slideUp(150)

      $('ul.tpl-block-dropdown').click (e) -> e.stopPropagation()

      $('body').click -> close_all_dropdowns()

      $('.tpl-block-dropdown-link').click ->
          close_all_dropdowns()
          $($(this).data('target'))
              .css({'min-width':$(this).width()})
              .slideDown(200)
          return false


) window.jQuery, window


