/**
 * Mixin that adds 'webp' to the allowedExtensions list of any file-uploader
 * component, so the validate-file-type rule accepts WebP files client-side.
 *
 * This covers all PageBuilder and Magento UI image uploaders without needing
 * UI component XML merges (which fail for unnamed formElements nodes).
 */
define([], function () {
    'use strict';

    return function (Component) {
        return Component.extend({
            initialize: function () {
                this._super();

                if (this.allowedExtensions &&
                    typeof this.allowedExtensions === 'string' &&
                    this.allowedExtensions.indexOf('webp') === -1
                ) {
                    this.allowedExtensions += ' webp';
                }

                return this;
            }
        });
    };
});
