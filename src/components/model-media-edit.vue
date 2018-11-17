<template>
  <div v-cloak>
    <input type="hidden" :name="options.name" :value="value_json">

    <div class="input-group mb-1" v-for="(item, index) in items">
      <template v-if="item.removed">
        <input type="text" class="form-control removed"
               :value="item.media ? item.media.name : item.file.name" readonly>
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" type="button" @click="restore(index)">
            <i class="fas fa-fw fa-undo"></i>
          </button>
        </div>
      </template>
      <template v-else-if="item.media">
        <input type="text" class="form-control" :class="item.file ? 'is-valid' : ''" v-model="item.media.name"
               @blur="on_name_blur(index)" pattern="^[^\\/%?*:|<>&quot;]*$" required>
        <div class="input-group-append">
          <a class="btn btn-outline-secondary" :href="item.media.view_url" target="_blank">
            <i class="fas fa-fw fa-external-link-alt"></i>
          </a>
          <button class="btn btn-outline-danger" type="button" @click="remove(index)">
            <i class="fas fa-fw fa-times"></i>
          </button>
        </div>
      </template>
      <template v-else>
        <input type="text" class="form-control" :class="item.error ? 'is-invalid' : ''"
               :value="item.file.name" readonly>
        <div class="input-group-append">
          <span class="input-group-text" v-if="item.progress !== null && !item.error">
            {{ (item.progress * 100).toFixed() }} %
          </span>
          <button type="button" class="btn btn-outline-secondary" :disabled="!item.error" @click="retry(index)">
            <template v-if="item.error">
              <i class="fas fa-fw fa-redo"></i>
            </template>
            <template v-else>
              <i class="fas fa-fw fa-spinner fa-pulse"></i>
            </template>
          </button>
          <button type="button" class="btn btn-outline-danger" :disabled="!(item.progress === null || item.error)"
                  @click="remove(index)">
            <i class="fas fa-fw fa-times"></i>
          </button>
        </div>
      </template>
    </div>

    <div v-if="!limit_reached">
      <div v-if="drop_zone_visible" id="drag" class="text-center mt-1 py-5 border rounded"
           ref="drop_zone" :class="drop_zone_drag ? 'border-primary text-primary' : ''">
        <i class="fas fa-2x fa-cloud-upload-alt"></i>
      </div>

      <div v-else>
        <button type="button" class="btn btn-secondary" @click="select_files"
                :disabled="!uploading_available">
          <i class="fas fa-fw fa-plus"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
  import media from './../media';
  import _ from 'lodash';

  export default {
    name: 'model-media-edit',
    props: ['data'],
    data() {
      let options = JSON.parse(this.data);

      return {
        options,
        uploading_active: false,
        window_drag: false,
        drop_zone_drag: false,
        items: [],
      };
    },
    computed: {
      value_json: function() {
        return JSON.stringify(this.items.filter(item => !item.removed && item.media).map(item => item.media));
      },
      uploading_available: function() {
        return !this.limit_reached;
      },
      drop_zone_visible: function() {
        return this.window_drag && this.uploading_available;
      },
      limit_reached: function() {
        return !this.options.multiple && this.items.length > 0;
      },
    },
    watch: {},
    methods: {
      on_name_blur: function(index) {
        const item = this.items[index];
        if (item.media.name) {
          const extension = item.media.extension ? '.' + item.media.extension : null;
          if (extension && !item.media.name.endsWith(extension)) {
            item.media.name += extension;
          }
        }
      },
      select_files: function() {
        if (this.uploading_available) {
          media.select(this.options.multiple, this.options.mime)
              .then(files => {
                this.add_items(files);
              });
        }
      },
      add_items: function(items) {
        if (this.uploading_available) {
          for (let item of items) {
            if (!this.limit_reached) {
              const plain = _.isPlainObject(item);
              this.items.push({
                file: plain ? null : item,
                media: plain ? item : null,
                progress: plain ? 1 : null,
                error: null,
                removed: false,
              });
            }
          }

          this.upload_next_file();
        }
      },
      upload_next_file: function() {
        if (this.uploading_active) return;
        this.uploading_active = true;

        const item = this.items.find(item => !item.removed && !item.media && !item.error);

        if (!item) {
          this.uploading_active = false;
          return;
        }

        media.upload(item.file, {
          onprogress: (progress, index, event) => {
            item.progress = progress;
          },
          onuploaded: (result, error, file) => {
            if (result && result[0]) {
              item.media = result[0];
            } else {
              item.error = error || true;
              console.log('Upload error:', error);
            }

            this.uploading_active = false;
            this.upload_next_file();
          },
        });
      },
      remove: function(index) {
        this.items[index].removed = true;
      },
      restore: function(index) {
        const item = this.items[index];
        item.removed = false;
        if (item.error) {
          this.retry(index);
        }
      },
      retry: function(index) {
        const item = this.items[index];
        item.error = null;
        item.progress = null;
        this.upload_next_file();
      },
      on_paste: function(event) {
        const clipboardData = event.clipboardData || event.originalEvent.clipboardData;
        const files = [];

        for (let item of clipboardData.items) {
          if (item.kind == 'file') {
            const file = item.getAsFile();
            files.push(file);
          }
        }

        this.add_items(files);
      },
      on_drag: function(event) {
        this.window_drag = ['dragenter', 'dragover'].includes(event.type);
        this.drop_zone_drag = this.window_drag && this.$refs.drop_zone
            && (this.$refs.drop_zone == event.target || this.$refs.drop_zone.contains(event.target));
      },
      on_drop: function(event) {
        if (event.target == this.$refs.drop_zone) {
          const dataTransfer = event.dataTransfer || event.originalEvent.dataTransfer;
          const files = [];

          for (let file of dataTransfer.files) {
            files.push(file);
          }

          this.add_items(files);
        }
      },
      on_unload: function(event) {
        if (this.uploading_active) {
          event.preventDefault();
          event.returnValue = '';
        }
      },
    },
    mounted() {
      this.add_items(this.options.value);

      //window.addEventListener('beforeunload', this.on_unload, false);
      window.addEventListener('paste', this.on_paste, false);

      window.addEventListener('drop', event => {
        event.preventDefault();
        event.stopPropagation();

        this.on_drop(event);
      }, false);

      const on_drag = _.throttle(this.on_drag, 100);
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(type => {
        window.addEventListener(type, event => {
          event.preventDefault();
          event.stopPropagation();

          on_drag(event);
        }, false);
      });
    },
  };
</script>

<style lang="scss" scoped>
  @import url("https://use.fontawesome.com/releases/v5.5.0/css/all.css");

  [v-cloak] {
    display: none;
  }

  .removed {
    opacity: 0.3;
  }
</style>
