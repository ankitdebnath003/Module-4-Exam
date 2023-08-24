/**
 * Here we are converting the phone number from normal text to (xxx) xxx-xxxx
 * format. And when giving other than 10 digit phone number the format again
 * converted to normal text. And also disable to submit field if not giving
 * valid phone number.
 */
(function ($) {
  Drupal.behaviors.phoneNumber = {
    attach: function (context) {
      $('#edit-otp-button', context).click(function (event) {
        event.preventDefault();
      });
      console.log('ads');
    }
  };

})(jQuery, Drupal);
