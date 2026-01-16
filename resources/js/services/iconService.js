/**
 * Icon Service - Handles fetching and caching icons
 * Uses window object for cross-component cache
 */

// Global cache namespace
if (!window.HeroiconCache) {
  window.HeroiconCache = {
    icons: null,
    loading: false,
    loadingPromise: null,
    error: null,
  };
}

export default {
  /**
   * Fetch icons for specified sets
   * Returns cached data if available, otherwise fetches from API
   *
   * @param {Array} iconSets - Array of icon set metadata [{value, label}, ...]
   * @returns {Promise<Array>} Array of all icons across all sets
   */
  async fetchIcons(iconSets) {
    // Return cached icons if available
    if (window.HeroiconCache.icons) {
      return Promise.resolve(window.HeroiconCache.icons);
    }

    // If already loading, return existing promise (prevents duplicate requests)
    if (window.HeroiconCache.loading && window.HeroiconCache.loadingPromise) {
      return window.HeroiconCache.loadingPromise;
    }

    // Start loading
    window.HeroiconCache.loading = true;
    window.HeroiconCache.error = null;

    const setKeys = iconSets.map(set => set.value);
    const queryString = setKeys.map(key => `sets[]=${encodeURIComponent(key)}`).join('&');

    window.HeroiconCache.loadingPromise = Nova.request()
      .get(`/nova-vendor/heroicon/icons?${queryString}`)
      .then(response => {
        const iconSetsData = response.data.iconSets;

        // Flatten all icons from all sets into single array
        const allIcons = [];
        Object.values(iconSetsData).forEach(iconSet => {
          if (iconSet.icons) {
            allIcons.push(...iconSet.icons);
          }
        });

        // Cache the icons
        window.HeroiconCache.icons = allIcons;
        window.HeroiconCache.loading = false;
        window.HeroiconCache.loadingPromise = null;

        return allIcons;
      })
      .catch(error => {
        window.HeroiconCache.loading = false;
        window.HeroiconCache.loadingPromise = null;
        window.HeroiconCache.error = error;
        throw error;
      });

    return window.HeroiconCache.loadingPromise;
  },

  /**
   * Clear the cache (useful for testing or manual refresh)
   */
  clearCache() {
    window.HeroiconCache.icons = null;
    window.HeroiconCache.loading = false;
    window.HeroiconCache.loadingPromise = null;
    window.HeroiconCache.error = null;
  },

  /**
   * Check if icons are cached
   */
  isCached() {
    return !!window.HeroiconCache.icons;
  },

  /**
   * Get loading state
   */
  isLoading() {
    return window.HeroiconCache.loading;
  }
};
