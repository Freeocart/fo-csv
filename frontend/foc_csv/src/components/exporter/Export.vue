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

        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Settings') }}
          </div>
          <div class="panel-body">
            <div class="form-group">
              <label class="label label-default">{{ $t('Entries per query') }}</label>
              <input type="text" class="form-control" v-model="entriesPerQuery">
            </div>

            <div class="form-group">
              <label class="label label-default">{{ $t('Encoding') }}</label>
              <select class="form-control" v-model="encoding">
                <option v-for="(encodingName, idx) in encodings" :key="idx" :value="encodingName">{{ encodingName }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="panel panel-primary">
          <div class="panel-heading">

          </div>
          <div class="panel-body">
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <right-sidebar></right-sidebar>

        <div class="panel panel-primary">
          <div class="panel-heading">
            {{ $t('Images') }}
          </div>
          <div class="panel-body">

            <div class="form-group">
              <label class="label label-default">{{ $t('Export images mode') }}</label>
              <select class="form-control" v-model="exportImagesMode">
                <option :value="null">{{ $t('Not selected') }}</option>
                <option>{{ $t('Full URL') }}</option>
                <option>{{ $t('File name') }}</option>
              </select>
            </div>

            <div class="form-group">
              <label class="label label-default">{{ $t('Create images ZIP') }}</label>
              <input type="checkbox" class="form-control" v-model="createImagesZIP">
            </div>

            <div class="form-group">
              <label class="label label-default">{{ $t('Gallery images delimiter') }}</label>
              <input type="text" class="form-control" v-model="galleryImagesDelimiter">
            </div>
          </div>
        </div>
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
      'encoding',
      'exportImagesMode',
      'createImagesZIP',
      'galleryImagesDelimiter'
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
