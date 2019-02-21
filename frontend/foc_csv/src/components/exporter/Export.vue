<template>
  <div class="foc-csv-export">
    <template v-if="working">
      <div class="panel panel-primary">
        <div class="panel-heading">
          {{ $t('Export in progress') }}
        </div>
        <div class="panel-body">
          <progress-bar :progress="{ current, total }"></progress-bar>
        </div>
      </div>
    </template>

    <error-message :errors="errors">
      <p>{{ $t('During export we catched some errors, please check foc logs!') }}</p>
    </error-message>

    <div class="panel panel-success" v-if="showDownloadLinks">
      <div class="panel-heading">
        {{ $t('Complete') }}
      </div>
      <div class="panel-body">
        <p>{{ $t('Check your download links:') }}</p>
        <div class="btn-group">
          <a :href="csvFileUrl" target="_blank" class="btn btn-primary">{{ $t('CSV file') }}</a>
          <a v-if="collectedImages > 0" target="_blank" :href="imagesZipUrl" class="btn btn-default">{{ $t('Images ZIP file') }}</a>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="row">
          <div class="col-md-8">
            <h1>{{ $t('Export submodule') }}</h1>
          </div>
          <div class="col-md-4 text-right">
            <button @click.prevent="submitExportData" class="btn btn-warning btn-lg"><i class="fa fa-rocket"></i> {{ $t('Start export!') }}</button>
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
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="label label-default">{{ $t('Process lines per query') }}</label>
                  <input type="text" class="form-control" v-model="entriesPerQuery">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" v-model="csvHeader"> {{ $t('Render CSV header') }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <export-fields v-model="bindings"></export-fields>
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
import { mapVuexModels } from 'vuex-models'
import LeftSidebar from './LeftSidebar'
import RightSidebar from './RightSidebar'
import ExportFields from './ExportFields'
import ProgressBar from '@/components/common/ProgressBar'
import ErrorMessage from '@/components/common/ErrorMessage'
const { mapGetters } = createNamespacedHelpers('exporter')

export default {
  name: 'export',
  components: {
    LeftSidebar,
    RightSidebar,
    ExportFields,
    ProgressBar,
    ErrorMessage
  },
  data () {
    return {
      msg: 'Export',
      errors: 0,
      collectedImages: 0,
      imagesZipUrl: null,
      csvFileUrl: null
    }
  },
  computed: {
    ...mapVuexModels([
      'entriesPerQuery',
      'csvHeader',
      'bindings'
    ], 'exporter'),
    ...mapVuexModels({
      total: 'exportJobTotal',
      current: 'exportJobCurrent',
      working: 'exportJobWorking'
    }, 'exporter'),
    ...mapGetters([
      'submittableData',
      'profile'
    ]),
    showDownloadLinks () {
      return this.working === false && this.csvFileUrl !== null
    }
  },
  methods: {
    setErrorState () {
      this.current = 0
      this.working = false
      this.errors = this.errors || 1
    },
    async submitExportPart (callbackUrl, requestConfig) {
      try {
        let response = await this.$api.exporter.submitPart({
          callbackUrl,
          options: {
            ...requestConfig,
            profile: this.profile
          }
        })

        if (response.data.status === 'fail') {
          this.errorMessages = response.data.message
          this.errors = 1
          this.setErrorState()
          return
        }

        let position = response.data.message.position

        this.current = position

        this.collectedImages += (parseInt(response.data.message.collected_images) || 0);

        if (this.current < this.total) {
          this.submitExportPart(callbackUrl, response.data.message)
        }
        else {
          this.working = false
          this.current = 0
          this.errors = response.data.message.errors
        }
      }
      catch (e) {
        this.setErrorState()
      }
    },
    async submitExportData () {
      let data = this.submittableData
      this.errors = 0
      this.csvFileUrl = null
      this.imagesZipUrl = null
      this.collectedImages = 0

      try {
        let response = await this.$api.exporter.submitData(data.profile)

        if (response.data.status === 'ok') {
          this.total = response.data.message.total
          this.working = true
          this.csvFileUrl = response.data.message.csvFileUrl
          this.imagesZipUrl = response.data.message.imagesZipUrl
          this.submitExportPart(response.data.message.exportUrl, response.data.message)
        }
      }
      catch (e) {
        this.working = false
        this.current = 0
        alert(this.$t('Error during sending!'))
        console.error(e)
      }
    }
  }
}
</script>
