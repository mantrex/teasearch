import { Command } from "ckeditor5/src/core";

export default class InsertAddressCommand extends Command {
  execute(addresseText) {
    const { editor } = this;
    const { model } = editor;
    let langCode = drupalSettings.path.currentLanguage;
    let address = `<div class='address-suggestion'>${addresseText}</div>`;
    let srcMap = 'https://maps.google.com/maps?t=&z=14&ie=UTF8&iwloc=B&output=embed&hl=' + langCode + '&q=' + addresseText;
    let map = `<iframe src="${srcMap}" class="i-address-suggestion" width="100%" height="450" allowfullscreen=""
    loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
    model.change(writer => {
      const content = writer.createElement('addressSuggestion');
      const docFrag = writer.createDocumentFragment();
      const viewFragment = editor.data.processor.toView(map + address);
      const modelFragment = editor.data.toModel(viewFragment);
      writer.append(content, docFrag);
      writer.append(modelFragment, content);
      model.insertContent(docFrag);
    });
  }

  refresh() {
    const {model} = this.editor;
    const {selection} = model.document;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'addressSuggestion',
    );
    this.isEnabled = allowedIn !== null;
  }

}

