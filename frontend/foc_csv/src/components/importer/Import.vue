<template>
  <div class="foc-csv-import">
    <template v-if="working">
      <div class="panel panel-primary">
        <div class="panel-heading">
          {{ $t('Import in progress') }}
        </div>
        <div class="panel-body">
          <import-progress :progress="{ current, total }"></import-progress>
        </div>
      </div>
    </template>
    <div class="row">
      <div class="col-md-12">
        <div class="row">
          <div class="col-md-8">
            <h1>{{ $t('Import submodule') }}</h1>
          </div>
          <div class="col-md-4 text-right">
            <button @click.prevent="submitImportData" :disabled="working" class="btn btn-warning btn-lg"><i class="fa fa-rocket"></i> {{ $t('Start import!') }}</button>
          </div>
        </div>
        <hr>
      </div>
      <div class="col-md-4">
        <left-sidebar></left-sidebar>
      </div>

      <div class="col-md-5">
        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Fields settings') }}
          </div>
          <div class="panel-body">

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('CSV file') }}</label>
              <csv-file-upload></csv-file-upload>
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Process lines per query') }}</label>
              <input type="text" v-model="processAtStepNum" class="form-control">
            </div>

            <div class="form-group">
              <label for="" class="label label-default">{{ $t('Skip first line') }}</label>
              <input type="checkbox" v-model="skipFirstLine">
            </div>

            <div class="form-group">
              <label for="" class="label label-danger">{{ $t('Key field') }}</label>
              <select v-model="keyField" class="form-control">
                <option v-for="(field, idx) in keyFields" :key="idx">{{ field }}</option>
              </select>
            </div>

            <label for="" class="label label-default">{{ $t('Fields matching') }}</label>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>{{ $t('CSV field') }}</th>
                  <th>{{ $t('DB field') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(field, idx) in csvFields" :key="idx">
                  <td>
                    <span>{{ field }}</span>
                  </td>
                  <td>
                    <db-fields-select :selected="currentProfile.bindings[idx]" :data="dbFields" @changed="bindDBToCsvField([ $event, idx ])"></db-fields-select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <right-sidebar></right-sidebar>
      </div>
    </div>
  </div>
</template>

<script>

import { createNamespacedHelpers } from 'vuex'
import { submitData, validateProfile, mapVuexModels } from '@/helpers'
import { IMPORT_URL } from '@/config'

import DbFieldsSelect from './DbFieldsSelect'
import ImportProgress from './ImportProgress'
import CsvFileUpload from './CsvFileUpload'
import RightSidebar from './RightSidebar'
import LeftSidebar from './LeftSidebar'

const { mapGetters, mapActions } = createNamespacedHelpers('importer')

export default {
  components: {
    DbFieldsSelect,
    CsvFileUpload,
    ImportProgress,
    RightSidebar,
    LeftSidebar
  },
  data () {
    return {
      msg: 'Import',
      newProfileName: '',
      importingCsvProgress: false,
      csvImportProgress: {
        current: 0,
        total: 0
      }
    }
  },
  computed: {
    ...mapGetters([
      'dbFields',
      'csvFields',
      'profile',
      'keyFields',
      'currentProfile',
      'submittableData'
    ]),
    ...mapVuexModels([
      'processAtStepNum',
      'skipFirstLine',
      'keyField'
    ], 'importer'),
    ...mapVuexModels({
      total: 'importJobTotal',
      current: 'importJobCurrent',
      working: 'importJobWorking'
    }, 'importer')
  },
  methods: {
    async submitImportPart ({ importUrl, key, position }) {
      try {
        let data = {
          key,
          position,
          profile: this.profile
        }
        let response = await this.$http.post(decodeURIComponent(importUrl), data)

        position = response.data.message.position
        this.current = position

        if (position < this.total) {
          this.submitImportPart({ importUrl, key, position })
        }
        else {
          this.working = false
        }
      }
      catch (e) {
        console.error(e)
      }
    },
    async submitImportData () {
      let data = this.submittableData

      if (validateProfile(data.profile)) {
        try {
          let response = await submitData(this.$store.actionUrl(IMPORT_URL), data)

          if (response.data.status === 'ok') {
            this.total = response.data.message.csvTotal
            this.working = true
            this.submitImportPart(response.data.message)
          }
        }
        catch (e) {
          alert('Ошибка при отправке!')
          console.error(e)
        }
      }
      else {
        alert('Error in profile!')
      }
    },
    ...mapActions([
      'bindDBToCsvField'
    ])
  },
  created () {

  }
}
</script>
