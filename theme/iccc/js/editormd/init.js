window.addEventListener('load', md => {
    Array.from(document.querySelectorAll("[id^='bettermarkdown']")).map(el => {
        const editor = editormd(el.id, {
            width: "100%",
            height: 740,
            path : '/theme/iccc/js/editormd/lib/',
            appendMarkdown : md,
            searchReplace : true,
            htmlDecode : "style,script,iframe|on*", 
            tocm            : true,
            toolbarIcons : function() {
                return [
                    "undo", "redo", "|", 
                    "bold", "del", "italic", "|", 
                    "h1", "h2", "h3", "h4", "|", 
                    "list-ul", "list-ol", "hr", "|",
                    "link", "table", "toc", "html-entities", "|",
                    "watch", "fullscreen", "search", "|",
                    "mainimg", "photolink", "allpeople", "photo", "cavepeeps"
                ]
            },
            toolbarIconsClass : {
                mainimg : "fa-image",
                photolink: "fa-image",
                allpeople : "fa-users",
                photo: "fa-image",
                cavepeeps: "fa-book",
                toc: "fa-list-alt"
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
            //onload: function() { this.setMarkdown(`{{ context.content.get(contentkey)|default('') }}`)}
        });
        document.querySelector('.nav-tabs').addEventListener('click', () => {
            editor.recreate();
        })
    })
})
