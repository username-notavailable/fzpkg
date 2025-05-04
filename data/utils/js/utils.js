'use strict';

function addCustomEventListener(selector, event, handler, rootSelector = 'body') {
    let rootElement = document.querySelector(rootSelector);
    //since the root element is set to be body for our current dealings
    rootElement.addEventListener(event, function (evt) {
            var targetElement = evt.target;
            
            while (targetElement != null) {
                if (targetElement.matches(selector)) {
                    handler(evt);
                    return;
                }

                targetElement = targetElement.parentElement;
            }
        },
        true
    );
}

function serializeFormData(form) {
    let formData = new FormData(form);
    let serializedData = {};
  
    for (var [name, value] of formData) {
        if (serializedData[name]) {
            if (!Array.isArray(serializedData[name])) {
                serializedData[name] = [serializedData[name]];
            }
            serializedData[name].push(value);
        } else {
            serializedData[name] = value;
        }
    }
  
    return serializedData;
}

export default { addCustomEventListener, serializeFormData };