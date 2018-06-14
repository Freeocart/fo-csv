import Vue from 'vue'
import Vuex from 'vuex'
import importer from './importer'
import exporter from './exporter'

Vue.use(Vuex)
console.log(importer)
const store = new Vuex.Store({
  modules: {
    importer,
    exporter
  }
})

export default store
