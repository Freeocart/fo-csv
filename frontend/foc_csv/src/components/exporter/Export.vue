<template>
  <div class="foc-csv-export">
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
            <h1>{{ $t('Export submodule') }}</h1>
          </div>
          <div class="col-md-4 text-right">
            <button :disabled="true" @click.prevent="submitExportData" class="btn btn-warning btn-lg"><i class="fa fa-rocket"></i> {{ $t('Start export!') }}</button>
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
                  <label class="label label-default">{{ $t('Render CSV header') }}</label>
                  <input type="checkbox" class="form-control" v-model="csvHeader">
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
import { mapVuexModels } from '@/helpers'
import LeftSidebar from './LeftSidebar'
import RightSidebar from './RightSidebar'
import ExportFields from './ExportFields'
import ProgressBar from '@/components/common/ProgressBar'

export default {
  components: {
    LeftSidebar,
    RightSidebar,
    ExportFields,
    ProgressBar
  },
  data () {
    return {
      msg: 'Export',
      errors: 0
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
    }, 'exporter')
  },
  methods: {
    submitExportData () {

    }
  }
}
</script>
