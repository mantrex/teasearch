import { Plugin } from "ckeditor5/src/core";
import AddressSuggestionEditing from './AddressSuggestionEditing';
import AddressSuggestionUI from './AddressSuggestionUI';

/**
 * The Address suggestion plugin.
 *
 * @internal
 */
export default class AddressSuggestion extends Plugin {
  static get requires() {
    return [AddressSuggestionEditing, AddressSuggestionUI];
  }
  /**
   * @inheritdoc
   */
  static get pluginName() {
    return "addressSuggestion";
  }

}
