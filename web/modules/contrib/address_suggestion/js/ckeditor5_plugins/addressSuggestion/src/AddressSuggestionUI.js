/**
 * @file registers the Address Suggestion toolbar button and binds functionality to it.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView, ContextualBalloon, clickOutsideHandler } from 'ckeditor5/src/ui';
import FormView from './AddressSuggestionIcon-view';
import icon from '../../../../icons/address-suggestion.svg';

export default class AddressSuggestionUI extends Plugin {
  init() {
    const editor = this.editor;
    this._balloon = this.editor.plugins.get(ContextualBalloon);
    this.formView = this._createFormView();

    // This will register the UrlParserIcons toolbar button.
    editor.ui.componentFactory.add('addressSuggestion', (locale) => {
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: editor.t('Address'),
        icon: icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      const command = editor.commands.get('InsertAddressCommand');
      buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

      // Execute the command when the button is clicked (executed).
      this.listenTo(buttonView, 'execute', () => {
        this._showUI();
      });

      return buttonView;
    });

  }

  _createFormView() {
    const editor = this.editor;
    const formView = new FormView(editor);

    // On submit send the user data to the writer, then hide the form view.
    this.listenTo(formView, 'submit', () => {
      let address = formView.searchInputView.fieldView.element.value;
      editor.execute('InsertAddressCommand', address);
      this._hideUI();
    });

    // Hide the form view after clicking the "Cancel" button.
    this.listenTo(formView, 'cancel', () => {
      this._hideUI();
    });

    // Hide the form view when clicking outside the balloon.
    clickOutsideHandler({
      emitter: formView,
      activator: () => this._balloon.visibleView === formView,
      contextElements: [this._balloon.view.element],
      callback: () => this._hideUI()
    });

    return formView;
  }

  _hideUI() {
    this.formView.searchInputView.fieldView.value = '';
    this.formView.element.reset();
    this._balloon.remove(this.formView);

    // Focus the editing view after closing the form view.
    this.editor.editing.view.focus();
  }

  _showUI() {
    this._balloon.add({
      view: this.formView,
      position: this._getBalloonPositionData(),
    });
    this.formView.focus();
  }

  _getBalloonPositionData() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;
    let target = null;

    // Set a target position by converting view selection range to DOM.
    target = () => view.domConverter.viewRangeToDom(
      viewDocument.selection.getFirstRange()
    );

    return {
      target
    };
  }

}
