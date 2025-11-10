// Patch CodeMirror to enable spellcheck before Editor.md initializes
(function() {
    function patchCodeMirror(CM) {
        if (!CM || !CM.fromTextArea || CM._spellcheckPatched) return false;

        var originalFromTextArea = CM.fromTextArea;
        CM.fromTextArea = function(textarea, options) {
            options = options || {};
            options.inputStyle = 'contenteditable';
            options.spellcheck = true;
            return originalFromTextArea.call(this, textarea, options);
        };
        CM._spellcheckPatched = true;
        return true;
    }

    // Keep checking for both global CodeMirror and editormd.$CodeMirror
    var globalPatched = false;
    var editormdPatched = false;

    var checkInterval = setInterval(function() {
        // Try global CodeMirror
        if (!globalPatched && typeof CodeMirror !== 'undefined') {
            if (patchCodeMirror(CodeMirror)) {
                globalPatched = true;
            }
        }

        // Try editormd.$CodeMirror (this is what Editor.md actually uses!)
        if (!editormdPatched && typeof editormd !== 'undefined' && editormd.$CodeMirror) {
            if (patchCodeMirror(editormd.$CodeMirror)) {
                editormdPatched = true;
            }
        }

        // Stop when both are patched
        if (globalPatched && editormdPatched) {
            clearInterval(checkInterval);
        }
    }, 50);

    // Stop checking after 10 seconds
    setTimeout(function() {
        clearInterval(checkInterval);
    }, 10000);
})();
