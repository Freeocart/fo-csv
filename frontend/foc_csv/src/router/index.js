import Vue from 'vue'
import Router from 'vue-router'
import Import from '@/components/Import'
import Export from '@/components/Export'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Import',
      component: Import
    },
    {
      path: '/export',
      name: 'Export',
      component: Export
    }
  ]
})
