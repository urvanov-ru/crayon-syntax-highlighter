(function ($) {

    var settings = UrvanovSyntaxHighlighterTagEditorSettings;

    window.urvanovSyntaxHighlighterQuickTags = new function () {

        var base = this;

        base.init = function () {
            base.sel = '*[id*="urvanov_syntax_highlighter_quicktag"],*[class*="urvanov_syntax_highlighter_quicktag"]';
            var buttonText = settings.quicktag_text;
            buttonText = buttonText !== undefined ? buttonText : 'urvanov_syntax_highlighter';
            QTags.addButton('urvanov_syntax_highlighter_quicktag', buttonText, function () {
                UrvanovSyntaxHighlighterTagEditor.showDialog({
                    insert: function (shortcode) {
                        QTags.insertContent(shortcode);
                    },
                    select: base.getSelectedText,
                    editor_str: 'html',
                    output: 'encode'
                });
                $(base.sel).removeClass('qt_urvanov_syntax_highlighter_highlight');
            });
            var qt_urvanov_syntax_highlighter;
            var find_qt_urvanov_syntax_highlighter = setInterval(function () {
                qt_urvanov_syntax_highlighter = $(base.sel).first();
                if (typeof qt_urvanov_syntax_highlighter != 'undefined') {
                    UrvanovSyntaxHighlighterTagEditor.bind(base.sel);
                    clearInterval(find_qt_urvanov_syntax_highlighter);
                }
            }, 100);
        };

        base.getSelectedText = function () {
            if (QTags.instances.length == 0) {
                return null;
            } else {
                var qt = QTags.instances[0];
                var startPos = qt.canvas.selectionStart;
                var endPos = qt.canvas.selectionEnd;
                return qt.canvas.value.substring(startPos, endPos);
            }
        };

    };

    $(document).ready(function () {
        urvanovSyntaxHighlighterQuickTags.init();
    });

})(jQueryUrvanovSyntaxHighlighter);