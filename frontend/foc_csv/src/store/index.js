import Vue from 'vue'
import Vuex from 'vuex'
import importer from './importer'

Vue.use(Vuex)

const store = new Vuex.Store({
  modules: {
    importer
  }
})

export default store
