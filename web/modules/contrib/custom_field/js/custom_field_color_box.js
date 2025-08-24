/**
 * @file
 * Attaches behaviors for Drupal's custom field color_boxes widget.
 */

((Drupal, once) => {
  /**
   * Add box color picker to color field widget.
   *
   * @param {HTMLElement} el
   *   The element to which the color picker will be added.
   * @param {Object} props
   *   Additional properties for configuring the color picker.
   */
  const addBoxColorPicker = (el, props) => {
    if (!props) {
      props = [];
    }

    props = {
      currentColor: '',
      blotchElemType: 'button',
      blotchClass: 'colorBox',
      blotchTransparentClass: 'transparentBox',
      addTransparentBlotch: true,
      clickCallback() {},
      iterationCallback: null,
      fillString: '&nbsp;',
      fillStringX: '?',
      colors: [
        '#AC725E',
        '#D06B64',
        '#F83A22',
        '#FA573C',
        '#FF7537',
        '#FFAD46',
        '#42D692',
        '#16A765',
        '#7BD148',
        '#B3DC6C',
        '#FBE983',
        '#92E1C0',
        '#9FE1E7',
        '#9FC6E7',
        '#4986E7',
        '#9A9CFF',
        '#B99AFF',
        '#C2C2C2',
        '#CABDBF',
        '#CCA6AC',
        '#F691B2',
        '#CD74E6',
        '#A47AE2',
      ],
      ...props,
    };

    function addBlotchElement(color, blotchClass, index) {
      const elem = document.createElement(props.blotchElemType);
      elem.classList.add(blotchClass);
      elem.setAttribute('value', color);
      elem.setAttribute('color', color);
      elem.setAttribute('title', color);
      elem.style.backgroundColor = color;
      if (props.currentColor.toLowerCase() === color.toLowerCase()) {
        elem.classList.add('active');
      }
      if (props.clickCallback) {
        elem.addEventListener('click', function clickListener(event) {
          event.preventDefault();
          const list = Array.from(this.parentNode.children);
          list.forEach((item) => {
            item.classList.remove('active');
          });
          this.classList.add('active');
          props.clickCallback(this.getAttribute('color'));
        });
      }
      el.append(elem);
      if (props.iterationCallback) {
        props.iterationCallback(elem, color, index);
      }
    }

    for (let i = 0; i < props.colors.length; ++i) {
      const color = props.colors[i];
      addBlotchElement(color, props.blotchClass, i);
    }

    if (props.addTransparentBlotch) {
      addBlotchElement('', props.blotchTransparentClass);
    }
  };

  /**
   * Enables box widget on color elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches a box widget to a color input element.
   */
  Drupal.behaviors.color_field = {
    attach(context, settings) {
      once('customField', '.custom-field-color-box-form', context).forEach(
        (element) => {
          const input =
            element.previousElementSibling.querySelector(':scope input');
          input.style.display = 'none';
          const props =
            settings.custom_field.color_box.settings[
              element.getAttribute('id')
            ];
          element.replaceChildren();
          addBoxColorPicker(element, {
            currentColor: input.value,
            colors: props.palette,
            blotchClass: 'custom_field_color_box__square',
            blotchTransparentClass: `custom_field_color_box__square--transparent`,
            addTransparentBlotch: !props.required,
            clickCallback(color) {
              input.value = color;
            },
          });
        },
      );
    },
  };
})(Drupal, once);
