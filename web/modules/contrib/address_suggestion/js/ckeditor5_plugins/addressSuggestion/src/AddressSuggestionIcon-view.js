import { View, LabeledFieldView, ListView, ListItemView, createLabeledInputText, ButtonView, submitHandler} from 'ckeditor5/src/ui';
import {icons} from 'ckeditor5/src/core';

/**
 * A class rendering the information required from user input.
 *
 * @extends module:ui/view~View
 *
 * @internal
 */
export default class AddressSuggestionIconView extends View {

  /**
   * @inheritdoc
   */
  constructor(editor) {
    const locale = editor.locale;
    super(locale);
    let addressList = [];
    this.searchInputView = this._createInput(editor.t('Address'));
    this.addressList = this._createAddressList(addressList);

    const config = editor.config.get('address_suggestion');
    let url = config['endpoint'] + '&q=';
    this.searchInputView.fieldView.on('input', (event) => {
      let search = event.source.element.value.toLowerCase();
      sessionStorage.setItem('addressSearch', search);
      let ajaxUrl = url + search;
      if (search.length > 3) {
        fetch(ajaxUrl).then(function (response) {
          if (!response.ok) {
            throw new Error('Request error: ' + response.status);
          }
          return response.json();
        }).then((data) => {
          if (data.length && !data.status) {
            this.addressList = this._createAddressList(data);
            // @todo It must add to this.addressList. I don't know how to do that
            // so I insert the results manually.
            const addressList = document.querySelector('.ck-address-suggestion ul.ck-list');
            if (addressList) {
              addressList.innerHTML = '';
            }
            const inputElement = document.querySelector('.ck-address-suggestion .ck-input');
            data.forEach(item => {
              const newLi = document.createElement('li');
              const btn = document.createElement('button');
              newLi.className = 'ck ck-list__item';
              newLi.setAttribute('role', 'presentation');
              btn.className = 'ck ck-button ck-off ck-button_with-text';
              btn.textContent = item.label;
              newLi.append(btn);
              btn.addEventListener('click', function () {
                const selectedLabel = this.textContent;
                inputElement.value = selectedLabel;
              });
              document.querySelector('.ck-address-suggestion ul.ck-list').appendChild(newLi);
            });
          }
        });
      }
    });
    // Create the save and cancel buttons.
    this.saveButtonView = this._createButton(
      editor.t('Save'), icons.check, 'ck-button-save'
    );
    this.saveButtonView.type = 'submit';
    this.cancelButtonView = this._createButton(
      editor.t('Cancel'), icons.cancel, 'ck-button-cancel'
    );
    // Delegate ButtonView#execute to FormView#cancel.
    this.cancelButtonView.delegate('execute').to(this, 'cancel');
    this.childViews = this.createCollection([
      this.searchInputView,
      this.addressList,
      this.saveButtonView,
      this.cancelButtonView
    ]);
    this.setTemplate({
      tag: 'form',
      attributes: {
        class: ['ck', 'ck-responsive-form', 'ck-address-suggestion'],
        tabindex: '-1'
      },
      children: this.childViews
    });
  }

  /**
   * @inheritdoc
   */
  render() {
    super.render();
    // Submit the form when the user clicked the save button or
    // pressed enter the input.
    submitHandler({
      view: this
    });
  }

  /**
   * @inheritdoc
   */
  focus() {
    this.childViews.first.focus();
  }

  // Create a generic input field.
  _createInput(label) {
    const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);
    labeledInput.label = label;
    labeledInput.inputMode = 'search';
    return labeledInput;
  }

  // Create a generic button.
  _createButton(label, icon, className) {
    const button = new ButtonView();

    button.set({
      label,
      icon,
      tooltip: true,
      class: className,
    });

    return button;
  }

  _createAddressSuggestion(address) {
    const button = new ButtonView();
    button.set({
      label: address.label,
      withText: true
    });
    button.on('execute', () => {
      this.searchInputView.fieldView.element.focus();
    });
    const liView = new ListItemView();
    liView.children.add(button)
    return liView;
  }

  // Create list address suggestion.
  _createAddressList(address) {
    const list = new ListView();
    address.forEach((element) => {
      let location = this._createAddressSuggestion(element);
      list.items.add(location);
    });
    list.set('attributes', {
      class: ['ck', 'ck-reset', 'ck-list', 'ck-address-list'],
    });
    return list;
  }

}
