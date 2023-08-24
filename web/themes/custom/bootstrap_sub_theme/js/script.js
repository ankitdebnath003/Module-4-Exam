/**
 * This is used to Show a Read More / Show Less button in the body of Blogs Nodes
 * and according to that each button will act accordingly.
 */
(function () {
    var readMoreButton = document.querySelectorAll('.read-more');
    var showLessButton = document.querySelectorAll('.show-less');
  
    // Creating a Read More button for showing the remaining body value.
    readMoreButton.forEach(function (button) {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        var contentContainer = button.closest('.content');
        var trimmedContent = contentContainer.querySelector('.trimmed-content');
        var fullContent = contentContainer.querySelector('.full-content');
        var showLessButton = contentContainer.querySelector('.show-less');
        trimmedContent.style.display = 'none';
        fullContent.style.display = 'block';
        showLessButton.style.display = 'inline';
        button.style.display = 'none';
      });
    });
  
    // Creating a Show Less button for trimming the body value to 50 words.
    showLessButton.forEach(function (button) {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        var contentContainer = button.closest('.content');
        var trimmedContent = contentContainer.querySelector('.trimmed-content');
        var fullContent = contentContainer.querySelector('.full-content');
        var readMoreButton = contentContainer.querySelector('.read-more');
        trimmedContent.style.display = 'block';
        fullContent.style.display = 'none';
        readMoreButton.style.display = 'inline';
        button.style.display = 'none';
      });
    });
  })();