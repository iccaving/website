(function() {

    var factory = function (exports) {

		var pluginName   = "custom-image";

		exports.fn.customImage = function() {

            var _this       = this;
            var cm          = this.cm;
            var lang        = this.lang;
            var editor      = this.editor;
            var settings    = this.settings;
            var cursor      = cm.getCursor();
            var selection   = cm.getSelection();
            var imageLang   = lang.dialog.image;
            var classPrefix = this.classPrefix;
            var iframeName  = classPrefix + "image-iframe";
			var dialogName  = classPrefix + pluginName, dialog;

			cm.focus();

            var loading = function(show) {
                var _loading = dialog.find("." + classPrefix + "dialog-mask");
                _loading[(show) ? "show" : "hide"]();
            };

            if (editor.find("." + dialogName).length < 1)
            {
                var guid   = (new Date).getTime();
                
                var dialogContent = `<div class="${classPrefix}form">
                                        <label>Link to image</label>
                                        <input type="text" data-url />
                                        <br/><br/>
                                        <label>Position</label>
                                        <select data-position>
                                            <option value="center">Centre</option>
                                            <option value="left">Left</option>
                                            <option value="right">Right</option>
                                        </select>
                                        <br/>
                                        <label>Caption</label>
                                        <input type="text" value="${selection}" data-caption />
                                        <br/>
                                        <label>External image</label>
                                        <input type="checkbox"data-external />
                                        <br/>
                                        <label>Link when clicked (if different to image)</label>
                                        <input type="text"data-link />
                                        <br/><br/>
                                    </div>`;

                dialog = this.createDialog({
                    title      : imageLang.title,
                    width      : 380,
                    height     : 425,
                    name       : dialogName,
                    content    : dialogContent,
                    mask       : settings.dialogShowMask,
                    drag       : settings.dialogDraggable,
                    lockScreen : settings.dialogLockScreen,
                    maskStyle  : {
                        opacity         : settings.dialogMaskOpacity,
                        backgroundColor : settings.dialogMaskBgColor
                    },
                    buttons : {
                        enter : [lang.buttons.enter, function() {
                            var url  = this.find("[data-url]").val();
                            var caption  = this.find("[data-caption]").val();
                            var position  = this.find("[data-position]").val();
                            var external  = this.find("[data-external]").val();
                            var link = this.find("[data-link]").val();
                            
                            cm.replaceSelection(`{{ photo("${url}", "${position}", "${caption}", "${external ? '1' : ''}", "${link}") }}`);
                            this.hide().lockScreen(false).hideMask();
                            return false;
                        }],

                        cancel : [lang.buttons.cancel, function() {
                            this.hide().lockScreen(false).hideMask();

                            return false;
                        }]
                    }
                });

                dialog.attr("id", classPrefix + "custom-image-" + guid);

            }

			dialog = editor.find("." + dialogName);
			dialog.find("[type=\"text\"]").val("");
            Array.from(dialog.find("[type=\"checkbox\"]")).forEach(i => i.checked = false)

			this.dialogShowMask(dialog);
			this.dialogLockScreen();
			dialog.show();

		};

	};

	// CommonJS/Node.js
	if (typeof require === "function" && typeof exports === "object" && typeof module === "object")
    {
        module.exports = factory;
    }
	else if (typeof define === "function")  // AMD/CMD/Sea.js
    {
		if (define.amd) { // for Require.js

			define(["editormd"], function(editormd) {
                factory(editormd);
            });

		} else { // for Sea.js
			define(function(require) {
                var editormd = require("./../../editormd");
                factory(editormd);
            });
		}
	}
	else
	{
        factory(window.editormd);
	}

})();
