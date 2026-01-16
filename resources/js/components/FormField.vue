<template>
  <DefaultField :field="field" :errors="errors" :show-help-text="showHelpText">
    <template #field >
      <div class="flex flex-row">
        <div v-if="value" class="icon-preview mb-4">
          <span class="relative inline-block p-8 border border-gray-300 rounded-md">
            <span v-html="value"> </span>
            <span class="close-icon absolute top-0 right-0 cursor-pointer invisible" @click="clear">
              <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
              >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </span>
          </span>
        </div>
        <div class="flex justify-center items-center">
          <Button class="ml-2" @click.prevent="toggleModal" variant="link">
            {{ openModalText }}
          </Button>
          <Button
              class="ml-2"
              v-if="field.editor"
              variant="link"
              @click.prevent="toggleEditor"
          >
            {{ editButtonText }}
          </Button>
        </div>
      </div>
      <transition name="fade">
        <textarea
            v-show="editorOpened"
            :id="field.name"
            type="text"
            class="w-full form-control form-input form-input-bordered h-36 mt-2 heroicon-textarea"
            :class="errorClasses"
            :placeholder="field.name"
            v-model="value"
        />
      </transition>

      <Modal :show="modalOpened" @closing="closeModal" class="heroicon-modal">
        <div class="rounded-lg shadow-lg text-gray-500
         dark:text-gray-400 bg-gray-100 dark:bg-gray-900 "
        >
          <div class="px-8 py-6 border-b relative">
            <heading :level="2" class="mb-0 px-10">{{ __('Select Icon') }}</heading>
            <a href="#" class="heroicon-close" @click.prevent="closeModal">
              <svg
                  class="w-10 h-10"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
              >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </a>
          </div>

          <!-- NEW: Loading state -->
          <div v-if="loading" class="px-8 py-12 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-gray-100"></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">Loading icons...</p>
          </div>

          <!-- NEW: Error state -->
          <div v-else-if="error" class="px-8 py-12 text-center">
            <p class="text-red-600 dark:text-red-400">Failed to load icons. Please try again.</p>
            <Button class="mt-4" @click="retryLoadIcons">Retry</Button>
          </div>

          <!-- EXISTING: Controls and icons (only show when loaded) -->
          <template v-else-if="icons.length > 0">
            <div class="px-8 py-4 border-b heroicon-controls">
              <div class="flex flex-wrap -mx-4">
                <div class="px-4" style="width: 33%">
                  <div class="flex relative">
                    <select
                        id="type"
                        class="w-full form-control form-select
                        form-select-bordered heroicons-sets-select"
                        v-model="filter.type"
                        :disabled="disableOptions"
                    >
                      <option v-for="opt in iconOptions" :value="opt.value" :key="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                    <IconArrow class="pointer-events-none form-select-arrow" />
                  </div>
                </div>
                <div class="px-4" style="width: 66%">
                  <input
                      type="text"
                      id="search"
                      class="w-full form-control form-input form-input-bordered heroicons-input"
                      placeholder="Search icons"
                      v-model="filter.search"
                      @keypress.enter.prevent
                  />
                </div>
              </div>
            </div>
            <div class="px-8 py-6 heroicon-inner">
              <div class="flex flex-wrap items-baseline -mx-2 grid-container">
                <div
                    v-for="icon in filteredIcons"
                    :key="`${icon.type}_${icon.name}`"
                    class="
                    flex flex-col flex-1
                    items-center
                    justify-center
                    text-center
                    px-2
                    w-1/6
                    cursor-pointer
                    mb-4
                    min-h-90px
                  "
                    @click="saveIcon(icon)"
                >
                  <div v-html="icon.content" class="w-12 h-12 icon-container"></div>
                  <div>{{ icon.name }}</div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </Modal>
    </template>
  </DefaultField>
</template>

<script>
// eslint-disable-next-line import/no-unresolved
import { FormField, HandlesValidationErrors } from 'laravel-nova';
import { Button } from 'laravel-nova-ui';
import iconService from '../services/iconService';

export default {
  mixins: [FormField, HandlesValidationErrors],
  components: {
    Button,
  },
  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      defaultIcons: [],
      modalOpened: false,
      editorOpened: false,
      value: '',
      filter: {
        type: '',
        search: '',
      },
      // NEW: Loading states
      loading: false,
      error: null,
      iconsLoaded: false,
    };
  },
  methods: {
    setInitialValue() {
      this.value = this.field.value || '';
    },
    fill(formData) {
      formData.append(this.field.attribute, this.value || '');
    },
    clear() {
      this.value = '';
    },
    // MODIFIED: Load icons when modal opens
    async toggleModal() {
      this.modalOpened = !this.modalOpened;

      // Load icons on first modal open
      if (this.modalOpened && !this.iconsLoaded) {
        // Check if icons are now cached (by another field instance)
        if (iconService.isCached()) {
          // Use cached icons
          this.defaultIcons = window.HeroiconCache.icons;
          this.iconsLoaded = true;
        } else {
          // Load from API
          await this.loadIcons();
        }
      }
    },
    // NEW: Load icons from API
    async loadIcons() {
      this.loading = true;
      this.error = null;

      try {
        const icons = await iconService.fetchIcons(this.field.iconSets);
        this.defaultIcons = icons;
        this.iconsLoaded = true;
      } catch (err) {
        console.error('Failed to load icons:', err);
        this.error = err;
      } finally {
        this.loading = false;
      }
    },
    // NEW: Retry loading icons
    async retryLoadIcons() {
      await this.loadIcons();
    },
    toggleEditor() {
      this.editorOpened = !this.editorOpened;
    },
    closeModal() {
      this.modalOpened = false;
    },
    saveIcon(icon) {
      this.value = icon.content;
      this.filter.type = '';
      this.filter.search = '';
      this.closeModal();
    },
    escapeHandler(e) {
      if (e.key === 'Escape' && this.modalOpened) {
        this.closeModal();
      }
    },
  },
  computed: {
    icons() {
      let allIcons = this.defaultIcons;
      const enabledTypes = [];

      // MODIFIED: Use iconSets instead of icons
      this.field.iconSets.forEach((iconSet) => {
        enabledTypes.push(iconSet.value);
      });

      return allIcons.filter((icon) => enabledTypes.includes(icon.type));
    },
    filteredIcons() {
      let filteredIcons = this.icons;
      if (this.filter.type) {
        filteredIcons = filteredIcons.filter((icon) => icon.type === this.filter.type);
      }

      if (this.filter.search) {
        filteredIcons = filteredIcons.filter((icon) => icon.name.includes(this.filter.search));
      }
      return filteredIcons;
    },
    editButtonText() {
      if (this.editorOpened) {
        return 'Close';
      }
      return 'Edit';
    },
    openModalText() {
      if (this.value) {
        return 'Change icon';
      }
      return 'Add icon';
    },
    iconOptions() {
      // MODIFIED: Use iconSets
      if (this.field.iconSets.length > 1) {
        return [{ value: '', label: 'All' }, ...this.field.iconSets];
      }
      return this.field.iconSets;
    },
    disableOptions() {
      return this.field.iconSets.length === 1;
    },
  },
  created() {
    document.addEventListener('keydown', this.escapeHandler);
    this.filter.type = this.iconOptions[0].value;

    // NEW: Check if icons are already cached (for subsequent field instances)
    if (iconService.isCached()) {
      this.defaultIcons = window.HeroiconCache.icons;
      this.iconsLoaded = true;
    }
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.escapeHandler);
  },
};
</script>
<style>
.icon-preview svg {
  width: 60px;
  height: 60px;
}

.icon-container > svg {
  max-height: 100%;
  max-width: 100%;
  padding-bottom: 10px;
}

.icon-preview:hover .close-icon {
  visibility: visible;
}

.close-icon {
  transform: translate(50%, -50%);
  opacity: 0.75;
  transition: visibility 0.4s linear;
}

.close-icon:hover {
  opacity: 1;
}

.close-icon svg {
  width: 30px;
  height: 30px;
}

.heroicon-modal {
  max-width: 80%;
  overflow: hidden;
}
.dark .heroicon-modal {
  border-width: 2px;
  border-radius: 5px;
}
.heroicon-modal > div {
  overflow: hidden;
}

.heroicon-inner {
  height: 60vh;
  overflow-y: scroll;
  overflow-x: hidden;
}

.heroicon-close {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  right: 1.5rem;
  font-size: 1.5rem;
  color: #3c4b5f;
}

.heroicons-sets-select,
.heroicons-input {
  border-color: var(--colors-gray-200);
  color: rgba(var(--colors-gray-400), var(--tw-text-opacity));
}
.dark .heroicons-sets-select,
.dark .heroicons-input {
  border-color: var(--colors-gray-200);
  color: rgba(var(--colors-gray-400), var(--tw-text-opacity));
}

.heroicon-textarea {
  min-height: 110px;
}
</style>
