import { Plugin } from 'ckeditor5/src/core';
import { Widget } from 'ckeditor5/src/widget';
import InsertAddressCommand from "./InsertAddressCommand";

// cSpell:ignore AddressSuggestionEditing InsertAddressCommand
export default class AddressSuggestionEditing extends Plugin {
  static get requires() {
    return [Widget];
  }

  init() {
    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
  }

  _defineSchema() {
    // Schemas are registered via the central `editor` object.
    const schema = this.editor.model.schema;

    schema.register('addressSuggestion', {
      // Behaves like a self-contained object (e.g. an image).
      isObject: true,
      // Allow in places where other blocks are allowed (e.g. directly in the root).
      allowWhere: '$text',
      isInline: true,
      allowAttributes: ['class'],
    });
  }

  /**
   * Converters determine how CKEditor 5 models are converted into markup and
   * vice-versa.
   */
  _defineConverters() {
    // Converters are registered via the central editor object.
    const { conversion } = this.editor;
    // Data Downcast Converters: converts stored model data into HTML.
    // These trigger when content is saved.
    //
    // Instances of <urlAddress> are saved as
    // <address class="address-suggestion">{{inner content}}</address>.
    conversion.for('downcast').elementToElement({
      model: 'addressSuggestion',
      view: {
        name: 'address',
        classes: 'address-suggestion',
      },
    });

    // Upcast Converters: determine how existing HTML is interpreted by the
    // editor. These trigger when an editor instance loads.
    //
    // If <div class="address-suggestion"> is present in the existing markup
    // processed by CKEditor, then CKEditor recognizes and loads it as a
    // <urlAddress> model.
    conversion.for('upcast').elementToElement({
      model: 'addressSuggestion',
      view: {
        name: 'address',
        classes: 'address-suggestion',
      },
    });
  }

  _defineCommands() {
    const editor = this.editor;
    editor.commands.add(
      'InsertAddressCommand',
      new InsertAddressCommand(this.editor),
    );
  }
}
