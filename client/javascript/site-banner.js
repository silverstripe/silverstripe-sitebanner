(function () {
  function getStorageKey(id) {
    return 'SiteBanner_' + id + '_Dismiss';
  }

  // Called when a user closes a banner.
  function callback(event) {
    var button = event.currentTarget;
    var bannerId = button.getAttribute('data-id');
    var banner = document.getElementById('site-banner-' + bannerId);

    // The banner can only be closed once, so we don't need the click handler anymore.
    button.removeEventListener('click', callback);

    // Hide banner from the page
    banner.classList.add('site-banner--hidden');
    banner.setAttribute('aria-hidden', 'true');

    // Make sure the banner doesn't re-appear when the page is re-loaded.
    sessionStorage.setItem(getStorageKey(bannerId), 'true');
  }

  var bannersNodeList = document.querySelectorAll('.site-banner');
  var index = 0;
  var bannerId = 0;
  var button = null;


  for (index; index < bannersNodeList.length; index += 1) {
    bannerId = bannersNodeList[index].getAttribute('data-id');

    // Don't display banners which have been dismissed.
    if (sessionStorage.getItem(getStorageKey(bannerId))) {
      continue;
    }

    // Display the banner
    bannersNodeList[index].classList.remove('site-banner--hidden');
    bannersNodeList[index].setAttribute('aria-hidden', 'false');

    // Add a click event the "dismiss" button, if it exists.
    button = document.querySelector('#' + bannersNodeList[index].id + ' .site-banner-close');

    if (button) {
      // hide close button for browsers without sessionStorage compatibility
      if (!sessionStorage) {
        button.style.display = 'none';
      } else {
        button.addEventListener('click', callback);
      }
    }
  }
}());
