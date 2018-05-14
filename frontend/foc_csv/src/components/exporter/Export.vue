<template>
  <div class="foc-csv-export">
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
import { createNamespacedHelpers } from 'vuex'
import LeftSidebar from './LeftSidebar'
import RightSidebar from './RightSidebar'

const { mapGetters } = createNamespacedHelpers('exporter')

export default {
  components: {
    LeftSidebar,
    RightSidebar
  },
  data () {
    return {
      msg: 'Export'
    }
  },
  computed: {
    ...mapVuexModels([
      'entriesPerQuery',
      'csvHeader'
    ], 'exporter'),
    ...mapGetters([
      'encodings'
    ])
  },
  methods: {
    submitExportData () {

    }
  }
}
</script>
