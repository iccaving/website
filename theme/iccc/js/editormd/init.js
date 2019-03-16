window.addEventListener('load', md => {
    Array.from(document.querySelectorAll("[id^='bettermarkdown']")).map(el => {
        const editor = editormd(el.id, {
            width: "100%",
            height: 740,
            path : siteurl + '/theme/iccc/js/editormd/lib/',
            appendMarkdown : md,
            searchReplace : true,
            htmlDecode : "style,script,iframe|on*", 
            tocm            : true,
			placeholder      : "Write here...",
            toolbarIcons : function() {
                return [
                    "undo", "redo", "|", 
                    "bold", "del", "italic", "|", 
                    "h1", "h2", "h3", "h4", "|", 
                    "list-ul", "list-ol", "hr", "|",
                    "link", "table", "toc", "html-entities", "|",
                    "watch", "fullscreen", "search", "|",
                    "mainimg", "photolink", "photo", "|",
					"allpeople" , "cavepeeps"
                ]
            },
			toolbarIconTexts : {
                mainimg : "Main Image",
                photolink: "Gallery Link",
                allpeople : " All People",
                photo: "Photo",
                cavepeeps: "People",
			},
            toolbarHandlers : {
                mainimg : function(cm, icon, cursor, selection) {
                    cm.replaceSelection('\{\{ mainimg() \}\}')
                },
                photolink : function(cm, icon, cursor, selection) {
                    cm.replaceSelection('\{\{ photolink() \}\}')
                },
                allpeople : function(cm, icon, cursor, selection) {
                    cm.replaceSelection('\{\{ allpeople() \}\}')
                },
                photo: function() {
                    this.executePlugin("customImage", "custom-image/custom-image");
                },
                cavepeeps: function() {
                    this.executePlugin("cavepeeps", "cavepeeps/cavepeeps");
                },
                toc : function(cm, icon, cursor, selection) {
                    cm.replaceSelection('\{\{ toc(3) \}\}')
                },
            },
            lang : {
                toolbar : {
                    mainimg : "Display the main image",
                    photolink: "A link to the photo gallery",
                    allpeople : "A list of all the people with links to their caver pages",
                    photo: "Display a photo",
                    cavepeeps: "A list of the people on a specific trip with links to their caver pages",
                    toc: "A table of contents"
                }
            },
            watch: false,
            onload: function() { 
				// Workaround for issue where [object Event] gets appended.
				const text = this.markdownTextarea.contents().text();
				if (text.endsWith('[object Event]')) {
					this.setMarkdown(text.substring(0, text.length - 14))
				}
			}
        });
        document.querySelector('.nav-tabs').addEventListener('click', () => {
            editor.recreate();
            const text = editor.markdownTextarea.contents().text();
            if (text.endsWith('[object Event]')) {
                editor.setMarkdown(text.substring(0, text.length - 14))
            }
        })
    })
})
