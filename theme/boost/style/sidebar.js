require(['theme_boost/drawer'], function(drawer) {
    if (!drawer.isOpen()) {
        drawer.toggle();
    }
});