(function () {
    // Called when a user closes a banner.
    function callback(event) {
        var button = event.currentTarget;
        var bannerId = button.dataset.banner;
        var banner = document.getElementById('site-banner-' + bannerId);

        // The banner can only be closed once, so we don't need the click handler anymore.
        button.removeEventListener('click', callback);

        // Remove the banner from the page.
        banner.parentNode.removeChild(banner);

        // Make sure the banner doesn't re-appear when the page is re-loaded.
        document.cookie = 'SiteBanner_' + bannerId + '_Dismiss=1';
    }

    var buttonNodeList = document.querySelectorAll('button.site-banner-close');
    var index = 0;

    // Add click events to all banners which have close buttons.
    for (index; index < buttonNodeList.length; index += 1) {
        buttonNodeList[index].addEventListener('click', callback);
    }
}());
