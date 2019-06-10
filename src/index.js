export function install(Vue) {
  if (install.installed) return;
  install.installed = true;

  Vue.component('model-media-edit', require('./components/model-media-edit').default);
}
