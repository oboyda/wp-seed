
/* 
 * UTILS
 * ----------------------------- */

var acUtils = {
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
}

/* 
 * WINDOW FULL HEIGHT
 * ----------------------------- */
var acWinHeight = {
    
    elem: null,
    args: null,
    new: function(){
        return Object.assign({}, this);
    },
    
    init: function(elem, args){
        var _this = this;

        this.args = acUtils.setDefaultArgs(args, {
            minH: 0,
            resetResize: true,
            minWinWidth: 0
        });

        this.elem = (typeof elem !== "object") ? jQuery(elem) : elem;

        if(!this.elem.length){
            return;
        }

        if(this.args.minH){
            this.elem.css("min-height", this.args.minH + "px");
        }

        this.setHeight();

        if(this.args.resetResize){
            jQuery(window).resize(function(){
                _this.setHeight();
            });
        }
    },
    setHeight: function(){
        const winH = jQuery(window).height();
        const winW = jQuery(window).width();
        if(winH > this.args.minH && winW >= this.args.minWinWidth){
            this.elem.css("height", winH + "px");
        }else{
            this.elem.css("height", "auto");
        }
    }
};

/* 
 * RELATIVE HEIGHT
 * ----------------------------- */

var acRelHeight = {

    elems: null,

    new: function(){
        return Object.assign({}, this);
    },

    init: function(elems){
        var _this = this;

        this.elems = (typeof elems !== "string") ? elems : jQuery(elems);

        if(!this.elems.length){
            return;
        }

        this.setHeight(this.elems);
        jQuery(window).on("resize", function(){
            _this.setHeight(_this.elems);
        });
    },
    setHeight: function(elems){
        elems.each(function(){
            const elem = jQuery(this);
            const elemW = elem.width();
            const relH = parseFloat(elem.data("relh"));
            elem.css("height", Math.round(elemW/relH));
        });
    }
};

/* 
 * POPUP
 * ----------------------------- */

var acPopup = {

    popup: null,
    popupClose: null,
    popupWindow: null,
    popupContent: null,

    new: function(){
        return Object.assign({}, this);
    },

    init: function(popup){

        var _this = this;

        this.popup = (typeof popup !== "object") ? jQuery(popup) : popup;
        this.popupClose = this.popup.find(".popup-close");
        this.popupWindow = this.popup.find(".popup-window");
        this.popupContent = this.popup.find(".popup-content");

        if(!(this.popup.length && this.popupClose.length && this.popupWindow.length && this.popupContent.length)){
            return;
        }

        this.popupClose.on("click", function(){
            _this.closePopup();
        });
    },
    openPopup: function(content){
        if(typeof content == "object"){
            this.popupContent.children().remove();
            this.popupContent.append(content);
        }else{
            this.popupContent.html(content);
        }
        //this.popup.css("display", "block");
        this.popup.css("display", "flex");
    },
    closePopup: function(){
        this.popup.trigger("popup_close_before", [this.popup]);
        this.popup.css("display", "none");
        this.popupContent.html("");
        this.popup.trigger("popup_close_after", [this.popup]);
        //this.centerPopup(true);
    }
};

/* 
 * FORM
 * ----------------------------- */

var acForm = {

    args: null,
    form: null,
    advancedFiles: null,

    new: function(){
        return Object.assign({}, this);
    },
    init: function(form, args){

        var _this = this;

        /* Init properties
         * -------------------------------------------------- */
        this.args = {};
        this.form = (typeof form !== "object") ? jQuery(form) : form;
        this.advancedFiles = {};

        this.args = acUtils.setDefaultArgs(args, {
            actionUrl: "action",
            preventSubmit: true,
            advancedUploadArea: "",
            responseType: "json",
            beforeSubmit: null,
            submitDone: null,
            submitAlways: null,
            acMessagesObj: null,
            resetForm: false
        });
        if(this.args.actionUrl === "action"){
            this.args.actionUrl = this.form.attr("action");
        }

        /* Form not found. Finish
         * -------------------------------------------------- */
        if(!this.form.length){
            return;
        }

        this.initAdvancedUplaod();

        if(this.args.preventSubmit){
            this.form.submit(function(event){
                event.preventDefault();
                _this.submit();
            });
        }
    },
    submit: function(cbkDone, cbkFail, cbkAlways){
        var _this = this;
        
        if(typeof _this.args.beforeSubmit === "function"){
            _this.args.beforeSubmit(_this.form);
        }

        const data = this.collectData();

        if(data.fields === null){
            return;
        }

        var ajaxReq = {
            url: this.args.actionUrl,
            //url: this.form.attr("action"),
            //url: acUtilsVars.ajaxUrl,
            method: "post",
            data: data.fields,
            //cache: false,
            dataType: this.args.responseType,
            processData: false
        };

        if(data.enctype.indexOf("multipart/form-data") === 0){
            ajaxReq.contentType = false;
            ajaxReq.processData = false;
        }

        var submitBtn = this.form.find("[type='submit']");

        submitBtn.prop("disabled", true);
        this.form.addClass("submitting");

        jQuery.ajax(ajaxReq)
        .done(function(resp){

            if(_this.args.responseType === "json"){

                _this.validate(resp);

                if(resp.reload){
                    window.location.reload();
                    return;
                }
                if(resp.redirect){
                    window.location.assign(resp.redirect);
                    return;
                }

                /* Insert html content
                 * -------------------------------------------------- */
                const htmlTarget = (typeof resp.values.html_target !== "undefined") ? resp.values.html_target : _this.form.data("html_target");
                const htmlTargetMode = (typeof resp.values.html_target_mode !== "undefined") ? resp.values.html_target_mode : _this.form.data("html_target_mode");
                
                if(typeof htmlTarget !== "undefined" && typeof resp.values.html !== "undefined"){
                    switch(htmlTargetMode){
                        case "append":
                            jQuery(htmlTarget).append(resp.values.html);
                            break;
                        case "prepend":
                            jQuery(htmlTarget).prepend(resp.values.html);
                            break;
                        default:
                            jQuery(htmlTarget).replaceWith(resp.values.html);
                    }
                }
                
                if(typeof htmlTarget !== "undefined" && htmlTargetMode === "remove"){
                    jQuery(htmlTarget).remove();
                }
                
            }

            /* Callback
             * -------------------------------------------------- */
            if(typeof cbkDone === "function"){
                cbkDone(resp, _this.form);
            }
            if(typeof _this.args.submitDone === "function"){
                _this.args.submitDone(resp, _this.form);
            }
        })
        .fail(function(xhr, status, error){

            /* Callback
             * -------------------------------------------------- */
            if(typeof cbkFail === "function"){
                cbkDone(xhr, status, error, _this.form);
            }
            if(typeof _this.args.submitAlways === "function"){
                _this.args.submitFail(xhr, status, error, _this.form);
            }
        })
        .always(function(resp){

            submitBtn.prop("disabled", false);
            _this.form.removeClass("submitting");

            /* Display messages from server
             * -------------------------------------------------- */
            if(_this.args.acMessagesObj !== null){
                _this.args.acMessagesObj.displayAll(resp);
            }

            /* Callback
             * -------------------------------------------------- */
            if(typeof cbkAlways === "function"){
                cbkAlways(resp, _this.form);
            }
            if(typeof _this.args.submitAlways === "function"){
                _this.args.submitAlways(resp, _this.form);
            }
        });
    },
    validate: function(resp){

        if(typeof resp.errorFields !== "undefined" && resp.errorFields.length){
            for(let i=0; i < resp.errorFields.length; i++){
                const input = this.form.find("[name='"+resp.errorFields[i]+"']");
                if(input.is("[type='checkbox'],[type='radio']")){
                    input.parent().addClass("error-field");
                    input.on("change", function(){
                        const input = jQuery(this);
                        if(input.val() === ""){
                            input.parent().removeClass("error-field");
                        }
                    });
                }else{
                    input.addClass("error-field");
                    input.on("change", function(){
                        const input = jQuery(this);
                        if(input.val() === ""){
                            input.removeClass("error-field");
                        }
                    });
                }
            }
        }else if(this.args.resetForm && resp.status){
            this.reset();
        }
    },
    reset: function(){
        this.form.find(".error-field").removeClass("error-field");
        this.form.get(0).reset();
        this.advancedFiles = {};
    },
    collectData: function(){

        const data = {
            fields: null,
            enctype: this.form.attr("enctype")
        };

        if(typeof data.enctype === "undefined") data.enctype = "application/x-www-form-urlencoded";

        data.fields = (data.enctype.indexOf("multipart/form-data") === 0) ? new FormData(this.form.get(0)) : this.form.serialize();

        if(data.enctype.indexOf("multipart/form-data") === 0 && ("FormData" in window)){

            data.fields = new FormData(this.form.get(0));
            const advancedNames = Object.keys(this.advancedFiles);
            for(let ii=0; ii < advancedNames.length; ii++){
                const advancedName = advancedNames[ii];
                const advancedFiles = this.advancedFiles[advancedName];
                /* Remove empty file input so it does not create errors on the server
                 * ------------------------------------------------------------------- */
                data.fields.delete(advancedName);
                /* Appenda files from drop
                 * ------------------------------------------------------------------- */
                for(let i=0; i < advancedFiles.length; i++){
                    data.fields.append(advancedName, advancedFiles[i]);
                }
            }
        }else{

            data.fields = this.form.serialize();
        }

        return data;
    },
    isAdvancedUpload: function(){
        const div = document.createElement("div");
        return (("draggable" in div) || ("ondragstart" in div && "ondrop" in div)) && "FormData" in window && "FileReader" in window;
    },
    initAdvancedUplaod: function(){

        var _this = this;

        const advancedUploadAreas = (this.args.advancedUploadArea !== "") ? this.form.find(this.args.advancedUploadArea) : null;

        if(advancedUploadAreas !== null && this.isAdvancedUpload()){

            this.form.addClass("has-advanced-upload");

            advancedUploadAreas.each(function(){

                const advancedUploadArea = jQuery(this);
                const inputOpener = advancedUploadArea.find(".file-input-opener");
                const filesInput = advancedUploadArea.find("input[type='file']");
                const inputName = filesInput.attr("name");

                if(typeof inputName === "undefined"){
                    return;
                }

                advancedUploadArea.on("drag dragstart dragend dragover dragenter dragleave drop", function(event){
                    event.preventDefault();
                    event.stopPropagation();
                })
                .on("dragover dragenter", function(){
                    jQuery(this).addClass("is-dragover");
                })
                .on("dragleave dragend drop", function(){
                    jQuery(this).removeClass("is-dragover");
                })
                .on("drop", function(event){
                    _this.advancedFiles[inputName] = event.originalEvent.dataTransfer.files;
                    _this.form.trigger("advanced_files_attached", [jQuery(this), inputName]);
                });
                
                /* ----- show selected files counter -----*/
                
                filesInput.on("change", function(){
                    
                    const filesCount = filesInput[0].files.length;
                    const inputOpenerLabel = inputOpener.text();
                    
                    if(filesCount){
                        inputOpener.text(inputOpenerLabel + " ("+ filesCount +")");
                    }else{
                        inputOpener.text(inputOpenerLabel);
                    }
                });
            });
        }
    }
};

/* 
 * MESSAGES
 * ----------------------------- */

var acMessages = {

    msgCont: null,
    
    new: function(){
        return Object.assign({}, this);
    },
    init: function(selector){
        this.msgCont = jQuery(selector);
    },
    displayAll: function(ajaxResp){
        if(typeof ajaxResp.okMessages !== "undefined" && ajaxResp.okMessages.length){
            for(let i=0; i < ajaxResp.okMessages.length; i++){
                this.display(ajaxResp.okMessages[i], "success");
            }
        };
        if(typeof ajaxResp.errorMessages !== "undefined" && ajaxResp.errorMessages.length){
            for(let i=0; i < ajaxResp.errorMessages.length; i++){
                this.display(ajaxResp.errorMessages[i], "error");
            }
        };
    },
    display: function(msg, type){
        
        if(this.msgCont === null){
            return;
        }
        
        const msgHtml = '<div class="msg msg-'+type+'"><p>'+msg+'</p></div>';
        this.msgCont.append(msgHtml);
        const msgElem = this.msgCont.children().last();
        setTimeout(function(){
            msgElem.addClass("appearing");
            setTimeout(function(){
                msgElem.fadeOut("slow", function(){
                    jQuery(this).remove();
                });
            }, 4000);
        }, 200);
    }
};
