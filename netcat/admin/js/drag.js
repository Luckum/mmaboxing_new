/* $Id: drag.js 8524 2012-12-12 13:48:06Z ewind $ */
/* DRAG AND DROP */

var dragLabel = null;

dragManager = {
    dragInProgress: false,
    dragLabel: null,
    draggedObject: null,
    dropTargetObject: null,
    dropPositionIndicator: null,
    dropPositionImages: {},

    // координаты начала перетаскивания
    dragEventX: 0,
    dragEventY: 0,
    // флаг видимости dragLabel (метка видна только если курсор при перетаскивании отклонился на определенное расстояние)
    dragLabelVisible: false,

    // тип и ID перетаскиваемого объекта в netcat
    draggedInstance: {}, // { type: x, id: n }
    // то же для объекта, на который перетаскиваем
    droppedInstance: {},

    init: function() {
        this.dragLabel = createElement('DIV', {
            id: 'dragLabel',
            'style.display': 'none'
        }, document.body);
        this.dropPositionIndicator = createElement('IMG', {
            id: 'dropPositionIndicator',
            'style.display': 'none',
            'style.position': 'absolute',
            'style.zIndex': 5000
        }, document.body);

        // preload drop position indicators
        this.dropPositionImages.arrowRight = new Image();
        this.dropPositionImages.arrowRight.src = ICON_PATH + 'drop_arrow_right.gif';

        this.dropPositionImages.arrowTop = new Image();
        this.dropPositionImages.arrowTop.src = ICON_PATH + 'drop_arrow_top.gif';

        this.dropPositionImages.line = new Image();
        this.dropPositionImages.line.src = ICON_PATH + 'drop_line.gif';

    }, // end of dragManager.init ------

    // init event listeners on drag start
    initHandlers: function() {
        dragManager.initHandlersInWindow(window);

        var frames = document.getElementsByTagName('IFRAME');
        for (var i=0; i<frames.length; i++) {
            if (frames[i].src.search(/^chrome-/) === -1) {
                dragManager.initHandlersInWindow(frames[i].contentWindow);
            }
        }
    },  // end of initHandlers

    initHandlersInWindow: function(targetWindow) {
        // store frame coords
        if (targetWindow.frameElement) {
            targetWindow.frameOffset = getOffset(targetWindow.frameElement);
        }
        else {
            targetWindow.frameOffset = {
                left: 0,
                top: 0
            };
        }

        // (1) onmousemove
        targetWindow.document.onmousemove = function(e) {
            if (targetWindow != top) targetWindow.scroller.scroll(e);

            if (targetWindow.event) { // IE
                e = targetWindow.event;
            }

            var x = e.clientX + targetWindow.frameOffset.left;
            var y = e.clientY + targetWindow.frameOffset.top;
            dragManager.labelMove(x, y);
        }

        targetWindow.document.onmouseout = function(e) {
            targetWindow.scroller.scrollStop(e);
        }

        // (2) onmouseup
        targetWindow.mouseUpEventId = bindEvent(targetWindow.document, 'mouseup', dragManager.dragEnd);
    },

    removeHandlers: function() {
        dragManager.removeHandlersInWindow(window);

        var frames = document.getElementsByTagName('IFRAME');
        for (var i=0; i<frames.length; i++) {
            if (frames[i].src.search(/^chrome-/) === -1) {
                dragManager.removeHandlersInWindow(frames[i].contentWindow);
            }
        }
    },

    removeHandlersInWindow: function(targetWindow) {
        // (1) onmousemove
        targetWindow.document.onmousemove = null;
        targetWindow.document.onmouseout = null;
        if (targetWindow.scroller) targetWindow.scroller.scrollStop();
        // (2) onmouseup
        unbindEvent(targetWindow.mouseUpEventId);
    },


    idRegexp: /_?([a-z]+)(\d+)?\-([a-f\d]+)$/i, // class-123, message12-345

    // получить тип и ID сущности netcat из ID html-элемента
    // напр., "siteTree_sub-348") -> { type: 'sub', id: '348' }
    // "mainViewToolbar_subclass-223" -> { type: 'subclass', id: '223' }
    // в случае message - еще параметр typeNum
    // "message12-345 -> { type : 'message', typeNum: 12, id: 345 }
    getInstanceData: function(objectId) {
        var matches = objectId.match(dragManager.idRegexp);
        if (matches) {
            return {
                type: matches[1],
                typeNum: matches[2],
                id: matches[3]
            };
        }
        else return {};
    },

    /**
    * Функция-обработчик для объектов, которые объявлены как droppable
    * - определяет, может ли объект выступать в качестве цели для перетаскивания
    * - если да, перемещает индикаторы перетаскивания
    *
    * Должна быть *применена* (applied) к объекту (нужно использовать bindEvent)
    */
    dropTargetMouseOver: function(e) {
        if (!dragManager.dragInProgress) {
            return false;
        }

        if (!this.acceptsDrop) {
            return false;
        }

        if (dragManager.draggedObject == this) {
            return false;
        }

        var parentObject = this.parentNode;

        while (parentObject) {
            if (parentObject == dragManager.draggedObject) {
                return false;
            }
            parentObject = parentObject.parentNode;
        }

        var droppedId = this.id ? this.id : this.parentNode.id;

        dragManager.droppedInstance = dragManager.getInstanceData(droppedId);

        if (this.acceptsDrop()) {
            // save the object as current target for the drop
            dragManager.dropTargetObject = this;
            if (this.dropIndicator && dragManager.dropPositionImages[this.dropIndicator.name]) {
                var ind = dragManager.dropPositionIndicator;
				if (this.ownerDocument != ind.ownerDocument) {
					var local_ind = $nc(this.ownerDocument.body).data('dropPositionIndicator');
					if (!local_ind) {
						local_ind = $nc(ind).clone(true);
						$nc(this.ownerDocument.body).append(local_ind);
						local_ind = local_ind.get(0);
						$nc(this.ownerDocument.body).data('dropPositionIndicator', local_ind);
					}
					ind  = local_ind;
					dragManager.dropPositionIndicator = ind;
				}
                ind.src = dragManager.dropPositionImages[this.dropIndicator.name].src;
                var pos = getOffset(this, false, true);
                ind.style.top = $nc(this).offset().top + $nc(this).height() + 3 + 'px';
                ind.style.left = pos.left + this.dropIndicator.left + 'px';
                ind.style.display = '';
            }
        } // of accepts drop

        // stop event propagation
        if (e && e.stopPropagation) {
            e.stopPropagation();
        }
        else {
            if (this.document.parentWindow.event) {
                this.document.parentWindow.event.cancelBubble = true;
            }
        }
    },

    dropTargetMouseOut: function(e) {
        if (!dragManager.dragInProgress) {
            return;
        }
        dragManager.dropTargetObject = null;
        dragManager.droppedInstance = {}
        dragManager.dropPositionIndicator.style.display = 'none';
    },


    labelSetHTML: function(html) {
        this.dragLabel.innerHTML = html;
    },

    //
    labelMove: function(x, y, frameId) {
        // show only if mouse moved already for more than 12px
        if (!this.dragLabelVisible) {
            if (Math.abs(x - this.dragEventX) > 12 || Math.abs(y - this.dragEventY) > 12) {
                this.dragLabelVisible = true;
                this.dragLabel.style.display = '';
            }
        }
        if (!this.dragLabelVisible) return;

        this.dragLabel.style.top = y + 10 + 'px';
        this.dragLabel.style.left = x + 10 + 'px';

    }, // end of dragManager.labelMove


    /**
    * Сделать объект перетаскиваемым
    * @param {Object} объект-обработчик перетаскивания ("ручка", за
    *   которую можно "тащить" объект draggedObject)
    * @param {Object} объект, который собственно перетаскивается
    *   (если не указан, то это собственно handlerObject)
    */
    addDraggable: function(handlerObject, draggedObject) {
        handlerObject.ondragstart = dragManager.cancelEvent;
        if (!draggedObject) draggedObject = handlerObject;

        if (
            typeof(handlerObject['tagName']) != 'undefined' &&
                typeof(handlerObject['className']) != 'undefined' &&
                handlerObject.tagName == 'I' &&
                handlerObject.className
        ) {
            var classNames = handlerObject.className.split(' ');
            for (var i in classNames) {
                var className = classNames[i];
                if (className.replace(/\s/g, '') == 'nc-icon') {
                    handlerObject.style.cursor = 'move';
                }
            }
        }

        bindEvent(handlerObject, 'mousedown',
            function(e) {
                dragManager.dragStart(e ? e : window.event, draggedObject);
            }, true);
    },

    cancelEvent: function() {
        return false;
    },

    // начало перетаскивания
    // IE: window.event тоже передается первым параметром!
    dragStart: function(e, draggedObject) {
        // check if left button was pressed
        if ((e.stopPropagation && e.button != 0) ||    // DOM (Mozilla)
            (!e.stopPropagation && e.button != 1)      // IE
            ) {
            return;
        } // not a left mouse button

        dragManager.initHandlers();

        dragManager.draggedObject = draggedObject;
        dragManager.dragInProgress = true;

        var dragLabel = draggedObject.dragLabel;
        if (!dragLabel) {
            if (draggedObject.getAttribute('dragLabel')) {
                dragLabel = draggedObject.getAttribute('dragLabel');
            }
            else {
                dragLabel = draggedObject.innerHTML;
            }
        }

        dragManager.labelSetHTML(dragLabel);

        dragManager.draggedInstance = dragManager.getInstanceData(draggedObject.id);
        // store drag event coodrdinates
        var windowOffset = draggedObject.ownerDocument.defaultView ?
        draggedObject.ownerDocument.defaultView.frameOffset :  // moz
        draggedObject.ownerDocument.parentWindow.frameOffset;   // IE
        dragManager.dragEventX = e.clientX + windowOffset.left;
        dragManager.dragEventY = e.clientY + windowOffset.top;

        if (e.stopPropagation) {
            e.stopPropagation();
            e.preventDefault();
        }
        else if (e) {
            e.cancelBubble = true;
        }
    },

    // окончание перетаскивания
    dragEnd: function(e) {
        if (dragManager.dropTargetObject) {
            dragManager.dropTargetObject.dropHandler();
        }
        dragManager.dragInProgress = false;
        dragManager.draggedObject = null;
        dragManager.dropTargetObject = null;
        dragManager.dragLabelVisible = false;
        dragManager.dragLabel.style.display = 'none';
        dragManager.dropPositionIndicator.style.display = 'none';
        dragManager.draggedInstance = {};
        dragManager.droppedInstance = {};
        dragManager.removeHandlers();
    },

    /**
 * сделать объект принимающим перетаскиваемые объекты
 * @param {Object} object объект, который будет принимать перетаскиваемый объект(drop)
 * @param {Function} acceptFn функция проверки возможности сбрасывания объекта на object
 * @param {Function} onDrop функция, выполняемая при сбрасывании объекта на object
 * @param {Object} dropIndicator см. dragManager.init() - position indicators. Объект со свойствами
 *   { name, top|bottom, left }
 */
    addDroppable: function(object, acceptFn, onDropFn, dropIndicator) {
        object.acceptsDrop = acceptFn;
        object.dropHandler = onDropFn;
        object.dropIndicator = dropIndicator;

        bindEvent(object, 'mouseover', dragManager.dropTargetMouseOver);
        bindEvent(object, 'mouseout',  dragManager.dropTargetMouseOut);
    }
}



bindEvent(window, 'load', function() {
    dragManager.init();
});
