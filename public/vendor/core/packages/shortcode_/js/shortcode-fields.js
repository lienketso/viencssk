/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};
/*!******************************************************************************!*\
  !*** ./platform/packages/shortcode_/resources/assets/js/shortcode-fields.js ***!
  \******************************************************************************/


$(document).ready(function () {
  $(document).on('change', '.shortcode-tabs-quantity-select', function () {
    var $this = $(this);
    var quantity = parseInt($this.val()) || 1;
    $this.val(quantity);
    var $section = $this.closest('section');
    for (var index = 1; index <= $this.data('max'); index++) {
      var $el = $section.find('[data-tab-id=' + index + ']');
      if (index <= quantity) {
        if ($el.hasClass('d-none')) {
          $el.removeClass('d-none');
          $el.find('[data-name]').map(function (i, e) {
            $(e).prop('name', $(e).data('name'));
          });
        }
      } else {
        $el.addClass('d-none');
        $el.find('[name]').map(function (i, e) {
          $(e).data('name', $(e).prop('name'));
          $(e).removeProp('name');
        });
      }
    }
  });
});
/******/ })()
;