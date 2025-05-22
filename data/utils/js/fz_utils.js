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

function getCurrentFzSize() {
    let size = '';

    if (document.getElementById('fz-sizes') === null) {
        document.querySelector('body').innerHTML += `
            <div id="fz-sizes">
                <div class="fz-size-xs" data-size="xs"></div>
                <div class="fz-size-sm" data-size="sm"></div>
                <div class="fz-size-md" data-size="md"></div>
                <div class="fz-size-lg" data-size="lg"></div>
                <div class="fz-size-xl" data-size="xl"></div>
                <div class="fz-size-xxl" data-size="xxl"></div>
            </div>
        `;
    }

    document.querySelectorAll('#fz-sizes div').forEach((div) => {
        if (window.getComputedStyle(div).getPropertyValue('display') === 'block') {
            size = div.getAttribute('data-size');
        }
    });

    return size;
}

export default { addCustomEventListener, serializeFormData, getCurrentFzSize };