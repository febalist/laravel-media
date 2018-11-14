<template>
  <div v-cloak>
    <input type="hidden" :name="options.name" :value="value_json">

    <div v-if="options.multiple || media_array.length === 0">
      <button type="button" class="btn btn-secondary" @click="select_files"
              :disabled="progress !== null">
        Выбрать {{ options.multiple ? 'файлы' : 'файл' }}
      </button>
      <span class="ml-2" v-if="progress !== null">
        {{ progress * 100 }}%
      </span>
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
      };
    },
    computed: {
      value_json: function() {
        return JSON.stringify(this.media_array);
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
        media.select(this.options.multiple, this.options.mime)
            .then(files => {
              this.upload_files(files);
            });
      },
      upload_files: function(files) {
        media.upload(files, {
          onprogress: (progress, index, event) => {
            this.progress = parseFloat(progress.toFixed(2));
          },
          onuploaded: (result, error, file) => {
            if (result) {
              this.media_array = this.media_array.concat(result);
            } else {
              console.log(error);
            }
          },
        });
      },
      remove: function(index) {
        this.media_array.splice(index, 1);
      },
    },
    mounted() {
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
