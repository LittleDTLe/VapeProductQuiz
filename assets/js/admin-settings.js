function vvCopyShortcode(button) {
    const shortcode = '[vapevida_quiz]';
    
    // Get localized strings from the 'vvAdminSettings' object
    const copiedText = (typeof vvAdminSettings !== 'undefined' && vvAdminSettings.i18n.copied) 
        ? vvAdminSettings.i18n.copied 
        : 'Copied!';
    const copyFailedText = (typeof vvAdminSettings !== 'undefined' && vvAdminSettings.i18n.copyFailed) 
        ? vvAdminSettings.i18n.copyFailed 
        : 'Copy failed. Please copy manually.';

    navigator.clipboard.writeText(shortcode).then(function () {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<span class="dashicons dashicons-yes"></span>' + copiedText;
        button.classList.add('copied');

        setTimeout(function () {
            button.innerHTML = originalHTML;
            button.classList.remove('copied');
        }, 2000);
    }).catch(function (err) {
        console.error('Failed to copy: ', err);
        alert(copyFailedText);
    });
}

function vvToggleCustomAttributes(checkbox) {
    const allDefaultInfos = document.querySelectorAll('.vv-default-info');
    const allSelectWrappers = document.querySelectorAll('.vv-attribute-select-wrapper');

    if (checkbox.checked) {
        allDefaultInfos.forEach(function (info) {
            info.style.display = 'none';
        });
        allSelectWrappers.forEach(function (wrapper) {
            wrapper.style.display = 'block';
        });
    } else {
        allDefaultInfos.forEach(function (info) {
            info.style.display = 'block';
        });
        allSelectWrappers.forEach(function (wrapper) {
            wrapper.style.display = 'none';
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize color pickers
    if (typeof jQuery.fn.wpColorPicker === 'function') {
        jQuery('.vv-color-picker').wpColorPicker();
    }
    
    // Initialize custom attributes toggle
    const useCustomCheckbox = document.getElementById('use_custom_attributes');
    if (useCustomCheckbox) {
        vvToggleCustomAttributes(useCustomCheckbox);
    }
});