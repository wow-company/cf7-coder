'use strict';
(function($) {
  $(function() {
    const editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
    const codemirror_gen =
        {
          'indentUnit': 4,
          'indentWithTabs': true,
          'inputStyle': 'contenteditable',
          'lineNumbers': true,
          'lineWrapping': true,
          'styleActiveLine': true,
          'continueComments': true,
          'extraKeys': {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-\/': 'toggleComment',
            'Cmd-\/': 'toggleComment',
            'Alt-F': 'findPersistent',
            'Ctrl-F': 'findPersistent',
            'Cmd-F': 'findPersistent',
          },
          'direction': 'ltr',
          'gutters': ['CodeMirror-lint-markers'],
          'mode': 'css',
          'lint': true,
          'autoCloseBrackets': true,
          'autoCloseTags': true,
          'matchTags': {
            'bothTags': true,
          },
          'tabSize': 2,
        };

    if ($('#wpcf7-form').length) {
      let codemirror_el =
          {
            'tagname-lowercase': true,
            'attr-lowercase': true,
            'attr-value-double-quotes': false,
            'doctype-first': false,
            'tag-pair': true,
            'spec-char-escape': true,
            'id-unique': true,
            'src-not-empty': true,
            'attr-no-duplication': true,
            'alt-require': true,
            'space-tab-mixed-disabled': 'tab',
            'attr-unsafe-chars': true,
            'mode': 'htmlmixed',
          };

      editorSettings.codemirror = Object.assign(editorSettings.codemirror, codemirror_gen, codemirror_el);

      var editorHTML = wp.codeEditor.initialize('wpcf7-form', editorSettings);
    }

    if ($('#wpcf7-custom-css').length) {
      let codemirror_el = {
        'errors': true,
        'box-model': true,
        'display-property-grouping': true,
        'duplicate-properties': true,
        'known-properties': true,
        'outline-none': true,
      };
      editorSettings.codemirror = Object.assign(editorSettings.codemirror, codemirror_gen, codemirror_el);

      var editorCSS = wp.codeEditor.initialize('wpcf7-custom-css', editorSettings);
    }

    if ($('#wpcf7-custom-js').length) {
      let codemirror_el = {
        'boss': true,
        'curly': true,
        'eqeqeq': true,
        'eqnull': true,
        'es3': true,
        'expr': true,
        'immed': true,
        'noarg': true,
        'nonbsp': true,
        'onevar': true,
        'quotmark': 'single',
        'trailing': true,
        'undef': true,
        'unused': true,
        'browser': true,
        'globals': {
          '_': false,
          'Backbone': false,
          'jQuery': true,
          'JSON': false,
          'wp': false,
        },
        'mode': 'javascript',
      };

      editorSettings.codemirror = Object.assign(editorSettings.codemirror, codemirror_gen, codemirror_el);

      var editorJS = wp.codeEditor.initialize('wpcf7-custom-js', editorSettings);
    }

    var $wpcf7_taggen_insert = wpcf7.taggen.insert;
    wpcf7.taggen.insert = function(content) {
      insertTextAtCursor(content);
      $('#wpcf7-form').text(get_codemirror());
      $wpcf7_taggen_insert.apply(this, arguments);
    };

    function get_codemirror() {
      return editorHTML.codemirror.getValue();
    }

    function insertTextAtCursor(text) {
      var cursor = editorHTML.codemirror.getCursor();
      editorHTML.codemirror.replaceRange(text, cursor);
    }

    function sincronized_codemirror() {
      var text = editorHTML.codemirror.getValue();
      document.getElementById('wpcf7-form').value = text;
    }

    editorHTML.codemirror.on('keyup', function() {
      sincronized_codemirror();
    });

  });
})(jQuery);