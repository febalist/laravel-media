<template>
  <div v-cloak>
    <input type="hidden" :name="options.name" :value="value_json">

    <div v-if="!limit_reached">
      <div class="clearfix">
        <span class="float-right mt-1" v-if="uploading_active">
          {{ (progress * 100).toFixed() }}%
        </span>

        <button type="button" class="btn btn-secondary" @click="select_files"
                :disabled="!uploading_available">
          Выбрать {{ options.multiple ? 'файлы' : 'файл' }}
        </button>
      </div>

      <div v-if="drop_zone_visible" id="drag" class="text-center mt-1 p-5 border rounded"
           ref="drop_zone" :class="drop_zone_drag ? 'border-primary text-primary' : ''">
        Перетащите файлы сюда
      </div>
    </div>

    <div class="input-group" v-for="(media, index) in media_array"
         :class="index === 0 && !(options.multiple || media_array.length === 0) ? '' : 'mt-1'">
      <input type="text" class="form-control" v-model="media.filename" pattern="^[^\\/%?*:|<>&quot;]*$">
      <div class="input-group-append">
        <span class="input-group-text" v-if="media.extension">
          .{{ media.extension }}
        </span>
        <a class="btn btn-outline-secondary" :href="media.view_url" target="_blank">
          Открыть
        </a>
        <button class="btn btn-outline-danger" type="button" @click="remove(index)">
          Удалить
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
        media_array: options.value,
        progress: null,
        timeout: null,
        window_drag: false,
        drop_zone_drag: false,
      };
    },
    computed: {
      value_json: function() {
        return JSON.stringify(this.media_array);
      },
      uploading_active: function() {
        return this.progress !== null;
      },
      uploading_available: function() {
        return !this.uploading_active;
      },
      drop_zone_visible: function() {
        return this.window_drag && this.uploading_available;
      },
      limit_reached: function() {
        return !this.options.multiple && this.media_array.length > 0;
      },
    },
    watch: {
      progress: function(value) {
        if (value === 1) {
          this.timeout = setTimeout(() => {
            if (this.progress === 1) {
              this.progress = null;
              this.$forceUpdate();
            }
          }, 1000 * 0.5);
        } else if (this.timeout) {
          clearTimeout(this.timeout);
          this.timeout = null;
        }
      },
    },
    methods: {
      select_files: function() {
        if (this.uploading_available) {
          media.select(this.options.multiple, this.options.mime)
              .then(files => {
                this.upload_files(files);
              });
        }
      },
      upload_files: function(files) {
        if (this.uploading_available) {
          media.upload(files, {
            onprogress: (progress, index, event) => {
              this.progress = progress;
            },
            onuploaded: (result, error, file) => {
              if (result) {
                this.media_array = this.media_array.concat(result);
              } else {
                console.log(error);
              }
            },
          });
        }
      },
      remove: function(index) {
        this.media_array.splice(index, 1);
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

        this.upload_files(files);
      },
      on_drag: function(event) {
        this.window_drag = ['dragenter', 'dragover'].includes(event.type);
        this.drop_zone_drag = this.window_drag && event.target == this.$refs.drop_zone;
      },
      on_drop: function(event) {
        if (event.target == this.$refs.drop_zone) {
          const dataTransfer = event.dataTransfer || event.originalEvent.dataTransfer;
          const files = [];

          for (let file of dataTransfer.files) {
            files.push(file);
          }

          this.upload_files(files);
        }
      },
    },
    mounted() {
      window.addEventListener('paste', this.on_paste, false);
      window.addEventListener('drop', event => {
        event.preventDefault();
        event.stopPropagation();

        this.on_drop(event);
      }, false);

      const on_drag = _.throttle(this.on_drag, 100);
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        window.addEventListener(event, event => {
          event.preventDefault();
          event.stopPropagation();

          on_drag(event);
        }, false);
      });
    },
  };
</script>

<style scoped>
  [v-cloak] {
    display: none;
  }

  .truncate {
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>
