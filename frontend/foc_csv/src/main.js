// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import resource from 'vue-resource'
import App from './App'
import router from './router'
import i18n from './i18n'

import store from './store'
import UrlStore from './url-store'

import { validateAppConfig } from './helpers'

Vue.use(resource)
Vue.config.productionTip = true

// let AppConfig = require('./test.json')
let AppConfig = {}

if (window.FOC_CSV_PARAMS) {
  AppConfig = Object.assign(AppConfig, window.FOC_CSV_PARAMS)
}

if (validateAppConfig(AppConfig.requestConfig)) {
  let urlManager = new UrlStore(AppConfig.requestConfig)
  store.actionUrl = (action) => urlManager.actionUrl(action)

  store.dispatch('importer/setInitialData', AppConfig.initial)

  const language = AppConfig.language || 'en'

  /* eslint-disable no-new */
  new Vue({
    el: '#foc_csv',
    router,
    AppConfig,
    store,
    i18n: i18n(language),
    components: { App },
    template: '<App />'
  })
}
else {
  console.error('Failed to initialize! Wrong AppConfig!')
}
