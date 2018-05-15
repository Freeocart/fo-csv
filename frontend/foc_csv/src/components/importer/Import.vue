<template>
  <div class="foc-csv-import">
    <template v-if="working">
      <div class="panel panel-primary">
        <div class="panel-heading">
          {{ $t('Import in progress') }}
        </div>
        <div class="panel-body">
          <progress-bar :progress="{ current, total }"></progress-bar>
        </div>
      </div>
    </template>
    <div v-if="errors > 0">
      <div class="alert alert-danger" role="alert">
        <p>{{ $t('During import we had errors, please check foc logs!') }}</p>
        <p>{{ $t('Errors count') }} - <strong>{{ errors }}</strong></p>
      </div>
    </div>
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

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="" class="label label-default">{{ $t('Process lines per query') }}</label>
                  <input type="text" v-model="processAtStepNum" class="form-control">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="" class="label label-default">{{ $t('Skip lines') }}</label>
                  <input type="text" class="form-control" v-model="skipLines">
                </div>
              </div>
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
import { validateProfile, mapVuexModels } from '@/helpers'

import DbFieldsSelect from './DbFieldsSelect'
import ProgressBar from '@/components/common/ProgressBar'
import CsvFileUpload from './CsvFileUpload'
import RightSidebar from './RightSidebar'
import LeftSidebar from './LeftSidebar'

const { mapGetters, mapActions } = createNamespacedHelpers('importer')

export default {
  components: {
    DbFieldsSelect,
    CsvFileUpload,
    ProgressBar,
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
      },
      errors: 0
    }
  },
  computed: {
    ...mapGetters([
      'dbFields',
      'csvFields',
      'profile',
      'keyFields',
      'currentProfile',
      'submittableData',
      'importMode'
    ]),
    ...mapVuexModels([
      'processAtStepNum',
      'skipLines',
      'keyField'
    ], 'importer'),
    ...mapVuexModels({
      total: 'importJobTotal',
      current: 'importJobCurrent',
      working: 'importJobWorking'
    }, 'importer')
  },
  methods: {
    async submitImportPart (importUrl, obj) {
      try {
        obj.profile = this.profile
        let response = await this.$http.post(decodeURIComponent(importUrl), obj)
        let position = response.data.message.position

        this.current = position

        if (this.current < this.total) {
          this.submitImportPart(importUrl, response.data.message)
        }
        else {
          this.working = false
          this.current = 0
          this.errors = response.data.message.errors
        }
      }
      catch (e) {
        console.error(e)
        this.current = 0
        this.working = false
        this.errors = this.errors || 1
      }
    },
    async submitImportData () {
      let data = this.submittableData
      // reset errors counter
      this.errors = 0

      if (!this.checkIfDestructive(data.profile)) {
        alert(this.$t('Import canceled!'))
        return false
      }

      if (validateProfile(data.profile)) {
        try {
          let response = await this.$api.importer.submitData(data)

          if (response.data.status === 'ok') {
            this.total = response.data.message.csvTotal
            this.working = true
            this.submitImportPart(response.data.message.importUrl, response.data.message)
          }
        }
        catch (e) {
          this.working = false
          this.current = 0
          alert(this.$t('Error during sending!'))
          console.error(e)
        }
      }
      else {
        alert(this.$t('Invalid profile!'))
      }
    },
    checkIfDestructive (profile) {
      if (profile.importMode === 'removeByList' || profile.importMode === 'removeOthers') {
        return confirm(this.$t('Are you totally sure you want do this???! With this import method you can lose your data!'))
      }

      return true
    },
    ...mapActions([
      'bindDBToCsvField'
    ])
  },
  created () {

  }
}
</script>
