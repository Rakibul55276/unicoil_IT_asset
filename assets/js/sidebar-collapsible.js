(function () {
  'use strict';

  var STORAGE_KEY = 'itAssetSidebarOpenBranches';

  function directSubmenu(item) {
    for (var index = 0; index < item.children.length; index += 1) {
      var child = item.children[index];
      if (child.tagName === 'UL' && child.classList.contains('submenu')) {
        return child;
      }
    }
    return null;
  }

  function setBranchOpen(item, shouldOpen) {
    var submenu = directSubmenu(item);
    var toggle = null;
    for (var index = 0; index < item.children.length; index += 1) {
      var child = item.children[index];
      if (
        child.tagName === 'A' &&
        child.classList.contains('dropdown-toggle')
      ) {
        toggle = child;
        break;
      }
    }
    if (!submenu || !toggle) return;

    item.classList.toggle('open', shouldOpen);
    submenu.style.display = shouldOpen ? 'block' : 'none';
    toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

    if (!shouldOpen) {
      var openChildren = submenu.querySelectorAll('li.open');
      for (var childIndex = 0; childIndex < openChildren.length; childIndex += 1) {
        setBranchOpen(openChildren[childIndex], false);
      }
    }
  }

  function branchKey(item) {
    var labels = [];
    var currentItem = item;

    while (currentItem && currentItem.tagName === 'LI') {
      for (var index = 0; index < currentItem.children.length; index += 1) {
        var child = currentItem.children[index];
        if (
          child.tagName === 'A' &&
          child.classList.contains('dropdown-toggle')
        ) {
          labels.unshift(child.textContent.replace(/\s+/g, ' ').trim());
          break;
        }
      }

      var parentList = currentItem.parentElement;
      currentItem =
        parentList && parentList.classList.contains('submenu')
          ? parentList.parentElement
          : null;
    }

    return labels.join(' > ');
  }

  function loadOpenBranches() {
    try {
      var storedValue = window.sessionStorage.getItem(STORAGE_KEY);
      var storedBranches = storedValue ? JSON.parse(storedValue) : [];
      return Array.isArray(storedBranches) ? storedBranches : [];
    } catch (error) {
      return [];
    }
  }

  function saveOpenBranches(navigation) {
    var openBranches = [];
    var menuItems = navigation.querySelectorAll('li.open');

    for (var index = 0; index < menuItems.length; index += 1) {
      if (directSubmenu(menuItems[index])) {
        openBranches.push(branchKey(menuItems[index]));
      }
    }

    try {
      window.sessionStorage.setItem(
        STORAGE_KEY,
        JSON.stringify(openBranches)
      );
    } catch (error) {
      // Navigation still works when browser storage is unavailable.
    }
  }

  function initialiseSidebarCollapsibles() {
    var navigation = document.querySelector('#sidebar .nav-list');
    if (!navigation) return;

    /*
     * A new browser session starts with every group closed. During that
     * session, opened groups stay open across page navigation until the user
     * explicitly closes them.
     */
    var savedBranches = loadOpenBranches();
    var menuItems = navigation.querySelectorAll('li');
    for (var index = 0; index < menuItems.length; index += 1) {
      if (directSubmenu(menuItems[index])) {
        setBranchOpen(menuItems[index], false);
      }
    }
    for (var savedIndex = 0; savedIndex < menuItems.length; savedIndex += 1) {
      if (
        directSubmenu(menuItems[savedIndex]) &&
        savedBranches.indexOf(branchKey(menuItems[savedIndex])) !== -1
      ) {
        setBranchOpen(menuItems[savedIndex], true);
      }
    }

    /*
     * Capture the click before Ace's default accordion handler. Each branch is
     * toggled independently, so opening one group does not close another group.
     */
    navigation.addEventListener(
      'click',
      function (event) {
        var toggle = event.target.closest('a.dropdown-toggle');
        if (!toggle || !navigation.contains(toggle)) return;

        var item = toggle.parentElement;
        if (!item || !directSubmenu(item)) return;

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        setBranchOpen(item, !item.classList.contains('open'));
        saveOpenBranches(navigation);
      },
      true
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener(
      'DOMContentLoaded',
      initialiseSidebarCollapsibles
    );
  } else {
    initialiseSidebarCollapsibles();
  }
})();
