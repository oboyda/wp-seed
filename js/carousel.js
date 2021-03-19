
var acCarousel = {

    carousel: null,

    args: null,

    viewport: null,
    container: null,
    items: null,

    prevControl: null,
    nextControl: null,
    menuControls: null,

    new: function(){
        return Object.assign({}, this);
    },
    init: function(carousel, args){
        var _this = this;

        this.carousel = acUtils.selectItems(carousel);
        
        if(!this.carousel.length){
            return;
        }

        this.viewport = this.carousel.find(".carousel-viewport");
        this.container = this.carousel.find(".carousel-items");
        this.items = this.carousel.find(".carousel-item");
        
        if(!this.items.length){
            return;
        }
        
        this.args = acUtils.setDefaultArgs(args, {

            prevControl: null,
            nextControl: null,
            menuControls: null,
            
            fullHeight: false,
            maxIndex: this.items.length-1,
            moveNum: 1,
            direction: "x",
            enableSwipe: false,
            swipeDist: 80,
            perView: {
                /*xs: 1,
                sm: 2,
                md: 3,
                lg: 4*/
            }
        });

        if(this.args.prevControl !== null){
            this.prevControl = acUtils.selectItems(this.args.prevControl);
        }
        if(this.args.nextControl !== null){
            this.nextControl = acUtils.selectItems(this.args.nextControl);
        }
        if(this.args.menuControls !== null){
            this.menuControls = acUtils.selectItems(this.args.menuControls);
        }
        
        /*
         * ADD EVENT LISTENERS
         * ------------------------------- *
         */

        if(this.prevControl !== null){
            this.prevControl.click(function(){
                _this.movePrev();
            });
        }
        if(this.nextControl !== null){
            this.nextControl.click(function(){
                _this.moveNext();
            });
        }
        if(this.menuControls !== null){
            this.menuControls.click(function(){
                var menuItem = jQuery(this);
                if(!menuItem.hasClass("active")){
                    _this.moveMenu(menuItem);
                }
            });
        }

        if(this.args.enableSwipe){
            _this.bindSwipe();
        }
        
        this.carousel.addClass("loading");
        
        this.carousel.on("ac_images_loaded", function(){
        //jQuery(window).on("load", function(){
            
            _this.setDimensions();

            var activeInitItem = _this.items.filter(".active-init");
            if(activeInitItem.length){

                var toIndex = activeInitItem.index();
                _this.moveItemInit(toIndex);

            }else{
                
                _this.carousel.trigger("carousel_ready", [_this]);
            }

            _this.resetResize();

            /*
             * INIT SETTINGS
             * ------------------------------- *
             */

            _this.carousel.addClass("direction-"+_this.args.direction);

            _this.setItemActive();
            
            _this.carousel.removeClass("loading");
            _this.carousel.addClass("loaded");
        });
        
        setTimeout(function(){
            _this.preloadImages();
        }, 500);
        
    },
    resetResize: function(){
        var _this = this;
        
        _this.carousel.trigger("carousel_resized");
        
        jQuery(window).resize(function(){
            _this.setDimensions();
            _this.moveItem(_this.getCurrentIndex());

            _this.carousel.trigger("carousel_resized");
        });
    },
    setDimensions: function(){
        this.setItemsWidth();
        this.setViewportHeight();
        this.setMenuItemsVisible();
    },
    getItemsPerView: function(){
        
        var winSize = this.getWinSize();
        var perView = 1;

        var iSizes = ["xs", "sm", "md", "lg"];
        var iSize;
        for(let i=0; i<iSizes.length; i++){
            iSize = iSizes[i];

            if(typeof this.args.perView[iSize] !== "undefined"){
                perView = this.args.perView[iSize];
                if(winSize == iSize){
                    break;
                }
            }
        }
        
        return perView;
    },
    setItemsWidth: function(){
        
        if(this.args.direction !== "x"){
            return;
        }
        
        var viewPortWidth = this.viewport.width();
        var perView = this.getItemsPerView();
        var itemWidth = Math.ceil(viewPortWidth/perView);

        this.items.width(itemWidth);

        //Set container width
        var contWidth = 0;
        this.items.each(function(){
            contWidth += jQuery(this).width();
        });
        this.container.width(contWidth);
    },
    setViewportHeight: function(){
        var _this = this;
        
        this.carousel.trigger("before_set_viewport_height", [this]);
        
        setTimeout(function(){
            if(_this.args.fullHeight){
                _this.carousel.css("height", "100%");
                _this.viewport.css("height", "100%");
                _this.container.css("height", "100%");
            }else{

                _this.viewport.height(_this.getCurrentItem().height());

                /*if(_this.args.direction == "x"){
                    f(_this.getMoveNum() == 1){
                        _this.viewport.height(_this.getCurrentItem().height());
                    }else{
                        _this.viewport.height(_this.container.height());
                    }
                }else{
                    _this.viewport.height(_this.getCurrentItem().height());
                }*/
            }
        }, 500);
    },
    getWinSize: function(){

        var winWidth = jQuery(window).width();
        var winSize = 'xs';

        if(winWidth >= 576){
            winSize = 'sm';
        }
        if(winWidth >= 768){
            winSize = 'md';
        }
        if(winWidth >= 992){
            winSize = 'lg';
        }

        return winSize;
    },
    setItemActive: function(){
        var cIndex = this.getCurrentIndex();
        var itemActive = this.items.eq(cIndex);
        //setTimeout(function(){
            itemActive.addClass("active");
            itemActive.siblings().removeClass("active");
        //}, 600);
    },
    getMenuItemsVisible: function(){
        return (this.menuControls !== null) ? this.menuControls.length-this.getItemsPerView()+1 : 1;
    }, 
    setMenuItemsVisible: function(){
        if(this.menuControls !== null){
            var itemsVisible = this.getMenuItemsVisible();
            this.menuControls.each(function(index){
                if(itemsVisible > index){
                    jQuery(this).removeClass("ac-d-none");
                }else{
                    jQuery(this).addClass("ac-d-none");
                }
            });
        }
    },
    setMenuItemActive: function(toIndex){
        
        var itemsVisible = this.getMenuItemsVisible();
        if(itemsVisible < toIndex+1){
            return;
        }
        
        if(this.menuControls !== null){
            this.menuControls.each(function(index){
                var menuItem = jQuery(this);
                if(toIndex == menuItem.data("to_index")){
                    menuItem.addClass("active");
                }else{
                    menuItem.removeClass("active");
                }
            });
        }
    },
    moveMenu: function(menuItem){
        var toIndex = menuItem.data("to_index");
        this.moveItem(toIndex);
        this.setMenuItemActive(toIndex);
    },
    movePrev: function(){
        var cIndex = this.getCurrentIndex();
        var toIndex = cIndex-this.getMoveNum();
        if(toIndex < 0){
            toIndex = 0;
        }
        this.moveItem(toIndex);
        this.setMenuItemActive(toIndex);
    },
    moveNext: function(){
        var cIndex = this.getCurrentIndex();
        var maxIndex = this.items.length-1;
        var toIndex = cIndex+this.getMoveNum();
        if(toIndex > maxIndex){
            toIndex = maxIndex;
        }
        this.moveItem(toIndex);
        this.setMenuItemActive(toIndex);
    },
    moveItem: function(toIndex, initMove){
        var _this = this;

        initMove = (typeof initMove !== "undefined") ? initMove : false;

        var movePos = 0 - this.getMovePos(toIndex);

        if(this.args.direction == "x"){
            this.container.animate({ left: movePos+"px"}, "fast", "swing", function(){
                _this.moveItemAfter(toIndex, initMove);
            });
        }else{
            this.container.animate({ top: movePos+"px"}, "fast", "swing", function(){
                _this.moveItemAfter(toIndex, initMove);
            });
        }

        /*this.carousel.data("ci", toIndex);
        this.setItemActive();

        this.setViewportHeight();*/
    },
    moveItemAfter: function(toIndex, initMove){

        this.carousel.data("ci", toIndex);

        this.setItemActive();
        this.setViewportHeight();

        // Triggers
        this.carousel.trigger("move_after", [toIndex, this]);
        if(initMove){
            this.carousel.trigger("carousel_ready", [this]);
            this.carousel.trigger("move_after_init", [toIndex, this]);
        }else{
            this.carousel.trigger("move_after_not_init", [toIndex, this]);
        }
    },
    moveItemInit: function(toIndex){
        this.moveItem(toIndex, true);
        if(this.menuControls !== null){
            this.setMenuItemActive(toIndex);
        }
    },
    getMoveNum: function(){
        var moveNum = this.args.moveNum;
        if(this.args.direction == "x"){
            var itemWidth = this.container.width()/this.items.length;
            var viewItemsNum = Math.floor(this.viewport.width()/itemWidth);
            if(viewItemsNum < 1){
                viewItemsNum = 1;
            }
            if(moveNum > viewItemsNum){
                moveNum = viewItemsNum;
            }
        }
        return moveNum;
    },
    getCurrentIndex: function(){
        var cIndex = this.carousel.data("ci");
        return (typeof cIndex !== "undefined") ? parseInt(cIndex) : 0;
    },
    getCurrentItem: function(){
        var cIndex = this.getCurrentIndex();
        return this.items.eq(cIndex);
    },
    getMovePos: function(toIndex){

        var pos = 0;

        var toPos = this.items.eq(toIndex).position();
        var maxPos = 0;

        if(this.args.direction == "x"){
            var maxPos = this.container.width()-this.viewport.width();
            pos = toPos.left;
        }else{
            var maxPos = this.container.height()-this.viewport.height();
            pos = toPos.top;
        }
        if(pos > maxPos){
            pos = maxPos;
        }

        return pos;
    },
    bindSwipe: function(){
        var _this = this;

        if(!("ontouchstart" in document.documentElement)){
            return;
        }

        var swipeArgs = {
            touchstartX: 0,
            touchstartY: 0,
            touchendX: 0,
            touchendY: 0
        };

        this.viewport.on("touchstart", function(event){
            if(event.originalEvent.changedTouches.length){
                swipeArgs.touchstartX = event.originalEvent.changedTouches[0].screenX;
                swipeArgs.touchstartY = event.originalEvent.changedTouches[0].screenY;
            }
        });

        this.viewport.on("touchend", function(event){
            if(event.originalEvent.changedTouches.length){
                swipeArgs.touchendX = event.originalEvent.changedTouches[0].screenX;
                swipeArgs.touchendY = event.originalEvent.changedTouches[0].screenY;

                _this.handleSwipe(swipeArgs);
            }
        });
    },
    handleSwipe: function(swipeArgs){

        var swipeX = 0;
        var swipeY = 0;
        var dirX = "";
        var dirY = "";

        if(swipeArgs.touchendX < swipeArgs.touchstartX){
            swipeX = swipeArgs.touchstartX-swipeArgs.touchendX;
            dirX = "left";
        }
        if(swipeArgs.touchendX > swipeArgs.touchstartX){
            swipeX = swipeArgs.touchendX-swipeArgs.touchstartX;
            dirX = "right";
        }
        if(swipeArgs.touchendY < swipeArgs.touchstartY){
            swipeY = swipeArgs.touchstartY-swipeArgs.touchendY;
            dirY = "up";
        }
        if(swipeArgs.touchendY > swipeArgs.touchstartY){
            swipeY = swipeArgs.touchendY-swipeArgs.touchstartY;
            dirY = "down";
        }

        if(swipeX > swipeY && this.args.direction == "x" && swipeX > this.args.swipeDist){

            if(dirX == "left"){
                this.moveNext();
            }else{
                this.movePrev();
            }

        }else if(swipeY > this.args.swipeDist) {

            if(dirY == "up"){
                this.moveNext();
            }else{
                this.movePrev();
            }

        }
    },
    preloadImages: function(){
        var _this = this;
        
        if(this.carousel.hasClass("loaded")){
            this.carousel.trigger("ac_images_loaded");
            return;
        }
        
        var images = this.carousel.find("img");
        if(!images.length){
            this.carousel.trigger("ac_images_loaded");
            return;
        }
        
        var loaded = 0;
        for(var i=0; i < images.length; i++){
            const img = jQuery(images[i]);
            const imgElem = document.createElement("img");
            imgElem.addEventListener("load", function(){
                loaded++;
                if(loaded === images.length){
                    _this.carousel.trigger("ac_images_loaded");
                }
            });
            const imgSrc = img.attr("src");
            imgElem.setAttribute("src", imgSrc);
        }
    },
    setDefaultArgs: function(args, argsDefautl){
        if(typeof args === "undefined"){
            args = {};
        }
        const aKeys = Object.keys(argsDefautl);
        for(let i=0; i < aKeys.length; i++){
            const key = aKeys[i];
            if(typeof args[key] === "undefined"){
                args[key] = argsDefautl[key];
            }
        }
        return args;
    },
    selectItems: function(select){
        return (typeof select !== "object") ? jQuery(select) : select;
    }

};
