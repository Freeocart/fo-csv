import Vue from 'vue'
import Router from 'vue-router'
import Import from '@/components/importer/Import'
import Export from '@/components/Export'
import BackupRestore from '@/components/util/BackupRestore'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      redirect: '/import'
    },
    {
      path: '/import',
      name: 'Import',
      component: Import
    },
    {
      path: '/export',
      name: 'Export',
      component: Export
    },
    {
      path: '/util',
      name: 'Backup/Restore',
      component: BackupRestore
    }
  ]
})
