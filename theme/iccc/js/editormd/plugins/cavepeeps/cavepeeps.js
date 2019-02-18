(function() {

    var factory = function (exports) {

		var pluginName   = "cavepeeps";

		exports.fn.cavepeeps = function() {

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

            const cavepeeps = [];
            const cavepeeps_els = Array.from(document.querySelectorAll("input[name^='cavepeeps'], select[name^='cavepeeps']")).slice(1);
            for (let i = 0; i < cavepeeps_els.length;) {
                const date = cavepeeps_els[i].value;
                const cave = Array.from(cavepeeps_els[i+1].selectedOptions).map(o => o.text).join(' > ');
                const people = Array.from(cavepeeps_els[i+2].selectedOptions).map(o => o.text).join(', ');
				if (date !== "" || cave !== "") {
					const matches = cavepeeps.filter(c => c.date === date && c.cave === cave);
					if (matches.length === 1) {
						matches[0].people =  matches[0].people + ', ' + people;
						cavepeeps.push({...matches[0], index: 1});
						cavepeeps.push({date, cave, people, index: 2});
					} else if (matches.length > 1) {
						matches[0].people =  matches[0].people + ', ' + people;
						cavepeeps.push({date, cave, people, index: Math.max(matches.map(m => m.index)) + 1});
					} else {
						cavepeeps.push({date, cave, people});
					}
				}
                i = i + 3;
            }
			let select = "<p>No cavepeeps set up...</p>"
			if (cavepeeps.length) {
				select = `<label>Trip</label>
                            <select style="width:100%;" data-trip>
                                ${
                                    cavepeeps.map(c => `<option data-date="${c.date}" data-cave="${c.cave}" data-index="${c.index ? `${c.index}` : ''}" value="${c.date}-${c.cave}${c.index ? `-${c.index}` : ''}">${c.date} - ${c.cave} - ${c.index ? `${c.people}` : 'All'} </option>`)
                                }
                            </select>`
			};

            if (editor.find("." + dialogName).length < 1)
            {
                var guid   = (new Date).getTime();
                var action = settings.imageUploadURL + (settings.imageUploadURL.indexOf("?") >= 0 ? "&" : "?") + "guid=" + guid;

                if (settings.crossDomainUpload)
                {
                    action += "&callback=" + settings.uploadCallbackURL + "&dialog_id=editormd-image-dialog-" + guid;
                }

                var dialogContent = `<div class="${classPrefix}form">
                                        ${select}
                                    </div>`;

                dialog = this.createDialog({
                    title      : imageLang.title,
                    width      : 500,
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
                    buttons : cavepeeps.length ? {
                        enter : [lang.buttons.enter, function() {
                            var option = this.find("[data-trip]")[0].selectedOptions[0];
                            var date  = option.dataset['date'];
                            var cave  =  option.dataset['cave'];
                            var index  =  option.dataset['index'];
                            
                            cm.replaceSelection(`{{ people("${date}", "${cave}", "${index}") }}`);
                            this.hide().lockScreen(false).hideMask();
                            return false;
                        }],
                        cancel : [lang.buttons.cancel, function() {
                            this.hide().lockScreen(false).hideMask();

                            return false;
                        }]
                    } : {
                        cancel : [lang.buttons.cancel, function() {
                            this.hide().lockScreen(false).hideMask();

                            return false;
                        }]
					}
                });

                dialog.attr("id", classPrefix + "cavepeeps-" + guid);

            }

			dialog = editor.find("." + dialogName);
            dialog.find("[type=\"select\"]").val("");
            dialog.find(`.${classPrefix}form`).innerHTML = select;

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
